#!/bin/bash
# Runtime setup script - runs on container start
set -e

cd /var/www/html

# Install Composer dependencies if vendor/ missing
if [ ! -d vendor ]; then
    echo "Installing PHP dependencies (this may take a while)..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Install Node modules if missing
if [ ! -d node_modules ]; then
    echo "Installing Node dependencies (this may take a while)..."
    npm install
fi

# Build if missing
if [ ! -d public/build ]; then
    echo "Building frontend assets..."
    npm run build
fi

# Set permissions
chmod -R 775 storage bootstrap/cache

# Generate key if missing
if grep -q 'APP_KEY=$' .env || grep -q 'APP_KEY=base64:$' .env; then
    php artisan key:generate --no-interaction
fi

# Wait for services
for service in db:3306 redis:6379; do
    host=${service%:*}
    port=${service#*:}
    echo "Waiting for $host:$port..."
    until nc -z $host $port 2>/dev/null; do
        sleep 1
    done
    echo "$host ready"
done

# Migrate
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start
exec "$@"
