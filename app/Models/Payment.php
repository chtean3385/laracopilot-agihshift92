<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'booking_id', 'customer_id', 'amount',
        'payment_method', 'payment_type', 'status',
        'transaction_id', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}