#!/bin/bash
set -e

# ------------------------------
# Laravel container startup script
# ------------------------------

# Run Laravel commands
echo "Running storage link..."
php artisan storage:link || true   # skip if already exists

echo "Running migrations..."
# php artisan migrate --force
php artisan migrate:fresh --seed



# Start Laravel development server
# Bind to 0.0.0.0 so it's accessible outside the container
echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
