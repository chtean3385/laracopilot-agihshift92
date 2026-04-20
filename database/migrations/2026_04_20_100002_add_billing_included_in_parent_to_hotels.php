<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('hotels', 'billing_included_in_parent')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->boolean('billing_included_in_parent')->default(false)->after('parent_hotel_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotels', 'billing_included_in_parent')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->dropColumn('billing_included_in_parent');
            });
        }
    }
};
