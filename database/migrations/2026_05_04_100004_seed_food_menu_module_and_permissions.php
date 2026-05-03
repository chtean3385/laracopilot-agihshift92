<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. food-menu module for every hotel
        $hotels = DB::table('hotels')->pluck('id');
        foreach ($hotels as $hotelId) {
            if (! DB::table('modules')->where('hotel_id', $hotelId)->where('slug', 'food-menu')->exists()) {
                DB::table('modules')->insert([
                    'hotel_id'    => $hotelId,
                    'slug'        => 'food-menu',
                    'name'        => 'Food Menu & Ordering',
                    'description' => 'QR-based in-room food ordering. Guests scan a room QR, browse the menu, and place orders that are billed directly to their room.',
                    'is_enabled'  => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // 2. Permissions
        $perms = [
            ['slug' => 'food_menu.manage',        'label' => 'Manage Food Menu',        'module' => 'Food Menu', 'sort_order' => 33],
            ['slug' => 'food_menu.orders.view',   'label' => 'View Food Orders',        'module' => 'Food Menu', 'sort_order' => 34],
            ['slug' => 'food_menu.orders.manage', 'label' => 'Approve/Edit/Cancel Food Orders', 'module' => 'Food Menu', 'sort_order' => 35],
        ];

        foreach ($perms as $perm) {
            if (! DB::table('permissions')->where('slug', $perm['slug'])->exists()) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 3. Chef role — one per hotel (is_system=true, if it doesn't exist yet)
        foreach ($hotels as $hotelId) {
            if (! DB::table('roles')->where('hotel_id', $hotelId)->where('name', 'Chef')->exists()) {
                DB::table('roles')->insert([
                    'hotel_id'    => $hotelId,
                    'name'        => 'Chef',
                    'description' => 'Kitchen staff — can view and manage food orders. No access to bookings, guests, or financials.',
                    'is_system'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // 4. Grant permissions to roles
        $allFoodPermIds = DB::table('permissions')->where('slug', 'like', 'food_menu.%')->pluck('id');
        $managePerm     = DB::table('permissions')->where('slug', 'food_menu.manage')->value('id');
        $orderViewPerm  = DB::table('permissions')->where('slug', 'food_menu.orders.view')->value('id');
        $orderMgmtPerm  = DB::table('permissions')->where('slug', 'food_menu.orders.manage')->value('id');

        $adminRoles   = DB::table('roles')->where('name', 'Admin')->pluck('id');
        $managerRoles = DB::table('roles')->where('name', 'Manager')->pluck('id');
        $chefRoles    = DB::table('roles')->where('name', 'Chef')->pluck('id');

        $grantIfMissing = function (int $roleId, int $permId) {
            if (! DB::table('role_permissions')->where('role_id', $roleId)->where('permission_id', $permId)->exists()) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permId]);
            }
        };

        // Admin: all three food_menu permissions
        foreach ($adminRoles as $roleId) {
            foreach ($allFoodPermIds as $permId) {
                $grantIfMissing($roleId, $permId);
            }
        }

        // Manager: all three food_menu permissions
        foreach ($managerRoles as $roleId) {
            foreach ($allFoodPermIds as $permId) {
                $grantIfMissing($roleId, $permId);
            }
        }

        // Chef: orders.view + orders.manage only
        foreach ($chefRoles as $roleId) {
            if ($orderViewPerm) $grantIfMissing($roleId, $orderViewPerm);
            if ($orderMgmtPerm) $grantIfMissing($roleId, $orderMgmtPerm);
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
