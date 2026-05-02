<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaSource extends Model
{
    protected $table = 'ota_sources';

    protected $fillable = [
        'name',
        'sender_number',
        'waba_id',
        'message_pattern_key',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find an active OTA source by the sender's WhatsApp number (FROM field in webhook).
     * The waba_id column is reserved for future use when Meta exposes a reliable
     * sender-side business identifier; it is NOT matched against entry.id (receiving WABA).
     */
    public static function findBySender(string $phone): ?static
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        if (!$normalized) return null;

        return static::where('is_active', true)
            ->whereNotNull('sender_number')
            ->whereRaw("regexp_replace(sender_number, '[^0-9]', '', 'g') = ?", [$normalized])
            ->first();
    }

    /**
     * Content-pattern based detection: returns the active Generic catch-all source
     * (message_pattern_key = 'generic', null sender_number) if the body matches OTA format.
     * ONLY targets the generic source — named OTA sources (Booking.com, Airbnb etc.) are
     * exclusively matched by their configured sender_number / waba_id.
     * Used for demo testing and forwarded messages without a known OTA sender number.
     */
    public static function findByContentPattern(string $body): ?static
    {
        // Must have at minimum a property identifier and a booking reference to qualify
        $hasProperty   = (bool) preg_match('/Property\s*:/i', $body);
        $hasBookingRef = (bool) preg_match('/Booking\s+Ref\s*:|Confirmation\s+Code\s*:|Reservation\s+(?:Number|#|No)\s*:/i', $body);

        if (!$hasProperty || !$hasBookingRef) {
            return null;
        }

        // Strictly: only return the Generic catch-all source (pattern_key = 'generic')
        return static::where('is_active', true)
            ->where('message_pattern_key', 'generic')
            ->whereNull('sender_number')
            ->orderBy('id')
            ->first();
    }

    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }
}
