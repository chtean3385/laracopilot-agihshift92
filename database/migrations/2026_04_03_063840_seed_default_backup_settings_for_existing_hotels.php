<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelIds = DB::table('hotels')->pluck('id');
        $existing = DB::table('hotel_backup_settings')->pluck('hotel_id')->flip();

        foreach ($hotelIds as $hotelId) {
            if ($existing->has($hotelId)) {
                DB::table('hotel_backup_settings')
                    ->where('hotel_id', $hotelId)
                    ->update(['retention_count' => 3]);
                continue;
            }
            DB::table('hotel_backup_settings')->insert([
                'hotel_id'            => $hotelId,
                'auto_backup_enabled' => true,
                'interval_hours'      => 168,
                'retention_count'     => 3,
                'last_backup_at'      => null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }

    public function down(): void {}
};
