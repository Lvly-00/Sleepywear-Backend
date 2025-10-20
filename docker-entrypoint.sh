#!/bin/bash
set -e

# ------------------------------
# Laravel container startup script (Render-ready)
# ------------------------------

# Use the port Render assigns; fail if not set
if [ -z "$PORT" ]; then
  echo "Error: \$PORT is not set by Render. Exiting."
  exit 1
fi

echo "Render assigned port: $PORT"

# Ensure SQLite database file exists
DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    mkdir -p database
    touch "$DB_PATH"
    chmod 777 "$DB_PATH"
fi

# Install composer dependencies
echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

# Clear Laravel caches
echo "Clearing caches..."
php artisan optimize:clear

# Create storage symlink
echo "Creating storage link..."
php artisan storage:link || true

# Run migrations and seed only admin
echo "Running migrations and seeding admin account..."
php artisan migrate:fresh --force
php artisan db:seed --class=AdminSeeder --force

# Small delay to ensure the server binds before Render scans
sleep 2

# Start Laravel server on the assigned port
echo "Starting Laravel server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
