<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $hotelIds = DB::table('hotels')->pluck('id')->all();

        // 1. food-menu module for every hotel (bulk)
        if (!empty($hotelIds)) {
            $existing = DB::table('modules')
                ->where('slug', 'food-menu')
                ->whereIn('hotel_id', $hotelIds)
                ->pluck('hotel_id')->all();
            $missing = array_values(array_diff($hotelIds, $existing));
            if (!empty($missing)) {
                $rows = array_map(fn($hid) => [
                    'hotel_id'    => $hid,
                    'slug'        => 'food-menu',
                    'name'        => 'Food Menu & Ordering',
                    'description' => 'QR-based in-room food ordering. Guests scan a room QR, browse the menu, and place orders that are billed directly to their room.',
                    'is_enabled'  => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ], $missing);
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('modules')->insert($chunk);
                }
            }
        }

        // 2. Permissions (bulk)
        $perms = [
            ['slug' => 'food_menu.manage',        'label' => 'Manage Food Menu',        'module' => 'Food Menu', 'sort_order' => 33],
            ['slug' => 'food_menu.orders.view',   'label' => 'View Food Orders',        'module' => 'Food Menu', 'sort_order' => 34],
            ['slug' => 'food_menu.orders.manage', 'label' => 'Approve/Edit/Cancel Food Orders', 'module' => 'Food Menu', 'sort_order' => 35],
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

        // 3. Chef role — one per hotel (bulk)
        if (!empty($hotelIds)) {
            $existingChef = DB::table('roles')
                ->where('name', 'Chef')
                ->whereIn('hotel_id', $hotelIds)
                ->pluck('hotel_id')->all();
            $missingChef = array_values(array_diff($hotelIds, $existingChef));
            if (!empty($missingChef)) {
                $rows = array_map(fn($hid) => [
                    'hotel_id'    => $hid,
                    'name'        => 'Chef',
                    'description' => 'Kitchen staff — can view and manage food orders. No access to bookings, guests, or financials.',
                    'is_system'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ], $missingChef);
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('roles')->insert($chunk);
                }
            }
        }

        // 4. Grant permissions to roles (bulk)
        $allFoodPermIds = DB::table('permissions')->where('slug', 'like', 'food_menu.%')->pluck('id')->all();
        $orderViewPerm  = DB::table('permissions')->where('slug', 'food_menu.orders.view')->value('id');
        $orderMgmtPerm  = DB::table('permissions')->where('slug', 'food_menu.orders.manage')->value('id');

        $adminRoles   = DB::table('roles')->where('name', 'Admin')->pluck('id')->all();
        $managerRoles = DB::table('roles')->where('name', 'Manager')->pluck('id')->all();
        $chefRoles    = DB::table('roles')->where('name', 'Chef')->pluck('id')->all();

        $allRoleIds = array_unique(array_merge($adminRoles, $managerRoles, $chefRoles));
        $existingPairs = [];
        if (!empty($allRoleIds) && !empty($allFoodPermIds)) {
            $existingPairs = DB::table('role_permissions')
                ->whereIn('role_id', $allRoleIds)
                ->whereIn('permission_id', $allFoodPermIds)
                ->get(['role_id', 'permission_id'])
                ->map(fn($r) => $r->role_id . ':' . $r->permission_id)
                ->all();
        }
        $existingSet = array_flip($existingPairs);

        $insertRows = [];
        $addPair = function (int $roleId, ?int $permId) use (&$insertRows, &$existingSet) {
            if (!$permId) return;
            $key = $roleId . ':' . $permId;
            if (isset($existingSet[$key])) return;
            $insertRows[] = ['role_id' => $roleId, 'permission_id' => $permId];
            $existingSet[$key] = true;
        };

        foreach ($adminRoles as $roleId) {
            foreach ($allFoodPermIds as $permId) $addPair($roleId, $permId);
        }
        foreach ($managerRoles as $roleId) {
            foreach ($allFoodPermIds as $permId) $addPair($roleId, $permId);
        }
        foreach ($chefRoles as $roleId) {
            $addPair($roleId, $orderViewPerm);
            $addPair($roleId, $orderMgmtPerm);
        }
        if (!empty($insertRows)) {
            foreach (array_chunk($insertRows, 1000) as $chunk) {
                DB::table('role_permissions')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->where('slug', 'like', 'food_menu.%')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->where('slug', 'like', 'food_menu.%')->delete();
        DB::table('modules')->where('slug', 'food-menu')->delete();
        DB::table('roles')->where('name', 'Chef')->delete();
    }
};
