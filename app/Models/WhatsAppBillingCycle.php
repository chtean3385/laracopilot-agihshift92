<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBillingCycle extends Model
{
    protected $fillable = [
        'hotel_id', 'period_label', 'period_start', 'period_end',
        'message_count', 'rate_per_message', 'amount',
        'status', 'paid_at', 'paid_by', 'notes',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'rate_per_message' => 'decimal:4',
        'amount'           => 'decimal:2',
        'paid_at'          => 'datetime',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
