<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Add inventory module for every hotel that doesn't have it yet (bulk)
        $hotelIds = DB::table('hotels')->pluck('id')->all();
        if (!empty($hotelIds)) {
            $existing = DB::table('modules')
                ->where('slug', 'inventory')
                ->whereIn('hotel_id', $hotelIds)
                ->pluck('hotel_id')->all();

            $missing = array_values(array_diff($hotelIds, $existing));
            if (!empty($missing)) {
                $rows = array_map(fn($hid) => [
                    'hotel_id'    => $hid,
                    'slug'        => 'inventory',
                    'name'        => 'Inventory Management',
                    'description' => 'Track consumables, food ingredients, and hotel supplies. Monitor stock levels, record purchases and usage, and get low-stock alerts.',
                    'is_enabled'  => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ], $missing);
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('modules')->insert($chunk);
                }
            }
        }

        // 2. Add inventory permissions if they don't exist yet (bulk)
        $perms = [
            ['slug' => 'inventory.view',   'label' => 'View Inventory',          'module' => 'Inventory', 'sort_order' => 41],
            ['slug' => 'inventory.create', 'label' => 'Add Inventory Items',     'module' => 'Inventory', 'sort_order' => 42],
            ['slug' => 'inventory.edit',   'label' => 'Edit Inventory Items',    'module' => 'Inventory', 'sort_order' => 43],
            ['slug' => 'inventory.delete', 'label' => 'Delete Inventory Items',  'module' => 'Inventory', 'sort_order' => 44],
            ['slug' => 'inventory.adjust', 'label' => 'Adjust Stock Levels',     'module' => 'Inventory', 'sort_order' => 45],
        ];
        $permSlugs = array_column($perms, 'slug');
        $existingPermSlugs = DB::table('permissions')->whereIn('slug', $permSlugs)->pluck('slug')->all();
        $newPermRows = [];
        foreach ($perms as $p) {
            if (in_array($p['slug'], $existingPermSlugs, true)) continue;
            $newPermRows[] = array_merge($p, ['created_at' => $now, 'updated_at' => $now]);
        }
        if (!empty($newPermRows)) {
            DB::table('permissions')->insert($newPermRows);
        }

        // 3. Grant all inventory permissions to the Admin role for every hotel (bulk)
        $permIds    = DB::table('permissions')->where('slug', 'like', 'inventory.%')->pluck('id')->all();
        $adminRoles = DB::table('roles')->where('name', 'Admin')->pluck('id')->all();
        if (!empty($permIds) && !empty($adminRoles)) {
            $existing = DB::table('role_permissions')
                ->whereIn('role_id', $adminRoles)
                ->whereIn('permission_id', $permIds)
                ->get(['role_id', 'permission_id'])
                ->map(fn($r) => $r->role_id . ':' . $r->permission_id)
                ->all();
            $existingSet = array_flip($existing);

            $rows = [];
            foreach ($adminRoles as $roleId) {
                foreach ($permIds as $permId) {
                    if (isset($existingSet[$roleId . ':' . $permId])) continue;
                    $rows[] = ['role_id' => $roleId, 'permission_id' => $permId];
                }
            }
            if (!empty($rows)) {
                foreach (array_chunk($rows, 1000) as $chunk) {
                    DB::table('role_permissions')->insert($chunk);
                }
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->where('slug', 'like', 'inventory.%')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->where('slug', 'like', 'inventory.%')->delete();
        DB::table('modules')->where('slug', 'inventory')->delete();
    }
};
