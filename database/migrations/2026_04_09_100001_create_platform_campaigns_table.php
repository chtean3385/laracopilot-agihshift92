<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_campaigns', function (Blueprint $table) {
            $table->id();
            $table->json('hotel_ids')->nullable();
            $table->enum('channel', ['email', 'whatsapp', 'both'])->default('email');
            $table->string('template_key')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->string('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_campaigns');
    }
};
