<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('meal_breakfast')->default(false)->after('special_requests');
            $table->boolean('meal_lunch')->default(false)->after('meal_breakfast');
            $table->boolean('meal_dinner')->default(false)->after('meal_lunch');
            $table->decimal('meal_cost', 10, 2)->default(0)->after('meal_dinner');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['meal_breakfast', 'meal_lunch', 'meal_dinner', 'meal_cost']);
        });
    }
};
