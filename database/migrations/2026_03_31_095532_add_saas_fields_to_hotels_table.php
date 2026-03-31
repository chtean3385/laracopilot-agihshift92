<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
            $table->timestamp('plan_expires_at')->nullable()->after('trial_ends_at');
            $table->unsignedInteger('max_rooms')->default(50)->after('plan_expires_at');
            $table->unsignedInteger('max_users')->default(10)->after('max_rooms');
            $table->text('admin_notes')->nullable()->after('max_users');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'plan_expires_at', 'max_rooms', 'max_users', 'admin_notes']);
        });
    }
};
