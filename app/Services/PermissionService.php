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
}
