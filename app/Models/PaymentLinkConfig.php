<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class PaymentLinkConfig extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'upi_id', 'upi_name', 'upi_enabled',
        'razorpay_key_id', 'razorpay_key_secret', 'razorpay_enabled',
    ];

    protected $casts = [
        'upi_enabled'       => 'boolean',
        'razorpay_enabled'  => 'boolean',
    ];

    public static function getConfig(): self
    {
        return self::firstOrCreate([], [
            'upi_enabled'      => false,
            'razorpay_enabled' => false,
        ]);
    }
}
