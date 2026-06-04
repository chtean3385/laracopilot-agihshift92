#!/bin/bash
set -e

echo "[build.sh] Starting build..."

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "[build.sh] Clearing and warming Laravel caches..."
php artisan config:clear

# Use file driver so cache:clear doesn't need Redis.
# Redis is not available in the build container — only at runtime.
CACHE_STORE=file php artisan cache:clear

php artisan optimize

echo "[build.sh] Running safe migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
