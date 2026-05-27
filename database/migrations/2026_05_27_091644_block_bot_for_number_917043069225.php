<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Block the bot auto-greeting for this specific number.
        // The contact will still appear in the inbox if they message in,
        // but the bot will not run because subscribed = false.
        DB::table('wa_contacts')->upsert(
            [
                'phone'                => '917043069225',
                'contact_type'        => 'unknown',
                'display_name'        => 'Blocked (Bot Off)',
                'subscribed'          => false,
                'unsubscribed_at'     => now(),
                'last_message_at'     => null,
                'last_message_preview'=> null,
                'unread_count'        => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            ['phone'],
            ['subscribed', 'unsubscribed_at', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('wa_contacts')
            ->where('phone', '917043069225')
            ->update(['subscribed' => true, 'unsubscribed_at' => null, 'updated_at' => now()]);
    }
};
