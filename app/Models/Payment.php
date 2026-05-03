<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use App\Support\AnalyticsCache;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToHotel;

    protected static function boot(): void
    {
        parent::boot();

        $bump = function (self $payment) {
            if ($payment->hotel_id) {
                AnalyticsCache::bump((int) $payment->hotel_id);
            }
            $orig = $payment->getOriginal('hotel_id');
            if ($orig && (int) $orig !== (int) $payment->hotel_id) {
                AnalyticsCache::bump((int) $orig);
            }
        };

        static::created($bump);
        static::updated($bump);
        static::deleted($bump);
    }

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