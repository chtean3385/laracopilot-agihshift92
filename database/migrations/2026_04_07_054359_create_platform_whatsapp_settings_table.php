<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('meta_app_id')->nullable();
            $table->string('meta_app_secret')->nullable();
            $table->string('meta_config_id')->nullable()->comment('Business Login Configuration ID');
            $table->text('saas_token')->nullable()->comment('System User Access Token for shared CRM number');
            $table->string('saas_phone_number_id')->nullable();
            $table->string('saas_waba_id')->nullable()->comment('WhatsApp Business Account ID for shared number');
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('is_saas_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_whatsapp_settings');
    }
};
