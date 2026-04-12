<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaInboxConversation extends Model
{
    protected $table = 'wa_inbox_conversations';

    protected $fillable = [
        'hotel_id',
        'phone',
        'last_message_at',
        'last_message_preview',
        'unread_count',
        'last_24h_reset_at',
    ];

    protected $casts = [
        'last_message_at'   => 'datetime',
        'last_24h_reset_at' => 'datetime',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isWithin24hWindow(): bool
    {
        if (!$this->last_24h_reset_at) {
            return false;
        }
        return $this->last_24h_reset_at->diffInHours(now()) < 24;
    }
}
