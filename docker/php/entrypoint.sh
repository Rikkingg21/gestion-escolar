#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --optimize-autoloader
fi

chown -R www-data:www-data storage bootstrap/cache

exec php-fpm
