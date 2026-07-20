#!/bin/bash
# ============================================
# FULL DEPLOY — api.applab.my.id
# Paste seluruh script ini ke SSH terminal
# ============================================
set -e

echo "============================================"
echo "  REALTIME ATTENDANCE SYSTEM - FULL DEPLOY"
echo "  Domain: api.applab.my.id"
echo "============================================"

# ===== 1. SYSTEM UPDATE =====
echo "[1/8] Updating system..."
apt update && apt upgrade -y
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release ufw redis-server

# ===== 2. INSTALL PHP 8.3 =====
echo "[2/8] Installing PHP 8.3..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
  php8.3-pgsql php8.3-sqlite3 php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-gd php8.3-imagick php8.3-bcmath \
  php8.3-intl php8.3-zip php8.3-readline php8.3-opcache

# ===== 3. INSTALL COMPOSER =====
echo "[3/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
export COMPOSER_ALLOW_SUPERUSER=1

# ===== 4. INSTALL NGINX + SUPERVISOR =====
echo "[4/8] Installing Nginx + Supervisor..."
apt install -y nginx supervisor
systemctl enable nginx supervisor
systemctl start nginx supervisor

# ===== 5. FIREWALL + REDIS =====
echo "[5/8] Configuring firewall + Redis..."
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable
systemctl enable redis-server && systemctl start redis-server

# ===== 6. CLONE REPO =====
echo "[6/8] Cloning backend repository..."
mkdir -p /var/www/absensi
cd /var/www/absensi
rm -rf .git 2>/dev/null || true
git clone -b main https://github.com/Jyd25/backend_absensi.git .

# ===== 7. INSTALL COMPOSER DEPS (update to fix lock file) =====
echo "[7/8] Installing dependencies (composer update)..."
export COMPOSER_ALLOW_SUPERUSER=1
composer update --no-dev --optimize-autoloader --no-interaction

# ===== 8. WRITE .ENV =====
echo "[8/8] Writing production .env..."
cat > /var/www/absensi/.env << 'ENVEOF'
APP_NAME="Absensi System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.applab.my.id
FRONTEND_URL=https://frontend-jyd25.vercel.app

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
APP_TIMEZONE=Asia/Jakarta

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=ep-lingering-bird-adya0a8a-pooler.c-2.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_dO5TDH1mkYbU
DB_SSLMODE=require
DB_NEON_ENDPOINT=ep-lingering-bird-adya0a8a

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=cloudinary
QUEUE_CONNECTION=database
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

JWT_SECRET=IHei519HQpNvosHmgUgKyw3NKYBZCiXIx1Id3ljH5mu2gyodoYtsGZPaMWzPnrAI
JWT_ALGO=HS256
JWT_TTL=60
JWT_REFRESH_TTL=10080

REVERB_APP_KEY=4d19688de7b2e366be6ea4a09234a461
REVERB_APP_SECRET=02002b7fcf527ab4f29385dad56a6c7b5e77d050d8080244741db15a65d256fd
REVERB_APP_ID=868005
REVERB_HOST=api.applab.my.id
REVERB_PORT=443
REVERB_SCHEME=https

CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_URL=
ENVEOF

php artisan key:generate --force

# ===== 9. NGINX CONFIG =====
echo "Configuring Nginx..."
cat > /etc/nginx/sites-available/absensi << 'NGINX'
server {
    listen 80;
    listen [::]:80;
    server_name api.applab.my.id;

    root /var/www/absensi/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_connect_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;
    }

    location ~ /\.ht { deny all; }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    client_max_body_size 20M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 256;
}
NGINX

ln -sf /etc/nginx/sites-available/absensi /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ===== 10. SUPERVISOR — WORKER =====
echo "Configuring Supervisor — Queue Worker..."
cat > /etc/supervisor/conf.d/absensi-worker.conf << 'SW'
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
SW

# ===== 11. SUPERVISOR — REVERB =====
echo "Configuring Supervisor — Reverb WebSocket..."
cat > /etc/supervisor/conf.d/absensi-reverb.conf << 'SR'
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
SR

supervisorctl reread && supervisorctl update

# ===== 12. CRON =====
echo "Setting up Laravel scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/absensi && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ===== PERMISSIONS =====
chown -R root:www-data /var/www/absensi
find /var/www/absensi -type d -exec chmod 775 {} \;
find /var/www/absensi -type f -exec chmod 664 {} \;
chmod -R 775 /var/www/absensi/storage 2>/dev/null || true
chmod -R 775 /var/www/absensi/bootstrap/cache 2>/dev/null || true

# ===== MIGRATE =====
echo "Running migrations..."
php artisan migrate --force

# ===== CACHE =====
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ===== START SERVICES =====
supervisorctl start "absensi-worker:*"
supervisorctl start absensi-reverb

# ===== DONE =====
echo ""
echo "============================================"
echo "  DEPLOYMENT COMPLETE!"
echo "============================================"
echo ""
echo "Backend:  http://api.applab.my.id"
echo "API:      http://api.applab.my.id/api/v1/"
echo ""
echo "Services:"
supervisorctl status
echo ""
echo "Next: SSL (after DNS propagated)"
echo "  certbot --nginx -d api.applab.my.id"
echo "============================================"
