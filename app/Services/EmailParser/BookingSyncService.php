<?php

namespace App\Services\EmailParser;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\OtaBookingConflict;
use App\Models\ParsedEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingSyncService
{
    public function __construct(private EmailParserService $parser)
    {
    }

    /**
     * Process every pending parsed_emails row for the given hotel.
     * Returns a counts array.
     */
    public function processPendingForHotel(int $hotelId): array
    {
        $counts = ['processed' => 0, 'failed' => 0, 'duplicate' => 0, 'skipped' => 0];

        $rows = ParsedEmail::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($rows as $row) {
            try {
                $result = $this->processOne($row, $hotelId);
                $counts[$result] = ($counts[$result] ?? 0) + 1;
            } catch (\Throwable $e) {
                Log::error('BookingSyncService: processOne failed for parsed_email #' . $row->id . ' — ' . $e->getMessage());
                $row->update([
                    'status'      => 'failed',
                    'fail_reason' => mb_substr($e->getMessage(), 0, 1000),
                ]);
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * Returns one of: processed | duplicate | failed | skipped.
     */
    public function processOne(ParsedEmail $row, int $hotelId): string
    {
        $parsed = $this->parser->parse(
            $row->sender ?? '',
            $row->subject ?? '',
            $row->raw_body ?? ''
        );

        if (!$parsed) {
            // Not an OTA booking email — silently skip rather than mark as error.
            $row->update([
                'status'      => 'skipped',
                'fail_reason' => 'No OTA parser matched — not a booking confirmation email.',
            ]);
            return 'skipped';
        }

        $data       = $parsed['data'];
        $otaLabel   = $parsed['ota_label'];
        $externalId = $data['booking_id'] ?? null;

        // ── Required-field validation: never invent dates / guest identity ───
        $missing = [];
        if (empty($data['check_in']))  $missing[] = 'check_in';
        if (empty($data['check_out'])) $missing[] = 'check_out';
        if (empty($externalId) && empty($data['guest_name']) && empty($data['guest_email']) && empty($data['guest_phone'])) {
            $missing[] = 'booking_id_or_guest_identity';
        }
        if (!empty($missing)) {
            $row->update([
                'status'      => 'failed',
                'parsed_data' => $data,
                'fail_reason' => 'Missing required fields: ' . implode(', ', $missing),
            ]);
            return 'failed';
        }

        $checkIn  = $data['check_in'];
        $checkOut = $data['check_out'];

        try {
            $ci = new \DateTime($checkIn);
            $co = new \DateTime($checkOut);
        } catch (\Throwable $e) {
            $row->update([
                'status'      => 'failed',
                'parsed_data' => $data,
                'fail_reason' => 'Unparseable check-in/check-out date.',
            ]);
            return 'failed';
        }
        if ($co <= $ci) {
            $row->update([
                'status'      => 'failed',
                'parsed_data' => $data,
                'fail_reason' => 'check_out is not after check_in.',
            ]);
            return 'failed';
        }

        // ── Atomic dedup + insert: wrap in transaction with re-check ─────────
        if ($externalId) {
            $dup = DB::table('bookings')
                ->where('hotel_id', $hotelId)
                ->where('external_booking_id', $externalId)
                ->exists();

            if ($dup) {
                $row->update([
                    'status'      => 'duplicate',
                    'parsed_data' => $data,
                ]);
                return 'duplicate';
            }
        }

        // ── Customer find-or-create (no duplicates) ──────────────────────────
        $customer = $this->findOrCreateCustomer(
            $hotelId,
            $data['guest_name']  ?? 'OTA Guest',
            $data['guest_email'] ?? null,
            $data['guest_phone'] ?? null
        );

        // ── Room match + availability check ──────────────────────────────────
        $roomType = $data['room_type'] ?? null;

        [$roomId, $conflictType] = $this->matchRoomOrConflict(
            $hotelId,
            $roomType,
            $checkIn,
            $checkOut
        );

        // ── Build booking ────────────────────────────────────────────────────
        $hotelName = DB::table('hotels')->where('id', $hotelId)->value('name') ?? 'HOT';
        $prefix    = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $hotelName), 0, 3)) ?: 'HOT';
        $bookingNo = $prefix . '-EML-' . strtoupper(substr(uniqid(), -6));
        $nights    = max(1, (int) now()->parse($checkIn)->diffInDays($checkOut));

        try {
            $booking = DB::transaction(function () use (
                $hotelId, $externalId, $bookingNo, $customer, $roomId,
                $checkIn, $checkOut, $nights, $data, $otaLabel, $conflictType
            ) {
                if ($externalId) {
                    $exists = DB::table('bookings')
                        ->where('hotel_id', $hotelId)
                        ->where('external_booking_id', $externalId)
                        ->lockForUpdate()
                        ->exists();
                    if ($exists) {
                        throw new \RuntimeException('__DUPLICATE__');
                    }
                }

                return Booking::create([
                    'hotel_id'            => $hotelId,
                    'booking_number'      => $bookingNo,
                    'customer_id'         => $customer->id,
                    'room_id'             => $roomId,
                    'check_in_date'       => $checkIn,
                    'check_out_date'      => $checkOut,
                    'nights'              => $nights,
                    'adults'              => 1,
                    'children'            => 0,
                    'total_amount'        => $data['amount'] ?? 0,
                    'advance_payment'     => 0,
                    'balance_due'         => $data['amount'] ?? 0,
                    'special_requests'    => $data['special_request'] ?? null,
                    'source'              => $otaLabel,
                    'ota_ref'             => $externalId,
                    'ota_name'            => $otaLabel,
                    'external_booking_id' => $externalId,
                    'ota_conflict'        => $conflictType !== null,
                    'status'              => $conflictType ? 'pending_room_assignment' : 'confirmed',
                    'payment_status'      => 'pending',
                ]);
            });
        } catch (\Throwable $e) {
            // Either our explicit duplicate sentinel OR a unique-index violation
            // from a concurrent worker — both mean another row beat us to it.
            $msg = $e->getMessage();
            $isDuplicate = $msg === '__DUPLICATE__'
                || str_contains($msg, 'parsed_emails_hotel_external_booking_unique')
                || str_contains(strtolower($msg), 'duplicate')
                || str_contains(strtolower($msg), 'unique constraint');
            if ($isDuplicate) {
                $row->update([
                    'status'      => 'duplicate',
                    'parsed_data' => $data,
                ]);
                return 'duplicate';
            }
            throw $e;
        }

        $row->update([
            'parsed_data' => $data,
            'booking_id'  => $booking->id,
            'status'      => 'processed',
        ]);

        // ── Conflict record ──────────────────────────────────────────────────
        if ($conflictType) {
            OtaBookingConflict::create([
                'hotel_id'            => $hotelId,
                'booking_id'          => $booking->id,
                'parsed_email_id'     => $row->id,
                'conflict_type'       => $conflictType,
                'requested_room_type' => $roomType,
                'check_in_date'       => $checkIn,
                'check_out_date'      => $checkOut,
                'resolved'            => false,
            ]);
        }

        // ── WhatsApp notification to hotel admin ─────────────────────────────
        $this->notifyAdmin($hotelId, [
            'hotel_name'      => $hotelName,
            'guest_name'      => $customer->name,
            'source'          => $otaLabel,
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'booking_number'  => $bookingNo,
            'is_conflict'     => $conflictType !== null,
            'conflict_reason' => $this->conflictReason($conflictType),
        ]);

        return 'processed';
    }

    private function findOrCreateCustomer(int $hotelId, string $name, ?string $email, ?string $phone): Customer
    {
        $base = Customer::withoutGlobalScopes()->where('hotel_id', $hotelId);

        // 1. Email match (case-insensitive, portable).
        if ($email) {
            $emailLower = strtolower(trim($email));
            $candidates = (clone $base)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get(['id', 'email']);
            foreach ($candidates as $c) {
                if (strtolower((string) $c->email) === $emailLower) {
                    return Customer::withoutGlobalScopes()->find($c->id);
                }
            }
        }

        // 2. Phone match — normalise in PHP so it works on any DB driver.
        if ($phone) {
            $normalized = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($normalized) >= 7) {
                $shortNeedle = strlen($normalized) > 10 ? substr($normalized, -10) : $normalized;
                $candidates = (clone $base)
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->get(['id', 'phone']);
                foreach ($candidates as $c) {
                    $candNorm = preg_replace('/[^0-9]/', '', (string) $c->phone);
                    if ($candNorm === '') continue;
                    if ($candNorm === $normalized) {
                        return Customer::withoutGlobalScopes()->find($c->id);
                    }
                    $candShort = strlen($candNorm) > 10 ? substr($candNorm, -10) : $candNorm;
                    if ($candShort === $shortNeedle) {
                        return Customer::withoutGlobalScopes()->find($c->id);
                    }
                }
            }
        }

        return Customer::create([
            'hotel_id'    => $hotelId,
            'name'        => $name ?: 'OTA Guest',
            'email'       => $email,
            'phone'       => $phone ?: '',
            'id_type'     => 'aadhaar',
            'id_number'   => '',
            'address'     => null,
            'nationality' => 'Indian',
            'country'     => 'India',
        ]);
    }

    /**
     * Match a free room for (hotel, type, dates).
     * The OTA value is tokenized and compared to each room's normalized type
     * (e.g. "Deluxe Room" / "Deluxe Suite King" both match a room with type "deluxe").
     * Returns [room_id|null, conflict_type|null].
     */
    private function matchRoomOrConflict(int $hotelId, ?string $roomType, string $checkIn, string $checkOut): array
    {
        if (!$roomType || trim($roomType) === '') {
            return [null, 'no_room_matched'];
        }

        $tokens = $this->normaliseTokens($roomType);
        if (empty($tokens)) {
            return [null, 'no_room_matched'];
        }

        $rooms = DB::table('rooms')
            ->where('hotel_id', $hotelId)
            ->where('status', 'available')
            ->get(['id', 'type', 'room_number']);

        $candidateIds = [];
        $typeMatched  = false;
        foreach ($rooms as $r) {
            $roomTokens = $this->normaliseTokens(($r->type ?? '') . ' ' . ($r->room_number ?? ''));
            $typeOnly   = $this->normaliseTokens($r->type ?? '');
            foreach ($tokens as $t) {
                if (in_array($t, $roomTokens, true)) {
                    $candidateIds[] = (int) $r->id;
                    if (in_array($t, $typeOnly, true)) {
                        $typeMatched = true;
                    }
                    break;
                }
            }
        }
        $candidateIds = array_values(array_unique($candidateIds));

        if (empty($candidateIds)) {
            return [null, 'room_type_unavailable'];
        }

        $busy = DB::table('bookings')
            ->whereIn('room_id', $candidateIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'pending_room_assignment'])
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->pluck('room_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->all();

        $free = array_values(array_diff($candidateIds, $busy));
        if (!empty($free)) {
            return [$free[0], null];
        }

        return [null, $typeMatched ? 'dates_overlap' : 'no_room_matched'];
    }

    /**
     * Lowercase, strip non-alpha-numerics, drop generic stopwords like "room"/"suite".
     */
    private function normaliseTokens(string $value): array
    {
        $stop = ['room', 'rooms', 'suite', 'suites', 'with', 'and', 'the', 'a', 'an', 'view', 'bed'];
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        return array_values(array_filter($parts, fn($p) => $p !== '' && !in_array($p, $stop, true)));
    }

    private function conflictReason(?string $type): ?string
    {
        return match ($type) {
            'dates_overlap'         => 'All matching rooms are booked for these dates',
            'room_type_unavailable' => 'Requested room type unavailable',
            'no_room_matched'       => 'No matching room found in inventory',
            default                 => null,
        };
    }

    private function notifyAdmin(int $hotelId, array $vars): void
    {
        $adminPhone = DB::table('hotel_users')
            ->join('users', 'users.id', '=', 'hotel_users.user_id')
            ->where('hotel_users.hotel_id', $hotelId)
            ->where('hotel_users.is_hotel_admin', true)
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->orderBy('hotel_users.id')
            ->value('users.phone');

        if (!$adminPhone) {
            Log::info('EmailParser: no admin phone for hotel #' . $hotelId . ' — skipping WA notification');
            return;
        }

        if ($vars['is_conflict']) {
            $event  = 'ota_booking_conflict';
            $params = [
                $vars['hotel_name'],
                $vars['guest_name'],
                $vars['source'],
                $vars['check_in'],
                $vars['check_out'],
                $vars['conflict_reason'] ?? 'Manual room assignment required',
            ];
        } else {
            $event  = 'ota_booking_confirmed';
            $params = [
                $vars['hotel_name'],
                $vars['guest_name'],
                $vars['source'],
                $vars['check_in'],
                $vars['check_out'],
                $vars['booking_number'],
            ];
        }

        \App\Services\WhatsApp\WhatsAppService::sendTemplateForHotel(
            $hotelId,
            $adminPhone,
            $event,
            $params
        );
    }
}
