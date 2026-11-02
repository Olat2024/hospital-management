#!/usr/bin/env bash
set -e

echo "=== [bootstrap] Starting ==="
cd /var/www/html

# 1. Write .env so Artisan commands use the correct DB
cat > /var/www/html/.env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:/CHz4rN4SNWx0oc/EwStxGPL7N533j1uaX3L0dExkqY=
APP_DEBUG=false
APP_URL=https://hospital-management-y2x2.onrender.com
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DB_HOST=dpg-dbhkt2vlk1mc73d16c5g-a
DB_PORT=5432
DB_DATABASE=hospital_db_4vim
DB_USERNAME=hospital_db_4vim_user
DB_PASSWORD=eGj0QscoeIJOz7FHX2AcBbJG0zLNN80K
SESSION_DRIVER=database
CACHE_STORE=file
EOF

echo "=== [bootstrap] .env written ==="
grep DB_HOST .env

# 2. Remove any cached config baked into the image
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-*.php

# 3. Install Composer dependencies (generates vendor/autoload.php)
echo "=== [bootstrap] composer install ==="
composer install --no-dev --optimize-autoloader

if [ ! -f vendor/autoload.php ]; then
    echo "FATAL: vendor/autoload.php still missing after composer install"
    exit 1
fi

# 4. Regenerate autoloader so new Seeder classes are found
composer dump-autoload --no-dev --optimize

# 5. Ensure storage/cache dirs are writable
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

# 6. Clear caches AFTER .env is in place
php artisan config:clear
php artisan route:clear || true
php artisan view:clear || true

# 7. Verify DB connectivity before migrating
echo "=== [bootstrap] testing DB connection ==="
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"

# 8. Run migrations
echo "=== [bootstrap] running migrations ==="
php artisan migrate --force

# 9. Seed
echo "=== [bootstrap] seeding super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force

echo "=== [bootstrap] seeding permissions ==="
php artisan db:seed --class=PermissionSeeder --force

# 10. Clear Spatie + Laravel caches
php artisan cache:clear || true
php artisan permission:cache-reset || true

# 11. Verify seeding actually inserted data
echo "=== [bootstrap] verifying seeded data ==="
php artisan tinker --execute="
  echo 'permissions=' . \Spatie\Permission\Models\Permission::count() . PHP_EOL;
  echo 'roles=' . \Spatie\Permission\Models\Role::count() . PHP_EOL;
"

echo "=== [bootstrap] Done ==="