<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // wa_contacts — one row per unique phone number that has ever messaged
        if (!Schema::hasTable('wa_contacts')) {
            Schema::create('wa_contacts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('phone', 30)->unique();
                $table->unsignedBigInteger('hotel_id')->nullable()->index();
                $table->enum('contact_type', ['owner', 'guest', 'unknown'])->default('unknown');
                $table->string('display_name', 150)->nullable();
                $table->timestamp('consented_at')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->string('last_message_preview', 200)->nullable();
                $table->timestamps();
            });
        }

        // Add bypass toggle to platform_whatsapp_settings
        if (Schema::hasTable('platform_whatsapp_settings')
            && !Schema::hasColumn('platform_whatsapp_settings', 'skip_signature_check')) {
            Schema::table('platform_whatsapp_settings', function (Blueprint $table) {
                $table->boolean('skip_signature_check')->default(false)->after('webhook_verify_token');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_contacts');
        if (Schema::hasColumn('platform_whatsapp_settings', 'skip_signature_check')) {
            Schema::table('platform_whatsapp_settings', function (Blueprint $table) {
                $table->dropColumn('skip_signature_check');
            });
        }
    }
};
