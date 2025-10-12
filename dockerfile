# ----------------------------------------
# Build stage: install Composer dependencies
# ----------------------------------------
FROM composer:2.7 AS vendor

WORKDIR /app

# Copy Laravel files
COPY . .

# Install dependencies without dev packages
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader \
 && composer dump-autoload --no-dev

# ----------------------------------------
# App stage: PHP + Nginx
# ----------------------------------------
FROM php:8.2-fpm-alpine

# Install necessary packages
RUN apk add --no-cache nginx supervisor bash curl zip unzip git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy Laravel app
COPY . .

# Copy vendor from build stage
COPY --from=vendor /app/vendor ./vendor

# Ensure writable directories with correct ownership
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /var/log \
    && chown -R www-data:www-data storage bootstrap/cache /var/log \
    && chmod -R 775 storage bootstrap/cache /var/log \
    && touch /var/log/php-fpm.log /var/log/php-fpm-error.log \
    /var/log/nginx.log /var/log/nginx-error.log

# Copy supervisord & nginx configs
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# Clear and rebuild Laravel caches (ignore warnings)
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan config:cache \
 && php artisan route:cache \
 || true

EXPOSE 80

# Run supervisord as www-data to match file ownership
USER www-data

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
