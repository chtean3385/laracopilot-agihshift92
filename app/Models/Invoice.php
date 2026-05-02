<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToHotel, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'invoice_number', 'booking_id', 'customer_id',
        'total_amount', 'paid_amount', 'balance', 'status', 'issued_at', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'balance'      => 'decimal:2',
        'issued_at'    => 'datetime',
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