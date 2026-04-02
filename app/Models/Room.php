<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_number', 'type', 'capacity', 'price_per_night',
        'pricing_type', 'hourly_rate',
        'floor', 'view', 'amenities', 'description', 'status',
        'has_breakfast', 'breakfast_price',
        'has_lunch',     'lunch_price',
        'has_dinner',    'dinner_price',
        'has_extra_bed', 'extra_bed_price',
    ];

    protected $casts = [
        'price_per_night'  => 'decimal:2',
        'hourly_rate'      => 'decimal:2',
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

    public function isPerSlot(): bool  { return $this->pricing_type === 'per_slot'; }
    public function isPerHour(): bool  { return $this->pricing_type === 'per_hour'; }
    public function isPerNight(): bool { return $this->pricing_type === 'per_night'; }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(HotelTimeSlot::class, 'hotel_id', 'hotel_id')
                    ->withoutGlobalScopes()
                    ->where('is_active', true)
                    ->orderBy('base_price');
    }

    public function addOns()
    {
        return $this->hasMany(RoomAddOn::class)->where(function ($q) {
            $q->where('room_id', $this->id)->orWhereNull('room_id');
        })->where('is_active', true);
    }

    public function channelMapping()
    {
        return $this->hasOne(\App\Models\ChannelRoomMapping::class);
    }
}