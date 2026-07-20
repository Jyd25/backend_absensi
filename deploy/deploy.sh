#!/bin/bash
# ============================================
# Deploy Script - Jalankan di VPS
# Usage: bash deploy.sh
# ============================================

set -e

APP_DIR="/var/www/sistem-kehadiran"
REPO="https://github.com/Jyd25/backend_absensi.git"
BRANCH="main"

echo "=== Deploying Sistem Kehadiran Backend ==="

cd "$APP_DIR"

# ---- 1. Pull latest code ----
echo "[1/7] Pulling latest code..."
if [ ! -d ".git" ]; then
    git clone -b $BRANCH $REPO .
else
    git fetch origin
    git reset --hard origin/$BRANCH
    git clean -fd
fi

# ---- 2. Install PHP dependencies ----
echo "[2/7] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ---- 3. Environment setup ----
echo "[3/7] Configuring environment..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --force
    echo ">>> .env created — edit it with your actual values!"
fi

# ---- 4. Run migrations ----
echo "[4/7] Running migrations..."
php artisan migrate --force

# ---- 5. Cache optimizations ----
echo "[5/7] Caching config and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ---- 6. Set permissions ----
echo "[6/7] Setting permissions..."
chown -R deploy:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 775 {} \;
find "$APP_DIR" -type f -exec chmod 664 {} \;
chmod -R 775 "$APP_DIR/storage" 2>/dev/null || true
chmod -R 775 "$APP_DIR/bootstrap/cache" 2>/dev/null || true

# ---- 7. Restart services ----
echo "[7/7] Restarting services..."
systemctl reload php8.2-fpm
systemctl reload nginx
sudo -u deploy php artisan queue:restart 2>/dev/null || true

echo ""
echo "=== Deploy Complete! ==="
echo "Backend: https://applab.my.id"
echo "API: https://applab.my.id/api/v1/"
