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
                ->where('slug', 'extra-billing')
                ->exists();

            if (!$exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'extra-billing',
                    'name'        => 'Extra Billing',
                    'description' => 'Add post-booking charges (food, laundry, services, etc.) to occupied or confirmed bookings and reflect them on the final bill.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'extra-billing')->delete();
    }
};
