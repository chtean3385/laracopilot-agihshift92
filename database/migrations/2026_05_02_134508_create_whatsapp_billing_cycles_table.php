<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('period_label');           // e.g. "May 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('message_count')->default(0);
            $table->decimal('rate_per_message', 10, 4)->default(0.0086); // ₹0.0086
            $table->decimal('amount', 10, 2)->default(0);                 // rupees
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'period_start']); // one cycle per hotel per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_billing_cycles');
    }
};
