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
        // Priority rule for checkout.done (and any future events with PDF variants):
        // 1. If the PDF variant (has_document_attachment=true) is ACTIVE → use it exclusively.
        // 2. Only if the PDF variant is NOT active → fall back to the text-only template.
        // This means a hotel can control which message guests receive simply by toggling
        // the PDF variant on/off — no ambiguity, no double-sending.
        $pdf = static::where('trigger_event', $event)
            ->where('has_document_attachment', true)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if ($pdf) {
            return $pdf;
        }

        return static::where('trigger_event', $event)
            ->where('has_document_attachment', false)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();
    }

    public static function globalForEvent(string $event): ?static
    {
        // Same priority rule as forEvent() — PDF variant wins when active.
        $pdf = static::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('trigger_event', $event)
            ->where('has_document_attachment', true)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if ($pdf) {
            return $pdf;
        }

        return static::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('trigger_event', $event)
            ->where('has_document_attachment', false)
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
            'ota_booking_confirmed' => 'OTA Booking Confirmed (Email Parser)',
            'ota_booking_conflict'  => 'OTA Booking Conflict (Email Parser)',
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
