<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // All indexes use CREATE INDEX IF NOT EXISTS (PostgreSQL) so re-running
        // the migration on an already-indexed database is safe.
        $indexes = [
            // bookings — composite indexes covering the most common query patterns
            "CREATE INDEX IF NOT EXISTS bookings_hotel_status_idx       ON bookings (hotel_id, status)",
            "CREATE INDEX IF NOT EXISTS bookings_hotel_checkin_idx      ON bookings (hotel_id, check_in_date)",
            "CREATE INDEX IF NOT EXISTS bookings_hotel_checkout_idx     ON bookings (hotel_id, check_out_date)",
            "CREATE INDEX IF NOT EXISTS bookings_hotel_created_idx      ON bookings (hotel_id, created_at)",
            "CREATE INDEX IF NOT EXISTS bookings_customer_idx           ON bookings (customer_id)",
            "CREATE INDEX IF NOT EXISTS bookings_hotel_status_checkin   ON bookings (hotel_id, status, check_in_date)",

            // payments — weekly revenue GROUP BY query and dashboard sum filters
            "CREATE INDEX IF NOT EXISTS payments_hotel_status_created   ON payments (hotel_id, status, created_at)",
            "CREATE INDEX IF NOT EXISTS payments_status_idx             ON payments (status)",
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Log but don't halt — the app works without indexes, just slower
                \Illuminate\Support\Facades\Log::warning("Index migration warning: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $drops = [
            "DROP INDEX IF EXISTS bookings_hotel_status_idx",
            "DROP INDEX IF EXISTS bookings_hotel_checkin_idx",
            "DROP INDEX IF EXISTS bookings_hotel_checkout_idx",
            "DROP INDEX IF EXISTS bookings_hotel_created_idx",
            "DROP INDEX IF EXISTS bookings_customer_idx",
            "DROP INDEX IF EXISTS bookings_hotel_status_checkin",
            "DROP INDEX IF EXISTS payments_hotel_status_created",
            "DROP INDEX IF EXISTS payments_status_idx",
        ];

        foreach ($drops as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) {}
        }
    }
};
