<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'price_overridden')) {
            return;
        }
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('price_overridden')->default(false)->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('price_overridden');
        });
    }
};
