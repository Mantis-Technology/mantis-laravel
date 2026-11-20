#!/usr/bin/env bash
set -e

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ -n "${PORT:-}" ] && [ "$PORT" != "8080" ]; then
    sed -i "s/listen 8080 default_server;/listen ${PORT} default_server;/" /etc/nginx/sites-available/default
fi

php artisan config:clear
php artisan migrate --force
php artisan tenants:migrate --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm -D
exec nginx -g "daemon off;"