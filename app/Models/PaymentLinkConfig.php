<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLinkConfig extends Model
{
    protected $fillable = [
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
