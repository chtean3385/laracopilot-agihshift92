#!/bin/bash
set -e

echo "[build.sh] Starting build..."

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# bootstrap/app.php probes 127.0.0.1:6379 — if Redis is down (build container)
# it auto-sets CACHE_STORE=file so these commands never touch Redis.
echo "[build.sh] Clearing and warming caches..."
php artisan config:clear
php artisan optimize   # caches config + routes + views → fast production responses

echo "[build.sh] Running migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
