#!/bin/bash
set -e

echo "[build.sh] Starting build..."

# Tell bootstrap/app.php to use file/sync drivers (no Redis in build container)
export ARTISAN_BUILD=1

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "[build.sh] Clearing config cache..."
php artisan config:clear

echo "[build.sh] Running migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
