#!/usr/bin/env bash
set -e

cd /var/www/html

export APP_ENV=production
export APP_DEBUG=false
export LOG_CHANNEL=stderr
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_PORT="${DB_PORT:-5432}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export CACHE_STORE="${CACHE_STORE:-file}"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

: "${APP_KEY:?APP_KEY must be configured in the hosting environment}"
: "${APP_URL:?APP_URL or RENDER_EXTERNAL_URL must be configured in the hosting environment}"

if [ -z "${DB_URL:-}" ]; then
    : "${DB_HOST:?Set DB_URL or configure DB_HOST, DB_DATABASE, DB_USERNAME, and DB_PASSWORD}"
    : "${DB_DATABASE:?Set DB_URL or configure DB_HOST, DB_DATABASE, DB_USERNAME, and DB_PASSWORD}"
    : "${DB_USERNAME:?Set DB_URL or configure DB_HOST, DB_DATABASE, DB_USERNAME, and DB_PASSWORD}"
    : "${DB_PASSWORD:?Set DB_URL or configure DB_HOST, DB_DATABASE, DB_USERNAME, and DB_PASSWORD}"
fi

php artisan config:clear
php artisan route:clear
php artisan storage:link --force

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Seeding hospital and permissions ==="
php artisan db:seed --class=HospitalSeeder --force
php artisan db:seed --class=PermissionSeeder --force

echo "=== Creating super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force
echo "=== Super admin ready ==="

echo "=== Migrations complete ==="
