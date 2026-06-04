---
name: Predis Redis Client Configuration
description: Why REDIS_CLIENT must be hardcoded to predis in config/database.php on Replit/VPS
---

## Rule
In `config/database.php`, the Redis client MUST be hardcoded:
```php
'client' => 'predis',
```
Do NOT use `env('REDIS_CLIENT', 'predis')`.

**Why:** Replit shared env vars are NOT always visible via `getenv()` in shell-launched PHP processes (queue workers, artisan commands started from bash). The `.env` file has `REDIS_CLIENT=phpredis`. If `env()` is used, phpdotenv resolves it to `phpredis` — which requires the `ext-redis` C extension that is NOT installed. This crashes the queue worker with `Class "Redis" not found`.

**How to apply:** Any time Redis config is touched, verify `config/database.php` still has the hardcoded `'client' => 'predis'` line, not `env('REDIS_CLIENT', ...)`.

**predis** is installed as `predis/predis` via composer — pure PHP, no extension needed.
