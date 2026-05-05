# Hotel CRM — Laravel 12 Multi-Tenant SaaS

## ⚠️ DORMANT MODULE — `food-menu` (Task #111)

The standalone **Food Menu** module (controllers `FoodMenuAdminController`,
`FoodOrderController`, `FoodMenuPublicController`; tables `food_categories`,
`food_items`, `food_orders`, `food_order_items`; permissions `food_menu.*`) is
**dormant**. All scan-to-order functionality lives inside the **Restaurant**
module instead:

- **Admin print/manage QR codes:** `/restaurant/menu/qr` → table + room QR
  codes, downloadable as SVG/PNG, printable A4 PDF.
- **Guest scan-to-order public URLs:**
  - `/r/{slug}` — pick room or table at the start
  - `/r/{slug}/room/{room}` — in-room QR (auto-fills room, bills to room)
  - `/r/{slug}/table/{name}` — table QR (auto-fills table)
- **Order approval:** guest orders land with `source='guest_qr'` and
  `approval_status='pending'`. Staff approve/reject from the Restaurant
  Tables page banner or order detail page.
- Restaurant menu items now also support **image uploads** (stored under
  `storage/app/public/restaurant/{hotel_id}/`).

The food-menu code, routes, sidebar entry, dashboard banner, ModuleSeeder row,
HotelController provisioning row and `food_menu.*` permissions are all
**commented out** (not deleted) so the module can be re-enabled later. Do not
add new features to the dormant `food-menu` module — extend the Restaurant
module instead.



## Overview
Full hotel/resort management CRM built on Laravel 12, fully evolved into a multi-tenant SaaS platform. Features: guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register (signatures + ID docs), Pathik autofill, OTA Channel Manager, Payment Links, web-based installer, Per-Hotel Backup & Restore, SaaS Analytics Dashboard with Engagement Campaigns, Firebase Push Notifications (web + Android Flutter WebView), Platform Admin SaaS console, **Customisable Dashboard** (per-user widget show/hide + drag-to-reorder + hotel-wide admin defaults), **Live Dashboard** (Today's Agenda modal on login, Live Activity Feed widget auto-polling every 30s, KPI card auto-refresh every 60s, dual-tab notification bell showing platform notifications + hotel activity feed), **OTA WhatsApp Booking Sync** (auto-detect Booking.com / Airbnb / Agoda / MakeMyTrip / Goibibo booking confirmations arriving via WhatsApp → import queue → one-click confirm creates CRM booking with customer & booking number), and **OTA Email Booking Sync** (hotel admins forward OTA confirmation emails to a per-hotel inbound address → Mailgun inbound-parse webhook → same import queue + `source_channel` badge differentiates WhatsApp vs Email sources).

## ⚠️ DEPLOYMENT RULE — READ BEFORE EVERY PUBLISH

**`publicDir` must NEVER be set in the `[deployment]` block of `.replit`.**

Setting `publicDir = "public"` causes Replit autoscale to treat the app as a static site and skip the `run` command entirely — the web server never starts and the deployment silently fails.

**Correct deployment config (autoscale, no publicDir):**
```toml
[deployment]
deploymentTarget = "autoscale"
run = ["bash", "scripts/start.sh"]
build = ["bash", "-c", "composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan cache:clear && php artisan optimize && php artisan app:safe-migrate"]
```

**If deployment fails with "no run command" or app not starting:**
→ Check that `publicDir` has NOT crept back into `[deployment]`. If it has, call `deployConfig()` with `deploymentTarget="autoscale"`, `run=["bash","scripts/start.sh"]`, and `build=[...]` — with NO `publicDir` argument. Then re-publish.

The `.replit` file cannot be edited directly by agents — only `deployConfig()` can update it.

## ⚠️ PERMISSION SAFETY RULE — NEVER REVERT THIS

**`provisionHotel()` in `SafeMigrate.php` only assigns permissions to NEWLY CREATED roles.**

It must NEVER assign or add permissions to roles that already exist. Hotel admins configure permissions manually via the Roles UI — if the deploy script re-adds "missing" permissions, it silently overrides those manual settings on every publish.

The correct logic: permission assignment is **inside** the `if (!$existing)` block only. When a role already exists, the deploy script skips its permissions entirely.

---

## Architecture
- **Framework**: Laravel 12 (PHP 8.2)
- **Database (dev)**: Replit managed PostgreSQL (`heliumdb` on host `helium:5432`) — credentials set as Replit dev env vars (`DB_HOST=helium`, `DB_DATABASE=heliumdb`, `DB_USERNAME=postgres`, `DB_PASSWORD=password`, `DB_PORT=5432`). The `.env` has `DB_CONNECTION=pgsql`. Run migrations with `php artisan migrate --force`.
- **Database (production)**: Replit managed PostgreSQL for production (host `ep-fancy-scene-anzx2eyx.c...`, database `neondb`) — credentials stored as Replit production secrets (`PGHOST`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `DATABASE_URL`). Build command: `php artisan app:safe-migrate`.
- **bootstrap/app.php**: Copies DB env vars from `getenv()` into `$_ENV` before phpdotenv runs — this bridges Replit's secret injection (process env) into Laravel's `env()` function. List includes: `DB_CONNECTION`, `DATABASE_URL`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE`.
- **config/database.php pgsql**: Falls back to `getenv('PGHOST')`, `getenv('PGDATABASE')` etc. if `DB_HOST`/`DB_DATABASE` env vars are not set — covers production where secrets use PG* naming.
- **Frontend**: Blade templates + Tailwind CSS (CDN) + Font Awesome + Livewire 4
- **Authentication**: Custom session-based auth — two separate auth flows (Hotel CRM + Platform Admin)
- **RBAC**: Dynamic DB-driven roles & permissions per hotel
- **Port**: 5000 (`php artisan serve --host=0.0.0.0 --port=5000`)
- **Mail**: SMTP via `mail.dreamstechnology.in:465` (smtps), from `support@dreamstechnology.in`

---

## How to Use the System

### Test Credentials
| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | Super@#3385 | Platform Super Admin |
| admin@resort.com | admin123 | Hotel Admin (Demo Hotel + Beach Resort) |
| admin2@hotel.com | admin123 | Hotel Admin (Beach Resort) |

---

### 1. Platform Admin — Add a New Hotel

**Login:** Go to `/platform/login` → use `superadmin@gmail.com` / `Super@#3385`

**Steps:**
1. Click **Hotels** in the left sidebar
2. Click **+ New Hotel** (top right)
3. Fill in **Hotel Details**: Name (required), Email, Phone, Address
4. Choose a **Plan** (Basic / Pro / Enterprise), **Billing Cycle** (Monthly/Yearly), and optionally set **Custom Pricing**
5. Optionally set **Trial Days** (e.g. 7 → hotel starts on trial plan, locked after 7 days) or **Plan Valid For Days** (sets expiry date)
6. Fill in **Hotel Admin Account**:
   - **Admin Email**: If this email already exists in the system (user from another hotel), they are automatically linked to the new hotel — **no new account is created, leave password blank**
   - **Admin Password**: Required only for brand-new users; leave blank if the person already has an account
7. Click **Create & Provision Hotel**

One click creates: hotel + settings + 4 modules (WhatsApp, Payment Links, Pathik, Channel Manager) + 3 roles (Admin, Manager, Receptionist) + admin user — all in one database transaction.

---

### 2. Hotel CRM — Staff Login & Multi-Hotel Picker

**Login URL:** `/login`

- Enter email + password
- If account is linked to **1 hotel** → goes directly to that hotel's dashboard
- If account is linked to **2+ hotels** → shows a **hotel picker screen** to choose which hotel to enter
- After entering, the user can switch hotels by logging out and selecting a different one at `/select-hotel`

---

### 3. Add an Existing User to Another Hotel

**Option A — From Create Hotel form:**
- Go to Platform Admin → Hotels → New Hotel
- Enter the existing user's email in the "Admin Email" field
- Leave password blank
- Submit — the user is linked to the new hotel and will see the hotel picker on next login

**Option B — From Edit Hotel page:**
- Go to Platform Admin → Hotels → Edit (any hotel)
- Scroll to **"Add New User to This Hotel"**
- Enter name, email, password (creates new user if email doesn't exist, links if it does), select role
- Click Add User

---

### 4. Trial & Plan Expiry Management (Edit Hotel page)

Go to Platform Admin → Hotels → Edit → scroll past Save Changes button to **Trial & Plan Expiry** card.

| Button | What it does |
|--------|-------------|
| **Activate Trial** | Sets plan to `trial`, sets trial_ends_at = today + N days. Overrides any previous trial. |
| **Cancel Trial** | Clears trial_ends_at, reverts plan to selected plan (e.g. Basic). Only shown when trial is active. |
| **Extend Plan** | Adds N days to plan_expires_at (from today if already expired). |
| **Cancel Plan Expiry** | Clears plan_expires_at (no expiry limit). Only shown when a date is set. |

---

### 5. Enter a Hotel's CRM as Platform Admin

- Go to Platform Admin → Hotels (or Dashboard → Tenant Directory)
- Click **View CRM** next to any hotel
- You are now inside that hotel's CRM with full super-admin access
- The session stores `crm_sa_hotel_filter` (not `crm_hotel_id`) to track which hotel you're viewing
- To exit: click **Back to Platform Admin** in the top-right of the CRM

---

### 6. Suspend / Reactivate / Delete a Hotel

From Platform Admin → Hotels list:
- **Suspend** (red button): Blocks all staff logins immediately; hotel data preserved
- **Activate** (green button): Restores access for suspended hotels
- **Delete** (dark red button): Only available when hotel is suspended; permanently deletes all data — hotel, rooms, bookings, payments, guests, users. **Cannot be undone.**

From Edit Hotel page: same Suspend/Reactivate button is at the bottom right.

---

### 7. Platform Dashboard

URL: `/platform/dashboard`

- **MRR card** — Monthly Recurring Revenue from all active subscriptions (custom pricing applied per hotel)
- **Active Subscriptions** — Count of active hotels
- **Suspended Tenants** — Count + "on trial" badge if any trials are running
- **Next Month Expected** — Projected next-month revenue from active subscriptions
- **Tenant Directory** — Full table of all hotels with plan, status, pricing, expiry, rooms, users, and actions

---

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
13. **Nested forms are invalid HTML**: Sub-forms (trial/suspend/etc) must be placed OUTSIDE the main `<form>` tag or they silently submit the outer form instead
14. **CSS responsive grids**: Never put `grid-template-columns` in inline `style=""` — inline styles override media queries. Use a class + `<style>` block instead.

---

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

**Auth**
- `GET  /platform/login` — Login page
- `POST /platform/login` — Authenticate
- `GET  /platform/login/verify-2fa` — TOTP verification step
- `POST /platform/login/verify-2fa` — Verify TOTP code
- `GET  /platform/settings/2fa` — 2FA setup (QR + enable/disable)
- `POST /platform/settings/2fa/enable` — Enable TOTP 2FA
- `POST /platform/settings/2fa/disable` — Disable TOTP 2FA

**Dashboard**
- `GET  /platform/dashboard` — SaaS KPI dashboard (MRR, ARR, hotel counts)
- `POST /platform/dismiss-reminder` — Dismiss dashboard reminder banner

**Hotels (Tenant Management)**
- `GET    /platform/hotels` — Tenant directory index
- `GET    /platform/hotels/create` — New hotel form
- `POST   /platform/hotels` — Create + provision hotel (sends welcome email)
- `GET    /platform/hotels/{id}/edit` — Edit hotel
- `PUT    /platform/hotels/{id}` — Update hotel
- `POST   /platform/hotels/{id}/suspend` — Suspend tenant
- `POST   /platform/hotels/{id}/activate` — Reactivate tenant
- `DELETE /platform/hotels/{id}` — Hard delete (must be suspended first)
- `POST   /platform/hotels/{id}/users` — Add/link user to hotel
- `POST   /platform/hotels/{id}/send-welcome` — Resend welcome email
- `GET    /platform/hotels/{id}/view-in-crm` — Enter hotel CRM as SA
- `POST   /platform/hotels/{id}/activate-trial` — Start trial (N days)
- `POST   /platform/hotels/{id}/cancel-trial` — Cancel trial, revert plan
- `POST   /platform/hotels/{id}/extend-plan` — Extend plan_expires_at by N days
- `POST   /platform/hotels/{id}/cancel-plan-expiry` — Remove plan expiry
- `POST   /platform/hotels/{id}/add-related` — Link related hotel group
- `POST   /platform/hotels/{id}/send-quick-wa` — Send WhatsApp message to hotel owner
- `POST   /platform/hotels/{id}/send-quick-push` — Send push notification to hotel
- `POST   /platform/hotels/send-wa-all` — Broadcast WhatsApp to all hotel owners
- `POST   /platform/hotels/{id}/module-toggle` — Enable/disable a module for hotel
- `GET    /platform/wa-templates` — Fetch approved WA templates (JSON)

**Plans**
- `GET /platform/plans` — Subscription plan list
- `GET /platform/plans/{id}/edit` — Edit plan
- `PUT /platform/plans/{id}` — Update plan pricing/limits

**Users (Cross-Tenant)**
- `GET  /platform/users` — All users across all hotels
- `GET  /platform/users/{id}` — User detail
- `GET  /platform/users/{id}/reset-password` — Reset password form
- `POST /platform/users/{id}/reset-password` — Set new password
- `POST /platform/users/{id}/hotel/{hotelId}/suspend` — Suspend user in hotel
- `POST /platform/users/{id}/hotel/{hotelId}/activate` — Reactivate user
- `POST /platform/users/{id}/toggle-wa-consent` — Toggle WA messaging consent

**Guests (Soft-Deleted)**
- `GET  /platform/guests/deleted` — View soft-deleted guests across tenants
- `POST /platform/guests/{id}/restore` — Restore soft-deleted guest

**Backups**
- `GET  /platform/backups` — Per-hotel backup list
- `POST /platform/backups/{id}/restore` — Restore a hotel backup

**Analytics & Campaigns**
- `GET  /platform/analytics` — SaaS engagement analytics dashboard
- `GET  /platform/analytics/campaigns` — Campaign history
- `POST /platform/analytics/campaigns` — Send engagement campaign (WA/Push)

**WhatsApp (Platform-Level)**
- `GET  /platform/whatsapp` — WhatsApp shared number settings
- `POST /platform/whatsapp` — Save WhatsApp settings
- `POST /platform/whatsapp/test` — Send test message via shared number
- `GET  /platform/whatsapp/templates` — Message template list
- `POST /platform/whatsapp/templates` — Create template
- `PUT  /platform/whatsapp/templates/{id}` — Edit template
- `DELETE /platform/whatsapp/templates/{id}` — Delete template
- `POST /platform/whatsapp/templates/{id}/toggle` — Enable/disable template
- `POST /platform/whatsapp/templates/{id}/submit-meta` — Submit template to Meta for approval
- `POST /platform/whatsapp/templates/sync-from-meta` — Sync approval status from Meta
- `GET  /platform/whatsapp/logs` — Webhook event log
- `POST /platform/whatsapp/logs/clear` — Clear webhook logs
- `GET  /platform/whatsapp/numbers` — Registered WhatsApp numbers
- `POST /platform/whatsapp/numbers` — Register new number
- `POST /platform/whatsapp/numbers/link` — Link existing number via Business Login
- `POST /platform/whatsapp/numbers/{id}/request-otp` — Request OTP for number
- `POST /platform/whatsapp/numbers/{id}/verify` — Verify OTP
- `POST /platform/whatsapp/numbers/{id}/sync` — Sync number status from Meta
- `DELETE /platform/whatsapp/numbers/{id}` — Remove number
- `POST /platform/wa/upload-media` — Upload WhatsApp media
- `GET  /platform/wa-inbox` — WhatsApp inbox view

**WhatsApp Billing**
- `GET  /platform/whatsapp/billing` — Per-hotel WA usage billing
- `POST /platform/whatsapp/billing/{hotelId}/mark-paid` — Mark hotel WA bill as paid
- `POST /platform/whatsapp/billing/{hotelId}/mark-unpaid` — Mark as unpaid
- `POST /platform/whatsapp/billing/{hotelId}/limit` — Set monthly message limit

**Push Notifications (Firebase)**
- `GET  /platform/notifications/settings` — Firebase/FCM settings
- `POST /platform/notifications/settings` — Save Firebase credentials
- `GET  /platform/notifications/send` — Send push notification form
- `POST /platform/notifications/send` — Broadcast push notification
- `GET  /platform/notifications/history` — Push notification send history

**OTA WhatsApp Sources**
- `GET    /platform/ota-sources` — OTA source patterns list
- `POST   /platform/ota-sources` — Create OTA source pattern
- `PUT    /platform/ota-sources/{id}` — Update pattern
- `DELETE /platform/ota-sources/{id}` — Delete pattern
- `POST   /platform/ota-sources/{id}/toggle` — Enable/disable

### Platform Features Implemented
- **SaaS Dashboard** — MRR/ARR cards (per-hotel effective pricing), Active/Suspended counts, Tenant Directory table (Plan + Subscription + Status + Expiry + Rooms + Bookings + Users)
- **Hotel Management** — Create/edit/suspend/activate/delete; custom billing cycle (monthly/yearly); custom per-hotel pricing with CUSTOM badge; plan defaults fallback
- **Multi-hotel user linking** — Existing user email auto-linked (no duplicate account); password optional
- **Subscription Pricing** — `billing_cycle`, `custom_monthly_price`, `custom_yearly_price` on hotels table
- **Trial Management** — Activate trial (N days), cancel, extend plan expiry, cancel expiry — all standalone forms outside main edit form
- **MRR Calculation** — `custom > 0 ? custom : plan_default`; yearly hotels contribute `yearly/12`; banner breakdown per plan
- **Platform 2FA (TOTP)** — Microsoft/Google Authenticator; QR at `/platform/settings/2fa`; `Crypt::encryptString`; recovery codes (hashed); try/catch on decrypt
- **Hotel Delete** — Requires `suspended` status; hard-deletes all data in dependency order
- **Add User to Hotel** — Create new or link existing; set as hotel admin flag
- **Onboarding Email** — Auto on hotel creation + manual resend; HTML template with credentials, login URL, quick-start guide
- **Per-Hotel Module Toggle** — SA can enable/disable any module for any hotel from platform
- **Quick WhatsApp to Hotel Owner** — Send a templated WA message to any hotel owner directly from hotels list
- **Broadcast WhatsApp** — Send WA campaign to all hotel owners in one action
- **Push Notification to Hotel** — Send Firebase push to a specific hotel's devices
- **WhatsApp Settings** — Manage shared Meta number (token, phone number ID, WABA ID); test send
- **WhatsApp Templates** — Create/edit/delete templates; submit to Meta for approval; sync status from Meta; auto-version on edit (name suffix `_v2`, `_v3`…); enable/disable toggle
- **WhatsApp Numbers** — Register/link multiple WhatsApp numbers; OTP verify; sync status; remove
- **WhatsApp Webhook Logs** — View + clear incoming webhook event log
- **WhatsApp Billing** — Per-hotel message usage tracker; mark paid/unpaid; set monthly message limits
- **WA Inbox** — Platform-level WhatsApp message inbox view
- **Analytics & Campaigns** — Engagement analytics dashboard; campaign send (WA + Push) to segmented hotel lists; campaign history
- **Per-Hotel Backups** — Backup list per hotel; one-click restore
- **Push Notification Centre** — Firebase/FCM settings; broadcast push to all or specific hotels; send history
- **OTA WhatsApp Sources** — Manage OTA source patterns (Booking.com, Airbnb, Agoda, etc.) for OTA WA Booking Sync; enable/disable patterns
- **Guest Restore** — View soft-deleted guests across all tenants; restore from platform
- **Cross-Tenant User Management** — View all users; reset passwords; suspend/activate per hotel; WA consent toggle
- **Related Hotel Groups** — Link hotels together for group management
- **View-in-CRM** — SA enters any hotel's CRM via `crm_sa_hotel_filter` session; full SA privilege inside that hotel

### Hotel Index — columns
HOTEL | PLAN | SUBSCRIPTION | STATUS | EXPIRY | ROOMS | BOOKINGS | USERS | CREATED | ACTIONS
(Expiry column shows trial end date or plan expiry date when set. Revenue column removed.)

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
- **`hotels`** table: id, name, slug, status, plan, billing_cycle, custom_monthly_price, custom_yearly_price, max_rooms, max_users, trial_ends_at, plan_expires_at, address, phone, email, admin_notes
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
- `App\Services\ActivityLogger` — Writes to `activity_logs` with `hotel_id`; silently ignores failures; signature: `log(action, module, description)`

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

## Public Pricing Page (`/pricing`)

### Overview
A full ad-grade public landing page at `/pricing` — designed for Google / Meta ads traffic. Bypasses all auth middleware (`SetHotelContext`, `CheckTrialStatus`). Enquiry form sends email via SMTP.

### Files
| File | Purpose |
|------|---------|
| `app/Http/Controllers/PublicPricingController.php` | `index()` (renders page) + `enquire()` (sends email) |
| `resources/views/pricing.blade.php` | Full landing page — inline styles only |
| `app/Mail/PricingEnquiryMail.php` | Mailable — uses `$enquiryMessage` (NOT `$message`, which is Laravel reserved) |
| `resources/views/emails/pricing-enquiry.blade.php` | Email template |
| `public/hotel-crm-logo.png` | Logo used in nav + hero |

### Routes (in `routes/web.php`, outside auth middleware group)
```
GET  /pricing         → PublicPricingController@index
POST /pricing/enquire → PublicPricingController@enquire   (named: pricing.enquire)
```
CSRF exception added in `bootstrap/app.php` → `validateCsrfTokens(except: ['pricing/enquire'])`.

### Page Sections (top → bottom)
1. **Sticky nav** — logo + WhatsApp CTA button (+91 97252 25519)
2. **Hero** (dark navy) — "DREAM HOTEL MANAGEMENT" headline, "Are you a Hotel Owner?" tagline in amber, Cloud Based / Secure / Access Anywhere pills, CSS dashboard mockup (live stats: check-in 12, check-out 08, bookings 25, ₹2.45L revenue, room occupancy bars, recent bookings with status badges)
3. **Feature strip** (white) — Increase Bookings / Save Staff Time / Reduce Errors / Grow Your Business
4. **Plans grid** (4 columns) — data from `platform_plans` DB table; each card has coloured header, MRP strikethrough, actual price + "20% OFF" badge, include-label banner, feature list, room/user limits, extra module pricing footer
5. **Modules grid** — 11 real add-on modules from CRM, + "& More" dashed card
6. **Benefits** — 4 cards (Increase Bookings, Save Staff Time, Reduce Errors, Grow)
7. **Enquiry form** — plan selection highlight, AJAX POST to `/pricing/enquire`, sends email to `chetanmakwana3385@gmail.com`
8. **Footer CTA** — "LET'S GROW TOGETHER! 97252 25519" + WhatsApp button

### Pricing Display Logic
- **MRP** = `ceil(yearly_price × 1.2 / 100) × 100` — shown with strikethrough
- **Actual price** = real DB value — shown large with green "20% OFF" badge
- Example: Basic ₹5,999 → MRP ~~₹7,200~~, **₹5,999** + 20% OFF badge

### Extra Module Pricing (per plan)
| Plan | Extra Module Price | Notes |
|------|--------------------|-------|
| Basic | ₹3,000 / module | |
| Standard | ₹2,000 / module | |
| Premium | ₹1,000 / module | |
| Pro AI | All included | `all_modules_included = true` → shows "ALL INCLUDED" in green |

### Add-On Modules Shown (11 real modules)
WhatsApp Automation, Payment Links, Pathik Autofill, OTA Channel Manager, Time Slot & Hourly Pricing, Extra Billing, Restaurant Management, Booking Widget, Whole Hotel Booking, Slot Search Engine, OTA WhatsApp Sync

### Theme
- **Light** — white nav, white/light-gray body sections (#f1f5f9 / #fff / #f8fafc)
- **Hero stays dark navy** — creates strong visual contrast; dashboard mockup looks authentic
- Inline styles only (no Tailwind dynamic classes)
- Font Awesome from `/css/font-awesome.min.css`

### SEO (added to `<head>`)
- `<title>` — "Hotel Management Software India | Dream Hotel CRM — Plans from ₹5,999/Year"
- `<meta description>` — keyword-rich, mentions free demo
- `<meta keywords>` — 13 targeted terms (hotel management software India, hotel CRM, OTA channel manager, hotel software Gujarat, etc.)
- `<meta robots>` — `index, follow, max-snippet:-1, max-image-preview:large`
- `<link rel="canonical">` → `https://resort.dreamstechnology.in/pricing`
- Geo tags: `geo.region=IN`, `geo.country=India`
- **Open Graph** — og:title, og:description, og:image, og:type, og:locale `en_IN` (WhatsApp / Facebook / LinkedIn link previews)
- **Twitter Card** — `summary_large_image`
- **Schema.org JSON-LD (2 blocks):**
  - `SoftwareApplication` with 4 `Offer` nodes (price INR, priceValidUntil = current year end)
  - `FAQPage` with 4 Q&As (plan features, modules, demo, OTA support) — Google may show as expandable FAQ in search results
- **Note:** All `@` signs in JSON-LD escaped as `@@` (Blade treats `@type`, `@context` as directives otherwise)
- **Tracking placeholders** — HTML comments ready to uncomment: Meta Pixel block + Google Tag (GA4/Ads) block — replace `YOUR_PIXEL_ID` / `YOUR_GTAG_ID` when ready

### Enquiry Email
- **To**: `chetanmakwana3385@gmail.com`
- **SMTP**: `mail.dreamstechnology.in:465` (smtps), from `support@dreamstechnology.in`
- **Fields captured**: name, hotel name, phone, plan slug + label + price, rooms, optional message
- **Variable name**: `$enquiryMessage` in `PricingEnquiryMail` — NOT `$message` (Laravel reserves that variable in Mailable)

---

## SaaS Task Status
| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 1 | Multi-Hotel Core | ✅ | BelongsToHotel on 16 models, hotel_users pivot, hotel picker, HotelContext middleware |
| 2 | Platform Admin Console | ✅ | Purple sidebar, login, dashboard, hotel CRUD, user management, plans |
| 3 | SaaS Dashboard KPIs | ✅ | MRR/ARR cards, tenant directory, plan breakdown, effective custom pricing |
| 4 | Custom Pricing + Billing | ✅ | billing_cycle (monthly/yearly), custom_monthly/yearly_price, CUSTOM badge |
| 5 | Hotel Delete | ✅ | Hard delete (suspended only), dependency order, transaction |
| 6 | Add User to Hotel | ✅ | SA creates/links user from hotel edit page; hotel_users correctly |
| 7 | Platform 2FA (TOTP) | ✅ | QR setup, encrypted secret, recovery codes, rate limiting, try/catch on decrypt |
| 8 | Room Uniqueness | ✅ | Composite unique (room_number, hotel_id) — same room number across hotels |
| 9 | User Link Bug Fix | ✅ | SA hotel filter session used for hotel_users linking; orphaned users repaired |
| 10 | Login Page Generic | ✅ | "Hotel CRM / Staff Portal" heading — no hotel-specific name |
| 11 | Onboarding Email | ✅ | Auto on hotel creation + manual resend; HTML template with credentials + login URL |
| 12 | SMTP Configuration | ✅ | mail.dreamstechnology.in:465 (smtps), support@dreamstechnology.in |
| 13 | Subscription Column | ✅ | Hotels index shows effective price with billing cycle + CUSTOM badge |
| 14 | Revenue Column Removed | ✅ | Not relevant at SaaS platform level |
| 15 | Activity Logging | ✅ | ActivityLogger 3-arg signature, all CRM actions logged with hotel_id |
| 16 | Guest Soft Delete | ✅ | Soft-delete + platform restore; null-safe guards on all booking/invoice/payment views |
| 17 | Trial Enforcement | ✅ | 7-day trial, plan lock overlay, upgrade request page; CheckTrialStatus middleware |
| 18 | Trial Management (Platform) | ✅ | Activate trial, cancel trial, extend plan expiry, cancel expiry — standalone forms |
| 19 | View-in-CRM (SA) | ✅ | SA enters any hotel via `crm_sa_hotel_filter`; exit returns to platform |
| 20 | Expiry Column in Hotels Index | ✅ | EXPIRY column shows trial end or plan expiry dates with colour badges |
| 21 | Hindi Onboarding Tour | ✅ | 11-step JS/CSS tour, per-user localStorage, resolveVisible() for permission-gated steps |
| 22 | RBAC & Permissions | ✅ | Dynamic DB-driven roles/permissions per hotel; `permission:slug` middleware; `@canDo` |
| 23 | WhatsApp Automation (CRM) | ✅ | 6 trigger templates; wa.me deep links; config per hotel |
| 24 | WhatsApp Platform Integration | ✅ | Shared Meta number; templates with Meta approval flow; auto-versioning; webhook logs |
| 25 | WhatsApp Numbers Management | ✅ | Register/link/verify/sync/remove multiple WA numbers from platform |
| 26 | WhatsApp Billing | ✅ | Per-hotel usage tracking, paid/unpaid status, monthly message limits |
| 27 | Broadcast & Quick WA | ✅ | Quick WA to single hotel owner; broadcast to all hotel owners |
| 28 | WA Inbox | ✅ | Platform-level WhatsApp inbox view |
| 29 | Firebase Push Notifications | ✅ | FCM settings (web + Android Flutter WebView); broadcast push; per-hotel push; history |
| 30 | Analytics & Campaigns | ✅ | SaaS engagement analytics dashboard; WA + Push campaigns with segmentation; history |
| 31 | Per-Hotel Backups | ✅ | Backup list per hotel; one-click restore from platform |
| 32 | Customisable Dashboard | ✅ | Per-user widget show/hide + drag-to-reorder; hotel-wide admin defaults |
| 33 | Live Dashboard | ✅ | Today's Agenda modal on login; Live Activity Feed (30s auto-poll); KPI auto-refresh (60s); dual-tab notification bell |
| 34 | OTA Channel Manager | ✅ | eZee Centrix + STAAH live APIs; SiteMinder + RateGain manual; OTA booking import |
| 35 | OTA WhatsApp Booking Sync | ✅ | Auto-detect Booking.com/Airbnb/Agoda/MMT booking confirmations via WA → import queue → one-click confirm |
| 36 | OTA Source Patterns (Platform) | ✅ | Manage detection patterns per OTA source; enable/disable per source |
| 37 | Payment Links | ✅ | UPI QR + Razorpay link generation per booking |
| 38 | Pathik Autofill | ✅ | Gujarat Pathik portal integration; Chrome Extension (MV3); API token auth |
| 39 | Restaurant Management | ✅ | Tables, categories, menu items, KOT, orders, bills, room billing, reports |
| 40 | Time Slot & Hourly Pricing | ✅ | Slot-based room pricing; available slot search |
| 41 | Guest Register | ✅ | Signature canvas; ID document upload; booking guest signatures |
| 42 | Extra Billing | ✅ | Post-stay extra charges on bookings |
| 43 | Food Billing | ✅ | Restaurant billing linked to hotel room bookings |
| 44 | Booking Widget | ✅ | Widget settings for hotel website booking form |
| 45 | Slot Search Engine | ✅ | Multi-filter availability search |
| 46 | Cross-Tenant User Management | ✅ | View all users; reset passwords; suspend/activate per hotel; WA consent toggle |
| 47 | Guest Restore (Platform) | ✅ | Soft-deleted guest list + restore across all tenants |
| 48 | Related Hotel Groups | ✅ | Link hotels together for group management |
| 49 | Public Pricing Landing Page | ✅ | Ad-grade /pricing page with hero, plans, modules, SEO, enquiry form, JSON-LD schema |
| 50 | AI Smart CRM | 🔲 PENDING | OpenAI-powered insights, plan-gated |

## Known Bugs Fixed
- `hasPages()`/`links()` called on Collection (not paginator) in users index — removed
- `crm_hotel_id` null when SA creates user via hotel filter — fixed to also check `crm_sa_hotel_filter`
- `Crypt::decryptString` could throw 500 on corrupted TOTP secret — wrapped in try/catch
- MRR used `??` null coalescing which failed when custom price = 0 (not null) — fixed to `> 0` check
- `MAIL_SCHEME=ssl` not valid in Laravel 12 for port 465 — changed to `smtps`
- Soft-deleted guest null crash on Booking/Invoice/Payment — `->withTrashed()` on `customer()` relationship + `?->` guards in all views
- Platform dashboard KPI grid not responsive — inline `style` overrode media queries; moved to class-based `<style>` block; breakpoints 600px (2-col) / 960px (4-col)
- "Activate Trial" / "Extend Plan" buttons did nothing — trial forms were nested inside main edit `<form>` (HTML ignores nested forms); moved outside as standalone card
- Create Hotel blocked existing user emails — changed from `unique:users,email` to smart link: if email exists, user is linked to new hotel without creating duplicate account
- Nested `<form>` in hotel edit page caused trial/expiry forms to submit incorrectly — moved trial and expiry forms outside the main hotel edit `<form>` tag
- Cancel trial and cancel expiry buttons were missing routes — added `POST /platform/hotels/{id}/cancel-trial` and `POST /platform/hotels/{id}/cancel-expiry` routes and controller methods
- WhatsApp webhook returning 419 CSRF — fixed via `validateCsrfTokens(except: ['webhook/*'])` in `bootstrap/app.php` (L11 has no `VerifyCsrfToken` class to extend)

---

## Production Deploy Checklist

When deploying to production for the first time (or on a fresh Neon database), `app:safe-migrate` automatically seeds platform settings from Replit environment secrets. Set the following secrets in Replit's **Secrets** panel (production environment) before deploying:

### Database (Neon PostgreSQL)
| Secret | Purpose | Example |
|--------|---------|---------|
| `DB_HOST` or `PGHOST` | Neon host | `ep-fancy-scene-anzx2eyx.c...neon.tech` |
| `DB_DATABASE` or `PGDATABASE` | Database name | `neondb` |
| `DB_USERNAME` or `PGUSER` | DB user | `neondb_owner` |
| `DB_PASSWORD` or `PGPASSWORD` | DB password | _(secret)_ |
| `DB_SSLMODE` | SSL mode | `require` |

### WhatsApp Platform Credentials (`platform_whatsapp_settings`)
| Secret | Column seeded | Purpose |
|--------|--------------|---------|
| `WA_SAAS_TOKEN` | `saas_token` | System User Access Token for the shared CRM WhatsApp number |
| `WA_SAAS_PHONE_NUMBER_ID` | `saas_phone_number_id` | Meta phone number ID (e.g. `123456789012345`) |
| `WA_SAAS_WABA_ID` | `saas_waba_id` | WhatsApp Business Account ID (e.g. `1291891699064537`) |
| `WA_META_APP_ID` | `meta_app_id` | Meta App ID (e.g. `1390491206132028`) |
| `WA_META_APP_SECRET` | `meta_app_secret` | Meta App Secret for HMAC webhook verification |
| `WA_META_CONFIG_ID` | `meta_config_id` | Business Login Configuration ID (for hotel OAuth flow) |
| `WA_WEBHOOK_VERIFY_TOKEN` | `webhook_verify_token` | Webhook verify token (default: `resort-crm-whatsapp-2026`) |

**Note:** These are only seeded when `platform_whatsapp_settings` is empty. To re-seed, delete the row from the table, then re-deploy.

### Firebase Platform Credentials (`platform_firebase_settings`)
| Secret | Column seeded | Purpose |
|--------|--------------|---------|
| `FIREBASE_PROJECT_ID` | `firebase_project_id` | Firebase project ID (e.g. `hotel-crm-e9e34`) |
| `FIREBASE_API_KEY` | `firebase_api_key` | Firebase Web API key |
| `FIREBASE_MESSAGING_SENDER_ID` | `firebase_messaging_sender_id` | FCM sender ID (e.g. `884314304438`) |
| `FIREBASE_APP_ID` | `firebase_app_id` | Firebase App ID |
| `FIREBASE_VAPID_KEY` | `firebase_vapid_key` | VAPID key for web push |
| `FCM_SERVER_KEY` | `fcm_server_key` | Legacy FCM server key (for Android/Flutter WebView) |
| `FIREBASE_SERVICE_ACCOUNT_JSON` | `service_account_json` | Full service account JSON string (for server-side FCM v1 API) |

### Mail
| Secret | Purpose |
|--------|---------|
| `MAIL_PASSWORD` | SMTP password for `support@dreamstechnology.in` |

### What `app:safe-migrate` does (and does NOT do)
- Runs `php artisan migrate --force` — applies only **schema changes** (new tables, new columns, indexes).
- Seeds the `users` table with the Platform Super Admin if it is empty.
- Seeds `platform_plans` if empty.
- Seeds `permissions` catalog if empty.
- Provisions roles, modules, and WhatsApp templates for existing hotels (idempotent).
- Seeds global WhatsApp templates (`hotel_id = null`) for the shared Meta number.
- Seeds `platform_whatsapp_settings` and `platform_firebase_settings` from env vars **if and only if those tables are empty**.
- **NEVER** touches: guests, bookings, check-ins, invoices, payments, rooms, activity logs, or any hotel transactional data.

---

## ⚠️ PRE-PUBLISH VERIFICATION (check this EVERY time before publishing)

### 1. Deployment target
- Must be **`autoscale`** in `.replit` — **not** `vm`. Laravel web apps fail with `vm` target.
- Run command: `["bash", "scripts/start.sh"]` — starts on port 5000.
- Build command: `composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan cache:clear && php artisan optimize && php artisan app:safe-migrate`

### 2. WhatsApp owner templates (Platform → Hotel Owner messaging)
- The 2 platform owner templates (`crm_dashboard_update` / `login_reminder`) are **dynamically resolved from DB** — `platformWaTemplates()` queries the `whatsapp_templates` table for the latest APPROVED version by base name pattern.
- **Before publishing**: Go to Platform Admin → Message Templates. Confirm both `crm_dashboard_update` (or latest `_v{N}`) and `login_reminder` (or latest `_v{N}`) show **Approved** status.
- If body was edited → name auto-versioned → status reset to Pending → must Submit to Meta → wait for Meta approval → Sync from Meta → confirm Approved. Only then publish.
- **Never hardcode** template names in PHP code. All send paths use `platformWaTemplates()` which reads from DB.

### 3. Environment secrets (production only)
All these must be set in Replit Secrets (production environment):
- `DB_PASSWORD` / `PGPASSWORD` — Neon DB password
- `MAIL_PASSWORD` — SMTP password
- `WA_SAAS_TOKEN`, `WA_SAAS_PHONE_NUMBER_ID`, `WA_SAAS_WABA_ID` — Platform WhatsApp
- `WA_META_APP_ID`, `WA_META_APP_SECRET`, `WA_WEBHOOK_VERIFY_TOKEN`
- `FIREBASE_PROJECT_ID`, `FIREBASE_API_KEY`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`, `FIREBASE_VAPID_KEY`, `FCM_SERVER_KEY`, `FIREBASE_SERVICE_ACCOUNT_JSON`

### 4. New migrations
- Check `git diff <last-deploy-commit> HEAD --name-only | grep migration`
- All new migrations must be **ADD COLUMN** or **CREATE TABLE** only — never DROP or ALTER existing columns.
- SafeMigrate guard protects production from `migrate:fresh` — only `migrate --force` runs.

### 5. Quick smoke test after publish
1. Open `https://resort.dreamstechnology.in/platform/login` — login works ✅
2. Open Platform Admin → Dashboard — loads without error ✅
3. Open Platform Admin → Message Templates — templates show correct Approved status ✅
4. Open Platform Admin → Hotels → click WA button on any hotel — modal shows 2 templates, send works ✅
5. Open Hotel CRM → `/login` — hotel staff login works ✅

### 7. After every deploy — verify deployment config is registered (MANDATORY)
After any session that touches `.replit` or deployment settings, call `deployConfig()` to re-register the run/build commands with Replit's deployment service. The `.replit` file alone is not enough — the platform can lose the config between deploys. If the user ever reports "No run command configured" or port errors, call `deployConfig()` immediately without waiting for more details.

### 6. After every publish — log it (MANDATORY — no reminder needed)
Record the checkpoint SHA, date, and what changed in **`published_updates.md`** (root of repo).
This must be the **very last action** of any session that includes a deploy or checkpoint — update the log AFTER the final checkpoint SHA is known, not before.
Keep `replit.md` for architecture/conventions; keep `published_updates.md` for the publish history.
