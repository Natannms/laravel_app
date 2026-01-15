#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/app/private \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ ! -f composer.json ]; then
  composer create-project laravel/laravel /tmp/app "^12.0"
  cp -R /tmp/app/. /var/www/html/
  rm -rf /tmp/app
fi

if [ ! -f .env ]; then
  cp .env.example .env
fi

sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env || true
sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env || true
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env || true
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=laravel/' .env || true
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' .env || true
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env || true

# Instala dependências somente se vendor não existir, e evita scripts
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --no-scripts || true
fi

php artisan key:generate || true

if id www-data >/dev/null 2>&1; then
  chown -R www-data:www-data storage bootstrap/cache
else
  chown -R 82:82 storage bootstrap/cache || true
fi
chmod -R u+rwX,g+rwX storage bootstrap/cache || true

if [ "${APP_ENV:-}" = "local" ] || [ "${APP_DEBUG:-}" = "true" ]; then
  chmod -R 0777 storage bootstrap/cache || true
fi

php artisan storage:link || true

if ! grep -q '"filament/filament"' composer.json; then
  composer require filament/filament:^3.0
  php artisan filament:install || true
fi

php artisan migrate --force || true

exec docker-php-entrypoint php-fpm
