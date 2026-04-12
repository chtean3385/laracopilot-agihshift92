<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_inbox_conversations')) {
            return;
        }

        Schema::create('wa_inbox_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->nullable();
            $table->string('phone', 30);
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 200)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_24h_reset_at')->nullable();
            $table->timestamps();

            $table->unique('phone');
            $table->index('hotel_id');
            $table->index('last_message_at');

            $table->foreign('hotel_id')->references('id')->on('hotels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_inbox_conversations');
    }
};
