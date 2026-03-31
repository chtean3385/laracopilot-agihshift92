# Hotel CRM — Laravel 12 (SaaS in Progress)

## Overview
Full hotel/resort management CRM built on Laravel 12, evolving into a multi-tenant SaaS platform. Features: guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register (signatures + ID docs), Pathik autofill, OTA Channel Manager, Payment Links, and a web-based self-hosting installer.

## Architecture
- **Framework**: Laravel 12 (PHP 8.2)
- **Database (dev)**: SQLite (`database/database.sqlite`)
- **Database (production/self-hosted)**: MySQL 8.0+
- **Frontend**: Blade templates + Tailwind CSS (CDN) + Font Awesome + Livewire 4
- **Authentication**: Custom session-based auth (users table + `is_super_admin` flag)
- **RBAC**: Dynamic DB-driven roles & permissions
- **Port**: 5000 (`php artisan serve --host=0.0.0.0 --port=5000`)

## Critical Coding Rules
1. **JS in Blade**: Use inline `<script>` inside `@section('content')`, NOT `@push('scripts')` for inline JS
2. **Blade raw output in JS**: Always use `{!! json_encode($var) !!}` — NEVER `{{ json_encode($var) }}` (double-encoding breaks JS) and NEVER `'{{ addslashes($var) }}'` (newlines break JS strings)
3. **Admin layout**: yields `@yield('content')`, stacks `@stack('styles')` + `@stack('scripts')`; NO `@yield('modals')`; CSRF meta in `<head>`
4. **Module checks**: `Module::isEnabled('slug')` — slugs: `whatsapp`, `payment_links`, `pathik`, `channel_manager`
5. **Session role**: `session('crm_user_role')` stores role name string (e.g. 'Admin', 'Manager') from DB login; 'Super Admin' only for hardcoded system account
6. **Route URLs in JS**: Always use `'{{ route('name') }}'` — never hardcode `/path` (breaks in subdirectory hosting)
7. **Roles table columns**: `id`, `name`, `description`, `is_system`, `hotel_id`, `created_at`, `updated_at` — NO `slug` column
8. **Installer creates Admin user**: role='Admin', is_super_admin=false + creates hotel record + hotel_users entry. Hardcoded superadmin@gmail.com is system-only, never in DB.
9. **Multi-tenant scoping**: All 16 data models use `BelongsToHotel` trait — queries auto-scope to `HotelContext::getHotel()`. When context is null (Super Admin / installer), no scope is applied. Always use `withoutGlobalScopes()` for cross-hotel queries.
10. **Hotel session keys**: `crm_hotel_id`, `crm_hotel_name`, `crm_hotel_count`, `crm_hotel_options` (picker array)

## Setup (Dev — Replit)
1. `composer install`
2. Copy `.env.example` to `.env` → `php artisan key:generate`
3. `touch database/database.sqlite`
4. `php artisan migrate --force`
5. `php artisan db:seed --class=RolesAndPermissionsSeeder`
6. `php artisan db:seed --class=ModuleSeeder`
7. `php artisan db:seed --class=SettingSeeder`
8. `php artisan storage:link`
9. `php artisan serve --host=0.0.0.0 --port=5000`

## Setup (Self-Hosted — Web Installer)
Visit `/install` in the browser. Three-step wizard:
- Step 1: MySQL DB credentials → Test Connection AJAX
- Step 2: App name, URL, admin email + password
- Step 3: Runs migrations, seeds, creates superadmin, writes `storage/installed.lock`
After install, `/install` is permanently locked (middleware redirects to `/login`).

**Apache requirement**: `AllowOverride All` + `mod_rewrite` enabled. App must be served with document root at the `public/` folder, OR use the root `.htaccess` (project root) which redirects to `public/`.

## Users
| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | Super@#3385 | Super Admin (`is_super_admin=true`) |
| admin@resort.com | admin123 | Admin |

Super Admin bypasses all permission checks. User roles from `hotel_users` pivot stored in `session('crm_user_role')`.

## Key Routes
- `/install` — Web installer (locked after first run)
- `/login`, `/logout` — Auth
- `/dashboard` — Main dashboard
- `/customers` — Guest management
- `/rooms` — Room management
- `/bookings` — Bookings
- `/checkin`, `/checkout` — Check-in/out
- `/payments` — Payments
- `/invoices` — Invoices
- `/reports` — Reports (Manager+ only)
- `/settings` — Settings (Admin+)
- `/activity-log` — Audit trail
- `/roles` — Roles & Permissions
- `/modules` — Feature toggles
- `/whatsapp` — WhatsApp config
- `/pathik` — Pathik autofill module
- `/channel-manager` — OTA Channel Manager

## Modules (feature flags)
Stored in `modules` table. Check with `Module::isEnabled('slug')`:
| Slug | Feature |
|------|---------|
| `whatsapp` | WhatsApp messaging |
| `payment_links` | Payment link generation |
| `pathik` | Pathik portal autofill |
| `channel_manager` | OTA Channel Manager |

## Models
- `Customer` — Hotel guests (+ `signature` column, `id_document_path`)
- `CustomerDocument` — Guest ID documents
- `Room` — Rooms (+ meal options: `has_breakfast/lunch/dinner`, prices; `has_extra_bed`, `extra_bed_price`)
- `Booking` — Reservations (+ meal plan, extra beds, `special_requests` for OTA ref)
- `BookingGuest` — Additional guests on a booking (+ `signature`, `id_document_path`, `id_document_name`)
- `Payment` — Payment records
- `Invoice` — Invoices
- `Setting` — App-wide settings (logo, tagline, GST%, etc.)
- `Role` / `Permission` — RBAC
- `ActivityLog` — Audit trail
- `Module` — Feature flags
- `WhatsAppConfig` / `WhatsAppTemplate` — WA messaging
- `PathikConfig` — Pathik autofill API token
- `ChannelManagerConfig` / `ChannelRoomMapping` / `ChannelBooking` — OTA module

## Signature Canvas Pattern
- Container div: `touch-action: none` (prevents mobile scroll hijacking)
- Canvas sized to container width on every open (resets drawing)
- `_ciReady` flag on canvas element — listeners attached only once
- Primary guest: 150ms init delay + `scrollIntoView` on toggle
- Guest pads: same 150ms delay
- Blank canvas check before save (`isCanvasBlank()` using ImageData buffer)
- Primary save: `POST /guests/{id}/signature` → `CustomerController::saveSignature()`
- Guest save: `POST /bookings/{bookingId}/guests/{guestId}/signature` → `BookingGuestController::saveSignature()`

## Multi-Hotel (SaaS) Architecture — Task #1 COMPLETE
- **`hotels`** table: id, name, slug, status, plan (+ address/phone/email)
- **`hotel_users`** pivot: links users to hotels with a per-hotel role
- **`HotelContext`** singleton (`app(HotelContext::class)`): set by `SetHotelContext` middleware from `session('crm_hotel_id')`
- **`BelongsToHotel`** trait: applied to all 16 data models — auto-adds `HotelScope` + auto-fills `hotel_id` on `creating`
- **Login flow**: After auth → look up `hotel_users` → 1 hotel = auto-select; multiple = `/select-hotel` picker; 0 = error
- **Super Admin** has no `crm_hotel_id` — sees all data (scope inactive)
- **Hotel scope disabled** for: installer routes, health check, Pathik extension fetch API
- **Seeders**: All 4 seeders (Modules, Roles, Settings, WhatsApp) auto-detect or create hotel_id=1

## Services
- `App\Services\HotelContext` — Singleton; `setHotel(int)` / `getHotel()` / `isSet()` / `clear()`
- `App\Services\PermissionService` — `check($slug)`: Super Admin bypasses all; others checked against `crm_permissions` session array
- `App\Services\ActivityLogger` — `log($action, $module, $description)`: writes to `activity_logs` with `hotel_id`; silently ignores failures

## RBAC
- Middleware: `permission:slug` — blocks unauthorized routes
- Blade: `@canDo('slug') ... @endCanDo`
- Super Admin always returns `true` for all checks
- Permissions loaded on login → stored in `session('crm_permissions')`

## WhatsApp
- Uses `wa.me` deep links (no API key needed)
- 6 trigger templates: Booking Confirmation, Check-In Details, Payment Reminder, Check-Out Reminder, Welcome, Custom
- Config stored in `whatsapp_configs` table

## Pathik Module
- API token: 32-char random, stored in `pathik_configs`
- Data pushed to Cache (60 min TTL), fetched by Chrome Extension via API token
- Chrome Extension in `public/pathik-extension/` (MV3)
- Extension ZIP auto-generated at `public/pathik-extension.zip`

## OTA Channel Manager
- Providers: eZee Centrix (live API), STAAH (live API), SiteMinder, RateGain (manual)
- OTA bookings create Customer + Booking with `OTA-XXXXXX` booking number

## Branding
- Logo stored at `storage/app/public/logos/`
- Settings shared via `AppServiceProvider` → `View::composer('*', ...)` (lazy, hotel-scoped at render time)
- `trustProxies(at: '*')` in `bootstrap/app.php` — handles Replit HTTPS proxy headers

## DatabaseSeeder (production-safe)
Only seeds essential data — NO demo data:
- `ModuleSeeder` → default modules
- `RolesAndPermissionsSeeder` → roles + 25 permissions
- `SettingSeeder` → default settings
- `WhatsAppTemplateSeeder` → default WA templates

## SaaS Task Status
- **Task #1** ✅ COMPLETE: Multi-Hotel Core — BelongsToHotel trait on 16 models, hotel_users pivot, hotel picker
- **Task #2** PENDING: Platform Admin Dashboard — per-hotel feature control, subscriptions (depends on #1)
- **Task #3** PENDING: AI Smart CRM — OpenAI-powered insights, plan-gated (depends on #1)

## SaaS Admin Hierarchy (planned)
- **SaaS Admin** (`is_platform_admin=true`, `/platform/` routes) — controls feature availability per hotel
- **Hotel Super Admin** (`/admin/` full access) — manages staff + roles within enabled features
- **Hotel Staff** (Manager/Receptionist/Accountant) — RBAC-limited

## Email
Currently `MAIL_MAILER=log` — password reset emails written to `storage/logs/laravel.log`. To enable: set SMTP credentials in `.env`.

## Installer Files
- `app/Http/Controllers/InstallerController.php` — test DB, write .env, run migrations/seeds
- `app/Http/Middleware/CheckNotInstalled.php` — blocks `/install` if `storage/installed.lock` exists
- `resources/views/installer/index.blade.php` — 3-step wizard UI
- `.htaccess` (project root) — redirects to `public/` for subfolder Apache hosting
