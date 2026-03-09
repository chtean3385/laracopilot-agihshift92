<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('has_extra_bed')->default(false)->after('dinner_price');
            $table->decimal('extra_bed_price', 10, 2)->nullable()->after('has_extra_bed');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['has_extra_bed', 'extra_bed_price']);
        });
    }
};
