<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_leads')
            && !Schema::hasColumn('whatsapp_leads', 'lead_score')) {
            Schema::table('whatsapp_leads', function (Blueprint $table) {
                $table->string('lead_score', 20)->nullable()
                    ->after('lead_status')
                    ->comment('hot|warm|cold — numeric score label after all 8 steps');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_leads', 'lead_score')) {
            Schema::table('whatsapp_leads', function (Blueprint $table) {
                $table->dropColumn('lead_score');
            });
        }
    }
};
