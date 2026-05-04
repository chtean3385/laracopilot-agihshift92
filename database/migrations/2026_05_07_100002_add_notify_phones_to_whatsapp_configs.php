<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->boolean('notify_on_booking')->default(false)->after('test_phone');
            $table->json('notify_phones')->nullable()->after('notify_on_booking');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn(['notify_on_booking', 'notify_phones']);
        });
    }
};
