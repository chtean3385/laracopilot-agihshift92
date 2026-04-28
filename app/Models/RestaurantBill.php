<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class RestaurantBill extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'order_id',
        'booking_id',
        'bill_number',
        'bill_type',
        'payment_method',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_reference',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'   => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'paid_at'    => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(RestaurantOrder::class, 'order_id');
    }

   public function booking()
{
    return $this->belongsTo(\App\Models\Booking::class, 'booking_id');
}

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    // Helpers
    public function isRoomCharge(): bool
    {
        return $this->bill_type === 'room';
    }

    public function isDirect(): bool
    {
        return $this->bill_type === 'direct';
    }

    public function paymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash'  => 'Cash',
            'card'  => 'Card',
            'upi'   => 'UPI',
            'room'  => 'Added to Room Bill',
            default => 'Pending',
        };
    }

    // Generate unique bill number
    public static function generateBillNumber(): string
    {
        do {
            $number = 'RB-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('bill_number', $number)->exists());

        return $number;
    }
}