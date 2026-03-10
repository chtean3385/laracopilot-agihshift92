<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_manager_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('ezee');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('hotel_code')->nullable();
            $table->string('property_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('extra_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_manager_configs');
    }
};
