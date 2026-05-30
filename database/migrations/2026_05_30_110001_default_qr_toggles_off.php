<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change column defaults to false so new rows default to OFF.
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('qr_checkin_enabled')->default(false)->change();
            $table->boolean('qr_checkout_enabled')->default(false)->change();
        });

        // Flip all existing hotel settings to OFF (opt-in, not opt-out).
        DB::table('settings')->update([
            'qr_checkin_enabled'  => false,
            'qr_checkout_enabled' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('qr_checkin_enabled')->default(true)->change();
            $table->boolean('qr_checkout_enabled')->default(true)->change();
        });
    }
};
