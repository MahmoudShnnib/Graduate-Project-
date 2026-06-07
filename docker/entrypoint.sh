#!/bin/sh
set -e

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ -z "${DB_HOST:-}" ]; then
    echo "ERROR: DB_HOST is not set. Point it at your Postgres host (Dokploy Postgres internal name or IP)."
    exit 1
fi

# ---------------------------------------------------------------------------
# Phase 1 — readiness: DB must accept connections AND Laravel auth must work
# before any migration runs. pg_isready alone is not enough (Postgres can be
# "ready" while auth or the database is still initialising).
# ---------------------------------------------------------------------------

echo "==> [1/3] Waiting for PostgreSQL (TCP)..."
TRIES=0
until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-graduate}" -d "${DB_DATABASE:-graduate_project}" >/dev/null 2>&1; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge 60 ]; then
        echo "ERROR: PostgreSQL TCP not ready after 120s"
        exit 1
    fi
    sleep 2
done

echo "==> [1/3] Waiting for Laravel database connection..."
TRIES=0
until php artisan db:show --no-interaction >/dev/null 2>&1; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge 30 ]; then
        echo "ERROR: Laravel cannot connect to the database after 60s (check DB_HOST / DB_PASSWORD)"
        exit 1
    fi
    sleep 2
done

# ---------------------------------------------------------------------------
# Phase 2 — bootstrapping: migrations + seeds + caches BEFORE any HTTP traffic.
# Nginx / PHP-FPM are not started until this block completes successfully.
# ---------------------------------------------------------------------------

echo "==> [2/3] Linking storage..."
php artisan storage:link --force

echo "==> [2/3] Running migrations..."
php artisan migrate --force --no-interaction

echo "==> [2/3] Seeding roles & permissions (idempotent)..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force --no-interaction

echo "==> [2/3] Seeding settings (idempotent)..."
php artisan db:seed --class=SettingsSeeder --force --no-interaction

echo "==> [2/3] Checking for first-deploy seed..."
if php artisan app:check-fresh-install; then
    echo "==> Fresh database — seeding academic data and admin accounts..."
    php artisan db:seed --class=SpecializationSeeder --force --no-interaction
    php artisan db:seed --class=SemesterSeeder --force --no-interaction
    php artisan db:seed --class=CourseSeeder --force --no-interaction
    php artisan db:seed --class=AdminUserSeeder --force --no-interaction
    echo "==> Initial seed done."
else
    echo "==> Existing data found, skipping initial seed."
fi

echo "==> [2/3] Caching config / routes / views / events..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction
php artisan filament:optimize --no-interaction || true

# ---------------------------------------------------------------------------
# Phase 3 — start web server only after migrations succeeded.
# ---------------------------------------------------------------------------

echo "==> [3/3] Starting PHP-FPM + Nginx..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
