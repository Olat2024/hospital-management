#!/usr/bin/env bash
set -euo pipefail

echo "=== [bootstrap] Starting ==="
cd /var/www/html

# 1. Check environment variables
required_env=(APP_KEY APP_URL DATABASE_URL)
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
export SESSION_DRIVER="file"
export CACHE_STORE="${CACHE_STORE:-file}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"

# 2. Check vendor directory
if [[ ! -f vendor/autoload.php ]]; then
    echo "FATAL: vendor/autoload.php is missing from the production image."
    exit 1
fi

# 3. Prepare directories
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 4. Register packages and clear old cache
php artisan package:discover --ansi
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Run database migrations (moved to the front)
echo "=== [bootstrap] Running migrations ==="
php artisan migrate --force

# 6. Run seeders (moved to the front)
echo "=== [bootstrap] Seeding permissions ==="
php artisan db:seed --class=PermissionSeeder --force

echo "=== [bootstrap] Seeding super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force

# 7. Clear permission cache
php artisan permission:cache-reset

# 8. Verify seeded data (using direct php scripts instead of tinker)
echo "=== [bootstrap] Verifying seeded data ==="
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "permissions=" . \Spatie\Permission\Models\Permission::count() . PHP_EOL;
echo "roles=" . \Spatie\Permission\Models\Role::count() . PHP_EOL;
'

echo "=== [bootstrap] Done ==="