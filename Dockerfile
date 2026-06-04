FROM php:8.3-fpm-alpine

# System deps
# freetype-dev + libjpeg-turbo-dev are required by mPDF's GD image processing
RUN apk add --no-cache \
    nginx supervisor \
    postgresql-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev \
    oniguruma-dev \
    nodejs npm

# GD must be configured before installing — mPDF needs freetype + jpeg support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip gd bcmath opcache

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

# Build Vite assets (Filament admin/student panel themes + Tailwind CSS)
RUN npm run build \
    && composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080
CMD ["/entrypoint.sh"]
