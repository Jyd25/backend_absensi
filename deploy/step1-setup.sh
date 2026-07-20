#!/bin/bash
# ============================================
# STEP-BY-STEP DEPLOYMENT — copy paste via SSH
# ============================================
set -e
echo "Starting deployment..."

# 1. Update system
apt update && apt upgrade -y
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release ufw redis-server

# 2. Install PHP 8.2
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd php8.2-imagick php8.2-bcmath php8.2-intl php8.2-zip php8.2-readline php8.2-opcache

# 3. Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# 4. Install Nginx + Supervisor
apt install -y nginx supervisor
systemctl enable nginx supervisor
systemctl start nginx supervisor

# 5. Install Certbot
apt install -y certbot python3-certbot-nginx

# 6. Firewall
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable

# 7. Enable Redis
systemctl enable redis-server && systemctl start redis-server

# 8. Clone backend
mkdir -p /var/www/absensi
cd /var/www/absensi
git clone -b main https://github.com/Jyd25/backend_absensi.git . 2>/dev/null || git pull origin main

# 9. Install PHP deps
composer install --no-dev --optimize-autoloader --no-interaction

# 10. Setup .env
cp .env.example .env
php artisan key:generate --force

echo ""
echo "============================================"
echo "  BASIC SETUP COMPLETE!"
echo "  Now edit .env: nano /var/www/absensi/.env"
echo "  Then continue with nginx config..."
echo "============================================"
