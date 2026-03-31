<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(string $action, string $module, string $description): void
    {
        try {
            ActivityLog::create([
                'hotel_id'    => session('crm_hotel_id'),
                'user_name'   => session('crm_user_name', 'System'),
                'user_email'  => session('crm_user_email', ''),
                'user_role'   => session('crm_user_role', ''),
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
