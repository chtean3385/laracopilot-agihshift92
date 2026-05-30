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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('arrival_city', 100)->nullable()->after('city');
            $table->string('travel_reason', 255)->nullable()->after('arrival_city');
            $table->string('dispatch_city', 100)->nullable()->after('travel_reason');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['arrival_city', 'travel_reason', 'dispatch_city']);
        });
    }
};
