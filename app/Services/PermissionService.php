<?php

namespace App\Services;

class PermissionService
{
    public static function check(string $slug): bool
    {
        if (session('crm_user_role') === 'Super Admin') {
            return true;
        }

        $permissions = session('crm_permissions', []);

        if ($permissions === ['*']) {
            return true;
        }

        return in_array($slug, $permissions);
    }

    public static function role(): string
    {
        return session('crm_user_role', '');
    }

    public static function isSuperAdmin(): bool
    {
        return session('crm_user_role') === 'Super Admin';
    }

    /**
     * Check if the user has ANY of the given permission slugs.
     */
    public static function hasAny(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (self::check($slug)) return true;
        }
        return false;
    }

    /**
     * Check if the user has NONE of the given permission slugs.
     */
    public static function hasNone(array $slugs): bool
    {
        return !self::hasAny($slugs);
    }

    /**
     * True when the user only has restaurant permissions and no hotel-wide
     * permissions (guests, rooms, bookings, payments, reports, settings, etc.).
     * Used to show a stripped-down "kitchen / F&B" dashboard.
     */
    public static function isRestaurantOnly(): bool
    {
        if (self::isSuperAdmin()) return false;

        $perms = session('crm_permissions', []);
        if ($perms === ['*']) return false;
        if (empty($perms)) return false;

        $hotelWide = [
            'guests.view', 'guests.manage',
            'rooms.view', 'rooms.manage',
            'bookings.view', 'bookings.manage',
            'checkin.process', 'checkout.process',
            'payments.view', 'invoices.view',
            'reports.view',
            'settings.view', 'settings.manage',
            'roles.view', 'users.view',
            'activity_log.view',
            'data.truncate',
            'backup.restore',
        ];

        // If user has ANY hotel-wide permission, they are NOT restaurant-only
        foreach ($hotelWide as $p) {
            if (in_array($p, $perms)) return false;
        }

        // Only has restaurant.* permissions
        return true;
    }

    /**
     * Get all permissions the current user has.
     */
    public static function permissions(): array
    {
        if (self::isSuperAdmin()) return ['*'];
        return session('crm_permissions', []);
    }
}
