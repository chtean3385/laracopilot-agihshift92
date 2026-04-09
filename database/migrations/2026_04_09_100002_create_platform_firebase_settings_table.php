<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_firebase_settings', function (Blueprint $table) {
            $table->id();
            $table->text('firebase_project_id')->nullable();
            $table->text('firebase_api_key')->nullable();
            $table->text('firebase_messaging_sender_id')->nullable();
            $table->text('firebase_app_id')->nullable();
            $table->text('firebase_vapid_key')->nullable();
            $table->text('fcm_server_key')->nullable();
            $table->boolean('push_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_firebase_settings');
    }
};
