#!/bin/bash
# ============================================
# Migrate from Neon PostgreSQL to VPS MySQL
# Run on VPS as root:
#   bash /tmp/migrate-to-mysql.sh
# ============================================

set -e

APP_DIR="/var/www/sistem-kehadiran"
APP_USER="www-data"
DB_NAME="sistem_kehadiran"
DB_USER="sistem_kehadiran"
DB_PASS="Absensi\$ecure2025!"
ROOT_PASS=$(openssl rand -base64 16)

echo "============================================"
echo "  MIGRATE: NeonDB PostgreSQL → VPS MySQL"
echo "============================================"

# 1. Install MySQL
echo "[1/7] Installing MySQL..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq mysql-server mysql-client > /dev/null 2>&1

# Ensure MySQL is running
systemctl enable mysql
systemctl start mysql
sleep 2

echo "  ✓ MySQL installed and running"

# 2. Create database and user
echo "[2/7] Creating database and user..."
mysql -u root <<EOSQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOSQL

echo "  ✓ Database '${DB_NAME}' created, user '${DB_USER}' granted"

# 3. Ensure PHP MySQL extensions
echo "[3/7] Ensuring PHP MySQL extensions..."
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')

# Try php-mysql first, then php${PHP_VERSION}-mysql
if apt-get install -y -qq php${PHP_VERSION}-mysql > /dev/null 2>&1; then
    echo "  ✓ php${PHP_VERSION}-mysql installed"
elif apt-get install -y -qq php-mysql > /dev/null 2>&1; then
    echo "  ✓ php-mysql installed"
else
    echo "  ⚠ Could not install php-mysql package, checking if already available..."
fi

# Verify PDO MySQL
php -r "if (!extension_loaded('pdo_mysql')) { echo 'ERROR: pdo_mysql not loaded' . PHP_EOL; exit(1); } echo '  ✓ pdo_mysql loaded' . PHP_EOL;"

# Restart PHP-FPM
systemctl restart php${PHP_VERSION}-fpm
echo "  ✓ PHP-FPM restarted"

# 4. Update .env on VPS
echo "[4/7] Updating .env..."
cd ${APP_DIR}

# Backup old .env
cp .env .env.bak.neon

# Replace DB settings
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE='"${DB_NAME}"'/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME='"${DB_USER}"'/' .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env

# Remove Neon-specific lines
sed -i '/^DB_SSLMODE=/d' .env
sed -i '/^DB_NEON_ENDPOINT=/d' .env

echo "  ✓ .env updated for MySQL"

# 5. Run migrations
echo "[5/7] Running migrations..."
php artisan migrate:fresh --force 2>&1

echo "  ✓ Migrations completed"

# 6. Run seeders
echo "[6/7] Running seeders..."
php artisan db:seed --force 2>&1

echo "  ✓ Seeders completed"

# 7. Cache configs
echo "[7/7] Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
chown -R ${APP_USER}:${APP_USER} ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache

echo ""
echo "============================================"
echo "  ✓ MIGRATION COMPLETE"
echo "============================================"
echo ""
echo "  Database:  ${DB_NAME} (MySQL, localhost)"
echo "  User:      ${DB_USER}"
echo "  Password:  ${DB_PASS}"
echo ""
echo "  Old .env backup: .env.bak.neon"
echo ""
echo "  Test: php artisan tinker --execute='echo DB::connection()->getPdo()->getDriverName();'"
echo "  Expected output: mysql"
echo ""
