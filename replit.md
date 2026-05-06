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
- **Production (Replit Secrets):** `PGHOST`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `DB_SSLMODE`, `MAIL_PASSWORD`, `WA_SAAS_TOKEN`, `WA_SAAS_PHONE_NUMBER_ID`, `WA_SAAS_WABA_ID`, `WA_META_APP_ID`, `WA_META_APP_SECRET`, `WA_WEBHOOK_VERIFY_TOKEN`, `FIREBASE_PROJECT_ID`, `FIREBASE_API_KEY`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`, `FIREBASE_VAPID_KEY`, `FCM_SERVER_KEY`, `FIREBASE_SERVICE_ACCOUNT_JSON`

## Stack
- **Framework:** Laravel 12 (PHP 8.2)
- **Frontend:** Blade templates, Tailwind CSS (CDN), Font Awesome, Livewire 4
- **Database:** PostgreSQL (Replit managed for dev and production)
- **ORM:** Eloquent
- **Validation:** Laravel's built-in validation
- **Build Tool:** Composer

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

## Pointers
- **Laravel Documentation:** `https://laravel.com/docs/12.x`
- **Livewire Documentation:** `https://livewire.laravel.com/docs/4.x`
- **Tailwind CSS Documentation:** `https://tailwindcss.com/docs`
- **Replit Deployment Guide:** `https://docs.replit.com/hosting/deployments/`
- **PostgreSQL Documentation:** `https://www.postgresql.org/docs/`
- **WhatsApp Business Platform API:** `https://developers.facebook.com/docs/whatsapp/`
- **Firebase Documentation:** `https://firebase.google.com/docs`

## Release History

| Date | Version | Checkpoint | What Changed |
|------|---------|------------|--------------|
| 2026-05-05 | v1.0.0 | `9cce1eb8` | Client proposal PDF + PPTX export |
| 2026-05-05 | v1.0.1 | `5d8538a4` | Logo persists across deploys (base64 in DB); GST invoice print fixes; Compact invoice style; Mark Available bug fix |
| 2026-05-05 | v1.0.2 | `43128628` | Deployment run command re-registered with Replit platform |
| 2026-05-05 | v1.0.3 | `c2eff7fe` | Room type Non-AC fix — was silently rejected by validation on save |
| 2026-05-06 | v1.0.4 | `0cd8824c` | Release history added to replit.md; deploy logging procedure enforced |