#!/bin/bash
set -e

echo "[build.sh] Starting build..."

# composer --optimize-autoloader handles the classmap — no artisan needed.
echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# config:clear just deletes a file — safe, no Redis.
echo "[build.sh] Clearing config cache..."
php artisan config:clear

# app:safe-migrate connects to DB only — no Redis.
echo "[build.sh] Running migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
