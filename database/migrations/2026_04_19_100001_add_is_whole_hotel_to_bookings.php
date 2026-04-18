<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_whole_hotel')->default(false)->after('ota_conflict');
            $table->string('whole_hotel_pricing_type', 20)->nullable()->after('is_whole_hotel');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['is_whole_hotel', 'whole_hotel_pricing_type']);
        });
    }
};
