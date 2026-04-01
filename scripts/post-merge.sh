#!/bin/bash
set -e

# Install PHP dependencies
composer install --no-interaction --no-progress --prefer-dist

# Run migrations (idempotent — safe to run multiple times)
php artisan migrate --force

# Clear compiled views and config cache
php artisan view:clear
php artisan config:clear
