#!/bin/bash
set -e

echo "Running migrations..."
php artisan migrate --force

echo "Seeding database..."
php artisan db:seed --force || true

echo "Generating APP_KEY if empty..."
php artisan key:generate --force || true

echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=8000
