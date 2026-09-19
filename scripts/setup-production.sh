#!/bin/bash
# One-command production setup script
# Usage: ./scripts/setup-production.sh

set -euo pipefail

echo "============================================"
echo "  Production Setup"
echo "============================================"

# Check prerequisites
command -v docker >/dev/null 2>&1 || { echo "Docker required"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "Docker Compose required"; exit 1; }

# Create directories
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p docker/nginx/ssl
mkdir -p /backups

# Set permissions
chmod -R 775 storage bootstrap/cache

# Generate .env if missing
if [ ! -f .env ]; then
    echo "Creating .env from template..."
    cp docker/scripts/.env.production .env
    
    # Generate random passwords
    DB_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)
    DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)
    APP_KEY=$(php artisan key:generate --show 2>/dev/null || echo "base64:$(openssl rand -base64 32)")
    
    sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASSWORD/" .env
    sed -i "s/DB_ROOT_PASSWORD=/DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD/" .env
    sed -i "s|APP_KEY=|APP_KEY=$APP_KEY|" .env
    
    echo "Generated passwords saved to .env"
fi

# Build and start containers
echo "Building containers..."
docker-compose -f docker-compose.prod.yml build

echo "Starting containers..."
docker-compose -f docker-compose.prod.yml up -d

# Wait for database
echo "Waiting for database..."
sleep 10

# Run migrations
echo "Running migrations..."
docker exec dmsaas-app php artisan migrate --force

# Build assets
echo "Building assets..."
docker exec dmsaas-app npm run build

# Optimize
echo "Optimizing..."
docker exec dmsaas-app php artisan config:cache
docker exec dmsaas-app php artisan route:cache
docker exec dmsaas-app php artisan view:cache
docker exec dmsaas-app php artisan event:cache

# Health check
echo "Running health check..."
sleep 5
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
if [ "$HTTP_CODE" = "200" ]; then
    echo "Health check: OK"
else
    echo "Health check: FAILED (HTTP $HTTP_CODE)"
fi

echo "============================================"
echo "  Setup complete!"
echo "  App: http://localhost"
echo "  Health: http://localhost/health"
echo "============================================"
