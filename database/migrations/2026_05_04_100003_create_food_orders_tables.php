<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->index();
            $table->string('order_number', 20)->unique();
            $table->string('room_number', 20);
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('guest_name')->nullable();
            $table->text('guest_notes')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'approved', 'cancelled'])->default('pending')->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('food_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('food_item_id')->nullable()->index();
            $table->string('name', 150);
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('food_orders')->onDelete('cascade');
            $table->foreign('food_item_id')->references('id')->on('food_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_order_items');
        Schema::dropIfExists('food_orders');
    }
};
