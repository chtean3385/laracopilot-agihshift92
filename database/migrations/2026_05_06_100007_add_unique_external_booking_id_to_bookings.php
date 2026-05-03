<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings') || !Schema::hasColumn('bookings', 'external_booking_id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        $name   = 'bookings_hotel_external_booking_unique';

        try {
            if ($driver === 'pgsql') {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS {$name} ON bookings (hotel_id, external_booking_id) WHERE external_booking_id IS NOT NULL");
            } elseif ($driver === 'sqlite') {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS {$name} ON bookings (hotel_id, external_booking_id) WHERE external_booking_id IS NOT NULL");
            } elseif ($driver === 'mysql') {
                // MySQL allows multiple NULLs in a UNIQUE index, so a plain composite unique
                // gives the same effective behavior (NULLs aren't deduplicated).
                $exists = DB::select(
                    "SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='bookings' AND index_name=?",
                    [$name]
                );
                if (empty($exists)) {
                    DB::statement("CREATE UNIQUE INDEX {$name} ON bookings (hotel_id, external_booking_id)");
                }
            }
        } catch (\Throwable $e) {
            // Existing duplicates would block index creation; surface in logs but don't fail deploy.
            \Illuminate\Support\Facades\Log::warning('Could not create unique index on bookings(external_booking_id): ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        $name   = 'bookings_hotel_external_booking_unique';
        try {
            if ($driver === 'mysql') {
                DB::statement("DROP INDEX {$name} ON bookings");
            } else {
                DB::statement("DROP INDEX IF EXISTS {$name}");
            }
        } catch (\Throwable $e) {
        }
    }
};
