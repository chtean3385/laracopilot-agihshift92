<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use BelongsToHotel;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'hotel_id',
        'trigger_event',
        'template_name',
        'message_body',
        'variables_hint',
        'is_active',
        'approval_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public static function approvalStatuses(): array
    {
        return [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }
}
