<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('resort_name')->default('Grand Paradise Resort');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('check_in_time')->default('14:00');
            $table->string('check_out_time')->default('11:00');
            $table->string('currency')->default('INR');
            $table->decimal('tax_rate',5,2)->default(12);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('settings'); }
};