#!/usr/bin/env bash
set -e
set -x

cd /var/www/html

# --- 1. Remove any cached config baked into the image BEFORE writing .env ---
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-*.php

# --- 2. Write .env ---
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

echo "=== .env written ==="
grep DB_HOST /var/www/html/.env

# --- 3. Clear caches AFTER .env is in place ---
php artisan config:clear
php artisan route:clear || true
php artisan view:clear || true

# --- 4. Ensure storage/bootstrap cache dirs are writable ---
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

# --- 5. Install dependencies ---
composer install --no-dev --working-dir=/var/www/html
composer dump-autoload --no-dev --working-dir=/var/www/html

# --- 6. Verify DB connectivity before touching the schema ---
echo "=== Testing DB connection ==="
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"

# --- 7. Migrate ---
echo "=== Running migrations ==="
php artisan migrate --force

# --- 8. Clear caches again (Spatie + Laravel) ---
echo "=== Clearing caches ==="
php artisan cache:clear || true
php artisan permission:cache-reset || true

# --- 9. Seed ---
echo "=== Creating super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force
echo "=== Super admin ready ==="

echo "=== Seeding permissions ==="
php artisan db:seed --class=PermissionSeeder --force
echo "=== Permissions ready ==="

# --- 10. Verify seeding actually inserted data ---
echo "=== Verifying seeded data ==="
php artisan tinker --execute="
  echo 'permissions=' . \Spatie\Permission\Models\Permission::count() . PHP_EOL;
  echo 'roles=' . \Spatie\Permission\Models\Role::count() . PHP_EOL;
  foreach (\Spatie\Permission\Models\Role::withCount('permissions')->get() as \$r) {
      echo \$r->name . ' => ' . \$r->permissions_count . PHP_EOL;
  }
"

echo "=== Done ==="