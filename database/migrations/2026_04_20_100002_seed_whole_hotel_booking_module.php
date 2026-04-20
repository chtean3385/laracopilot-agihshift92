<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelIds = DB::table('hotels')->pluck('id');

        foreach ($hotelIds as $hotelId) {
            if (!DB::table('modules')->where('hotel_id', $hotelId)->where('slug', 'whole-hotel-booking')->exists()) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'whole-hotel-booking',
                    'name'        => 'Whole Hotel Booking',
                    'description' => 'Allow booking the entire hotel at once — all rooms are blocked and the calendar shows a whole-hotel banner.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'whole-hotel-booking')->delete();
    }
};
