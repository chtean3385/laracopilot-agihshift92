<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelIds = DB::table('hotels')->pluck('id');

        foreach ($hotelIds as $hotelId) {
            // Rename existing time-slot-pricing module to reflect narrowed scope
            DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'time-slot-pricing')
                ->update([
                    'name'        => 'Time Slot Pricing',
                    'description' => 'Enable fixed time-block (per-slot) room pricing and booking.',
                    'updated_at'  => now(),
                ]);

            // Seed hourly-pricing module (disabled by default)
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'hourly-pricing')
                ->exists();

            if (!$exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'hourly-pricing',
                    'name'        => 'Hourly Pricing',
                    'description' => 'Enable per-hour room pricing and booking.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'hourly-pricing')->delete();
        DB::table('modules')->where('slug', 'time-slot-pricing')->update([
            'name'        => 'Time Slot & Hourly Pricing',
            'description' => 'Enable time-slot and hourly room pricing modes.',
            'updated_at'  => now(),
        ]);
    }
};
