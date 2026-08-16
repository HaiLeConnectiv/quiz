#!/bin/sh

set -e

echo "Starting Laravel..."

# Đảm bảo quyền
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Không chạy cache:clear vì có thể gây lỗi permission trên Render

# Storage link
php artisan storage:link || true

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# PHP-FPM
php-fpm -D

# Nginx
nginx -g "daemon off;"
