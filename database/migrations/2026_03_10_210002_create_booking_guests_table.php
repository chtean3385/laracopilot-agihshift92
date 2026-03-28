<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('age')->nullable();
            $table->enum('gender', ['male','female','other'])->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->date('dob')->nullable();
            $table->string('relation')->nullable();
            $table->text('signature')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('id_document_name')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking_guests');
    }
};
