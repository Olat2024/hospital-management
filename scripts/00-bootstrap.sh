#!/usr/bin/env bash
set -euo pipefail

echo "=== [bootstrap] Starting ==="
cd /var/www/html

required_env=(APP_KEY APP_URL DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD)
for name in "${required_env[@]}"; do
    if [[ -z "${!name:-}" ]]; then
        echo "FATAL: Required Render environment variable ${name} is not set."
        exit 1
    fi
done

export APP_NAME="${APP_NAME:-Hospital Management}"
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export CACHE_STORE="${CACHE_STORE:-file}"

rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php

if [[ ! -f vendor/autoload.php ]]; then
    echo "FATAL: vendor/autoload.php is missing from the production image."
    exit 1
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan package:discover --ansi
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "=== [bootstrap] Checking database connectivity ==="
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"

echo "=== [bootstrap] Running migrations ==="
php artisan migrate --force

echo "=== [bootstrap] Seeding permissions ==="
php artisan db:seed --class=PermissionSeeder --force

echo "=== [bootstrap] Seeding super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force

php artisan permission:cache-reset

echo "=== [bootstrap] Verifying seeded data ==="
php artisan tinker --execute="
  echo 'permissions=' . \Spatie\Permission\Models\Permission::count() . PHP_EOL;
  echo 'roles=' . \Spatie\Permission\Models\Role::count() . PHP_EOL;
"

echo "=== [bootstrap] Done ==="
