<?php

namespace App\Services;

use App\Models\OtaImportedBooking;
use App\Models\OtaSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtaBookingParserService
{
    /**
     * Entry point called from the WhatsApp webhook.
     *
     * @param  string       $senderPhone          The FROM number (OTA platform's number).
     * @param  string       $messageBody          Raw message text.
     * @param  OtaSource    $otaSource            Matched OTA source record.
     * @param  string|null  $recipientPhoneNumberId  Meta phone_number_id of the RECEIVING WA Business number.
     */
    public function handle(
        string $senderPhone,
        string $messageBody,
        OtaSource $otaSource,
        ?string $recipientPhoneNumberId = null
    ): void {
        try {
            $parsed = $this->parse($messageBody, $otaSource->message_pattern_key);

            if (!$parsed) {
                Log::warning('OtaBookingParser: could not parse message from ' . $senderPhone, [
                    'ota'     => $otaSource->name,
                    'message' => mb_substr($messageBody, 0, 300),
                ]);
                return;
            }

            [$hotelId, $matchedBy] = $this->resolveHotel(
                $recipientPhoneNumberId,
                $parsed['property_name'] ?? null
            );

            if (!$hotelId) {
                Log::warning('OtaBookingParser: could not resolve hotel', [
                    'sender'          => $senderPhone,
                    'recipient_pnid'  => $recipientPhoneNumberId,
                    'property_name'   => $parsed['property_name'] ?? null,
                ]);
                return;
            }

            if (!$this->moduleEnabled($hotelId)) {
                Log::info('OtaBookingParser: ota_whatsapp_sync module disabled for hotel #' . $hotelId);
                return;
            }

            $bookingRef = $parsed['booking_ref'] ?? null;
            if ($bookingRef && $this->isDuplicate($bookingRef, $hotelId)) {
                OtaImportedBooking::create([
                    'hotel_id'      => $hotelId,
                    'ota_source_id' => $otaSource->id,
                    'raw_message'   => $messageBody,
                    'booking_ref'   => $bookingRef,
                    'ota_name'      => $otaSource->name,
                    'property_name' => $parsed['property_name'] ?? null,
                    'matched_by'    => $matchedBy,
                    'status'        => 'duplicate',
                ]);
                Log::info('OtaBookingParser: duplicate booking ref ' . $bookingRef . ' for hotel #' . $hotelId);
                return;
            }

            OtaImportedBooking::create([
                'hotel_id'        => $hotelId,
                'ota_source_id'   => $otaSource->id,
                'raw_message'     => $messageBody,
                'booking_ref'     => $bookingRef,
                'guest_name'      => $parsed['guest_name']      ?? null,
                'guest_phone'     => $parsed['guest_phone']      ?? null,
                'checkin'         => $parsed['checkin']          ?? null,
                'checkout'        => $parsed['checkout']         ?? null,
                'room_type'       => $parsed['room_type']        ?? null,
                'guests_count'    => $parsed['guests_count']     ?? null,
                'amount'          => $parsed['amount']           ?? null,
                'special_request' => $parsed['special_request']  ?? null,
                'ota_name'        => $otaSource->name,
                'property_name'   => $parsed['property_name']    ?? null,
                'matched_by'      => $matchedBy,
                'status'          => 'pending',
            ]);

            Log::info('OtaBookingParser: new pending import created for hotel #' . $hotelId . ' from ' . $otaSource->name, [
                'booking_ref' => $bookingRef,
                'guest'       => $parsed['guest_name'] ?? 'Unknown',
                'matched_by'  => $matchedBy,
            ]);

        } catch (\Throwable $e) {
            Log::error('OtaBookingParserService error: ' . $e->getMessage(), [
                'sender'  => $senderPhone,
                'ota'     => $otaSource->name,
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Parsers ───────────────────────────────────────────────────────────────

    private function parse(string $body, string $patternKey): ?array
    {
        return match ($patternKey) {
            'booking_com' => $this->parseBookingCom($body),
            'airbnb'      => $this->parseAirbnb($body),
            'agoda'       => $this->parseAgoda($body),
            'makemytrip'  => $this->parseMakeMyTrip($body),
            'goibibo'     => $this->parseGoibibo($body),
            default       => $this->parseGeneric($body),
        };
    }

    private function parseGeneric(string $body): ?array
    {
        $result = [];

        if (preg_match('/Property:\s*(.+)/i', $body, $m))        $result['property_name']   = trim($m[1]);
        if (preg_match('/OTA:\s*(.+)/i', $body, $m))             $result['ota_label']        = trim($m[1]);
        if (preg_match('/Booking Ref:\s*(.+)/i', $body, $m))     $result['booking_ref']      = trim($m[1]);
        if (preg_match('/Guest Name:\s*(.+)/i', $body, $m))      $result['guest_name']       = trim($m[1]);
        if (preg_match('/Guest Phone:\s*(.+)/i', $body, $m))     $result['guest_phone']      = preg_replace('/[^0-9+]/', '', trim($m[1]));
        if (preg_match('/Check-?in:\s*(.+)/i', $body, $m))       $result['checkin']          = $this->parseDate(trim($m[1]));
        if (preg_match('/Check-?out:\s*(.+)/i', $body, $m))      $result['checkout']         = $this->parseDate(trim($m[1]));
        if (preg_match('/Room(?:\s+Type)?:\s*(.+)/i', $body, $m)) $result['room_type']       = trim($m[1]);
        if (preg_match('/Guests?:\s*(.+)/i', $body, $m))         $result['guests_count']     = trim($m[1]);
        if (preg_match('/Amount:\s*[₹Rs\.]*\s*([\d,]+)/i', $body, $m)) $result['amount']     = (float) str_replace(',', '', $m[1]);
        if (preg_match('/Special Request:\s*(.+)/i', $body, $m)) $result['special_request']  = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseBookingCom(string $body): ?array
    {
        $result = $this->parseGeneric($body) ?? [];

        if (preg_match('/reservation\s+(?:number|#|no\.?)[:.]?\s*([A-Z0-9\-]+)/i', $body, $m))
            $result['booking_ref']   = trim($m[1]);
        if (preg_match('/property[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['property_name'] = trim($m[1]);
        if (preg_match('/guest[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['guest_name']    = trim($m[1]);
        if (preg_match('/arrival[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['checkin']       = $this->parseDate(trim($m[1]));
        if (preg_match('/departure[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['checkout']      = $this->parseDate(trim($m[1]));

        return !empty($result) ? $result : null;
    }

    private function parseAirbnb(string $body): ?array
    {
        $result = $this->parseGeneric($body) ?? [];

        if (preg_match('/confirmation code[:\s]+([A-Z0-9]+)/i', $body, $m))
            $result['booking_ref']   = trim($m[1]);
        if (preg_match('/listing[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['property_name'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseAgoda(string $body): ?array
    {
        $result = $this->parseGeneric($body) ?? [];

        if (preg_match('/booking id[:\s]+(\d+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseMakeMyTrip(string $body): ?array
    {
        $result = $this->parseGeneric($body) ?? [];

        if (preg_match('/booking id[:\s]+(NHT\w+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseGoibibo(string $body): ?array
    {
        return $this->parseMakeMyTrip($body);
    }

    // ── Hotel resolution ──────────────────────────────────────────────────────

    /**
     * Resolve the destination hotel using the Meta phone_number_id of the RECEIVING
     * WhatsApp Business number, then optionally narrow by property name.
     *
     * Resolution order:
     *  1. Look up whatsapp_configs by phone_number_id → single hotel (per-hotel WA config).
     *     Constrain property-name matching to that hotel + its children.
     *  2. Check platform_whatsapp_settings.saas_phone_number_id (shared SaaS number).
     *     Use property name to narrow across all OTA-sync-enabled hotels.
     *     Only return a hotel if property name uniquely identifies exactly one.
     *  3. If phone_number_id is null (legacy / no metadata) → property-name-only across all hotels,
     *     but only when exactly ONE hotel matches (no ambiguous fallback).
     *
     * Returns [hotel_id|null, matched_by].
     */
    private function resolveHotel(?string $recipientPhoneNumberId, ?string $propertyName): array
    {
        // ── Step 1: per-hotel WA config match ────────────────────────────────
        if ($recipientPhoneNumberId) {
            $waConfig = DB::table('whatsapp_configs')
                ->where('phone_number_id', $recipientPhoneNumberId)
                ->first();

            if ($waConfig) {
                $rootId = (int) $waConfig->hotel_id;

                // Build candidate pool: root hotel + direct children
                $candidates = DB::table('hotels')
                    ->where(function ($q) use ($rootId) {
                        $q->where('id', $rootId)
                          ->orWhere('parent_hotel_id', $rootId);
                    })
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();

                if (!empty($propertyName)) {
                    $match = $this->matchByPropertyName($propertyName, $candidates);
                    if ($match) return [$match, 'name_match'];
                }

                // Single hotel in pool → direct match
                if (count($candidates) === 1) {
                    return [$candidates[0], 'wa_config'];
                }

                // Multiple children — require property name to disambiguate
                if (count($candidates) > 1) {
                    Log::warning('OtaBookingParser: multiple hotels under WA config #' . $waConfig->id . ' — property name required to disambiguate', [
                        'candidates' => $candidates,
                        'property'   => $propertyName,
                    ]);
                    // Fall back to root hotel (message came to root's WA number)
                    return [$rootId, 'wa_config_root'];
                }
            }

            // ── Step 2: platform/SaaS shared number ──────────────────────────
            $platform = DB::table('platform_whatsapp_settings')->first();
            if ($platform && $platform->saas_phone_number_id === $recipientPhoneNumberId) {
                // Shared number — property name REQUIRED to identify the hotel
                if (!empty($propertyName)) {
                    $match = $this->matchByPropertyName($propertyName, null);
                    if ($match !== null) return [$match, 'platform_number_name'];
                }

                Log::warning('OtaBookingParser: message arrived on shared SaaS number but no property name matched', [
                    'property' => $propertyName,
                ]);
                return [null, 'unresolved'];
            }
        }

        // ── Step 3: no phone_number_id (fallback) ─────────────────────────────
        // Only allow if property name uniquely identifies exactly one hotel
        if (!empty($propertyName)) {
            $match = $this->matchByPropertyName($propertyName, null);
            if ($match !== null) return [$match, 'name_only'];
        }

        return [null, 'unresolved'];
    }

    /**
     * Match $propertyName against hotels.name / hotels.ota_alias.
     * If $candidateIds is provided, search is constrained to those hotel IDs only.
     * Returns the hotel ID only when exactly ONE hotel matches (strict: no silent multi-match).
     */
    private function matchByPropertyName(string $propertyName, ?array $candidateIds): ?int
    {
        $norm = strtolower(trim($propertyName));

        $query = DB::table('hotels')
            ->where('status', 'active')
            ->where(function ($q) use ($norm) {
                $q->whereRaw('LOWER(name) = ?', [$norm])
                  ->orWhereRaw('LOWER(ota_alias) = ?', [$norm])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%' . $norm . '%'])
                  ->orWhereRaw('LOWER(ota_alias) LIKE ?', ['%' . $norm . '%']);
            });

        if (!empty($candidateIds)) {
            $query->whereIn('id', $candidateIds);
        }

        $matches = $query->pluck('id')->map(fn($id) => (int) $id)->toArray();

        if (count($matches) === 1) {
            return $matches[0];
        }

        if (count($matches) > 1) {
            Log::warning('OtaBookingParser: property name "' . $propertyName . '" matched ' . count($matches) . ' hotels — cannot auto-assign', [
                'candidates' => $matches,
            ]);
        }

        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function moduleEnabled(int $hotelId): bool
    {
        return \App\Models\Module::isEnabledForHotel('ota_whatsapp_sync', $hotelId);
    }

    private function isDuplicate(string $bookingRef, int $hotelId): bool
    {
        return OtaImportedBooking::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('booking_ref', $bookingRef)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
    }

    private function parseDate(string $str): ?string
    {
        if (!$str) return null;
        $str = trim($str);

        foreach ([
            'd M Y', 'd F Y', 'Y-m-d', 'd/m/Y', 'm/d/Y',
            'd-m-Y', 'D, d M Y', 'j M Y', 'j F Y',
        ] as $format) {
            $d = \DateTime::createFromFormat($format, $str);
            if ($d) return $d->format('Y-m-d');
        }

        try {
            return (new \DateTime($str))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
