<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE restaurant_tables DROP CONSTRAINT IF EXISTS restaurant_tables_status_check");
        DB::statement("ALTER TABLE restaurant_tables ADD CONSTRAINT restaurant_tables_status_check CHECK (status IN ('free','occupied','dirty','unavailable'))");
    }
    public function down(): void
    {
        DB::statement("ALTER TABLE restaurant_tables DROP CONSTRAINT IF EXISTS restaurant_tables_status_check");
        DB::statement("ALTER TABLE restaurant_tables ADD CONSTRAINT restaurant_tables_status_check CHECK (status IN ('free','occupied','unavailable'))");
    }
};