<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hotel_email_configs')) return;

        Schema::create('hotel_email_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->string('email_address');
            $table->text('email_password'); // Crypt-encrypted
            $table->string('imap_host');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('encryption', 10)->default('ssl'); // ssl|tls
            $table->string('folder_to_watch')->default('INBOX');
            $table->dateTime('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->unique('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_email_configs');
    }
};
