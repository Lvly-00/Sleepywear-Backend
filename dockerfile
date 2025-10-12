# ----------------------------------------
# 1️⃣ Build stage — install Composer deps
# ----------------------------------------
FROM composer:2.7 AS vendor

WORKDIR /app

# Copy only composer files to leverage caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# ----------------------------------------
# 2️⃣ Application stage — PHP + Nginx
# ----------------------------------------
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache nginx curl zip unzip git supervisor bash

# Install PHP extensions commonly used by Laravel
RUN docker-php-ext-install pdo pdo_mysql

# Copy application code
WORKDIR /var/www/html
COPY . .

# Copy vendor from the build stage
COPY --from=vendor /app/vendor ./vendor

# Copy default nginx config
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Optimize Laravel
RUN php artisan config:clear && php artisan cache:clear && php artisan route:cache

# Create necessary directories
RUN mkdir -p /run/nginx && chmod -R 775 storage bootstrap/cache

# Copy supervisor config (to run both Nginx + PHP-FPM)
COPY ./docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
