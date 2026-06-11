#!/bin/sh
set -e

if [ ! -x /usr/local/bin/composer ]; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if [ ! -f vendor/autoload.php ]; then
    echo "Installing PHP dependencies..."
    /usr/local/bin/composer install --no-interaction --prefer-dist --no-progress
fi

php artisan config:clear --ansi
php artisan migrate --force
php artisan db:seed --force

exec "$@"
