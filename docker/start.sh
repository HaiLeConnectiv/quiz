#!/bin/sh

set -e

echo "Starting Laravel..."

# Permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Database migration + seeder
php artisan migrate --seed --force

# Storage
php artisan storage:link || true

# Laravel cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# PHP-FPM
php-fpm -D

# Nginx
nginx -g "daemon off;"
