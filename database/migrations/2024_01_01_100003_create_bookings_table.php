<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('bookings');
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('room_id');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->dateTime('actual_checkin_at')->nullable();
            $table->dateTime('actual_checkout_at')->nullable();
            $table->integer('nights')->default(1);
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('advance_payment', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->text('special_requests')->nullable();
            $table->string('status')->default('confirmed');
            $table->string('payment_status')->default('pending');
            $table->text('checkin_notes')->nullable();
            $table->text('checkout_notes')->nullable();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};