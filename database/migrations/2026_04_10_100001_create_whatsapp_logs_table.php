<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 10)->default('incoming');
            $table->string('event_type', 60)->nullable();
            $table->string('phone', 30)->nullable();
            $table->unsignedBigInteger('hotel_id')->nullable();
            $table->string('status', 20)->default('ok');
            $table->jsonb('payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['event_type']);
            $table->index(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
