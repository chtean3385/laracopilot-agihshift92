<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_checkin_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('id_document_path')->nullable();
            $table->text('signature_data')->nullable();
            $table->json('additional_guests')->nullable();
            $table->date('requested_check_in')->nullable();
            $table->date('requested_check_out')->nullable();
            $table->unsignedTinyInteger('guests_count')->default(1);
            $table->enum('status', ['pending', 'converted', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_checkin_requests');
    }
};
