<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_backup_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->unique();
            $table->boolean('auto_backup_enabled')->default(false);
            $table->unsignedInteger('interval_hours')->default(24);
            $table->unsignedInteger('retention_count')->default(10);
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_backup_settings');
    }
};
