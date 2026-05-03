<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ota_email_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->unique();
            $table->string('inbound_email')->unique();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_email_sources');
    }
};
