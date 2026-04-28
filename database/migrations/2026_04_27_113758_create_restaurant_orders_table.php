<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->unsignedBigInteger('table_id');
            $table->unsignedBigInteger('booking_id')->nullable(); // linked room booking if any
            $table->string('order_number')->unique();             // ORD-XXXXXX
            $table->enum('status', ['open', 'kotted', 'served', 'billed', 'cancelled'])->default('open');
            $table->enum('bill_type', ['direct', 'room'])->default('direct');
            $table->enum('payment_method', ['cash', 'card', 'upi', 'room', 'pending'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(5);       // snapshot of food gst rate
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('table_id')->references('id')->on('restaurant_tables')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->index('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};