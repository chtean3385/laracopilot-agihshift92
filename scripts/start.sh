#!/bin/bash
set -e

echo "[start.sh] Starting Laravel application on port 5000..."
echo "[start.sh] APP_ENV=${APP_ENV:-not set}"
echo "[start.sh] PHP version: $(php -r 'echo PHP_VERSION;')"

# NOTE: Do NOT run `config:clear` / `config:cache` here.
# Autoscale containers have a read-only filesystem (only /tmp is writable),
# so writing to bootstrap/cache/* fails and can corrupt the prebuilt cache.
# The build phase already runs `php artisan optimize`, so config is cached
# at image build time with the correct production env.

echo "[start.sh] Verifying app can bootstrap..."
php artisan --version 2>&1

echo "[start.sh] Launching server on 0.0.0.0:5000 ..."
exec php artisan serve --host=0.0.0.0 --port=5000
