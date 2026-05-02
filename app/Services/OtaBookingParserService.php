<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\OtaImportedBooking;
use App\Models\OtaSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtaBookingParserService
{
    public function handle(string $senderPhone, string $messageBody, OtaSource $otaSource): void
    {
        try {
            $parsed = $this->parse($messageBody, $otaSource->message_pattern_key);

            if (!$parsed) {
                Log::warning('OtaBookingParser: could not parse message from ' . $senderPhone, [
                    'ota'     => $otaSource->name,
                    'message' => mb_substr($messageBody, 0, 300),
                ]);
                return;
            }

            [$hotelId, $matchedBy] = $this->resolveHotel($senderPhone, $parsed['property_name'] ?? null);

            if (!$hotelId) {
                Log::warning('OtaBookingParser: could not resolve hotel for sender ' . $senderPhone, [
                    'property_name' => $parsed['property_name'] ?? null,
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
                'hotel_id'       => $hotelId,
                'ota_source_id'  => $otaSource->id,
                'raw_message'    => $messageBody,
                'booking_ref'    => $bookingRef,
                'guest_name'     => $parsed['guest_name']     ?? null,
                'guest_phone'    => $parsed['guest_phone']    ?? null,
                'checkin'        => $parsed['checkin']        ?? null,
                'checkout'       => $parsed['checkout']       ?? null,
                'room_type'      => $parsed['room_type']      ?? null,
                'guests_count'   => $parsed['guests_count']   ?? null,
                'amount'         => $parsed['amount']         ?? null,
                'special_request'=> $parsed['special_request']?? null,
                'ota_name'       => $otaSource->name,
                'property_name'  => $parsed['property_name']  ?? null,
                'matched_by'     => $matchedBy,
                'status'         => 'pending',
            ]);

            Log::info('OtaBookingParser: new pending import created for hotel #' . $hotelId . ' from ' . $otaSource->name, [
                'booking_ref' => $bookingRef,
                'guest'       => $parsed['guest_name'] ?? 'Unknown',
            ]);

        } catch (\Throwable $e) {
            Log::error('OtaBookingParserService error: ' . $e->getMessage(), [
                'sender'  => $senderPhone,
                'ota'     => $otaSource->name,
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

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

        if (preg_match('/Property:\s*(.+)/i', $body, $m))       $result['property_name']  = trim($m[1]);
        if (preg_match('/OTA:\s*(.+)/i', $body, $m))            $result['ota_label']       = trim($m[1]);
        if (preg_match('/Booking Ref:\s*(.+)/i', $body, $m))    $result['booking_ref']     = trim($m[1]);
        if (preg_match('/Guest Name:\s*(.+)/i', $body, $m))     $result['guest_name']      = trim($m[1]);
        if (preg_match('/Guest Phone:\s*(.+)/i', $body, $m))    $result['guest_phone']     = preg_replace('/[^0-9+]/', '', trim($m[1]));
        if (preg_match('/Check-?in:\s*(.+)/i', $body, $m))      $result['checkin']         = $this->parseDate(trim($m[1]));
        if (preg_match('/Check-?out:\s*(.+)/i', $body, $m))     $result['checkout']        = $this->parseDate(trim($m[1]));
        if (preg_match('/Room(?:\s+Type)?:\s*(.+)/i', $body, $m)) $result['room_type']     = trim($m[1]);
        if (preg_match('/Guests?:\s*(.+)/i', $body, $m))        $result['guests_count']    = trim($m[1]);
        if (preg_match('/Amount:\s*[₹Rs\.]*\s*([\d,]+)/i', $body, $m)) $result['amount']  = (float) str_replace(',', '', $m[1]);
        if (preg_match('/Special Request:\s*(.+)/i', $body, $m)) $result['special_request'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseBookingCom(string $body): ?array
    {
        $result = $this->parseGeneric($body);
        if (!$result) $result = [];

        if (preg_match('/reservation\s+(?:number|#|no\.?)[:.]?\s*([A-Z0-9\-]+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);
        if (preg_match('/property[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['property_name'] = trim($m[1]);
        if (preg_match('/guest[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['guest_name'] = trim($m[1]);
        if (preg_match('/arrival[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['checkin'] = $this->parseDate(trim($m[1]));
        if (preg_match('/departure[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['checkout'] = $this->parseDate(trim($m[1]));

        return !empty($result) ? $result : null;
    }

    private function parseAirbnb(string $body): ?array
    {
        $result = $this->parseGeneric($body);
        if (!$result) $result = [];

        if (preg_match('/confirmation code[:\s]+([A-Z0-9]+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);
        if (preg_match('/listing[:\s]+(.+?)(?:\n|$)/i', $body, $m))
            $result['property_name'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseAgoda(string $body): ?array
    {
        $result = $this->parseGeneric($body);
        if (!$result) $result = [];

        if (preg_match('/booking id[:\s]+(\d+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseMakeMyTrip(string $body): ?array
    {
        $result = $this->parseGeneric($body);
        if (!$result) $result = [];

        if (preg_match('/booking id[:\s]+(NHT\w+)/i', $body, $m))
            $result['booking_ref'] = trim($m[1]);

        return !empty($result) ? $result : null;
    }

    private function parseGoibibo(string $body): ?array
    {
        return $this->parseMakeMyTrip($body);
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

    private function resolveHotel(string $senderPhone, ?string $propertyName): array
    {
        $normalized = preg_replace('/[^0-9]/', '', $senderPhone);
        $short      = strlen($normalized) > 10 ? substr($normalized, -10) : $normalized;

        $hotelIds = DB::table('hotels')
            ->where(function ($q) use ($normalized, $short) {
                $q->whereRaw("regexp_replace(phone, '[^0-9]', '', 'g') = ?", [$normalized])
                  ->orWhereRaw("right(regexp_replace(phone, '[^0-9]', '', 'g'), 10) = ?", [$short]);
            })
            ->pluck('id')
            ->toArray();

        if (!empty($propertyName)) {
            $propNormalized = strtolower(trim($propertyName));

            $candidates = DB::table('hotels')
                ->where(function ($q) use ($propNormalized) {
                    $q->whereRaw('LOWER(name) = ?', [$propNormalized])
                      ->orWhereRaw('LOWER(ota_alias) = ?', [$propNormalized])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%' . $propNormalized . '%'])
                      ->orWhereRaw('LOWER(ota_alias) LIKE ?', ['%' . $propNormalized . '%']);
                })
                ->pluck('id')
                ->toArray();

            if (!empty($candidates)) {
                $intersect = array_intersect($hotelIds, $candidates);
                if (!empty($intersect)) {
                    return [(int) reset($intersect), 'name_match'];
                }
                return [(int) reset($candidates), 'name_match'];
            }
        }

        if (count($hotelIds) === 1) {
            return [(int) $hotelIds[0], 'number_only'];
        }

        if (count($hotelIds) > 1) {
            Log::warning('OtaBookingParser: ambiguous hotel match for sender ' . $senderPhone . ' (' . count($hotelIds) . ' hotels share this number)');
            return [(int) $hotelIds[0], 'ambiguous'];
        }

        $platform = DB::table('platform_whatsapp_settings')->first();
        if ($platform) {
            $platformNorm = preg_replace('/[^0-9]/', '', $platform->saas_phone_number ?? '');
            if ($platformNorm && ($platformNorm === $normalized || substr($platformNorm, -10) === $short)) {
                $demoHotel = DB::table('hotels')->orderBy('id')->value('id');
                if ($demoHotel) return [(int) $demoHotel, 'platform_number'];
            }
        }

        return [null, 'unresolved'];
    }

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
}
