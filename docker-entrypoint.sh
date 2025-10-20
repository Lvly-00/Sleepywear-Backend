#!/bin/bash
set -e

# Ensure SQLite database exists
DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    mkdir -p database
    touch "$DB_PATH"
    chmod 777 "$DB_PATH"
fi

# Laravel setup
php artisan storage:link || true
php artisan migrate:fresh --force --seed

# Start Laravel server
exec php artisan serve --host=0.0.0.0 --port=8000
