<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to avoid any doctrine/dbal requirement.
        // ALTER COLUMN ... SET DEFAULT works on all PostgreSQL versions.
        DB::statement('ALTER TABLE settings ALTER COLUMN qr_checkin_enabled SET DEFAULT false');
        DB::statement('ALTER TABLE settings ALTER COLUMN qr_checkout_enabled SET DEFAULT false');

        // Flip all existing hotel settings to OFF (opt-in, not opt-out).
        DB::table('settings')->update([
            'qr_checkin_enabled'  => false,
            'qr_checkout_enabled' => false,
        ]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE settings ALTER COLUMN qr_checkin_enabled SET DEFAULT true');
        DB::statement('ALTER TABLE settings ALTER COLUMN qr_checkout_enabled SET DEFAULT true');
    }
};
