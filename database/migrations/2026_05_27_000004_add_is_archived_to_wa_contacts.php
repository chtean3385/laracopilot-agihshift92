<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_contacts')) return;
        Schema::table('wa_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_contacts', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('unread_count');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('wa_contacts') && Schema::hasColumn('wa_contacts', 'is_archived')) {
            Schema::table('wa_contacts', function (Blueprint $table) {
                $table->dropColumn('is_archived');
            });
        }
    }
};
