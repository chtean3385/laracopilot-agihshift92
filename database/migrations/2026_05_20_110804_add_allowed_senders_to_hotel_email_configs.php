<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotel_email_configs', function (Blueprint $table) {
            $table->json('allowed_senders')->nullable()->after('folder_to_watch');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_email_configs', function (Blueprint $table) {
            $table->dropColumn('allowed_senders');
        });
    }
};
