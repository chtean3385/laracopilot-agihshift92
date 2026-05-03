<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ota_booking_conflicts')) return;

        Schema::create('ota_booking_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('parsed_email_id')->nullable();
            $table->string('conflict_type'); // room_type_unavailable|dates_overlap|no_room_matched
            $table->string('requested_room_type')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->boolean('resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('parsed_email_id')->references('id')->on('parsed_emails')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['hotel_id', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_booking_conflicts');
    }
};
