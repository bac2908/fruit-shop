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

composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data \
    storage/app/private \
    storage/app/public \
    storage/framework \
    storage/logs \
    bootstrap/cache || true

if [ -f artisan ]; then
    if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        php artisan key:generate --force --no-interaction
    fi

    php artisan config:clear || true

    database_attempt=1
    database_attempt_limit=30
    until php -r '
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            getenv("DB_HOST") ?: "mysql",
            getenv("DB_PORT") ?: "3306",
            getenv("DB_DATABASE") ?: "fruitshop"
        );
        new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 2]);
    ' >/dev/null 2>&1; do
        if [ "$database_attempt" -ge "$database_attempt_limit" ]; then
            echo "Database is not ready after ${database_attempt_limit} attempts." >&2
            exit 1
        fi

        echo "Waiting for MySQL (${database_attempt}/${database_attempt_limit})..."
        database_attempt=$((database_attempt + 1))
        sleep 2
    done

    php artisan migrate --force --no-interaction
    if [ ! -e public/storage ]; then
        php artisan storage:link
    fi
fi

exec "$@"
