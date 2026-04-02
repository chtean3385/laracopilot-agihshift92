<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAddOn extends Model
{
    protected $fillable = [
        'booking_id', 'add_on_id', 'name', 'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
