#!/bin/bash
set -e

echo "[start.sh] Starting Hotel CRM (nginx + PHP-FPM)..."
echo "[start.sh] APP_ENV=${APP_ENV:-not set}"
echo "[start.sh] PHP version: $(php -r 'echo PHP_VERSION;')"

APP_DIR="$(pwd)"
RUN_DIR="/tmp/hotel-crm-run"
CURRENT_USER="$(whoami)"

mkdir -p "$RUN_DIR"

# ── PHP-FPM config ────────────────────────────────────────────────────────────
cat > "$RUN_DIR/php-fpm.conf" << PHPFPMEOF
[global]
pid = $RUN_DIR/php-fpm.pid
error_log = $RUN_DIR/php-fpm-error.log
daemonize = yes

[www]
user = $CURRENT_USER
group = $CURRENT_USER
listen = $RUN_DIR/php-fpm.sock
listen.mode = 0666
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
clear_env = no
php_admin_value[error_log] = $RUN_DIR/php-fpm-www.log
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 25M
php_admin_value[memory_limit] = 256M
PHPFPMEOF

# ── Nginx config (replace placeholders in template) ──────────────────────────
sed -e "s|__APP_DIR__|$APP_DIR|g" \
    -e "s|__RUN_DIR__|$RUN_DIR|g" \
    "$APP_DIR/scripts/nginx.conf.template" > "$RUN_DIR/nginx.conf"

# ── Start queue worker in background ─────────────────────────────────────────
echo "[start.sh] Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 --timeout=60 --queue=default >> "$RUN_DIR/queue.log" 2>&1 &
echo "[start.sh] Queue worker started (PID $!)"

# ── Start scheduler in background ─────────────────────────────────────────────
echo "[start.sh] Starting scheduler..."
php artisan schedule:work >> "$RUN_DIR/scheduler.log" 2>&1 &
echo "[start.sh] Scheduler started (PID $!)"

# ── Start PHP-FPM (daemonizes itself) ─────────────────────────────────────────
echo "[start.sh] Starting PHP-FPM..."
php-fpm -y "$RUN_DIR/php-fpm.conf"
# Wait for the socket to appear
for i in 1 2 3 4 5; do
    [ -S "$RUN_DIR/php-fpm.sock" ] && break
    sleep 1
done
echo "[start.sh] PHP-FPM started (socket: $RUN_DIR/php-fpm.sock)"

# ── Start nginx (foreground — keeps the container alive) ──────────────────────
echo "[start.sh] Starting nginx on port 5000..."
exec nix-shell -p nginx --run "nginx -e $RUN_DIR/nginx-error.log -c $RUN_DIR/nginx.conf -g 'daemon off;'"
