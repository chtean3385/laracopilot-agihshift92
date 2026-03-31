<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class ChannelRoomMapping extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_id', 'channel_room_code', 'channel_rate_plan', 'extra_bed_rate',
    ];

    protected $casts = [
        'extra_bed_rate' => 'decimal:2',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
