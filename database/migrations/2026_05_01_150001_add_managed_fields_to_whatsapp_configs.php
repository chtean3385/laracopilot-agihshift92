<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('phone_number', 30)->nullable()->after('phone_number_id');
            $table->string('managed_display_name', 120)->nullable()->after('phone_number');
            $table->string('managed_otp_status', 20)->nullable()->after('managed_display_name'); // pending|verified
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'managed_display_name', 'managed_otp_status']);
        });
    }
};
