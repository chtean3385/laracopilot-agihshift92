<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('nights')->default(1);
            $table->decimal('total_amount',10,2)->default(0);
            $table->decimal('advance_payment',10,2)->default(0);
            $table->decimal('due_amount',10,2)->default(0);
            $table->enum('status',['pending','confirmed','checked_in','checked_out','cancelled'])->default('pending');
            $table->enum('payment_status',['pending','partial','paid'])->default('pending');
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('bookings'); }
};