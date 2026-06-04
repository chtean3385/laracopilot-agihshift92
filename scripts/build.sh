#!/bin/bash
set -e

echo "[build.sh] Starting build..."

# Redis is needed by cache:clear and optimize (CACHE_STORE=redis).
# Start it daemonized for the duration of the build; it dies when this
# process exits since it's not a system service in the build container.
echo "[build.sh] Starting Redis for build phase..."
redis-server --daemonize yes --port 6379 --save "" --loglevel warning 2>/dev/null || true
sleep 1

echo "[build.sh] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "[build.sh] Clearing and warming Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan optimize

echo "[build.sh] Running safe migrations..."
php artisan app:safe-migrate

echo "[build.sh] Build complete."
