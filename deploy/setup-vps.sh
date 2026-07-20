#!/bin/bash
# ============================================
# VPS Initial Setup - Ubuntu 22.04/24.04
# Jalankan sekali saat VPS baru
# Usage: sudo bash setup-vps.sh
# ============================================

set -e

echo "=== Realtime Attendance System - VPS Setup ==="
echo ""

# ---- 1. System Update ----
echo "[1/8] Updating system..."
apt update && apt upgrade -y

# ---- 2. Install essentials ----
echo "[2/8] Installing essentials..."
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# ---- 3. Install PHP 8.2 + extensions ----
echo "[3/8] Installing PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
  php8.2-pgsql php8.2-sqlite3 php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-gd php8.2-imagick php8.2-bcmath \
  php8.2-intl php8.2-zip php8.2-readline php8.2-opcache \
  php8.2-redis php8.2-redis

# Verify PHP
php -v

# ---- 4. Install Composer ----
echo "[4/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer --version

# ---- 5. Install Nginx ----
echo "[5/8] Installing Nginx..."
apt install -y nginx
systemctl enable nginx
systemctl start nginx

# ---- 6. Install Node.js 20 LTS ----
echo "[6/8] Installing Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
node -v
npm -v

# ---- 7. Install Supervisor ----
echo "[7/8] Installing Supervisor..."
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor

# ---- 8. Install Redis (optional, for caching/broadcasting) ----
echo "[8/8] Installing Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# ---- Firewall ----
echo "Configuring firewall..."
ufw allow 'Nginx Full'
ufw allow 22
ufw allow 80
ufw allow 443
echo "y" | ufw enable

# ---- Create deploy user (optional) ----
echo ""
echo "Creating deploy user..."
adduser --disabled-password --gecos "" deploy 2>/dev/null || true
usermod -aG www-data deploy
usermod -aG sudo deploy

# ---- Create app directory ----
mkdir -p /var/www/sistem-kehadiran
chown -R deploy:www-data /var/www/sistem-kehadiran
chmod -R 775 /var/www/sistem-kehadiran

# ---- SSL (Let's Encrypt) ----
echo ""
echo "Installing Certbot for SSL..."
apt install -y certbot python3-certbot-nginx

echo ""
echo "=== Setup Complete! ==="
echo ""
echo "Next steps:"
echo "1. Point domain applab.my.id to VPS IP (A record)"
echo "2. Run: sudo certbot --nginx -d applab.my.id -d www.applab.my.id"
echo "3. Clone backend repo to /var/www/sistem-kehadiran"
echo "4. Configure .env"
echo "5. Run deploy script"
echo ""
echo "PHP version: $(php -r 'echo PHP_VERSION;')"
echo "Nginx: $(nginx -v 2>&1)"
echo "Node: $(node -v)"
echo "Composer: $(composer -V --format=flat 2>/dev/null || echo 'installed')"
