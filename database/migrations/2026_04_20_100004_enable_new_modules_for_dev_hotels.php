<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['whole-hotel-booking', 'slot-search-engine'] as $slug) {
            DB::table('modules')
                ->whereIn('hotel_id', [1, 2])
                ->where('slug', $slug)
                ->update(['is_enabled' => true, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['whole-hotel-booking', 'slot-search-engine'] as $slug) {
            DB::table('modules')
                ->whereIn('hotel_id', [1, 2])
                ->where('slug', $slug)
                ->update(['is_enabled' => false, 'updated_at' => now()]);
        }
    }
};
