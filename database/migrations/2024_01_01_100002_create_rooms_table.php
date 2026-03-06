<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('rooms');
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('type')->default('standard');
            $table->integer('capacity')->default(2);
            $table->decimal('price_per_night', 10, 2)->default(0);
            $table->integer('floor')->nullable();
            $table->string('view')->nullable();
            $table->text('amenities')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
};