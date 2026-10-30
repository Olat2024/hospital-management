#!/usr/bin/env bash

# 1. Clear any stale config cache FIRST
php artisan config:clear

# 2. Write .env from Render env vars
cat > /var/www/html/.env <<EOF
APP_NAME=Laravel
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}
LOG_CHANNEL=stderr
DB_CONNECTION=${DB_CONNECTION:-pgsql}
DATABASE_URL=${DATABASE_URL}
SESSION_DRIVER=file
CACHE_STORE=file
EOF

echo "Wrote .env"

# 3. Now cache with fresh values
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force || echo "Migrations failed, continuing anyway"

echo "Deploy script finished"