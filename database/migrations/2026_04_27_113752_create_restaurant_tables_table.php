<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->string('name');           // Table 1, Table 2, VIP Table etc.
            $table->integer('capacity')->default(4);
            $table->enum('status', ['free', 'occupied', 'unavailable'])->default('free');
            $table->string('section')->nullable(); // optional future use
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->index('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};