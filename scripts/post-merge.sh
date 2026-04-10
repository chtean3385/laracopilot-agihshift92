#!/bin/bash
set -e

# ============================================================
# post-merge.sh — Runs automatically after every task merge
# and as the production build command.
#
# WHAT THIS SCRIPT DOES:
#   1. Installs/updates PHP dependencies (composer install)
#   2. Runs `app:safe-migrate` which:
#      a. Applies pending schema migrations (new tables/columns only)
#      b. Seeds the Platform Super Admin if users table is empty
#      c. Seeds platform_plans if empty
#      d. Seeds the global permissions catalog if empty
#      e. Provisions roles, modules, WhatsApp templates for all hotels (idempotent)
#      f. Seeds global WhatsApp templates (hotel_id = null) for the shared Meta number
#      g. Seeds platform_whatsapp_settings from WA_* env vars if table is empty
#      h. Seeds platform_firebase_settings from FIREBASE_*/FCM_* env vars if table is empty
#   3. Clears compiled views and config cache
#
# WHAT THIS SCRIPT NEVER DOES:
#   - Does NOT insert, update, or delete: guests, bookings, check-ins,
#     invoices, payments, rooms, activity logs, or any hotel transactional data.
#   - Does NOT run migrate:fresh or drop tables in production.
#   - Does NOT overwrite platform settings that already exist in the database.
# ============================================================

# Install PHP dependencies
composer install --no-interaction --no-progress --prefer-dist

# Run safe migrations + seed platform data
php artisan app:safe-migrate

# Clear compiled views and config cache
php artisan view:clear
php artisan config:clear
