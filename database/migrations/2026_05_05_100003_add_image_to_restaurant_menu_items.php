<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $t) {
            if (!Schema::hasColumn('restaurant_menu_items', 'image_path')) {
                $t->string('image_path')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $t) {
            $t->dropColumn('image_path');
        });
    }
};
