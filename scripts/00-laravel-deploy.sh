#!/usr/bin/env bash
set -e

php artisan config:clear || true
php artisan route:clear || true

echo "=== Render env check ==="
echo "DATABASE_URL raw: [${DATABASE_URL}]"
env | grep -i database || echo "No DATABASE vars in env"
echo "=== end env check ==="

if [ -z "$DATABASE_URL" ]; then
  echo "ERROR: DATABASE_URL is empty - php-fpm is still clearing it!"
  exit 1
fi

cat > /var/www/html/.env <<EOF
APP_NAME=Laravel
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}
SESSION_DRIVER=database
CACHE_STORE=file
EOF

echo "=== .env written ==="
grep DATABASE_URL /var/www/html/.env

composer install --no-dev --working-dir=/var/www/html

echo "=== Running migrations ==="
php artisan migrate --force
echo "=== Migrations complete ==="