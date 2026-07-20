#!/bin/bash
# ============================================
# FULL DEPLOYMENT SCRIPT — applab.my.id
# Jalankan di VPS sebagai root:
#   bash /tmp/deploy-all.sh
# ============================================

set -e

APP_DIR="/var/www/absensi"
REPO="https://github.com/Jyd25/backend_absensi.git"
BRANCH="main"
DOMAIN="applab.my.id"

echo "============================================"
echo "  REALTIME ATTENDANCE SYSTEM - VPS DEPLOY"
echo "  Domain: $DOMAIN"
echo "  Server: Ubuntu 24.04"
echo "============================================"
echo ""

# ==================
# STEP 1: System Update & Essentials
# ==================
echo "[1/10] System update & installing essentials..."
apt update && apt upgrade -y
apt install -y curl wget git unzip software-properties-common \
  apt-transport-https ca-certificates gnupg lsb-release ufw

# ==================
# STEP 2: Install PHP 8.2
# ==================
echo "[2/10] Installing PHP 8.2 + extensions..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
  php8.2-pgsql php8.2-sqlite3 php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-gd php8.2-imagick php8.2-bcmath \
  php8.2-intl php8.2-zip php8.2-readline php8.2-opcache

echo "PHP version: $(php -r 'echo PHP_VERSION;')"

# ==================
# STEP 3: Install Composer
# ==================
echo "[3/10] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
echo "Composer: $(composer -V --format=flat 2>/dev/null || echo 'installed')"

# ==================
# STEP 4: Install Nginx
# ==================
echo "[4/10] Installing Nginx..."
apt install -y nginx
systemctl enable nginx
systemctl start nginx

# ==================
# STEP 5: Install Supervisor
# ==================
echo "[5/10] Installing Supervisor..."
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor

# ==================
# STEP 6: Install Redis
# ==================
echo "[6/10] Installing Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# ==================
# STEP 7: Firewall
# ==================
echo "[7/10] Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ==================
# STEP 8: Create app directory & clone repo
# ==================
echo "[8/10] Cloning backend repository..."
mkdir -p "$APP_DIR"
cd "$APP_DIR"

# Clone fresh
if [ -d ".git" ]; then
    git fetch origin
    git reset --hard origin/$BRANCH
else
    git clone -b $BRANCH $REPO .
fi

echo "Repository cloned to $APP_DIR"

# ==================
# STEP 9: Install dependencies & configure
# ==================
echo "[9/10] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create .env
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --force
    echo ".env created with fresh APP_KEY"
fi

# ==================
# STEP 10: Configure Nginx
# ==================
echo "[10/10] Configuring Nginx..."
cat > /etc/nginx/sites-available/absensi << 'NGINX'
server {
    listen 80;
    listen [::]:80;
    server_name applab.my.id www.aplab.my.id;

    root /var/www/absensi/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_connect_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    client_max_body_size 20M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 256;
}
NGINX

ln -sf /etc/nginx/sites-available/absensi /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

# ==================
# Configure Supervisor — Queue Worker
# ==================
echo "Configuring Supervisor — Queue Worker..."
cat > /etc/supervisor/conf.d/absensi-worker.conf << 'SUPERVISOR_WORKER'
[program:absensi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/absensi/artisan queue:work database --tries=3 --timeout=3600 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/absensi/storage/logs/worker.log
stopwaitsecs=3600
stopsignal=TERM
SUPERVISOR_WORKER

# ==================
# Configure Supervisor — Reverb WebSocket
# ==================
echo "Configuring Supervisor — Reverb WebSocket..."
cat > /etc/supervisor/conf.d/absensi-reverb.conf << 'SUPERVISOR_REVERB'
[program:absensi-reverb]
process_name=%(program_name)s
command=php /var/www/absensi/artisan reverb:start --port=8080
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/www/absensi/storage/logs/reverb.log
stopwaitsecs=10
stopsignal=TERM
SUPERVISOR_REVERB

supervisorctl reread
supervisorctl update

# ==================
# Setup Cron
# ==================
echo "Setting up Laravel scheduler cron..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/absensi && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ==================
# Set Permissions
# ==================
echo "Setting permissions..."
chown -R root:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 775 {} \;
find "$APP_DIR" -type f -exec chmod 664 {} \;
chmod -R 775 "$APP_DIR/storage" 2>/dev/null || true
chmod -R 775 "$APP_DIR/bootstrap/cache" 2>/dev/null || true

# ==================
# Install Certbot (SSL)
# ==================
echo "Installing Certbot for SSL..."
apt install -y certbot python3-certbot-nginx

# ==================
# DONE
# ==================
echo ""
echo "============================================"
echo "  SETUP COMPLETE!"
echo "============================================"
echo ""
echo "Services installed:"
echo "  - PHP $(php -r 'echo PHP_VERSION;')-FPM"
echo "  - Nginx"
echo "  - Supervisor"
echo "  - Redis"
echo "  - Composer $(composer -V --format=flat 2>/dev/null)"
echo ""
echo "Next steps:"
echo "  1. Point DNS: A record @ -> 103.247.10.232"
echo "  2. Edit .env: nano /var/www/absensi/.env"
echo "  3. Run migrations: cd /var/www/absensi && php artisan migrate --force"
echo "  4. SSL: certbot --nginx -d applab.my.id -d www.aplab.my.id"
echo "  5. Start services: supervisorctl start 'absensi-worker:*' absensi-reverb"
echo ""
