# Hotel CRM

A multi-tenant SaaS CRM for hotel management, featuring guest management, bookings, payments, OTA integrations, and a platform administration console.

## Run & Operate

**Run:** `php artisan serve --host=0.0.0.0 --port=5000`
**Build:** `composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan cache:clear && php artisan optimize && php artisan app:safe-migrate`
**Typecheck:** _Populate as you build_
**Codegen:** _Populate as you build_
**DB Push:** `php artisan migrate --force`

**Required Environment Variables (Production):**
- **Database:** `DB_HOST` (or `PGHOST`), `DB_DATABASE` (or `PGDATABASE`), `DB_USERNAME` (or `PGUSER`), `DB_PASSWORD` (or `PGPASSWORD`), `DB_SSLMODE` (`require`)
- **Mail:** `MAIL_PASSWORD` (for SMTP)
- **WhatsApp Platform:** `WA_SAAS_TOKEN`, `WA_SAAS_PHONE_NUMBER_ID`, `WA_SAAS_WABA_ID`, `WA_META_APP_ID`, `WA_META_APP_SECRET`, `WA_META_CONFIG_ID`, `WA_WEBHOOK_VERIFY_TOKEN`
- **Firebase Platform:** `FIREBASE_PROJECT_ID`, `FIREBASE_API_KEY`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`, `FIREBASE_VAPID_KEY`, `FCM_SERVER_KEY`, `FIREBASE_SERVICE_ACCOUNT_JSON`

## Stack

- **Framework:** Laravel 12 (PHP 8.2)
- **Frontend:** Blade, Tailwind CSS (CDN), Font Awesome, Livewire 4
- **Database:** PostgreSQL (Replit managed)
- **ORM:** Eloquent
- **Validation:** Laravel's built-in validation
- **Build Tool:** Composer

## Where things live

- **Application Logic:** `app/`
- **Database Migrations & Seeds:** `database/migrations/`, `database/seeders/`
- **Views:** `resources/views/`
- **API Routes:** `routes/api.php`
- **Web Routes:** `routes/web.php`
- **Console Commands:** `app/Console/Commands/`
- **DB Schema Source-of-Truth:** `database/migrations/`
- **API Contracts:** `routes/api.php`
- **Theme Files:** Inline styles in Blade templates, `public/css/font-awesome.min.css`

## Architecture decisions

- **Multi-tenancy:** Implemented using a `hotels` table, `hotel_users` pivot, `HotelContext` singleton, and `BelongsToHotel` trait on all 16 core data models for automatic query scoping.
- **Platform Admin (SaaS Console):** Separate authentication flow and routes (`/platform/`) with `Super Admin` role, allowing management of hotels, users, plans, and global SaaS features.
- **Dynamic RBAC:** Roles and permissions are database-driven per hotel, managed via UI, and loaded into session for checks.
- **Flexible Pricing & Trials:** Subscription plans are DB-driven with per-hotel custom pricing, billing cycles, and trial management features.
- **OTA Integration:** Automated booking synchronization via WhatsApp and email parsing, and direct APIs for channel managers.

## Product

- **Guest Management:** Comprehensive profiles, ID document uploads, digital signatures.
- **Booking & Room Management:** Flexible booking creation, room inventory, check-in/out processes.
- **Payments & Invoicing:** Record payments, generate invoices, payment link generation.
- **Reporting & Analytics:** Hotel-specific reports and a SaaS-level analytics dashboard for platform admins.
- **Communication:** WhatsApp messaging automation (templates, shared number, billing) and Firebase push notifications.
- **OTA Channel Manager:** Integrations with various OTAs (eZee Centrix, STAAH, SiteMinder, RateGain) for booking and inventory sync.
- **Live Dashboard:** Real-time activity feed, KPI auto-refresh, and customizable widgets.
- **Web Installer:** Guided setup for initial database and application configuration.
- **Public Pricing Page:** An ad-grade landing page with pricing plans, feature highlights, and an enquiry form.

## User preferences

- **Communication:** I prefer clear, concise language. Please avoid jargon where possible.
- **Development Workflow:** I prefer an iterative approach, with regular updates and opportunities for feedback.
- **Coding Style:** I appreciate well-structured, readable code.
- **Interaction:** Please ask for clarification if anything is unclear before making significant changes.
- **File Management:** Do not modify the `published_updates.md` file; it is for logging publish history.

## Gotchas

- **Deployment `publicDir`:** Never set `publicDir` in `.replit`'s `[deployment]` block; it causes silent deployment failures.
- **`provisionHotel()` Permissions:** This function in `SafeMigrate.php` must ONLY assign permissions to NEWLY CREATED roles to avoid overriding manual configurations.
- **JS in Blade:** Use `{!! json_encode($var) !!}` for raw output to prevent double-encoding.
- **Nested Forms:** HTML does not support nested forms; sub-forms must be placed outside the main `<form>` tag.
- **CSS Responsive Grids:** Avoid inline `grid-template-columns` styles; use classes and `<style>` blocks for responsive behavior.
- **Dormant `food-menu` module:** Do not add new features to this module; extend the `Restaurant` module instead.

## Pointers

- **Laravel Documentation:** [https://laravel.com/docs/12.x](https://laravel.com/docs/12.x)
- **Livewire Documentation:** [https://livewire.laravel.com/docs/quickstart](https://livewire.laravel.com/docs/quickstart)
- **Tailwind CSS Documentation:** [https://tailwindcss.com/docs](https://tailwindcss.com/docs)
- **Replit Documentation:** [https://docs.replit.com/](https://docs.replit.com/)
- **PostgreSQL Documentation:** [https://www.postgresql.org/docs/](https://www.postgresql.org/docs/)