#!/bin/bash
set -e

echo "=== Digital Marketing SaaS - Staging Deploy ==="

APP_DIR="/var/www/staging"

cd ${APP_DIR}

echo "Pulling latest code..."
git pull origin staging

echo "Building Docker images..."
docker-compose -f docker-compose.staging.yml build --no-cache

echo "Starting containers..."
docker-compose -f docker-compose.staging.yml up -d

echo "Running migrations..."
docker exec dms-staging-app php artisan migrate --force

echo " Caching config..."
docker exec dms-staging-app php artisan config:cache
docker exec dms-staging-app php artisan route:cache
docker exec dms-staging-app php artisan view:cache

echo "Restarting queue workers..."
docker exec dms-staging-app php artisan queue:restart

echo "Staging deployment complete!"
echo "App: https://staging.digitalmarketingsaas.com"
echo "Health: https://staging.digitalmarketingsaas.com/health"
