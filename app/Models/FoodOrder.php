<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class FoodOrder extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'order_number', 'room_number', 'booking_id',
        'guest_name', 'guest_notes', 'status', 'total_amount',
        'approved_by', 'approved_at', 'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'approved_at'  => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(FoodOrderItem::class, 'order_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'     => 'Pending',
            'in_progress' => 'In Progress',
            'approved'    => 'Approved',
            'cancelled'   => 'Cancelled',
            default       => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'     => '#f59e0b',
            'in_progress' => '#3b82f6',
            'approved'    => '#16a34a',
            'cancelled'   => '#dc2626',
            default       => '#64748b',
        };
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'FM-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::withoutGlobalScopes()->where('order_number', $number)->exists());

        return $number;
    }
}
