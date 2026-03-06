<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('resort_name');
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('tax_rate')->default('12');
            $table->string('currency')->default('INR');
            $table->string('currency_symbol')->default('Rs');
            $table->string('check_in_time')->default('14:00');
            $table->string('check_out_time')->default('11:00');
            $table->text('cancellation_policy')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};