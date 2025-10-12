# ----------------------------------------
# 1️⃣ Build stage — Composer dependencies
# ----------------------------------------
FROM composer:2.7 AS vendor

WORKDIR /app

# Copy the entire Laravel project to ensure artisan exists
COPY . .

# Install dependencies without dev packages
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader \
 && composer dump-autoload --no-dev

# ----------------------------------------
# 2️⃣ App stage — PHP + Nginx (API)
# ----------------------------------------
FROM php:8.2-fpm-alpine

# Install necessary packages
RUN apk add --no-cache nginx curl zip unzip git supervisor bash

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Copy all application files
COPY . .

# Copy vendor dependencies from the builder stage
COPY --from=vendor /app/vendor ./vendor

# Copy server configs
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Clear and rebuild Laravel cache (ignore warnings)
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan config:cache \
 && php artisan route:cache \
 || true

# Ensure writable directories for Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache


EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
