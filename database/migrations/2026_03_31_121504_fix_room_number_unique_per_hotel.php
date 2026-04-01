<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the global unique constraint on room_number (allows same number across hotels)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS rooms_room_number_unique');
        } else {
            DB::statement('DROP INDEX IF EXISTS "rooms_room_number_unique"');
        }

        // Add composite unique: room_number must be unique within each hotel
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['room_number', 'hotel_id'], 'rooms_room_number_hotel_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS rooms_room_number_hotel_unique');
        } else {
            DB::statement('DROP INDEX IF EXISTS "rooms_room_number_hotel_unique"');
        }

        Schema::table('rooms', function (Blueprint $table) {
            $table->unique('room_number');
        });
    }
};
