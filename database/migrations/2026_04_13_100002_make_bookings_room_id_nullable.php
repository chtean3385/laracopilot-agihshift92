<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow NULL room_id — needed for conflict bookings (website widget + OTA email parser)
        // where a booking is accepted without an assigned room (pending_room_assignment status).
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'room_id')) {
                // Drop the existing FK + NOT NULL constraint, re-add as nullable FK
                $table->unsignedBigInteger('room_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Note: reverting nullable to NOT NULL can only be done safely
        // if no NULL values exist at that point. This is intentionally left
        // as a no-op since reverting is not safely automatable.
    }
};
