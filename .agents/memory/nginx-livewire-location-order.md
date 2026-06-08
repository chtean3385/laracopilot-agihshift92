---
name: Nginx Livewire location block ordering
description: Livewire's JS endpoint (/livewire-*/livewire.js) is a dynamic PHP route — the static-file regex must NOT come before the Livewire location block in nginx.conf.
---

## Rule
In `scripts/nginx.conf.template`, the `location ~ ^/livewire-[^/]+/` block MUST be placed **before** the static-file regex `location ~* \.(css|js|...)$`.

Nginx matches regex locations in order of appearance — the first match wins. If the static-file rule comes first, `/livewire-611cdfc2/livewire.js` matches `\.js$` and is served as a static file that does not exist on disk → 404. This silently breaks ALL Livewire components (search, pagination, `wire:click`).

**Why:** Livewire 4 serves its JS bundle from a dynamically-generated URL like `/livewire-{APP_KEY_HASH}/livewire.js`. There is no real file at that path in `public/` — it is a PHP route handled by `FrontendAssets@returnJavaScriptAsFile`.

**How to apply:** Whenever editing `scripts/nginx.conf.template`, verify the Livewire location block appears before the `~*` static-file block. The correct order:
1. `location ~ ^/livewire-[^/]+/` → `try_files $uri /index.php?$query_string;`
2. `location ~* \.(css|js|...)$` → static file serving with `try_files $uri =404;`
3. `location /` → Laravel fallback
