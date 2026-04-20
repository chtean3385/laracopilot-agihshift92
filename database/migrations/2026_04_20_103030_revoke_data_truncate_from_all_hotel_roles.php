<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permId = DB::table('permissions')->where('slug', 'data.truncate')->value('id');

        if (!$permId) {
            return;
        }

        DB::table('role_permissions')
            ->where('permission_id', $permId)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally empty — dangerous permissions are not restored on rollback.
    }
};
