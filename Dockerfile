# Production image — Laravel + nginx + PHP-FPM on port 8080.
#
# Dokploy (Application / Dockerfile, not Compose):
#   - Build: Dockerfile at repo root
#   - Container port: 8080
#   - Postgres: create a separate Dokploy Postgres service; set DB_* env vars below
#   - Mount (recommended): /var/www/storage/app/public → persistent volume (uploads)
#
# Local smoke test:
#   docker build -t graduate-project .
#   docker run --rm -p 8080:8080 --env-file .env graduate-project
#
FROM php:8.4-fpm-alpine

# System deps
# freetype-dev + libjpeg-turbo-dev are required by mPDF's GD image processing
# icu-dev is required for ext-intl (Filament + Symfony 8)
RUN apk add --no-cache \
    nginx supervisor \
    postgresql-dev postgresql-client \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev \
    oniguruma-dev icu-dev \
    nodejs npm

# nginx.conf runs workers as www-data, but `apk add nginx` creates /var/lib/nginx
# (incl. the client_body temp dir) owned by the `nginx` user. When a www-data worker
# spools an upload body larger than client_body_buffer_size to disk it gets EACCES and
# returns its own 500 — before the request ever reaches PHP-FPM. This is why /apply
# file uploads 500'd. Make the temp/log dirs owned by the worker user.
RUN mkdir -p /var/lib/nginx/tmp/client_body /var/lib/nginx/logs \
    && chown -R www-data:www-data /var/lib/nginx

# GD must be configured before installing — mPDF needs freetype + jpeg support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip gd bcmath opcache intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# PHP deps layer (only invalidates when composer files change)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Node deps layer (only invalidates when package files change)
COPY package.json package-lock.json ./
RUN npm ci

# Full app code
COPY . .

# .dockerignore excludes storage/framework/* and storage/logs — Laravel still needs
# those paths during `artisan package:discover` (triggered by dump-autoload).
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Build Vite assets (Filament admin/student panel themes + Tailwind CSS)
RUN npm run build \
    && composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

# Hit the framework health endpoint (registered in bootstrap/app.php health: '/up').
# busybox wget ships with the alpine base; --spider does a HEAD-style check.
HEALTHCHECK --interval=30s --timeout=5s --start-period=240s --retries=5 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1:8080/up || exit 1

CMD ["/entrypoint.sh"]
