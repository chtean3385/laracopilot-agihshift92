<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('qr_checkin_enabled')->default(true)->after('cancellation_policy');
            $table->boolean('qr_checkout_enabled')->default(true)->after('qr_checkin_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['qr_checkin_enabled', 'qr_checkout_enabled']);
        });
    }
};
