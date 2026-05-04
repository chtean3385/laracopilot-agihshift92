<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Remove food_menu permissions entirely (module is dormant/hidden) ─────
        $foodPermIds = DB::table('permissions')
            ->where('slug', 'like', 'food_menu.%')
            ->pluck('id')
            ->toArray();

        if (!empty($foodPermIds)) {
            DB::table('role_permissions')->whereIn('permission_id', $foodPermIds)->delete();
            DB::table('permissions')->whereIn('id', $foodPermIds)->delete();
        }

        // ── 2. Ensure every hotel has a restaurant module row ─────────────────────
        $restaurantPerms = [
            ['slug' => 'restaurant.view',    'label' => 'View Restaurant',            'module' => 'Restaurant', 'sort_order' => 31],
            ['slug' => 'restaurant.tables',  'label' => 'Manage Tables',              'module' => 'Restaurant', 'sort_order' => 32],
            ['slug' => 'restaurant.menu',    'label' => 'Manage Menu & Categories',   'module' => 'Restaurant', 'sort_order' => 33],
            ['slug' => 'restaurant.orders',  'label' => 'Take & Manage Orders',       'module' => 'Restaurant', 'sort_order' => 34],
            ['slug' => 'restaurant.billing', 'label' => 'Process Restaurant Billing', 'module' => 'Restaurant', 'sort_order' => 35],
            ['slug' => 'restaurant.reports', 'label' => 'View Restaurant Reports',    'module' => 'Restaurant', 'sort_order' => 36],
        ];

        // Ensure restaurant permissions exist
        foreach ($restaurantPerms as $perm) {
            if (!DB::table('permissions')->where('slug', $perm['slug'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        $restaurantPermIds = DB::table('permissions')
            ->where('slug', 'like', 'restaurant.%')
            ->pluck('id')
            ->toArray();

        $hotels = DB::table('hotels')->pluck('id');

        foreach ($hotels as $hotelId) {
            // Add missing restaurant module row
            $exists = DB::table('modules')
                ->where('hotel_id', $hotelId)
                ->where('slug', 'restaurant')
                ->exists();

            if (!$exists) {
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

            // Re-grant restaurant permissions to Admin role for this hotel
            // (they may have been revoked by earlier migration when module was disabled)
            $adminRoleId = DB::table('roles')
                ->where('hotel_id', $hotelId)
                ->where('name', 'Admin')
                ->value('id');

            if ($adminRoleId) {
                foreach ($restaurantPermIds as $permId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $adminRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentional no-op — permission and module data changes are not safely reversible.
    }
};
