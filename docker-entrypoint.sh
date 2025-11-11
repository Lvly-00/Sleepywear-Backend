#!/bin/bash
set -e

echo "Running storage link..."
php artisan storage:link || true

echo "Running production migrations..."
if ! php artisan migrate --force; then
    echo "Migration failed. Dropping and recreating schema..."
    php artisan db:wipe --force
    php artisan migrate --force
fi

echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
