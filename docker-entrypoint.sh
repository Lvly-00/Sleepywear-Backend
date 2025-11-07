#!/bin/bash
set -e

DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    mkdir -p database
    touch "$DB_PATH"
    chmod 777 "$DB_PATH"
fi

echo "Running storage link..."
php artisan storage:link || true

echo "Running migrations..."
php artisan migrate --force

echo "Checking if database is empty..."
if php artisan db:is-empty | grep -q 'Database is empty'; then
    echo "Database empty. Running seeders..."
    php artisan db:seed --force
else
    echo "Database already has data. Skipping seeders."
fi

echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
