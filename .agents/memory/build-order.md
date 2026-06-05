---
name: Build order — safe-migrate before optimize
description: safe-migrate calls view:clear; must run before optimize or the view cache is wiped right after being built.
---

## Rule
`php artisan app:safe-migrate` calls `$this->call('view:clear')` at line 17 of `SafeMigrate.php`. If `optimize` runs before `safe-migrate`, the view cache is immediately destroyed and views recompile on every first access in production.

## Correct build.sh order
1. `composer install --no-dev --optimize-autoloader`
2. `php artisan config:clear`
3. `php artisan app:safe-migrate`   ← clears views as part of migrations
4. `php artisan optimize`           ← caches config + routes + views AFTER clear

**Why:** Wrong order (optimize then safe-migrate) caused views to never be cached in production, contributing to slow page loads.

**How to apply:** Always keep optimize as the LAST step in build.sh.
