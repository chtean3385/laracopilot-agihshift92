# Hotel CRM
A multi-tenant SaaS platform for hotel/resort management, offering guest, room, booking, payment, and reporting features for hotel staff and a centralized administration console for platform owners.

## Run & Operate
- **Run:** `php artisan serve --host=0.0.0.0 --port=5000` (development) or `bash scripts/start.sh` (production)
- **Build:** `composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan cache:clear && php artisan optimize && php artisan app:safe-migrate`
- **Typecheck:** _Populate as you build_
- **Codegen:** _Populate as you build_
- **DB Push:** `php artisan app:safe-migrate` (handles migrations and idempotent seeding)

**Required `ENV` variables:**
- **Development (`.env`):** `DB_CONNECTION=pgsql`, `DB_HOST=helium`, `DB_DATABASE=heliumdb`, `DB_USERNAME=postgres`, `DB_PASSWORD=password`, `DB_PORT=5432`
- **Production (Replit Secrets):** `APP_KEY` *(must match .env exactly — keeps sessions alive across deploys)*, `PGHOST`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `DB_SSLMODE`, `MAIL_PASSWORD`, `WA_SAAS_TOKEN`, `WA_SAAS_PHONE_NUMBER_ID`, `WA_SAAS_WABA_ID`, `WA_META_APP_ID`, `WA_META_APP_SECRET`, `WA_WEBHOOK_VERIFY_TOKEN`, `FIREBASE_PROJECT_ID`, `FIREBASE_API_KEY`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`, `FIREBASE_VAPID_KEY`, `FCM_SERVER_KEY`, `FIREBASE_SERVICE_ACCOUNT_JSON`

## Stack
- **Framework:** Laravel 12 (PHP 8.2)
- **Frontend:** Blade templates, Tailwind CSS (CDN), Font Awesome, Livewire 4
- **SPA (Platform WA Inbox):** React 18 + Vite (pnpm) + `@vitejs/plugin-react-swc` — WhatsApp Inbox at `/platform/wa-inbox`; Blade page mounts `<div id="wa-inbox-root">`, built assets served via `public/build/`
- **Database:** PostgreSQL (Replit managed for dev and production)
- **ORM:** Eloquent
- **Validation:** Laravel's built-in validation
- **Build Tool:** Composer (PHP) + pnpm/Vite (JS SPA assets)

## Where things live
- **Application Logic:** `app/`
- **Configuration:** `config/`
- **Database Migrations & Seeds:** `database/migrations/`, `database/seeders/`
- **Views:** `resources/views/`
- **Platform Layout:** `resources/views/layouts/platform.blade.php`
- **Email Templates:** `resources/views/emails/`
- **DB Schema Source-of-Truth:** `database/migrations/`
- **API Contracts:** Defined implicitly by Laravel routes and controllers.
- **Theme Files:** Primarily Tailwind CSS within Blade templates.
- **Service Providers:** `app/Providers/`
- **Middleware:** `app/Http/Middleware/`
- **Installer Files:** `app/Http/Controllers/InstallerController.php`, `app/Http/Middleware/CheckNotInstalled.php`, `resources/views/installer/index.blade.php`
- **WA Inbox SPA Source:** `resources/js/wa-inbox/` (React components + entry point)
- **WA Inbox Built Assets:** `public/build/` (Vite output — committed, served by Laravel)
- **WA Inbox API Controller:** `app/Http/Controllers/Platform/WaInboxApiController.php`
- **Vite Config:** `vite.config.js` (pnpm; entry: `resources/js/wa-inbox/main.jsx`)
- **Scheduler:** `routes/console.php` (Laravel 12 style — `Schedule::command()`/`Schedule::call()`)

## Architecture decisions
- **Multi-tenancy:** Implemented via a `BelongsToHotel` trait on all 16 data models, leveraging a `HotelContext` singleton and `SetHotelContext` middleware to automatically scope queries to the active hotel.
- **Two Auth Systems:** Separate session-based authentication flows for Hotel CRM staff and Platform Administrators, including 2FA for platform admins.
- **Dynamic RBAC:** Role-Based Access Control is database-driven and configurable per hotel, with permissions loaded into the session on login.
- **Idempotent Provisioning:** The `app:safe-migrate` command and hotel creation logic are designed to be idempotent, ensuring that schema changes and initial data seeding (users, roles, modules, settings) do not overwrite manual configurations on existing entities.
- **Configurable Modules:** Key features like WhatsApp, Payment Links, Pathik integration, and Channel Manager are implemented as toggleable modules, managed via feature flags.

## Product
- **Hotel CRM:** Guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register, Pathik autofill, OTA Channel Manager, Payment Links.
- **Platform Admin (SaaS Console):** Centralized management of hotels (tenants), subscription plans, users across tenants, SaaS analytics dashboard (MRR, active subscriptions), trial and plan expiry management, and super admin login with 2FA.
- **Customizable Dashboard:** Per-user widget show/hide and drag-to-reorder, with hotel-wide admin defaults.
- **Multi-Hotel User Accounts:** Users can be linked to multiple hotels and switch between them via a hotel picker.

## User preferences
- **Communication style:** I prefer simple language and clear, actionable instructions.
- **Workflow:** I want iterative development with small, testable changes. Please confirm major changes before implementation.
- **Coding style:** I prefer well-commented, maintainable code following Laravel conventions.
- **Interaction:** Ask before making major changes to existing code or database schemas.
- **Context:** Always consider the multi-tenant architecture and security implications for both hotel staff and platform administrators.
- **MANDATORY — Release log (no reminder will be given):** After every production push, append a new row to the `## Release History` table at the bottom of this file. Format: `| YYYY-MM-DD | vX.Y.Z | \`short-sha\` | What changed |`. Increment the patch version each deploy. Do this as the very last step, after the checkpoint SHA is known.

## Gotchas
- **Deployment `publicDir`:** Never set `publicDir` in `.replit`'s `[deployment]` block, as it breaks the Laravel web server.
- **Permission Safety:** `provisionHotel()` must only assign permissions to *newly created* roles to avoid overwriting manual configurations by hotel admins.
- **Nested Forms:** Sub-forms (e.g., for trial/expiry management) must be placed *outside* the main `<form>` tag to prevent unintended form submissions.
- **JS in Blade:** Use `{!! json_encode($var) !!}` for raw output in JavaScript to prevent double-encoding issues.
- **CSRF Token:** For webhooks, CSRF validation must be explicitly disabled via `validateCsrfTokens(except: ['webhook/*'])` in `bootstrap/app.php`.
- **Responsive Grids:** Avoid inline `grid-template-columns` styles as they override media queries; use classes instead.
- **Livewire 4 — single root element:** A `<style>` tag placed *before* the root `<div>` in a Livewire component silently kills ALL `wire:click` events on the page. Always place `<style>` inside the root element or after it.
- **Eager-loaded relations — collection vs query:** When a relation is already loaded via `with()`, use `$model->relation->sum()` (collection, zero extra queries) not `$model->relation()->sum()` (fires a new DB query). The parentheses version silently duplicates the query.
- **WA Template data-body encoding:** Never wrap `data-body` in Blade's `{{ }}` — use `{!! $var !!}` or a plain PHP `htmlspecialchars()` call. Double-encoding corrupts template variable placeholders like `{{1}}`.

## Pointers
- **Laravel Documentation:** `https://laravel.com/docs/12.x`
- **Livewire Documentation:** `https://livewire.laravel.com/docs/4.x`
- **Tailwind CSS Documentation:** `https://tailwindcss.com/docs`
- **Replit Deployment Guide:** `https://docs.replit.com/hosting/deployments/`
- **PostgreSQL Documentation:** `https://www.postgresql.org/docs/`
- **WhatsApp Business Platform API:** `https://developers.facebook.com/docs/whatsapp/`
- **Firebase Documentation:** `https://firebase.google.com/docs`

# Hotel CRM — Laravel 12 Multi-Tenant SaaS

## Overview
Full hotel/resort management CRM built on Laravel 12, fully evolved into a multi-tenant SaaS platform. Features: guest management, rooms, bookings, check-in/out, payments, invoices, reports, RBAC, activity audit logging, WhatsApp messaging, Guest Register (signatures + ID docs), Pathik autofill, OTA Channel Manager, Payment Links, web-based installer, Per-Hotel Backup & Restore, SaaS Analytics Dashboard with Engagement Campaigns, Firebase Push Notifications (web + Android Flutter WebView), Platform Admin SaaS console, and **Customisable Dashboard** (per-user widget show/hide + drag-to-reorder + hotel-wide admin defaults).

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
15. **Eager-loaded relations — no duplicate queries**: When a relation is loaded via `with()`, use `$model->relation` (collection), not `$model->relation()` (new query builder). The parentheses form fires a redundant DB round-trip.
16. **WA template approval_status**: When saving a WhatsApp template without changing the body, always preserve the existing `approval_status` from the DB — never reset it to `pending`. Only reset `approval_status` to `pending` when the message body itself changes.

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
- `GET /platform/wa-inbox` — WhatsApp Inbox React SPA (full-screen conversation view)
- `GET /platform/api/wa/conversations` — JSON: paginated conversation list
- `GET /platform/api/wa/conversations/{id}` — JSON: single conversation detail
- `GET /platform/api/wa/conversations/{id}/messages` — JSON: messages in conversation
- `POST /platform/api/wa/conversations/{id}/send` — JSON: send text message
- `POST /platform/api/wa/conversations/{id}/send-template` — JSON: send approved template
- `POST /platform/api/wa/conversations/{id}/mark-read` — JSON: mark conversation read
- `GET /platform/api/wa/templates` — JSON: approved templates list
- `GET /platform/api/wa/stats` — JSON: inbox stats (unread count, etc.)
- (5 more JSON API endpoints under `/platform/api/wa/` for bulk blast, assignment, etc.)
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
- **SaaS Dashboard** — MRR/ARR cards (per-hotel effective pricing), Active/Suspended counts, Tenant Directory table with Plan + Subscription + Status + Expiry + Rooms + Users columns
- **Hotel Management** — Create/edit/suspend/activate/delete hotels; custom billing cycle (monthly/yearly); custom per-hotel pricing with CUSTOM badge; falls back to plan defaults when custom price is 0/null
- **Multi-hotel user linking** — Creating a hotel with an existing user's email links them (no duplicate account); password is optional in that case
- **Subscription Pricing** — `billing_cycle`, `custom_monthly_price`, `custom_yearly_price` on hotels table; dashboard and hotels index both show effective price
- **Trial Management** — Activate trial (N days), cancel trial (revert plan), extend plan expiry, cancel plan expiry — all as standalone forms outside the main edit form
- **MRR Calculation** — Per-hotel: `custom_monthly_price > 0 ? custom : plan_default`; yearly hotels contribute `yearly/12` to MRR; banner breakdown shows actual per-plan MRR contribution
- **Platform 2FA (TOTP)** — Microsoft Authenticator / Google Authenticator; QR setup at `/platform/settings/2fa`; TOTP secret encrypted with `Crypt::encryptString`; recovery codes (hashed); login gated to 2FA verify step; `Crypt::decryptString` wrapped in try/catch for resilience
- **Hotel Delete** — Requires `suspended` status; hard deletes all related data in dependency order
- **Add User to Hotel** — Via `POST /platform/hotels/{id}/users`; sets as hotel admin if checked
- **Onboarding Email** — Auto-sent on hotel creation + manual "Send Email" button on hotels list; beautiful HTML template with login credentials, login URL (`https://resort.dreamstechnology.in/login`), quick-start guide, Dreams Technology branding
- **WhatsApp Inbox SPA** — React 18 SPA at `/platform/wa-inbox`; full conversation view with message threading; send free-text or approved templates; bulk-blast up to 200 contacts; 14 JSON API endpoints via `WaInboxApiController`; built with Vite + `@vitejs/plugin-react-swc` (pnpm); Blade wrapper mounts `<div id="wa-inbox-root">`

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

## Release History

| Date | Version | Checkpoint | What Changed |
|------|---------|------------|--------------|
| 2026-05-05 | v1.0.0 | `9cce1eb8` | Client proposal PDF + PPTX export |
| 2026-05-05 | v1.0.1 | `5d8538a4` | Logo persists across deploys (base64 in DB); GST invoice print fixes; Compact invoice style; Mark Available bug fix |
| 2026-05-05 | v1.0.2 | `43128628` | Deployment run command re-registered with Replit platform |
| 2026-05-05 | v1.0.3 | `c2eff7fe` | Room type Non-AC fix — was silently rejected by validation on save |
| 2026-05-06 | v1.0.4 | `0cd8824c` | Release history added to replit.md; deploy logging procedure enforced |
| 2026-05-06 | v1.0.5 | `af39272d` | Invoice edit back-calculates pre-tax room tariff so GST bill line items are consistent with edited total; deployment run command registered |
| 2026-05-07 | v1.0.6 | `72c1dd7b` | WA Booking Confirmed template updated with hotel location and contact number variables |
| 2026-05-07 | v1.0.7 | `8bf29d1c` | WA template submission validation fixed — only blocks variable at very first/last line of template body |
| 2026-05-07 | v1.0.8 | `e93facf9` | Pricing page hero: CSS mockup replaced with real screenshot image slider (auto-play, arrows, dots) |
| 2026-05-07 | v1.0.9 | `34bbfe89` | Modules page changed from cards to list rows with enable/disable actions |
| 2026-05-07 | v1.0.10 | `ea1b9bf3` | Slot Search shortcut only shows when slot-search-engine module is enabled |
| 2026-05-07 | v1.0.11 | `cb04b7dc` | Removed Check In/Out, Reports, and Time & Slot shortcuts; Slot Search shortcut gated by module |
| 2026-05-07 | v1.0.12 | `7388eee` | GST-inclusive booking pricing updates, WhatsApp total fix, analytics JS crash guard, and whole-hotel custom price visibility fix |
| 2026-05-08 | v1.0.13 | `415aa96` | Performance: WhatsApp sends queued (async), 8 composite DB indexes, dashboard 7-query revenue loop → 1 GROUP BY, queue worker in start.sh |
| 2026-05-14 | v1.0.14 | `f0e4231` | GST-inclusive pricing shown throughout app: booking create price summary (live JS), room dropdown, room card, rooms search, booking detail Rate/Night |
| 2026-05-20 | v1.0.15 | `f9df66d` | Fix IMAP SSL SNI error (novalidate-cert); add Simulate Email modal to email parser config page |
| 2026-05-20 | v1.0.16 | `85abc64` | Email parser: "Create Booking" option in Simulate Email; booking_id regex matches "Ref:" format; non-OTA emails marked "skipped" not "failed"; scheduler note corrected |
| 2026-05-21 | v1.0.17 | `f9f57e0` | Fix deploy failure: remove schedule:work from start.sh (caused Cloud Run health-check timeout); fix SyntaxError: Unexpected token '&' — replace {{ json_encode() }} with {!! json_encode() !!} in admin.blade.php toast scripts, ota-bookings edit button, and whatsapp/templates 3× edit buttons |
| 2026-05-21 | v1.0.18 | `ff9e35e` | Add cron webhook endpoint GET /webhook/cron/emails-sync?token=SECRET — allows external cron service (cron-job.org) to trigger email sync every 5 min in production |
| 2026-05-21 | v1.0.19 | `b9469c7` | Green OTA banner auto-clears when user visits Bookings page (mark-as-seen via session timestamp); banner reappears on next new import |
| 2026-05-21 | v1.0.20 | `2b38ac0` | Fix CRON_SECRET not readable in production — add to bootstrap/app.php env-bridging list so env('CRON_SECRET') works after config:cache |
| 2026-05-22 | v1.0.21 | `2b38ac0` | Per-role dashboard & sidebar customization: Chef (restaurant-only) sees only Restaurant KPIs, shortcuts, and sidebar items; Admin/Manager retain full access; `isRestaurantOnly()` + `hasAny()` helpers in PermissionService |
| 2026-05-27 | v1.0.22 | `a579557` | Hindi language toggle (EN/हिं pill button in header) via Google Translate; widget div moved to body, https script URL, targeted CSS suppression |
| 2026-05-27 | v1.0.23 | `6b088ca` | WA Inbox: fix "Type: unsupported" — webhook now stores human-friendly labels (📷 Image, 🎵 Voice, ⚠️ Unsupported, etc.) for all media/reaction types; chat bubble renders them with coloured icons |
| 2026-05-27 | v1.0.24 | `pending` | WA Inbox: + Bulk Blast button — paste numbers (one per line), select approved template, fill variables, send to up to 200 contacts; results shown per number; sent messages appear in inbox. Bot blocked for 917043069225 via migration. |
| 2026-05-30 | v1.0.25 | `pending` | QR Self-Service Check-In & Check-Out: public `/g/checkin/{slug}` 6-step wizard (EN/HI, signature, ID upload), `/g/checkout/{token}` guest bill view with UPI QR; admin QR Arrivals queue (`/qr-arrivals`) with assign/cancel; sidebar QR Arrivals link with live pending badge; Guest Checkout QR modal on checkout show page; migrations for `guest_checkin_requests` table and checkout token fields on bookings. |
| 2026-05-30 | v1.0.26 | `pending` | QR Arrivals UX: inline quick-assign modal on index page (no navigation needed — view guest details, signature, ID doc, assign room & dates, recalculate total, cancel all in one modal); blue pulsing dashboard banner for pending QR requests (gated by checkin.process permission); Firebase push notification fired to all hotel staff devices on new QR check-in submission. |
| 2026-05-30 | v1.0.27 | `pending` | Per-role dashboard & sidebar: sidebar "Restaurant" section label added; Customize bar hidden for restaurant-only users; QR code-review fixes — base64 data-req (XSS), restaurant charges in checkout bill, full auto-fill fields in lookup, checkout_token auto-gen in Booking::boot, booking-show checkout QR modal, success-view reference number (QR-XXXXX). |
| 2026-05-30 | v1.0.28 | `pending` | WA Inbox React SPA at `/platform/wa-inbox`: React 18 + Vite (pnpm + @vitejs/plugin-react-swc); 14 JSON API routes via `WaInboxApiController`; conversation list, threaded message view, free-text & template send, bulk blast (up to 200 contacts); Blade page mounts `<div id="wa-inbox-root">`. |
| 2026-05-30 | v1.0.29 | `pending` | WA Templates: fix double-encoding bug on `data-body` ({{ }} → {!! !!}); `templateSave` now preserves DB `approval_status` when body is unchanged (prevents false pending resets); header image upload field added to Create Template modal. |
| 2026-06-01 | v1.0.30 | `8604237` | Perf: DB keep-alive scheduler (`SELECT 1` every 4 min via `Schedule::call()`) prevents Neon cold-start; Dashboard: 4 Room `COUNT` queries → 1 `GROUP BY`; 2 Payment `SUM` queries → 1 conditional `SUM(CASE WHEN)`; 3 `DashboardPreference` queries → 1; CheckOut: 3 `relation()` new-query calls → `relation` collection access (no extra queries). |
| 2026-06-04 | v1.0.31 | `74e4ae0` | WA chatbot: intercept "Book Demo"/"Call Me Back" button clicks before state machine (prevents corrupt mid-flow step answers); "Hi"/"Hello"/"Hey"/"Demo" now always restart the flow even mid-flow; scheduler added to `scripts/start.sh` so DB keep-alive ping runs on production too. |
| 2026-06-04 | v1.0.32 | `b81476f` | Perf: nginx + PHP-FPM replaces `php artisan serve` (parallel requests, gzip, static file bypass); CACHE_STORE switched to `file` (no extra DB round-trip per cache hit); dashboard skips DB queries for hidden widgets (arrivals-departures, kpi-row-1, recent-room-pair, revenue-trend, slot-availability, booking-calendar); preferences loaded first so guards are active before any query runs. |
| 2026-06-04 | v1.0.33 | `pending` | Fix: PHP-FPM pool upload limits — upload_max_filesize 2M→20M, post_max_size 8M→25M, memory_limit 128M→256M (fixes "file failed to upload" on document upload). |
