<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformFirebaseSetting extends Model
{
    protected $table = 'platform_firebase_settings';

    protected $fillable = [
        'firebase_project_id',
        'firebase_api_key',
        'firebase_messaging_sender_id',
        'firebase_app_id',
        'firebase_vapid_key',
        'fcm_server_key',
        'service_account_json',
        'push_enabled',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
    ];

    public static function instance(): static
    {
        return static::firstOrNew(['id' => 1]);
    }
}
