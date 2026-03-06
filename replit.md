# Hotel Management System - Laravel 12

## Overview
A hotel management system built with Laravel 12 (PHP), SQLite, and Tailwind CSS. Features include customer management, room management, bookings, check-in/check-out, payments, invoices, and reports.

## Architecture
- **Framework**: Laravel 12
- **Language**: PHP 8.2
- **Database**: SQLite (stored at `database/database.sqlite`)
- **Frontend**: Blade templates + Tailwind CSS (via Vite)
- **Authentication**: Custom session-based auth

## Setup
1. `composer install` - Install PHP dependencies
2. Copy `.env.example` to `.env` and generate key: `php artisan key:generate`
3. Create SQLite file: `touch database/database.sqlite`
4. Run migrations: `php artisan migrate --force`
5. Seed data: `php artisan db:seed --force`
6. Build assets: `npm install && npm run build`
7. Start server: `php artisan serve --host=0.0.0.0 --port=5000`

## Development
- **Workflow**: "Start application" runs `php artisan serve --host=0.0.0.0 --port=5000` on port 5000
- **Assets**: Vite builds CSS/JS into `public/build/`

## Key Routes
- `/` - Redirects to dashboard
- `/login` - Login page
- `/dashboard` - Main dashboard
- `/customers` - Customer management
- `/rooms` - Room management
- `/bookings` - Booking management
- `/checkin`, `/checkout` - Check-in/out management
- `/payments` - Payment management
- `/invoices` - Invoice management
- `/reports` - Reports
- `/settings` - Settings

## Models
- `Customer` - Hotel guests
- `CustomerDocument` - Guest ID documents
- `Room` - Hotel rooms
- `Booking` - Room reservations
- `Payment` - Payment records
- `Invoice` - Generated invoices
- `Setting` - App-wide settings
