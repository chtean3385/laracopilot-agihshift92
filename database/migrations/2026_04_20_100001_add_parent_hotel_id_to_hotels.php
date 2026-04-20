<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (!Schema::hasColumn('hotels', 'parent_hotel_id')) {
                $table->unsignedBigInteger('parent_hotel_id')->nullable()->after('id');
                $table->index('parent_hotel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (Schema::hasColumn('hotels', 'parent_hotel_id')) {
                // Drop the index explicitly before the column for cross-DB safety
                try { $table->dropIndex(['parent_hotel_id']); } catch (\Throwable $e) { /* index may not exist */ }
                $table->dropColumn('parent_hotel_id');
            }
        });
    }
};
