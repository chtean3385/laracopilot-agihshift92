<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix Test/Generic: 917043069225 was seeded as sender_number but it is the RECEIVING
        // SaaS Meta number, not an OTA sender. Remove it so the record becomes a
        // content-pattern catch-all (null sender_number = any message matching OTA format).
        DB::table('ota_sources')
            ->where('message_pattern_key', 'generic')
            ->whereRaw("regexp_replace(sender_number, '[^0-9]', '', 'g') = ?", ['917043069225'])
            ->update(['sender_number' => null, 'notes' => 'Generic catch-all: matches any message with OTA format (Property: / Booking Ref:). Used for demo and forwarded messages.', 'updated_at' => now()]);

        // Enable ota_whatsapp_sync for hotel ID 1 (Demo Hotel) so the demo path works
        DB::table('modules')
            ->where('hotel_id', 1)
            ->where('slug', 'ota_whatsapp_sync')
            ->update(['is_enabled' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('ota_sources')
            ->where('message_pattern_key', 'generic')
            ->whereNull('sender_number')
            ->update(['sender_number' => '917043069225', 'notes' => 'SaaS admin test number for demo testing', 'updated_at' => now()]);

        DB::table('modules')
            ->where('hotel_id', 1)
            ->where('slug', 'ota_whatsapp_sync')
            ->update(['is_enabled' => false, 'updated_at' => now()]);
    }
};
