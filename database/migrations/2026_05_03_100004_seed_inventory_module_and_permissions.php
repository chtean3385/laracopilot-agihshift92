<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Add inventory module for every hotel that doesn't have it yet
        $hotels = DB::table('hotels')->pluck('id');
        foreach ($hotels as $hotelId) {
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'inventory')
                ->exists();

            if (! $exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'inventory',
                    'name'        => 'Inventory Management',
                    'description' => 'Track consumables, food ingredients, and hotel supplies. Monitor stock levels, record purchases and usage, and get low-stock alerts.',
                    'is_enabled'  => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // 2. Add inventory permissions if they don't exist yet
        $perms = [
            ['slug' => 'inventory.view',   'label' => 'View Inventory',          'module' => 'Inventory', 'sort_order' => 41],
            ['slug' => 'inventory.create', 'label' => 'Add Inventory Items',     'module' => 'Inventory', 'sort_order' => 42],
            ['slug' => 'inventory.edit',   'label' => 'Edit Inventory Items',    'module' => 'Inventory', 'sort_order' => 43],
            ['slug' => 'inventory.delete', 'label' => 'Delete Inventory Items',  'module' => 'Inventory', 'sort_order' => 44],
            ['slug' => 'inventory.adjust', 'label' => 'Adjust Stock Levels',     'module' => 'Inventory', 'sort_order' => 45],
        ];

        foreach ($perms as $perm) {
            if (! DB::table('permissions')->where('slug', $perm['slug'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 3. Grant all inventory permissions to the Admin role for every hotel
        $permIds   = DB::table('permissions')->where('slug', 'like', 'inventory.%')->pluck('id');
        $adminRoles = DB::table('roles')->where('name', 'Admin')->pluck('id');

        foreach ($adminRoles as $roleId) {
            foreach ($permIds as $permId) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
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
