<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelIds = DB::table('hotels')->pluck('id');

        foreach ($hotelIds as $hotelId) {
            if (!DB::table('modules')->where('hotel_id', $hotelId)->where('slug', 'slot-search-engine')->exists()) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'slot-search-engine',
                    'name'        => 'Slot Search Engine',
                    'description' => 'Full-screen multi-filter search for slot availability across date ranges, slot types, rooms, and booking status.',
                    'is_enabled'  => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'slot-search-engine')->delete();
    }
};
