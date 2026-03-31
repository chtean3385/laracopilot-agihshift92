# Hotel CRM — Laravel 12 Multi-Tenant SaaS

## Overview
Full hotel/resort management CRM built on Laravel 12, fully evolved into a multi-tenant SaaS platform. Features: guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register (signatures + ID docs), Pathik autofill, OTA Channel Manager, Payment Links, web-based installer, and a Platform Admin SaaS console.

## Architecture
- **Framework**: Laravel 12 (PHP 8.2)
- **Database (dev)**: SQLite (`database/database.sqlite`)
- **Database (production/self-hosted)**: MySQL 8.0+
- **Frontend**: Blade templates + Tailwind CSS (CDN) + Font Awesome + Livewire 4
- **Authentication**: Custom session-based auth — two separate auth flows (Hotel CRM + Platform Admin)
- **RBAC**: Dynamic DB-driven roles & permissions per hotel
- **Port**: 5000 (`php artisan serve --host=0.0.0.0 --port=5000`)
- **Mail**: SMTP via `mail.dreamstechnology.in:465` (smtps), from `support@dreamstechnology.in`

## Critical Coding Rules
1. **JS in Blade**: Use inline `<script>` inside `@section('content')`, NOT `@push('scripts')` for inline JS (platform layout supports `@stack('scripts')` and `@stack('styles')`)
2. **Blade raw output in JS**: Always use `{!! json_encode($var) !!}` — NEVER `{{ json_encode($var) }}` (double-encoding breaks JS)
3. **Admin layout**: yields `@yield('content')`, stacks `@stack('styles')` + `@stack('scripts')`; NO `@yield('modals')`; CSRF meta in `<head>`
4. **Platform layout**: `resources/views/layouts/platform.blade.php` — purple sidebar; uses `@stack('scripts')` at line ~287
5. **Module checks**: `Module::isEnabled('slug')` — slugs: `whatsapp`, `payment_links`, `pathik`, `channel_manager`
6. **Session role**: `session('crm_user_role')` stores role name string (e.g. 'Admin', 'Manager'); 'Super Admin' for platform admin only
7. **Route URLs in JS**: Always use `'{{ route('name') }}'` — never hardcode `/path`
8. **Roles table columns**: `id`, `name`, `description`, `is_system`, `hotel_id`, `created_at`, `updated_at` — NO `slug` column
9. **Multi-tenant scoping**: All 16 data models use `BelongsToHotel` trait — queries auto-scope to `HotelContext::getHotel()`
10. **Hotel session keys**: `crm_hotel_id`, `crm_hotel_name`, `crm_hotel_count`, `crm_hotel_options`
11. **Platform controllers**: Use `DB::table()` throughout — no HotelContext/Eloquent global scopes
12. **currentHotelId() in UserController**: Falls back to `session('crm_sa_hotel_filter')` when `crm_hotel_id` is null — fixes SA-created user linking

## Platform Admin (SaaS Console) — `/platform/` routes

### Session Keys (Platform)
| Key | Value |
|-----|-------|
| `crm_logged_in` | `true` |
| `crm_user_id` | user id |
| `crm_user_name` | name |
| `crm_user_email` | email |
| `crm_user_role` | `'Super Admin'` |
| `crm_is_super_admin` | `true` |
| `crm_hotel_id` | `null` |
| `crm_sa_hotel_filter` | hotel id (when viewing a specific hotel's CRM as SA) |
| `platform_2fa_pending_user_id` | set during 2FA verification step |

### Platform Routes
- `GET /platform/login` — Platform admin login
- `GET /platform/2fa` — TOTP verification step
- `GET /platform/dashboard` — SaaS KPI dashboard (MRR, ARR, subscriptions)
- `GET /platform/hotels` — Tenant directory
- `GET /platform/hotels/create` — Create new hotel
- `POST /platform/hotels` — Store hotel (provisions all tables + sends welcome email)
- `GET /platform/hotels/{id}/edit` — Edit hotel
- `PUT /platform/hotels/{id}` — Update hotel
- `POST /platform/hotels/{id}/suspend` — Suspend tenant
- `POST /platform/hotels/{id}/activate` — Reactivate tenant
- `DELETE /platform/hotels/{id}` — Hard delete (must be suspended first)
- `POST /platform/hotels/{id}/users` — Add user to hotel
- `POST /platform/hotels/{id}/send-welcome` — Send onboarding email manually
- `GET /platform/hotels/{id}/view-in-crm` — Enter hotel's CRM as SA
- `GET /platform/users` — All users across tenants
- `GET /platform/plans` — Manage subscription plans
- `GET /platform/activity-log` — Platform-wide activity log
- `GET /platform/settings/2fa` — Platform admin 2FA setup (TOTP)

### Platform Features Implemented
- **SaaS Dashboard** — MRR/ARR cards (per-hotel effective pricing), Active/Suspended counts, Tenant Directory table with Plan + Subscription + Status + Rooms + Bookings + Users columns
- **Hotel Management** — Create/edit/suspend/activate/delete hotels; custom billing cycle (monthly/yearly); custom per-hotel pricing with CUSTOM badge; falls back to plan defaults when custom price is 0/null
- **Subscription Pricing** — `billing_cycle`, `custom_monthly_price`, `custom_yearly_price` on hotels table; dashboard and hotels index both show effective price
- **MRR Calculation** — Per-hotel: `custom_monthly_price > 0 ? custom : plan_default`; yearly hotels contribute `yearly/12` to MRR; banner breakdown shows actual per-plan MRR contribution
- **Platform 2FA (TOTP)** — Microsoft Authenticator / Google Authenticator; QR setup at `/platform/settings/2fa`; TOTP secret encrypted with `Crypt::encryptString`; recovery codes (hashed); login gated to 2FA verify step; `Crypt::decryptString` wrapped in try/catch for resilience
- **Hotel Delete** — Requires `suspended` status; hard deletes all related data in dependency order
- **Add User to Hotel** — Via `POST /platform/hotels/{id}/users`; sets as hotel admin if checked
- **Onboarding Email** — Auto-sent on hotel creation + manual "Send Email" button on hotels list; beautiful HTML template with login credentials, login URL (`https://resort.dreamstechnology.in/login`), quick-start guide, Dreams Technology branding

### Hotel Index — columns
HOTEL | PLAN | SUBSCRIPTION | STATUS | ROOMS | BOOKINGS | USERS | CREATED | ACTIONS
(Revenue column intentionally removed)

## Test Credentials
| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | Super@#3385 | Platform Super Admin |
| admin@resort.com | admin123 | Hotel Admin (Default Hotel) |
| admin2@hotel.com | admin123 | Hotel Admin (Beach Resort) |

## Hotel CRM Login
- URL: `/login` — Generic "Hotel CRM / Staff Portal" heading (not hotel-specific)
- After login → looks up `hotel_users` → 1 hotel = auto-select; multiple = `/select-hotel` picker
- Platform admin entering hotel CRM: sets `crm_sa_hotel_filter` in session (not `crm_hotel_id`)

## Key Hotel CRM Routes
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
- `/install` — Web installer (locked after first run)

## Multi-Hotel (SaaS) Architecture
- **`hotels`** table: id, name, slug, status, plan, billing_cycle, custom_monthly_price, custom_yearly_price, max_rooms, max_users, address, phone, email, admin_notes
- **`hotel_users`** pivot: links users to hotels with per-hotel role + is_hotel_admin flag
- **`HotelContext`** singleton (`app(HotelContext::class)`): set by `SetHotelContext` middleware
- **`BelongsToHotel`** trait: applied to all 16 data models — auto-adds `HotelScope` + fills `hotel_id` on `creating`
- **Room uniqueness**: Composite unique `(room_number, hotel_id)` — same room number can exist across hotels
- **`SetHotelContext` middleware**: Reads `crm_hotel_id` first; if SA, reads `crm_sa_hotel_filter`; registered in web group (after session starts)

## Subscription Plans
Stored in `platform_plans` table (DB-driven). Fallback to `config/plans.php`.
- Plans have: slug, name, label, monthly_price, yearly_price, badge_bg, badge_text, max_rooms, max_users, sort_order, is_active
- Per-hotel custom prices override plan defaults when `> 0`

## Models
- `Customer` — Hotel guests (+ `signature`, `id_document_path`)
- `CustomerDocument` — Guest ID documents
- `Room` — Rooms (+ meal options, extra bed; unique per hotel)
- `Booking` — Reservations (+ meal plan, extra beds, special_requests for OTA ref)
- `BookingGuest` — Additional guests (+ signature, id_document_path)
- `Payment` — Payment records
- `Invoice` — Invoices
- `Setting` — App-wide settings (logo, tagline, GST%, currency, check-in/out times)
- `Role` / `Permission` — RBAC
- `ActivityLog` — Audit trail
- `Module` — Feature flags
- `WhatsAppConfig` / `WhatsAppTemplate`
- `PathikConfig`
- `ChannelManagerConfig` / `ChannelRoomMapping` / `ChannelBooking`
- `HotelUser` — Pivot model

## Services
- `App\Services\HotelContext` — Singleton; `setHotel(int)` / `getHotel()` / `isSet()` / `clear()`
- `App\Services\PermissionService` — Super Admin bypasses all; others checked against `crm_permissions` session
- `App\Services\ActivityLogger` — Writes to `activity_logs` with `hotel_id`; silently ignores failures

## Mail / Email
- **Driver**: SMTP via `mail.dreamstechnology.in:465` (smtps / SSL)
- **From**: `support@dreamstechnology.in` / "Hotel CRM"
- **MAIL_PASSWORD**: stored as Replit secret
- **Mailables**:
  - `App\Mail\HotelWelcomeMail` — Onboarding email with hotel name, login URL, credentials, plan, quick-start guide
  - `App\Mail\PasswordResetMail` — Password reset link (60-min expiry)
- **Templates**: `resources/views/emails/hotel-welcome.blade.php`, `resources/views/emails/password-reset.blade.php`
- **Login URL in welcome email**: `https://resort.dreamstechnology.in/login`

## Modules (feature flags)
| Slug | Feature |
|------|---------|
| `whatsapp` | WhatsApp messaging |
| `payment_links` | Payment link generation |
| `pathik` | Pathik portal autofill |
| `channel_manager` | OTA Channel Manager |

## RBAC
- Middleware: `permission:slug` — blocks unauthorized routes
- Blade: `@canDo('slug') ... @endCanDo`
- Super Admin always returns `true` for all permission checks
- Permissions loaded on login → stored in `session('crm_permissions')`

## WhatsApp
- Uses `wa.me` deep links (no API key required)
- 6 trigger templates: Booking Confirmation, Check-In Details, Payment Reminder, Check-Out Reminder, Welcome, Custom
- Config stored in `whatsapp_configs` table

## Pathik Module
- API token: 32-char random, stored in `pathik_configs`
- Data pushed to Cache (60 min TTL), fetched by Chrome Extension via API token
- Chrome Extension in `public/pathik-extension/` (MV3)

## OTA Channel Manager
- Providers: eZee Centrix (live API), STAAH (live API), SiteMinder, RateGain (manual)
- OTA bookings create Customer + Booking with `OTA-XXXXXX` booking number

## Installer
- Visit `/install` — 3-step wizard (DB credentials → App config → Run migrations + seeds)
- Locked by `storage/installed.lock` after first run
- **Files**: `InstallerController.php`, `CheckNotInstalled.php`, `resources/views/installer/index.blade.php`

## Signature Canvas Pattern
- Container: `touch-action: none`; canvas sized to container on open
- `_ciReady` flag — listeners attached only once; 150ms init delay
- Blank canvas check before save (`isCanvasBlank()` via ImageData)
- Primary guest: `POST /guests/{id}/signature`
- Booking guest: `POST /bookings/{bookingId}/guests/{guestId}/signature`

## Setup (Dev — Replit)
1. `composer install`
2. Copy `.env.example` → `.env` → `php artisan key:generate`
3. `touch database/database.sqlite`
4. `php artisan migrate --force`
5. `php artisan db:seed --class=RolesAndPermissionsSeeder`
6. `php artisan db:seed --class=ModuleSeeder`
7. `php artisan db:seed --class=SettingSeeder`
8. `php artisan storage:link`
9. `php artisan serve --host=0.0.0.0 --port=5000`

## SaaS Task Status
| Task | Status | Description |
|------|--------|-------------|
| #1 Multi-Hotel Core | ✅ COMPLETE | BelongsToHotel on 16 models, hotel_users pivot, hotel picker, HotelContext middleware |
| #2 Platform Admin Console | ✅ COMPLETE | Purple sidebar layout, login, dashboard, hotel CRUD, user management, plans |
| #3 SaaS Dashboard KPIs | ✅ COMPLETE | MRR/ARR cards, tenant directory, plan breakdown, effective custom pricing |
| #4 Custom Pricing + Billing | ✅ COMPLETE | billing_cycle (monthly/yearly), custom_monthly/yearly_price per hotel, CUSTOM badge |
| #5 Hotel Delete | ✅ COMPLETE | Hard delete (suspended only), dependency order, transaction |
| #6 Add User to Hotel | ✅ COMPLETE | SA can create user from hotel edit page; links hotel_users correctly |
| #7 Platform 2FA (TOTP) | ✅ COMPLETE | TOTP setup/verify/disable, encrypted secret, recovery codes, rate limiting |
| #8 Room Uniqueness Fix | ✅ COMPLETE | Composite unique (room_number, hotel_id) instead of global unique |
| #9 User Link Bug Fix | ✅ COMPLETE | SA hotel filter session used for hotel_users linking; orphaned users repaired |
| #10 Login Page Generic | ✅ COMPLETE | Removed hotel-specific name; shows "Hotel CRM / Staff Portal" for all hotels |
| #11 Onboarding Email | ✅ COMPLETE | Welcome email on hotel creation + manual Send Email button from hotels list |
| #12 SMTP Configuration | ✅ COMPLETE | mail.dreamstechnology.in:465 (smtps), support@dreamstechnology.in |
| #13 Subscription Column | ✅ COMPLETE | Hotels index shows effective subscription price with billing cycle + CUSTOM badge |
| #14 Revenue Column Removed | ✅ COMPLETE | Revenue column removed from platform hotels index (not relevant at SaaS level) |
| #15 AI Smart CRM | PENDING | OpenAI-powered insights, plan-gated (depends on stable platform) |

## Known Bugs Fixed
- `hasPages()`/`links()` called on Collection (not paginator) in users index — removed
- `crm_hotel_id` null when SA creates user via hotel filter — fixed to also check `crm_sa_hotel_filter`
- `Crypt::decryptString` could throw 500 on corrupted TOTP secret — wrapped in try/catch
- MRR used `??` null coalescing which failed when custom price = 0 (not null) — fixed to `> 0` check
- `MAIL_SCHEME=ssl` not valid in Laravel 12 for port 465 — changed to `smtps`
