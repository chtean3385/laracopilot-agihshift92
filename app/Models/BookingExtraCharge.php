<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingExtraCharge extends Model
{
    protected $fillable = [
        'booking_id',
        'name',
        'category',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'added_by',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->withTrashed();
    }

    public static function categories(): array
    {
        return [
            'food'       => 'Food & Beverage',
            'drink'      => 'Drinks / Bar',
            'laundry'    => 'Laundry',
            'transport'  => 'Transport',
            'spa'        => 'Spa & Wellness',
            'pharmacy'   => 'Pharmacy / Medical',
            'service'    => 'Room Service',
            'activity'   => 'Activities / Excursions',
            'parking'    => 'Parking',
            'other'      => 'Other',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categories()[$this->category] ?? ucfirst($this->category);
    }
}
