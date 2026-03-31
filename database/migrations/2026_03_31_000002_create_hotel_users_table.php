<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('Admin');
            $table->boolean('is_hotel_admin')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['hotel_id', 'user_id']);
            $table->index('hotel_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_users');
    }
};
