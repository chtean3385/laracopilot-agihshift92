<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('extra_beds')->default(0)->after('meal_cost');
            $table->decimal('extra_bed_cost', 10, 2)->default(0)->after('extra_beds');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['extra_beds', 'extra_bed_cost']);
        });
    }
};
