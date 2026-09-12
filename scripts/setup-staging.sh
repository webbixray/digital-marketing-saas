#!/bin/bash
set -e

echo "=== Digital Marketing SaaS - Staging Setup ==="

# Create required directories
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/app/{media,public}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache

# Copy environment file
cp .env.staging.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed staging data
php artisan db:seed --class=StagingSeeder --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Staging environment ready ==="
echo "Run 'php artisan serve' or use docker-compose.staging.yml"
