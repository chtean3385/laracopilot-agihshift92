<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->unsignedInteger('wa_daily_limit')->nullable()->after('backup_enabled');
            $table->unsignedInteger('wa_monthly_limit')->nullable()->after('wa_daily_limit');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['wa_daily_limit', 'wa_monthly_limit']);
        });
    }
};
