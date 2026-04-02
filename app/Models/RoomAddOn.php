<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class RoomAddOn extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'room_id', 'name', 'price', 'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRoom($query, $roomId)
    {
        return $query->where(function ($q) use ($roomId) {
            $q->where('room_id', $roomId)->orWhereNull('room_id');
        });
    }
}
