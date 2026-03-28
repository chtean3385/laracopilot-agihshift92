<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'trigger_event',
        'template_name',
        'message_body',
        'variables_hint',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function forEvent(string $event): ?static
    {
        return static::where('trigger_event', $event)->where('is_active', true)->first();
    }

    public static function allEvents(): array
    {
        return [
            'booking.created'    => 'Booking Confirmed',
            'checkin.tomorrow'   => 'Check-In Reminder (Day Before)',
            'checkin.done'       => 'Arrival Welcome (On Check-In)',
            'payment.received'   => 'Payment Received',
            'checkout.done'      => 'Check-Out Thank You + Bill',
            'feedback.request'   => 'Feedback Request (2 Days After Stay)',
        ];
    }
}
