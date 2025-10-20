#!/bin/bash
set -e

# ------------------------------
# Laravel container startup script
# ------------------------------

# Ensure SQLite database file exists
DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    mkdir -p database
    touch "$DB_PATH"
    chmod 777 "$DB_PATH"
fi

# Optimize Composer autoload
echo "Running composer install..."
composer install --no-dev --optimize-autoloader

# Clear all caches to prevent factory / faker issues
echo "Clearing Laravel caches..."
php artisan optimize:clear

# Run storage link (skip if already exists)
echo "Running storage link..."
php artisan storage:link || true

# Run migrations and seeders fresh
echo "Running migrations and database seeding..."
php artisan migrate:fresh --force --seed

# Start Laravel development server
echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
