<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hotel_backups ALTER COLUMN backup_data TYPE jsonb USING backup_data::jsonb');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hotel_backups ALTER COLUMN backup_data TYPE text USING backup_data::text');
        }
    }
};
