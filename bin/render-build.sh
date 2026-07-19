#!/usr/bin/env bash
# render-build.sh — Build script for Render.com

set -e  # Exit on error

echo "Installing Composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "Generating application key..."
php artisan key:generate --force

echo "Running database migrations..."
php artisan migrate --force

echo "Seeding database..."
php artisan db:seed --force || true

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Storage link..."
php artisan storage:link || true

echo "Build complete!"
