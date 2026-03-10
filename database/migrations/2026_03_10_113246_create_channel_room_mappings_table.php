<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_room_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('channel_room_code');
            $table->string('channel_rate_plan')->nullable();
            $table->decimal('extra_bed_rate', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['room_id', 'channel_room_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_room_mappings');
    }
};
