#!/bin/sh

set -e

echo "Starting Laravel..."

# Tạo cache/config
php artisan config:clear
php artisan cache:clear

# Tạo storage link nếu chưa có
php artisan storage:link || true

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Khởi động PHP-FPM
php-fpm -D

# Khởi động Nginx ở foreground
nginx -g "daemon off;"
