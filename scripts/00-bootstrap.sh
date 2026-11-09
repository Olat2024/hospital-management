#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Environment variable checks
: "${APP_KEY:?}" "${APP_URL:?}" "${DATABASE_URL:?}"

# Only do the most basic directory preparation
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Check vendor
if [[ ! -f vendor/autoload.php ]]; then
    echo "FATAL: vendor missing"
    exit 1
fi

# Clean cache (this is fine)
php artisan config:clear
php artisan route:clear
php artisan view:clear

# --- Key: Temporarily comment out all database-related steps ---
# echo "Checking DB..."
# php artisan tinker --execute="DB::connection()->getPdo();"

# echo "Running migrations..."
# php artisan migrate --force

# echo "Seeding..."
# php artisan db:seed --class=PermissionSeeder --force
# php artisan db:seed --class=SuperAdminSeeder --force

echo "=== Bootstrap minimal done ==="