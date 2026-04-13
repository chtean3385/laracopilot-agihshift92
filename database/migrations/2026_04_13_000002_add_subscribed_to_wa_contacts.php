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
            if (!Schema::hasColumn('wa_contacts', 'subscribed')) {
                $table->boolean('subscribed')->default(true)->after('consented_at')
                    ->comment('false = contact sent STOP, do not send proactive messages');
            }
            if (!Schema::hasColumn('wa_contacts', 'unsubscribed_at')) {
                $table->timestamp('unsubscribed_at')->nullable()->after('subscribed');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wa_contacts')) return;
        Schema::table('wa_contacts', function (Blueprint $table) {
            foreach (['subscribed', 'unsubscribed_at'] as $col) {
                if (Schema::hasColumn('wa_contacts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
