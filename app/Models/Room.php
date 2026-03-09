<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number', 'type', 'capacity', 'price_per_night',
        'floor', 'view', 'amenities', 'description', 'status',
        'has_breakfast', 'breakfast_price',
        'has_lunch',     'lunch_price',
        'has_dinner',    'dinner_price',
        'has_extra_bed', 'extra_bed_price',
    ];

    protected $casts = [
        'price_per_night'  => 'decimal:2',
        'capacity'         => 'integer',
        'floor'            => 'integer',
        'has_breakfast'    => 'boolean',
        'breakfast_price'  => 'decimal:2',
        'has_lunch'        => 'boolean',
        'lunch_price'      => 'decimal:2',
        'has_dinner'       => 'boolean',
        'dinner_price'     => 'decimal:2',
        'has_extra_bed'    => 'boolean',
        'extra_bed_price'  => 'decimal:2',
    ];

    public function hasMeals(): bool
    {
        return $this->has_breakfast || $this->has_lunch || $this->has_dinner;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}