# Azure Paradise Resort CRM — Laravel 12

## Overview
A full hotel/resort management CRM built with Laravel 12, SQLite, and Tailwind CSS. Features guest management, room management, bookings, check-in/check-out, payments, invoices, reports, activity logging, dynamic role-based access control, and WhatsApp messaging.

## Architecture
- **Framework**: Laravel 12
- **Language**: PHP 8.2
- **Database**: SQLite (`database/database.sqlite`)
- **Frontend**: Blade templates + Tailwind CSS (CDN) + Font Awesome + Livewire 4
- **Authentication**: Custom session-based auth (hardcoded users)
- **RBAC**: Dynamic DB-driven roles & permissions system

## Setup
1. `composer install`
2. Copy `.env.example` to `.env` and run `php artisan key:generate`
3. `touch database/database.sqlite`
4. `php artisan migrate --force`
5. `php artisan db:seed --class=RolesAndPermissionsSeeder`
6. `php artisan storage:link`
7. `php artisan serve --host=0.0.0.0 --port=5000`

## Users (DB-driven)
Users are now stored in the `users` table and can be managed via `/users`. Super Admin is still hardcoded as a bypass.
| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | Super@#3385 | Super Admin (hardcoded bypass) |
| admin@resort.com | admin123 | Admin |
| manager@resort.com | manager123 | Manager |
| receptionist@resort.com | recept123 | Receptionist |

Seed users with: `php artisan db:seed --class=UsersTableSeeder`

## Email Configuration
Currently set to `MAIL_MAILER=log` — password reset emails are written to `storage/logs/laravel.log` instead of being sent.
To enable real email sending, provide SMTP credentials and update `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com   # or smtp.gmail.com / smtp.mailtrap.io
MAIL_PORT=465
MAIL_ENCRYPTION=tls
MAIL_USERNAME=resend        # or your SMTP username
MAIL_PASSWORD=your_api_key  # store as Replit secret MAIL_PASSWORD
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Resort CRM"
```
Resend.com integration is available in Replit but was not connected. Re-connect it to get an API key automatically.

## Key Routes
- `/login` — Login page (dynamic branding from settings)
- `/dashboard` — Main dashboard
- `/customers` — Guest management + WhatsApp messaging
- `/rooms` — Room management
- `/bookings` — Booking management
- `/checkin`, `/checkout` — Check-in/out
- `/payments` — Payment management
- `/invoices` — Invoice management
- `/reports` — Reports (Manager+ only)
- `/settings` — Settings (Admin+ only)
- `/activity-log` — Activity audit log (permission-gated)
- `/roles` — Roles & Permissions manager (Super Admin + permitted)

## Meal Options Feature
Each room can offer optional breakfast, lunch, and/or dinner, each with a price-per-night. When creating a booking, available meals are shown as checkboxes; selected meals are added to the booking total. Meal cost is stored in `bookings.meal_cost` (plus `meal_breakfast`, `meal_lunch`, `meal_dinner` booleans). Meals are displayed on booking show, invoice show, and invoice print as a separate line item.

Room model fields: `has_breakfast`, `breakfast_price`, `has_lunch`, `lunch_price`, `has_dinner`, `dinner_price`
Booking model fields: `meal_breakfast`, `meal_lunch`, `meal_dinner`, `meal_cost`

## Models
- `Customer` — Hotel guests
- `CustomerDocument` — Guest ID documents
- `Room` — Hotel rooms (+ meal option fields)
- `Booking` — Room reservations (+ meal plan fields)
- `Payment` — Payment records
- `Invoice` — Generated invoices
- `Setting` — App-wide settings (logo, tagline, GST, etc.)
- `Role` — Roles (Admin, Manager, Receptionist + custom)
- `Permission` — Permission slugs (25 permissions across 9 modules)
- `ActivityLog` — Audit trail of all CRM actions

## Services
- `App\Services\PermissionService` — Static `check($slug)`: Super Admin bypasses all, others checked against `crm_permissions` session array
- `App\Services\ActivityLogger` — Static `log($action, $module, $description)`: writes to `activity_logs` table; silently ignores failures

## RBAC
- Permission middleware: `permission:slug` — blocks routes for unauthorized roles
- Blade directive: `@canDo('slug') ... @endCanDo` — hides UI elements
- Super Admin always returns `true` for all permission checks
- Permissions loaded from DB on login, stored in `session('crm_permissions')`
- Role permission changes take effect on the user's next login

## WhatsApp Integration
- Customer list: green WhatsApp icon next to phone number → opens `wa.me/{phone}` in new tab
- Customer detail: "WhatsApp" button → modal with 4 message templates (Booking Reminder, Check-In Details, Payment Reminder, Check-Out Reminder) + custom → builds `wa.me` URL with encoded message
- No API key needed — uses WhatsApp Web deep links

## Branding / Settings
- Resort logo stored at `storage/app/public/logos/`
- Accessed via `asset('storage/' . $settings->logo)`
- Settings globally shared via `AppServiceProvider::View::share('settings', ...)`
- Logo + tagline appear: sidebar, login screen, invoice view, invoice print
- `trustProxies(at: '*')` in `bootstrap/app.php` — trusts Replit's HTTPS proxy headers so generated URLs use correct scheme without forcing HTTPS globally

## Workflow
"Start application" runs `php artisan serve --host=0.0.0.0 --port=5000`
