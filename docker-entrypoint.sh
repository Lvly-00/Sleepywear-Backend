#!/bin/bash
set -e

# ------------------------------
# Laravel container startup script (Render-ready)
# ------------------------------

# Use the port Render assigns (default to 8000 if $PORT is not set)
PORT=${PORT:-8000}

# Ensure SQLite database file exists
DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    mkdir -p database
    touch "$DB_PATH"
    chmod 777 "$DB_PATH"
fi

# Install composer dependencies and optimize autoloader
echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

# Clear all Laravel caches to avoid factory/faker issues
echo "Clearing Laravel caches..."
php artisan optimize:clear

# Create storage symbolic link if not exists
echo "Running storage link..."
php artisan storage:link || true

# Run migrations and seed admin account only
echo "Running migrations and seeding admin account..."
php artisan migrate:fresh --force

php artisan db:seed --class=DatabaseSeeder --force

# Start Laravel development server on the correct port
echo "Starting Laravel server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
