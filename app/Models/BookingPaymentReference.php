<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPaymentReference extends Model
{
    protected $fillable = [
        'booking_id', 'payment_type', 'reference_number',
        'amount', 'submitted_by', 'notes',
        'verified', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
