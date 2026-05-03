<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ota_imported_bookings', function (Blueprint $table) {
            $table->string('source_channel')->default('whatsapp')->after('matched_by');
        });
    }

    public function down(): void
    {
        Schema::table('ota_imported_bookings', function (Blueprint $table) {
            $table->dropColumn('source_channel');
        });
    }
};
