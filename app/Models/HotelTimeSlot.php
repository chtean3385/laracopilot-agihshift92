<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class HotelTimeSlot extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'name', 'start_time', 'end_time',
        'is_overnight', 'base_price', 'description',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'is_active'    => 'boolean',
        'base_price'   => 'decimal:2',
        'sort_order'   => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_time');
    }

    public function getTimeRangeLabelAttribute(): string
    {
        $end = $this->is_overnight ? $this->end_time . ' (next day)' : $this->end_time;
        return $this->start_time . ' – ' . $end;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'time_slot_id');
    }
}
