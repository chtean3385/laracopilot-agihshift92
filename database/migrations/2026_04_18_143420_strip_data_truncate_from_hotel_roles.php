<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permId = DB::table('permissions')->where('slug', 'data.truncate')->value('id');
        if ($permId) {
            DB::table('role_permissions')->where('permission_id', $permId)->delete();
        }
    }

    public function down(): void
    {
        // No rollback — do not re-add the dangerous permission to any role
    }
};
