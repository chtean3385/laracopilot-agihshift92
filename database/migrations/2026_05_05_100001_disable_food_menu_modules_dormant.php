<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // food-menu module is dormant — disable for every hotel.
        // Code, tables and permissions are kept intact (no deletes) so it can be
        // re-enabled later if needed.
        DB::table('modules')
            ->where('slug', 'food-menu')
            ->update(['is_enabled' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Intentional no-op — re-enabling would require manual action.
    }
};
