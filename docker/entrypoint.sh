#!/bin/sh
set -e

echo "==> Linking storage..."
php artisan storage:link --force

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding roles & permissions (idempotent, runs every deploy)..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force

echo "==> Seeding settings (idempotent, runs every deploy)..."
php artisan db:seed --class=SettingsSeeder --force

echo "==> Checking for first-deploy seed..."
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | grep -E '^[0-9]+$' | head -1)
if [ -z "$USER_COUNT" ] || [ "$USER_COUNT" = "0" ]; then
    echo "==> Fresh database — seeding academic data and admin accounts..."
    php artisan db:seed --class=SpecializationSeeder --force
    php artisan db:seed --class=SemesterSeeder --force
    php artisan db:seed --class=CourseSeeder --force
    php artisan db:seed --class=AdminUserSeeder --force
    echo "==> Initial seed done."
else
    echo "==> Existing data found ($USER_COUNT users), skipping initial seed."
fi

echo "==> Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting PHP-FPM + Nginx..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
