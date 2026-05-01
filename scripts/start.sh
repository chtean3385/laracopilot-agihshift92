#!/bin/bash
set -e

echo "[start.sh] Starting Laravel application on port 5000..."
echo "[start.sh] APP_ENV=${APP_ENV:-not set}"
echo "[start.sh] PHP version: $(php -r 'echo PHP_VERSION;')"

# Clear any stale bootstrap config cache that may have been built with wrong env
if [ "${APP_ENV}" = "production" ]; then
    echo "[start.sh] Production: clearing and rebuilding config cache..."
    php artisan config:clear 2>&1 || true
    php artisan config:cache 2>&1 || true
fi

echo "[start.sh] Verifying app can bootstrap..."
php artisan --version 2>&1

echo "[start.sh] Launching server on 0.0.0.0:5000 ..."
exec php artisan serve --host=0.0.0.0 --port=5000
