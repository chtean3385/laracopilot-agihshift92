<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parsed_emails')) return;

        Schema::create('parsed_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->string('message_uid');
            $table->string('subject')->nullable();
            $table->string('sender')->nullable();
            $table->text('raw_body')->nullable();
            $table->json('parsed_data')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('status')->default('pending'); // pending|processed|failed|duplicate
            $table->text('fail_reason')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->unique(['hotel_id', 'message_uid'], 'parsed_emails_hotel_uid_unique');
            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parsed_emails');
    }
};
