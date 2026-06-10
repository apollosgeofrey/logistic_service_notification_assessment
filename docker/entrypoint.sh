#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

php artisan migrate --force
php artisan db:seed --force

exec "$@"
