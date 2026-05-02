<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all restaurant permission IDs
        $restaurantPermIds = DB::table('permissions')
            ->where('slug', 'like', 'restaurant.%')
            ->pluck('id')
            ->toArray();

        if (empty($restaurantPermIds)) {
            return;
        }

        // Find hotels where restaurant module is NOT enabled (or doesn't exist)
        $allHotelIds = DB::table('hotels')->pluck('id')->toArray();

        $enabledHotelIds = DB::table('modules')
            ->where('slug', 'restaurant')
            ->where('is_enabled', true)
            ->pluck('hotel_id')
            ->toArray();

        $disabledHotelIds = array_diff($allHotelIds, $enabledHotelIds);

        if (empty($disabledHotelIds)) {
            return;
        }

        // Get Admin role IDs for those hotels
        $adminRoleIds = DB::table('roles')
            ->where('name', 'Admin')
            ->whereIn('hotel_id', $disabledHotelIds)
            ->pluck('id')
            ->toArray();

        if (empty($adminRoleIds)) {
            return;
        }

        // Revoke restaurant permissions from those Admin roles
        DB::table('role_permissions')
            ->whereIn('role_id', $adminRoleIds)
            ->whereIn('permission_id', $restaurantPermIds)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally empty — do not re-grant permissions on rollback.
    }
};
