---
name: View composer N+1 settings
description: View::composer('*') fires for every Blade partial; cache Setting::first() in app container to avoid dozens of DB queries per page.
---

## Rule
In `AppServiceProvider`, any DB query inside `View::composer('*', ...)` runs for **every view partial that renders** — including every `@include`, layout, and Livewire component view. A complex page like booking-show renders 30-50 partials, causing 30-50 identical `Setting::first()` round-trips to the database.

## Fix
Cache the result in the app container so it runs exactly once per request:

```php
View::composer('*', function ($view) {
    if (!$view->offsetExists('settings')) {
        if (!app()->bound('crm.settings')) {
            try {
                app()->instance('crm.settings', \App\Models\Setting::first());
            } catch (\Throwable $e) {
                app()->instance('crm.settings', null);
            }
        }
        $view->with('settings', app('crm.settings'));
    }
});
```

`app()->instance()` is request-scoped in standard PHP-FPM (each request bootstraps a fresh container), so this is safe for multi-tenancy.

**Why:** Was causing 10s+ LCP on booking details page — root cause of the "VM is slow" complaint.

**How to apply:** Any time a DB query appears inside a `View::composer('*')`, wrap it with `app()->bound()`/`app()->instance()` guard.
