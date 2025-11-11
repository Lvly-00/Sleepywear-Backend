#!/bin/bash
set -e

echo "Running storage link..."
php artisan storage:link || true

if [ "$APP_ENV" = "production" ]; then
    echo "Running production migrations..."
    php artisan migrate --force --seed
else
    echo "Running development migrations..."
    php artisan migrate:fresh --force --seed
fi

echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
