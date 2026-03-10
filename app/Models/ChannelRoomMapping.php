<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelRoomMapping extends Model
{
    protected $fillable = [
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
