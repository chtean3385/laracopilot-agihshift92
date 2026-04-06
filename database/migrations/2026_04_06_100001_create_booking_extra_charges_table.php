<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_extra_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('name');
            $table->string('category', 50)->default('other');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extra_charges');
    }
};
