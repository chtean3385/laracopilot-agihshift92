#!/bin/bash
set -e

echo "[build.sh] Starting build..."

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "[build.sh] Clearing config cache and warming Laravel caches..."
php artisan config:clear
php artisan optimize

echo "[build.sh] Running safe migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
