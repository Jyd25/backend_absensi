#!/bin/bash
# ============================================
# POST-DEPLOY — SSL + Verification
# Jalankan SETELAH DNS sudah pointing
# ============================================
set -e

echo "=== Post-Deploy: SSL Setup ==="

# Install SSL
certbot --nginx -d applab.my.id -d www.aplab.my.id --non-interactive --agree-tos --email admin@applab.my.id

# Enable HTTPS redirect (uncomment in nginx config)
# The certbot command above should handle this automatically

# Reload nginx
nginx -t && systemctl reload nginx

# Verify
echo ""
echo "=== Verification ==="
echo "1. Nginx status:"
systemctl status nginx --no-pager -l | head -5

echo ""
echo "2. PHP-FPM status:"
systemctl status php8.2-fpm --no-pager -l | head -5

echo ""
echo "3. Supervisor status:"
supervisorctl status

echo ""
echo "4. Test API:"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost/api/v1/ 2>/dev/null || echo "API not reachable"

echo ""
echo "5. SSL test:"
curl -s -o /dev/null -w "HTTPS Status: %{http_code}\n" https://api.applab.my.id/ 2>/dev/null || echo "HTTPS not configured yet"

echo ""
echo "All checks done!"
