<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number', 'type', 'capacity', 'price_per_night',
        'floor', 'view', 'amenities', 'description', 'status',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'capacity'        => 'integer',
        'floor'           => 'integer',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}