<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'direction',
        'event_type',
        'phone',
        'hotel_id',
        'status',
        'payload',
        'notes',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public static function record(
        string $direction,
        string $eventType,
        string $status = 'ok',
        array  $payload = [],
        ?string $phone = null,
        ?int    $hotelId = null,
        ?string $notes = null
    ): void {
        try {
            static::create([
                'direction'  => $direction,
                'event_type' => $eventType,
                'status'     => $status,
                'payload'    => $payload,
                'phone'      => $phone,
                'hotel_id'   => $hotelId,
                'notes'      => $notes,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WhatsAppLog::record failed: ' . $e->getMessage());
        }
    }
}
