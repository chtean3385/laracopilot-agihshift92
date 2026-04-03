<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand varchar columns in whatsapp_configs to text.
     * Meta WhatsApp access tokens and IDs can exceed varchar(191/255).
     */
    public function up(): void
    {
        // Use raw SQL for PostgreSQL compatibility — ALTER COLUMN TYPE
        DB::statement('ALTER TABLE whatsapp_configs ALTER COLUMN provider TYPE text');
        DB::statement('ALTER TABLE whatsapp_configs ALTER COLUMN phone_number_id TYPE text');
        DB::statement('ALTER TABLE whatsapp_configs ALTER COLUMN webhook_verify_token TYPE text');
        DB::statement('ALTER TABLE whatsapp_configs ALTER COLUMN business_account_id TYPE text');
        DB::statement('ALTER TABLE whatsapp_configs ALTER COLUMN test_phone TYPE text');
    }

    public function down(): void
    {
        // Revert to varchar(255) — may fail if existing data is longer
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('provider', 255)->change();
            $table->string('phone_number_id', 255)->nullable()->change();
            $table->string('webhook_verify_token', 255)->nullable()->change();
            $table->string('business_account_id', 255)->nullable()->change();
            $table->string('test_phone', 255)->nullable()->change();
        });
    }
};
