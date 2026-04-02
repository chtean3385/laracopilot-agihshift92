<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_time_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->string('name');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->boolean('is_overnight')->default(false);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->index('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_time_slots');
    }
};
