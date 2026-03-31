<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use BelongsToHotel;

    const UPDATED_AT = null;

    protected $fillable = [
        'hotel_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'module',
        'description',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
