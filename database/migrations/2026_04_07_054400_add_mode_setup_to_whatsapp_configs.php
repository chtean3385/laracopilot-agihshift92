<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('mode')->default('shared')->after('is_active')
                  ->comment('shared = use CRM number, own = hotel own number');
            $table->unsignedTinyInteger('setup_step')->default(0)->after('mode')
                  ->comment('0=not started,1=token obtained,2=webhook done,3=templates submitted,5=complete');
            $table->boolean('setup_completed')->default(false)->after('setup_step');
            $table->string('waba_id')->nullable()->after('business_account_id')
                  ->comment('WhatsApp Business Account ID from Embedded Signup');
            $table->text('access_token')->nullable()->after('api_key')
                  ->comment('Long-lived access token from Meta Embedded Signup exchange');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn(['mode', 'setup_step', 'setup_completed', 'waba_id', 'access_token']);
        });
    }
};
