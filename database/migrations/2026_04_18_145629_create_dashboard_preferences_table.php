<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hotel_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->json('preferences');
            $table->boolean('is_hotel_default')->default(false);
            $table->timestamps();

            $table->unique(['hotel_id', 'user_id']);
            $table->index(['hotel_id', 'is_hotel_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_preferences');
    }
};
