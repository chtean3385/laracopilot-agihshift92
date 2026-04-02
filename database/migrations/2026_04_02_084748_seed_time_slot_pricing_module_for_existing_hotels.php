<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelIds = DB::table('hotels')->pluck('id');

        foreach ($hotelIds as $hotelId) {
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'time-slot-pricing')
                ->exists();

            if (!$exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'time-slot-pricing',
                    'name'        => 'Time Slot & Hourly Pricing',
                    'description' => 'Enable time-slot and hourly room pricing modes.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'time-slot-pricing')->delete();
    }
};
