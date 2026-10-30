#!/usr/bin/env bash
set -e

: "${APP_KEY:?APP_KEY must be configured in the hosting environment}"
: "${APP_URL:?APP_URL must be configured in the hosting environment}"
: "${DB_HOST:?DB_HOST must be configured}"
: "${DB_DATABASE:?DB_DATABASE must be configured}"
: "${DB_USERNAME:?DB_USERNAME must be configured}"
: "${DB_PASSWORD:?DB_PASSWORD must be configured}"

cd /var/www/app

cat > /var/www/app/.env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
SESSION_DRIVER=database
CACHE_STORE=file
EOF

echo "=== .env written ==="
grep -E "DB_HOST|DB_DATABASE" /var/www/app/.env

php artisan config:clear
php artisan route:clear

echo "=== Composer install ==="
composer install --no-dev --working-dir=/var/www/app

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Creating super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force
echo "=== Super admin ready ==="

echo "=== Migrations complete ==="