<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('time_slot_id')->nullable()->after('room_id');
            $table->date('booking_date')->nullable()->after('time_slot_id');
            $table->string('slot_start_time', 5)->nullable()->after('booking_date');
            $table->string('slot_end_time', 5)->nullable()->after('slot_start_time');
            $table->unsignedSmallInteger('hours_booked')->nullable()->after('slot_end_time');

            $table->foreign('time_slot_id')->references('id')->on('hotel_time_slots')->onDelete('set null');
            $table->index('time_slot_id');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['time_slot_id']);
            $table->dropIndex(['time_slot_id']);
            $table->dropIndex(['booking_date']);
            $table->dropColumn(['time_slot_id', 'booking_date', 'slot_start_time', 'slot_end_time', 'hours_booked']);
        });
    }
};
