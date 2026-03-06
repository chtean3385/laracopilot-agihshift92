<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount',10,2);
            $table->string('payment_method')->default('cash');
            $table->enum('payment_type',['advance','partial','final','refund'])->default('advance');
            $table->timestamp('payment_date')->nullable();
            $table->enum('status',['pending','completed','failed','refunded'])->default('completed');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('payments'); }
};