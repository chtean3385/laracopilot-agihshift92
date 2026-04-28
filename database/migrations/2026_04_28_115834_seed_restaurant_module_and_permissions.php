<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Add restaurant module for every hotel that doesn't have it yet
        $hotels = DB::table('hotels')->pluck('id');
        foreach ($hotels as $hotelId) {
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'restaurant')
                ->exists();

            if (! $exists) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'restaurant',
                    'name'        => 'Restaurant Management',
                    'description' => 'Manage restaurant tables, menu, orders, KOT printing and billing. Charge directly or add to guest room bill.',
                    'is_enabled'  => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // 2. Add restaurant permissions if they don't exist yet
        $restaurantPerms = [
            ['slug' => 'restaurant.view',    'label' => 'View Restaurant',            'module' => 'Restaurant', 'sort_order' => 31],
            ['slug' => 'restaurant.tables',  'label' => 'Manage Tables',              'module' => 'Restaurant', 'sort_order' => 32],
            ['slug' => 'restaurant.menu',    'label' => 'Manage Menu & Categories',   'module' => 'Restaurant', 'sort_order' => 33],
            ['slug' => 'restaurant.orders',  'label' => 'Take & Manage Orders',       'module' => 'Restaurant', 'sort_order' => 34],
            ['slug' => 'restaurant.billing', 'label' => 'Process Restaurant Billing', 'module' => 'Restaurant', 'sort_order' => 35],
            ['slug' => 'restaurant.reports', 'label' => 'View Restaurant Reports',    'module' => 'Restaurant', 'sort_order' => 36],
        ];

        foreach ($restaurantPerms as $perm) {
            $exists = DB::table('permissions')->where('slug', $perm['slug'])->exists();
            if (! $exists) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 3. Assign all restaurant permissions to every Admin role
        $permIds = DB::table('permissions')
            ->where('slug', 'like', 'restaurant.%')
            ->pluck('id');

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
        $permIds = DB::table('permissions')
            ->where('slug', 'like', 'restaurant.%')
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->where('slug', 'like', 'restaurant.%')->delete();
        DB::table('modules')->where('slug', 'restaurant')->delete();
    }
};
