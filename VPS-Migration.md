# VPS Migration Guide
**Hotel CRM — Move from Replit to Self-Hosted VPS**
*Stack: PHP 8.2 · Laravel 12 · PostgreSQL 16 · nginx · Node 20 · pnpm · React/Vite*

---

## Recommended VPS
**Hetzner CX32** — 4 vCPU / 8 GiB RAM / 80 GB SSD / €12.49/mo  
→ https://hetzner.com/cloud  
Choose: **Ubuntu 24.04 LTS**. Add your SSH public key during checkout.

---

## Phase 1 — Harden the Server

```bash
# As root — create deploy user
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

# Disable root SSH
nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
systemctl restart sshd

# Firewall
ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw enable
```

---

## Phase 2 — Install All Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl unzip

# PHP 8.2 + all extensions
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd

# PostgreSQL 16
sudo apt install -y postgresql-16
sudo systemctl enable --now postgresql

# Redis (queue + cache + sessions)
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
# Verify:
redis-cli ping   # should return PONG

# Create DB and user (replace STRONG_PASSWORD)
sudo -u postgres psql -c "CREATE USER hotelcrm_user WITH PASSWORD 'STRONG_PASSWORD';"
sudo -u postgres createdb -O hotelcrm_user hotelcrm

# nginx
sudo apt install -y nginx && sudo systemctl enable --now nginx

# Node.js 20 + pnpm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm install -g pnpm@10

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Certbot (SSL)
sudo snap install --classic certbot
sudo ln -sf /snap/bin/certbot /usr/local/bin/certbot
```

---

## Phase 3 — Deploy the Application

```bash
# Generate SSH deploy key for GitHub
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -C "vps-deploy"
cat ~/.ssh/github_deploy.pub   # Add this to GitHub repo → Settings → Deploy keys (read-only)

# Configure SSH to use deploy key for GitHub
cat >> ~/.ssh/config << 'EOF'
Host github.com
  IdentityFile ~/.ssh/github_deploy
  IdentitiesOnly yes
EOF

# Clone repo
git clone git@github.com:YOUR_ORG/YOUR_REPO.git /var/www/hotelcrm
cd /var/www/hotelcrm

# Create .env — copy all values from Replit Secrets, then change these:
cp .env.example .env
nano .env
```

**Key `.env` values to change for VPS:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://resort.dreamstechnology.in

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hotelcrm
DB_USERNAME=hotelcrm_user
DB_PASSWORD=STRONG_PASSWORD
DB_SSLMODE=           # leave blank — local connection needs no SSL

# Redis (queue, cache, sessions)
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=predis   # predis/predis is a Composer package — no ext-redis C extension needed
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```
> Copy all other secrets (APP_KEY, MAIL_*, WA_*, FIREBASE_*, FCM_*) from Replit Secrets as-is.

```bash
# Install dependencies & build assets
composer install --no-dev --optimize-autoloader
pnpm install && pnpm run build

# Set permissions
sudo chown -R deploy:www-data /var/www/hotelcrm
sudo chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan app:safe-migrate

# Generate app key only if APP_KEY is not already set in .env
# php artisan key:generate   ← SKIP if you copied APP_KEY from Replit (keep same key)
```

---

## Phase 4 — Configure nginx

```bash
sudo nano /etc/nginx/sites-available/hotelcrm
```

Paste this config:
```nginx
server {
    listen 80;
    server_name resort.dreamstechnology.in;
    root /var/www/hotelcrm/public;
    index index.php;

    client_max_body_size 25M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2|svg)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location ~ /\.ht { deny all; }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/hotelcrm /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# SSL certificate (certbot auto-configures nginx)
sudo certbot --nginx -d resort.dreamstechnology.in
```

---

## Phase 5 — Configure PHP-FPM Pool

```bash
sudo nano /etc/php/8.2/fpm/pool.d/hotelcrm.conf
```

```ini
[hotelcrm]
user = deploy
group = www-data
listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 40
pm.start_servers = 8
pm.min_spare_servers = 4
pm.max_spare_servers = 16
pm.max_requests = 500
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 25M
php_admin_value[memory_limit] = 256M
clear_env = no
```

```bash
# Remove default pool to avoid conflicts
sudo mv /etc/php/8.2/fpm/pool.d/www.conf /etc/php/8.2/fpm/pool.d/www.conf.disabled
sudo systemctl restart php8.2-fpm
```

---

## Phase 6 — Migrate Production DB from Neon to VPS

```bash
# Step 1: In Replit dev terminal — dump the Neon production DB
# The $DATABASE_URL env var has the Neon connection string
pg_dump "$DATABASE_URL" --no-owner --no-acl -Fc -f /tmp/prod_dump.dump

# Step 2: Transfer to VPS (run from Replit terminal)
scp /tmp/prod_dump.dump deploy@YOUR_VPS_IP:/tmp/prod_dump.dump

# Step 3: On VPS — restore
pg_restore --no-owner --no-acl -d hotelcrm /tmp/prod_dump.dump

# Step 4: Verify row counts match production
psql -U hotelcrm_user -d hotelcrm -c "SELECT count(*) FROM hotels;"
psql -U hotelcrm_user -d hotelcrm -c "SELECT count(*) FROM bookings;"
psql -U hotelcrm_user -d hotelcrm -c "SELECT count(*) FROM customers;"
```

---

## Phase 7 — Auto-Deploy on Git Push

The agent will create these scripts in the repo. After they are committed:

### Scripts created by agent
| File | Purpose |
|------|---------|
| `scripts/vps-deploy.sh` | Full deploy: backup → pull → build → migrate → reload |
| `scripts/vps-backup.sh` | Pre-deploy backup: pg_dump + code tar, timestamped + git SHA |
| `scripts/vps-restore.sh` | Restore from backup: `./restore.sh backup_20260604_143000_abc1234` |
| `public/deploy-hook.php` | GitHub webhook receiver — verifies HMAC, triggers deploy |

### Setup on VPS
```bash
# Allow deploy user to reload PHP-FPM and nginx without password
sudo visudo
# Add this line:
deploy ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.2-fpm, /usr/bin/systemctl reload nginx

# Create backup directory
sudo mkdir -p /var/backups/hotelcrm
sudo chown deploy:deploy /var/backups/hotelcrm

# Create deploy log
sudo touch /var/log/hotelcrm-deploy.log
sudo chown deploy:deploy /var/log/hotelcrm-deploy.log

# Make scripts executable
chmod +x /var/www/hotelcrm/scripts/vps-deploy.sh
chmod +x /var/www/hotelcrm/scripts/vps-backup.sh
chmod +x /var/www/hotelcrm/scripts/vps-restore.sh
```

### Add to `.env` on VPS
```env
DEPLOY_WEBHOOK_SECRET=generate_a_random_64_char_string_here
```

### Register GitHub Webhook
1. Go to GitHub repo → **Settings → Webhooks → Add webhook**
2. Payload URL: `https://resort.dreamstechnology.in/deploy-hook.php`
3. Content type: `application/json`
4. Secret: same value as `DEPLOY_WEBHOOK_SECRET`
5. Events: **Just the push event**
6. Active: ✅

### Test it
```bash
# On VPS — watch deploy log
tail -f /var/log/hotelcrm-deploy.log

# On Replit — push a dummy commit
git commit --allow-empty -m "test: trigger auto-deploy"
git push origin main
```
You should see the deploy log show: backup → pull → build → migrate → reload.

---

## How to Restore from a Backup

```bash
# List available backups
ls /var/backups/hotelcrm/

# Example output:
# backup_20260605_143000_abc1234/
# backup_20260605_120000_def5678/
# backup_20260604_180000_ghi9012/

# Restore to a specific backup
cd /var/www/hotelcrm
./scripts/vps-restore.sh backup_20260605_143000_abc1234

# What it does:
# 1. Stops PHP-FPM
# 2. Drops DB and restores from dump
# 3. Extracts code snapshot
# 4. Runs php artisan app:safe-migrate
# 5. Restarts PHP-FPM + nginx
```

Each backup folder contains:
- `db.dump` — full PostgreSQL dump
- `code.tar.gz` — full code snapshot (excludes vendor/, node_modules/)
- `manifest.json` — timestamp, git SHA, commit message

---

## Phase 8 — DNS Cutover

```bash
# BEFORE cutover — lower TTL to 60 seconds in your DNS panel
# Wait 24 hours for TTL to propagate globally
# THEN update A record for resort.dreamstechnology.in → VPS IP
# After 5 minutes, verify:
curl -I https://resort.dreamstechnology.in
```

---

## Phase 9 — Retire Replit Production

1. Publish → Manage → **Shut down** (stops billing for Reserved VM)
2. Keep the dev workflow running on Replit for development
3. **New workflow going forward:**
   ```
   Code on Replit dev → git commit → git push → VPS auto-deploys
   ```

---

## Quick Reference

| What | Where |
|------|-------|
| App root | `/var/www/hotelcrm` |
| nginx config | `/etc/nginx/sites-available/hotelcrm` |
| PHP-FPM pool | `/etc/php/8.2/fpm/pool.d/hotelcrm.conf` |
| nginx logs | `/var/log/nginx/error.log` |
| PHP-FPM logs | `/var/log/php8.2-fpm.log` |
| Deploy log | `/var/log/hotelcrm-deploy.log` |
| Backups | `/var/backups/hotelcrm/` |
| DB name | `hotelcrm` |
| DB user | `hotelcrm_user` |
| Laravel scheduler | Add to crontab: `* * * * * cd /var/www/hotelcrm && php artisan schedule:run` |
| Queue worker | Supervisor config (agent provides) — runs `php artisan queue:work` |

---

## Scheduler + Queue Worker (Crontab + Supervisor)

```bash
# Scheduler — add to deploy user's crontab
crontab -e
# Add:
* * * * * cd /var/www/hotelcrm && php artisan schedule:run >> /dev/null 2>&1
```

```bash
# Queue worker — install Supervisor
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/hotelcrm-worker.conf
```

```ini
[program:hotelcrm-worker]
command=php /var/www/hotelcrm/artisan queue:work --sleep=3 --tries=3 --timeout=60 --queue=default
directory=/var/www/hotelcrm
user=deploy
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/hotelcrm-worker.log
```

> **Note:** Redis must be running before Supervisor starts the worker. Since `redis-server` is a systemd service (enabled above), it starts automatically on boot — Supervisor workers start after it.

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start hotelcrm-worker
```

---

*When ready to proceed, tell the agent "implement VPS scripts" and it will create `vps-deploy.sh`, `vps-backup.sh`, `vps-restore.sh`, and `deploy-hook.php` ready to commit and use.*
