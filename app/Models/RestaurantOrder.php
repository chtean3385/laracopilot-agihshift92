<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class RestaurantOrder extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'table_id',
        'booking_id',
        'order_number',
        'status',
        'bill_type',
        'payment_method',
        'payment_status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'billed_at',
        // Task #111 — Guest QR / scan-to-order fields
        'source',              // staff | guest_qr
        'approval_status',     // null | pending | approved | rejected
        'room_number',
        'guest_name',
        'guest_phone',
        'guest_notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal'   => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'billed_at'  => 'datetime',
    ];

    // Relationships
    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

public function booking()
{
    return $this->belongsTo(\App\Models\Booking::class, 'booking_id');
}

    public function items()
    {
        return $this->hasMany(RestaurantOrderItem::class, 'order_id');
    }

    public function bill()
    {
        return $this->hasOne(RestaurantBill::class, 'order_id');
    }

    // Helpers
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'kotted', 'served']);
    }

    public function isGuestQr(): bool
    {
        return $this->source === 'guest_qr';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'open'      => '<span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">Open</span>',
            'kotted'    => '<span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">KOT Printed</span>',
            'served'    => '<span class="px-2 py-1 text-xs rounded bg-purple-100 text-purple-700">Served</span>',
            'billed'    => '<span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Billed</span>',
            'cancelled' => '<span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Cancelled</span>',
            default     => '',
        };
    }

    // Generate unique order number
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }
}