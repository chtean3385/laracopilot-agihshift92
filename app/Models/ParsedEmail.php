<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class ParsedEmail extends Model
{
    use BelongsToHotel;

    protected $table = 'parsed_emails';

    protected $fillable = [
        'hotel_id',
        'message_uid',
        'subject',
        'sender',
        'raw_body',
        'parsed_data',
        'booking_id',
        'status',
        'fail_reason',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'processed' => '#10b981',
            'pending'   => '#f59e0b',
            'failed'    => '#ef4444',
            'duplicate' => '#94a3b8',
            'skipped'   => '#cbd5e1',
            default     => '#64748b',
        };
    }
}
