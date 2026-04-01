<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(string $action, string $module, string $description): void
    {
        try {
            $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');

            ActivityLog::create([
                'hotel_id'    => $hotelId ? (int) $hotelId : null,
                'user_name'   => session('crm_user_name', 'System'),
                'user_email'  => session('crm_user_email', ''),
                'user_role'   => session('crm_user_role', ''),
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityLogger failed: ' . $e->getMessage());
        }
    }
}
