#!/bin/bash
set -e

# Install PHP dependencies
composer install --no-interaction --no-progress --prefer-dist

# Run safe migrations + seed global templates + provision hotels
php artisan app:safe-migrate

# Clear compiled views and config cache
php artisan view:clear
php artisan config:clear
