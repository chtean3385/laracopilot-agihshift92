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

# Start queue worker in the background so WhatsApp sends and other async
# jobs (e.g. notifications) don't block HTTP requests.
# --sleep=3  : wait 3s before polling when the queue is empty
# --tries=3  : retry failed jobs up to 3 times
# --timeout=60 : kill a job that runs longer than 60s
echo "[start.sh] Starting queue worker in background..."
php artisan queue:work --sleep=3 --tries=3 --timeout=60 --queue=default 2>&1 &
QUEUE_PID=$!
echo "[start.sh] Queue worker started (PID ${QUEUE_PID})"

echo "[start.sh] Launching server on 0.0.0.0:5000 ..."
exec php artisan serve --host=0.0.0.0 --port=5000
