<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_contacts')) return;

        Schema::table('wa_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_contacts', 'bot_state')) {
                $table->string('bot_state', 40)->nullable()->after('last_message_preview');
            }
            if (!Schema::hasColumn('wa_contacts', 'lead_status')) {
                $table->string('lead_status', 20)->nullable()->after('bot_state');
            }
            if (!Schema::hasColumn('wa_contacts', 'bot_service_interest')) {
                $table->string('bot_service_interest', 100)->nullable()->after('lead_status');
            }
            if (!Schema::hasColumn('wa_contacts', 'bot_timeline')) {
                $table->string('bot_timeline', 100)->nullable()->after('bot_service_interest');
            }
            if (!Schema::hasColumn('wa_contacts', 'bot_budget')) {
                $table->string('bot_budget', 100)->nullable()->after('bot_timeline');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wa_contacts')) return;

        Schema::table('wa_contacts', function (Blueprint $table) {
            $cols = ['bot_state', 'lead_status', 'bot_service_interest', 'bot_timeline', 'bot_budget'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('wa_contacts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
