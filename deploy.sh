#!/bin/bash
set -e

echo "╔══════════════════════════════════════════════════╗"
echo "║  DigitalMarketingSaaS — Production Deployment   ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Step 1: Environment check
echo -e "${YELLOW}[1/8] Environment Check${NC}"
if [ ! -f .env ]; then
    echo -e "${RED}ERROR: .env file not found. Copy from env.production.example${NC}"
    exit 1
fi

# Check APP_ENV
APP_ENV=$(grep "^APP_ENV=" .env | cut -d= -f2)
if [ "$APP_ENV" != "production" ]; then
    echo -e "${RED}WARNING: APP_ENV is not 'production' (current: $APP_ENV)${NC}"
fi

# Check APP_DEBUG
APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d= -f2)
if [ "$APP_DEBUG" = "true" ]; then
    echo -e "${RED}WARNING: APP_DEBUG should be false in production${NC}"
fi

echo -e "${GREEN}✓ Environment check complete${NC}"
echo ""

# Step 2: Install dependencies
echo -e "${YELLOW}[2/8] Installing Dependencies${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ PHP dependencies installed${NC}"
echo ""

# Step 3: Database
echo -e "${YELLOW}[3/8] Database Migration${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Database migrated${NC}"
echo ""

# Step 4: Cache
echo -e "${YELLOW}[4/8] Caching Configuration${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

# Step 5: Frontend assets
echo -e "${YELLOW}[5/8] Building Frontend Assets${NC}"
npm ci
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"
echo ""

# Step 6: Permissions
echo -e "${YELLOW}[6/8] Setting Permissions${NC}"
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 7: Queue restart
echo -e "${YELLOW}[7/8] Restarting Queue Workers${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue workers restarted${NC}"
echo ""

# Step 8: Health check
echo -e "${YELLOW}[8/8] Health Check${NC}"
php artisan health:check
echo ""

echo "╔══════════════════════════════════════════════════╗"
echo -e "${GREEN}║  Deployment Complete!                           ║${NC}"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo "  1. Verify https://your-domain.com loads correctly"
echo "  2. Test login/register flow"
echo "  3. Check /up endpoint for health status"
echo "  4. Monitor Sentry for errors"
echo "  5. Verify queue workers are running"
