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
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('billing_cycle')->default('monthly')->after('plan');
            $table->unsignedInteger('custom_monthly_price')->nullable()->after('billing_cycle');
            $table->unsignedInteger('custom_yearly_price')->nullable()->after('custom_monthly_price');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'custom_monthly_price', 'custom_yearly_price']);
        });
    }
};
