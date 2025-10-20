# ------------------------------
# Base image: PHP 8.2 FPM
# ------------------------------
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# ------------------------------
# Install system dependencies
# ------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nano \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------
# Install Composer
# ------------------------------
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# ------------------------------
# Copy Composer files first (for caching)
# ------------------------------
COPY composer.json composer.lock /var/www/
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress

# ------------------------------
# Copy application
# ------------------------------
COPY . /var/www

# Run Composer scripts
RUN composer dump-autoload --optimize

# ------------------------------
# Set permissions
# ------------------------------
# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Make it executable inside container
RUN ["chmod", "+x", "/usr/local/bin/docker-entrypoint.sh"]

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# ------------------------------
# Copy entrypoint script
# ------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ------------------------------
# Expose port
# ------------------------------
EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
