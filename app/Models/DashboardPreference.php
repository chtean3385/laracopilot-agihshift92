<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardPreference extends Model
{
    protected $fillable = [
        'hotel_id',
        'user_id',
        'preferences',
        'is_hotel_default',
    ];

    protected $casts = [
        'preferences'      => 'array',
        'is_hotel_default' => 'boolean',
    ];
}
