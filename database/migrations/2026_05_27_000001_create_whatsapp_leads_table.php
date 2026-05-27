<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // whatsapp_leads — one row per lead phone, upserted as bot collects answers
        if (!Schema::hasTable('whatsapp_leads')) {
            Schema::create('whatsapp_leads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('phone', 30)->unique()->index();
                $table->string('name', 150)->nullable();
                $table->string('hotel_name', 200)->nullable();
                $table->string('room_count', 20)->nullable();
                $table->string('current_system', 200)->nullable();
                $table->string('role', 50)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('implementation_timeline', 100)->nullable();
                $table->string('demo_datetime', 150)->nullable();
                $table->string('lead_status', 30)->default('new')
                    ->comment('new|hot|warm|cold|nurture|opted_out|completed');
                $table->string('current_step', 50)->nullable()
                    ->comment('bot state: step_1 through step_8 or completed');
                $table->boolean('opt_out')->default(false);
                $table->text('notes')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        // Add admin_notify_phone to platform_whatsapp_settings (for HOT lead alerts)
        if (Schema::hasTable('platform_whatsapp_settings')
            && !Schema::hasColumn('platform_whatsapp_settings', 'admin_notify_phone')) {
            Schema::table('platform_whatsapp_settings', function (Blueprint $table) {
                $table->string('admin_notify_phone', 30)->nullable()
                    ->after('webhook_verify_token')
                    ->comment('Phone number to notify when a HOT lead is detected');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_leads');
        if (Schema::hasColumn('platform_whatsapp_settings', 'admin_notify_phone')) {
            Schema::table('platform_whatsapp_settings', function (Blueprint $table) {
                $table->dropColumn('admin_notify_phone');
            });
        }
    }
};
