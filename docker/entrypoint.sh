#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then
        cp .env.docker.example .env
    elif [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [ -f artisan ]; then
    if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        php artisan key:generate --force --no-interaction
    fi

    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan storage:link || true
fi

exec "$@"
