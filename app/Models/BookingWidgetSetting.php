<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingWidgetSetting extends Model
{
    protected $fillable = [
        'hotel_id',
        'widget_title', 'primary_color', 'button_text',
        'min_advance_hours', 'max_advance_days',
        'auto_confirm',
        'require_advance_payment', 'advance_payment_amount',
        'upi_id', 'upi_qr_image',
        'default_country_code',
        'show_room_photos', 'show_prices',
        'thank_you_message',
    ];

    protected $casts = [
        'auto_confirm'             => 'boolean',
        'require_advance_payment'  => 'boolean',
        'show_room_photos'         => 'boolean',
        'show_prices'              => 'boolean',
        'advance_payment_amount'   => 'decimal:2',
        'min_advance_hours'        => 'integer',
        'max_advance_days'         => 'integer',
    ];

    public function getWidgetTitleAttribute($value): string
    {
        return $value ?: 'Book Your Stay';
    }

    public function getPrimaryColorAttribute($value): string
    {
        return $value ?: '#6366f1';
    }

    public function getButtonTextAttribute($value): string
    {
        return $value ?: 'Book Now';
    }

    public function getDefaultCountryCodeAttribute($value): string
    {
        return $value ?: 'IN';
    }

    public function getThankYouMessageAttribute($value): string
    {
        return $value ?: 'Thank you! We look forward to welcoming you.';
    }
}
