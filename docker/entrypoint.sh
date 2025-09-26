#!/bin/sh
set -e

# Run migrations if database is reachable
echo "Running migrations..."
php artisan migrate --force || echo "Migration failed or no DB available, continuing..."

# Start Apache server
echo "Starting Apache..."
exec apache2-foreground
