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
        'has_document_attachment',
        'approval_status',
        'meta_template_id',
        'meta_status',
    ];

    protected $casts = [
        'is_active'                => 'boolean',
        'has_document_attachment'  => 'boolean',
    ];

    public static function forEvent(string $event): ?static
    {
        return static::where('trigger_event', $event)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();
    }

    public static function globalForEvent(string $event): ?static
    {
        return static::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('trigger_event', $event)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();
    }

    public function extractVariableNames(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->message_body ?? '', $matches);
        return array_values(array_unique($matches[1]));
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

    public static function metaStatuses(): array
    {
        return [
            'not_submitted' => 'Not Submitted',
            'submitted'     => 'Submitted',
            'approved'      => 'Approved',
            'rejected'      => 'Rejected',
        ];
    }

    public function convertBodyForMeta(): string
    {
        $body = $this->message_body;
        $counter = 1;
        $body = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use (&$counter) {
            return '{{' . $counter++ . '}}';
        }, $body);
        return $body;
    }
}
