#!/bin/bash
# Production deployment script for Digital Marketing SaaS
# Usage: ./deploy.sh [staging|production]

set -euo pipefail

ENV=${1:-production}
APP_DIR="/var/www/digitalmarketingsaas"
BACKUP_DIR="/backups/$(date +%Y%m%d_%H%M%S)"

echo "============================================"
echo "  Deploying to: $ENV"
echo "  Timestamp: $(date)"
echo "============================================"

# 1. Pre-deployment checks
echo "[1/8] Running pre-deployment checks..."
cd $APP_DIR

# Check PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
echo "  PHP version: $PHP_VERSION"

# Check .env exists
if [ ! -f .env ]; then
    echo "ERROR: .env file not found"
    exit 1
fi

# 2. Create backup
echo "[2/8] Creating backup..."
mkdir -p $BACKUP_DIR

# Database backup
if command -v mysqldump &> /dev/null; then
    mysqldump -u $(grep DB_USERNAME .env | cut -d= -f2) \
              -p$(grep DB_PASSWORD .env | cut -d= -f2) \
              $(grep DB_DATABASE .env | cut -d= -f2) | gzip > $BACKUP_DIR/db.sql.gz
    echo "  Backup saved to: $BACKUP_DIR/db.sql.gz"
fi

# File backup
tar -czf $BACKUP_DIR/files.tar.gz --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/views/*' --exclude='node_modules' --exclude='vendor' .

# 3. Enable maintenance mode
echo "[3/8] Enabling maintenance mode..."
php artisan down --render="errors.503" --retry=60 --secret="$(openssl rand -hex 10)"

# 4. Pull latest code
echo "[4/8] Pulling latest code..."
git fetch origin
git reset --hard origin/main

# 5. Install dependencies
echo "[5/8] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress
npm ci

# 6. Database migrations
echo "[6/8] Running migrations..."
php artisan migrate --force

# 7. Build assets
echo "[7/8] Building assets..."
npm run build

# 8. Optimize and restart
echo "[8/8] Optimizing and restarting..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart

# Clear old caches
php artisan cache:clear

# Reload services
if systemctl is-active --quiet php8.4-fpm; then
    sudo systemctl reload php8.4-fpm
fi

if systemctl is-active --quiet nginx; then
    sudo systemctl reload nginx
fi

# Disable maintenance mode
php artisan up

echo "============================================"
echo "  Deployment complete!"
echo "  URL: $(grep APP_URL .env | cut -d= -f2)"
echo "============================================"

# Health check
sleep 5
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up)
if [ "$HTTP_CODE" = "200" ]; then
    echo "Health check: OK (HTTP $HTTP_CODE)"
else
    echo "WARNING: Health check failed (HTTP $HTTP_CODE)"
fi
