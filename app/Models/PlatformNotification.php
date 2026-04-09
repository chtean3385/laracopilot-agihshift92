<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformNotification extends Model
{
    protected $table = 'platform_notifications';

    protected $fillable = [
        'title', 'body', 'icon_url', 'action_url',
        'target', 'target_ids',
        'sent_count', 'delivered_count', 'sent_by', 'sent_at',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'sent_at'    => 'datetime',
    ];
}
