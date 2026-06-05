#!/bin/bash
set -e

echo "[build.sh] Starting build..."

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "[build.sh] Clearing config cache..."
php artisan config:clear

echo "[build.sh] Running migrations..."
# safe-migrate calls view:clear internally — run it BEFORE optimize
# so optimize caches views AFTER they've been cleared by migrations
php artisan app:safe-migrate

# bootstrap/app.php probes 127.0.0.1:6379 — if Redis is down (build container)
# it auto-sets CACHE_STORE=file so these commands never touch Redis.
echo "[build.sh] Warming config/route/view caches..."
php artisan optimize

echo "[build.sh] Build complete."
