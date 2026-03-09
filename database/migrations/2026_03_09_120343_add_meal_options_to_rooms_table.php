<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('has_breakfast')->default(false)->after('description');
            $table->decimal('breakfast_price', 10, 2)->nullable()->after('has_breakfast');
            $table->boolean('has_lunch')->default(false)->after('breakfast_price');
            $table->decimal('lunch_price', 10, 2)->nullable()->after('has_lunch');
            $table->boolean('has_dinner')->default(false)->after('lunch_price');
            $table->decimal('dinner_price', 10, 2)->nullable()->after('has_dinner');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['has_breakfast', 'breakfast_price', 'has_lunch', 'lunch_price', 'has_dinner', 'dinner_price']);
        });
    }
};
