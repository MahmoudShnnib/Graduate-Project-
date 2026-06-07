#!/bin/sh
set -e

# Fix ownership on the writable paths BEFORE anything else. The `app_storage` named
# volume is mounted over /var/www/storage/app and can come up owned by root (Docker
# seeds a fresh volume from the image, but a pre-existing volume keeps its own owner).
# If www-data (PHP-FPM) can't write storage/app/public, the /apply certificate upload
# throws UnableToWriteFile and nginx returns 500 on every submission. Re-asserting the
# owner here is idempotent and cheap, and also guarantees storage/logs is writable.
echo "==> Fixing storage permissions..."
chown -R www-data:www-data storage bootstrap/cache

echo "==> Linking storage..."
php artisan storage:link --force

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding roles & permissions (idempotent, runs every deploy)..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force

echo "==> Seeding settings (idempotent, runs every deploy)..."
php artisan db:seed --class=SettingsSeeder --force

echo "==> Checking for first-deploy seed..."
# Robust fresh-install detection via a dedicated artisan command (exit 0 = fresh).
# Replaces the old `tinker --execute ... | grep` parsing, which broke whenever tinker
# emitted banners/warnings on stdout. `if` swallows the non-zero exit on existing DBs,
# so `set -e` won't abort here.
if php artisan app:check-fresh-install; then
    echo "==> Fresh database — seeding academic data and admin accounts..."
    php artisan db:seed --class=SpecializationSeeder --force
    php artisan db:seed --class=SemesterSeeder --force
    php artisan db:seed --class=CourseSeeder --force
    php artisan db:seed --class=AdminUserSeeder --force
    echo "==> Initial seed done."
else
    echo "==> Existing data found, skipping initial seed."
fi

echo "==> Caching config / routes / views / events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
# filament:optimize bundles icons:cache + filament:cache-components. Skip-on-failure so a
# Filament build that lacks the command can't break the deploy (|| true keeps set -e calm).
php artisan filament:optimize || true

echo "==> Starting PHP-FPM + Nginx..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
