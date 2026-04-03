# Hotel CRM — Laravel 12 Multi-Tenant SaaS

## Overview
Full hotel/resort management CRM built on Laravel 12, fully evolved into a multi-tenant SaaS platform. Features: guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register (signatures + ID docs), Pathik autofill, OTA Channel Manager, Payment Links, web-based installer, Per-Hotel Backup & Restore, and a Platform Admin SaaS console.

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
- `POST /platform/hotels/{id}/activate-trial` — Set hotel to trial plan (N days)
- `POST /platform/hotels/{id}/cancel-trial` — Clear trial, revert to selected plan
- `POST /platform/hotels/{id}/extend-plan` — Add N days to plan_expires_at
- `POST /platform/hotels/{id}/cancel-plan-expiry` — Clear plan_expires_at (no expiry)
- `GET /platform/users` — All users across tenants
- `GET /platform/plans` — Manage subscription plans
- `GET /platform/activity-log` — Platform-wide activity log
- `GET /platform/settings/2fa` — Platform admin 2FA setup (TOTP)
- `POST /platform/hotels/{id}/start-trial` — Start a trial period for a hotel
- `POST /platform/hotels/{id}/extend-expiry` — Extend plan expiry date for a hotel
- `POST /platform/hotels/{id}/cancel-trial` — Cancel an active trial (reverts to no trial)
- `POST /platform/hotels/{id}/cancel-expiry` — Remove plan expiry date from a hotel

### Platform Features Implemented
- **SaaS Dashboard** — MRR/ARR cards (per-hotel effective pricing), Active/Suspended counts, Tenant Directory table with Plan + Subscription + Status + Expiry + Rooms + Bookings + Users columns
- **Hotel Management** — Create/edit/suspend/activate/delete hotels; custom billing cycle (monthly/yearly); custom per-hotel pricing with CUSTOM badge; falls back to plan defaults when custom price is 0/null
- **Multi-hotel user linking** — Creating a hotel with an existing user's email links them (no duplicate account); password is optional in that case
- **Subscription Pricing** — `billing_cycle`, `custom_monthly_price`, `custom_yearly_price` on hotels table; dashboard and hotels index both show effective price
- **Trial Management** — Activate trial (N days), cancel trial (revert plan), extend plan expiry, cancel plan expiry — all as standalone forms outside the main edit form
- **MRR Calculation** — Per-hotel: `custom_monthly_price > 0 ? custom : plan_default`; yearly hotels contribute `yearly/12` to MRR; banner breakdown shows actual per-plan MRR contribution
- **Platform 2FA (TOTP)** — Microsoft Authenticator / Google Authenticator; QR setup at `/platform/settings/2fa`; TOTP secret encrypted with `Crypt::encryptString`; recovery codes (hashed); login gated to 2FA verify step; `Crypt::decryptString` wrapped in try/catch for resilience
- **Hotel Delete** — Requires `suspended` status; hard deletes all related data in dependency order
- **Add User to Hotel** — Via `POST /platform/hotels/{id}/users`; sets as hotel admin if checked
- **Onboarding Email** — Auto-sent on hotel creation + manual "Send Email" button on hotels list; beautiful HTML template with login credentials, login URL (`https://resort.dreamstechnology.in/login`), quick-start guide, Dreams Technology branding

### Hotel Index — columns
HOTEL | PLAN | SUBSCRIPTION | STATUS | EXPIRY | ROOMS | BOOKINGS | USERS | CREATED | ACTIONS
(Revenue column intentionally removed; Expiry column shows trial end date or plan expiry date when set)

---

## How to Use the System

### Adding a New Hotel (from Platform Admin)
1. Log in at `/platform/login` with Super Admin credentials.
2. Complete TOTP verification at `/platform/2fa` if 2FA is enabled.
3. Go to **Hotels** in the sidebar → click **"Add Hotel"** (top-right).
4. Fill in: Hotel Name, Slug (auto-generated), Plan, Billing Cycle, custom pricing (optional), Max Rooms, Max Users, contact details, and admin notes.
5. Click **"Create Hotel"**. The system provisions all required database rows and automatically sends a welcome email to the hotel's email address with login credentials and the login URL.
6. The new hotel now appears in the Hotels index with its plan, subscription price, and status (active).

### Logging In as Hotel Staff (Hotel CRM Login)
1. Visit `/login`.
2. Enter your email and password.
3. If your account belongs to **one hotel**, you are taken directly to the hotel dashboard.
4. If your account belongs to **multiple hotels**, you are redirected to `/select-hotel` — the multi-hotel picker.

### Multi-Hotel User Picker (`/select-hotel`)
- Shown automatically when a user is linked to more than one hotel.
- Lists all hotels the user has access to with their role in each.
- Click a hotel card to select it — sets `crm_hotel_id` and `crm_hotel_name` in session.
- To switch hotels later, click **"Switch Hotel"** (available in the sidebar/nav) which clears the hotel session and redirects back to the picker.

### Switching Hotels (Hotel Staff)
- Click **"Switch Hotel"** in the navigation bar.
- This clears the active hotel session (`crm_hotel_id`) and returns you to `/select-hotel`.
- Select the desired hotel to resume working in it.

### Trial Management (Platform Admin)
1. From **Hotels** index, click **"Edit"** on the target hotel.
2. Scroll to the **"Trial Management"** card (outside the main form).
3. To **start a trial**: Enter the number of trial days → click **"Start Trial"**. This sets `trial_ends_at` on the hotel and shows a trial badge in the index.
4. To **cancel an active trial**: Click **"Cancel Trial"** — clears `trial_ends_at`.

### Extending / Cancelling Plan Expiry (Platform Admin)
1. From **Hotels** index, click **"Edit"** on the target hotel.
2. Scroll to the **"Plan Expiry"** card (outside the main form).
3. To **set/extend expiry**: Pick a date in the date picker → click **"Set Expiry"**. This sets `plan_expires_at` on the hotel, shown in the EXPIRY column of the index.
4. To **remove expiry**: Click **"Cancel Expiry"** — clears `plan_expires_at` so the plan never expires.

### Entering a Hotel CRM as Platform Admin (View-in-CRM)
1. Go to **Hotels** index (`/platform/hotels`).
2. Find the hotel and click **"Enter CRM"** (or use the action menu → "View in CRM").
3. This calls `GET /platform/hotels/{id}/view-in-crm` which sets `crm_sa_hotel_filter` in session (not `crm_hotel_id`) and redirects to `/dashboard`.
4. You now operate inside that hotel's CRM with Super Admin privileges — all data is scoped to that hotel via `SetHotelContext` middleware reading `crm_sa_hotel_filter`.
5. To exit, click **"Exit Hotel View"** in the CRM nav — clears `crm_sa_hotel_filter` and returns to the platform dashboard.

### Platform Dashboard (`/platform/dashboard`)
- Shows **MRR** (Monthly Recurring Revenue) and **ARR** (Annual Recurring Revenue) computed from all active hotels.
- Shows **Active** and **Suspended** hotel counts.
- Lists all tenants with Plan, Subscription price, Status, Rooms, Bookings, and Users counts.
- MRR calculation: monthly hotels contribute their effective monthly price; yearly hotels contribute `yearly_price / 12`.

### Adding a User to a Hotel (Platform Admin)
1. Go to **Hotels** index → click **"Edit"** on the hotel.
2. Scroll to the **"Add User to Hotel"** section.
3. Enter the user's email, name, and password (or select an existing user by email).
4. Optionally check **"Set as Hotel Admin"**.
5. Click **"Add User"** — creates the user (if new) and links them to the hotel via `hotel_users`.

### Suspend / Activate / Delete a Hotel
- **Suspend**: Hotels index → Actions → **"Suspend"**. Hotel status becomes `suspended`; staff cannot log in.
- **Activate**: Hotels index → Actions → **"Activate"**. Restores access for hotel staff.
- **Delete**: Hotel must be **suspended first**. Then Hotels index → Actions → **"Delete"**. This hard-deletes the hotel and all related data in dependency order (bookings, guests, payments, invoices, rooms, users, settings, etc.). This is **irreversible**.

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
| #17 Activity Logging | ✅ COMPLETE | ActivityLogger 3-arg signature, all CRM actions logged with hotel_id |
| #18 Guest Soft Delete | ✅ COMPLETE | Guest soft-delete + platform restore; null-safe guards on all booking/invoice/payment views |
| #19 Trial Enforcement | ✅ COMPLETE | 7-day trial, plan lock overlay, upgrade request page; CheckTrialStatus middleware |
| #20 Hindi Onboarding Tour | ✅ COMPLETE | 11-step JS/CSS tour, per-user localStorage, resolveVisible() for permission-gated steps |
| #21 replit.md How-To Guide | ✅ COMPLETE | Comprehensive usage guide added to this file |
| #15 AI Smart CRM | PENDING | OpenAI-powered insights, plan-gated (depends on stable platform) |
| #16 View-in-CRM (SA) | ✅ COMPLETE | SA can enter any hotel CRM via `/platform/hotels/{id}/view-in-crm`; sets `crm_sa_hotel_filter` |
| #17 Trial Management | ✅ COMPLETE | Start trial, cancel trial routes; trial_ends_at column on hotels; trial badge on hotel list |
| #18 Plan Expiry Management | ✅ COMPLETE | Extend expiry, cancel expiry routes; plan_expires_at column; expiry badge on hotel list |
| #19 Expiry Column in Hotel Index | ✅ COMPLETE | New EXPIRY column in hotels index showing trial or plan expiry dates with badges |
| #20 Trial/Expiry Forms (nested fix) | ✅ COMPLETE | Fixed nested `<form>` issue in hotel edit page; trial and expiry forms outside main form |

## Known Bugs Fixed
- `hasPages()`/`links()` called on Collection (not paginator) in users index — removed
- `crm_hotel_id` null when SA creates user via hotel filter — fixed to also check `crm_sa_hotel_filter`
- `Crypt::decryptString` could throw 500 on corrupted TOTP secret — wrapped in try/catch
- MRR used `??` null coalescing which failed when custom price = 0 (not null) — fixed to `> 0` check
- `MAIL_SCHEME=ssl` not valid in Laravel 12 for port 465 — changed to `smtps`
<<<<<<< HEAD
- Soft-deleted guest null crash on Booking/Invoice/Payment — `->withTrashed()` on `customer()` relationship + `?->` guards in all views
- Platform dashboard KPI grid not responsive — inline `style` overrode media queries; moved to class-based `<style>` block; breakpoints 600px (2-col) / 960px (4-col)
- "Activate Trial" / "Extend Plan" buttons did nothing — trial forms were nested inside main edit `<form>` (HTML ignores nested forms); moved outside as standalone card
- Create Hotel blocked existing user emails — changed from `unique:users,email` to smart link: if email exists, user is linked to new hotel without creating duplicate account
=======
- Nested `<form>` in hotel edit page caused trial/expiry forms to submit incorrectly — moved trial and expiry forms outside the main hotel edit `<form>` tag
- Cancel trial and cancel expiry buttons were missing routes — added `POST /platform/hotels/{id}/cancel-trial` and `POST /platform/hotels/{id}/cancel-expiry` routes and controller methods
>>>>>>> 525bdb2 (docs: Update replit.md with complete how-to guide and task status (Task #21))
