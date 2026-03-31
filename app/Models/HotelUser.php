<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelUser extends Model
{
    protected $fillable = [
        'hotel_id', 'user_id', 'role', 'is_hotel_admin', 'status',
    ];

    protected $casts = [
        'is_hotel_admin' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
