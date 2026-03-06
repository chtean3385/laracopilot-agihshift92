<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('room_type');
            $table->string('floor')->nullable();
            $table->integer('capacity')->default(2);
            $table->decimal('price_per_night',10,2)->default(0);
            $table->enum('status',['available','occupied','maintenance'])->default('available');
            $table->text('amenities')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('rooms'); }
};