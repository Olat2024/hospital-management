#!/usr/bin/env bash
set -e

cd /var/www/app

php artisan config:clear || true
php artisan route:clear || true

cat > /var/www/app/.env <<EOF
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
grep DB_HOST /var/www/app/.env

composer install --no-dev --working-dir=/var/www/app

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Creating super admin ==="
php artisan db:seed --class=SuperAdminSeeder --force
echo "=== Super admin ready ==="