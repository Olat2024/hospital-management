#!/usr/bin/env bash

php artisan config:clear
php artisan route:clear

cat > /var/www/html/.env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}
SESSION_DRIVER=file
CACHE_STORE=file
EOF

echo "=== .env written ==="
grep DATABASE_URL /var/www/html/.env

composer install --no-dev --working-dir=/var/www/html

php artisan migrate --force