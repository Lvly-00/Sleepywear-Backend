# ----------------------------------------
# Build stage: Composer dependencies
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

# Install packages
RUN apk add --no-cache nginx supervisor bash curl zip unzip git

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Working directory
WORKDIR /var/www/html

# Copy Laravel app
COPY . .

# Copy vendor from build stage
COPY --from=vendor /app/vendor ./vendor

# Create writable directories
RUN mkdir -p storage/framework/{cache/data,sessions,views} \
    storage/logs bootstrap/cache \
    /var/log/nginx /var/log/php-fpm \
    && chown -R www-data:www-data storage bootstrap/cache /var/log \
    && chmod -R 775 storage bootstrap/cache /var/log \
    && touch /var/log/nginx/error.log /var/log/nginx/access.log \
    /var/log/php-fpm/error.log /var/log/php-fpm/access.log

# Copy configs
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Clear Laravel caches
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan config:cache \
 && php artisan route:cache \
 || true

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
