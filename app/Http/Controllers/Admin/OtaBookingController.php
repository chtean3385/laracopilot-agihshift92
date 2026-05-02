<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Module;
use App\Models\OtaImportedBooking;
use App\Models\OtaSource;
use App\Services\ActivityLogger;
use App\Services\OtaBookingParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtaBookingController extends Controller
{
    /**
     * Resolve the effective hotel ID.
     * If a {hotelId} route parameter is present in the URL, use it (after authorizing
     * that the logged-in user belongs to that hotel). Otherwise fall back to session.
     * Returns null if the user is not authorized for the requested hotel.
     */
    private function resolveHotelId(Request $request): ?int
    {
        $urlHotelId = $request->route('hotelId');

        if ($urlHotelId !== null) {
            $urlHotelId = (int) $urlHotelId;

            // Authorize: the session user must belong to this hotel or be a platform admin
            $sessionHotelId = (int) session('crm_hotel_id');
            $isPlatformAdmin = session('platform_admin_logged_in', false);

            if (!$isPlatformAdmin && $sessionHotelId !== $urlHotelId) {
                return null; // unauthorized
            }

            return $urlHotelId;
        }

        return (int) session('crm_hotel_id') ?: null;
    }

    /**
     * Simulate an incoming OTA WhatsApp message directly (bypasses webhook).
     * Used for testing in dev environments where Meta's webhook points to production.
     * Runs the full parser pipeline: source detection → hotel resolution → import creation.
     */
    public function simulate(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);
        if (!$hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $body = trim($request->input('message', ''));
        if (!$body) {
            return response()->json(['success' => false, 'message' => 'Message body is required.']);
        }

        // Use the platform SaaS phone_number_id as the simulated recipient
        $platform              = DB::table('platform_whatsapp_settings')->first();
        $recipientPhoneNumberId = $platform ? (string) $platform->saas_phone_number_id : null;

        // Simulate a fake sender number (won't match any real OTA source → falls to content-pattern)
        $fakeSenderPhone = '0000000000';

        $otaSource = OtaSource::findBySender($fakeSenderPhone)
                  ?? OtaSource::findByContentPattern($body);

        if (!$otaSource) {
            return response()->json([
                'success' => false,
                'message' => 'Message format not recognised. Make sure it contains "Property:" and "Booking Ref:" lines.',
            ]);
        }

        try {
            (new OtaBookingParserService())->handle(
                $fakeSenderPhone,
                $body,
                $otaSource,
                $recipientPhoneNumberId
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Parser error: ' . $e->getMessage()]);
        }

        // Check if a new pending import was just created
        $latest = OtaImportedBooking::where('hotel_id', $hotelId)
            ->orderByDesc('created_at')
            ->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < 10) {
            $statusMsg = $latest->status === 'duplicate'
                ? 'Message parsed — marked as duplicate (same booking ref already exists).'
                : 'Message parsed and added to the import queue as pending.';
            return response()->json(['success' => true, 'message' => $statusMsg]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Parsed but hotel could not be resolved. Check that "Property: <hotel name>" exactly matches a hotel name in the system and the OTA module is enabled.',
        ]);
    }

    public function index(Request $request)
    {
        $hotelId = $this->resolveHotelId($request);

        if (!$hotelId) {
            abort(403, 'Unauthorized.');
        }

        if (!Module::isEnabledForHotel('ota_whatsapp_sync', $hotelId)) {
            abort(403, 'OTA WhatsApp Sync module is not enabled for this hotel.');
        }

        $counts = [
            'pending'   => OtaImportedBooking::where('hotel_id', $hotelId)->where('status', 'pending')->count(),
            'confirmed' => OtaImportedBooking::where('hotel_id', $hotelId)->where('status', 'confirmed')->count(),
            'rejected'  => OtaImportedBooking::where('hotel_id', $hotelId)->where('status', 'rejected')->count(),
            'duplicate' => OtaImportedBooking::where('hotel_id', $hotelId)->where('status', 'duplicate')->count(),
        ];

        $imports = OtaImportedBooking::with('otaSource')
            ->where('hotel_id', $hotelId)
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.ota-bookings.index', compact('imports', 'counts'));
    }

    public function confirm(Request $request, OtaImportedBooking $import)
    {
        $hotelId = $this->resolveHotelId($request);

        if (!$hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!Module::isEnabledForHotel('ota_whatsapp_sync', $hotelId)) {
            return response()->json(['success' => false, 'message' => 'Module not enabled.'], 403);
        }

        // Ensure the import belongs to the resolved hotel
        if ((int) $import->hotel_id !== $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        if ($import->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending imports can be confirmed.']);
        }

        try {
            DB::transaction(function () use ($import, $hotelId) {
                // Re-check for duplicates inside the transaction (race-condition safety)
                if ($import->booking_ref) {
                    $alreadyExists = DB::table('ota_imported_bookings')
                        ->where('hotel_id', $hotelId)
                        ->where('booking_ref', $import->booking_ref)
                        ->where('status', 'confirmed')
                        ->where('id', '!=', $import->id)
                        ->exists()
                        || DB::table('bookings')
                            ->where('hotel_id', $hotelId)
                            ->where('ota_ref', $import->booking_ref)
                            ->exists();

                    if ($alreadyExists) {
                        $import->update(['status' => 'duplicate']);
                        throw new \RuntimeException('duplicate');
                    }
                }

                $guestPhone = $import->guest_phone ?? '';
                $guestName  = $import->guest_name  ?? 'OTA Guest';

                $customer = null;
                if ($guestPhone) {
                    $normalized = preg_replace('/[^0-9]/', '', $guestPhone);
                    $short      = strlen($normalized) > 10 ? substr($normalized, -10) : $normalized;
                    $customer   = Customer::where('hotel_id', $hotelId)
                        ->where(function ($q) use ($normalized, $short) {
                            $q->whereRaw("regexp_replace(phone, '[^0-9]', '', 'g') = ?", [$normalized])
                              ->orWhereRaw("right(regexp_replace(phone, '[^0-9]', '', 'g'), 10) = ?", [$short]);
                        })->first();
                }

                if (!$customer) {
                    $customer = Customer::create([
                        'hotel_id'  => $hotelId,
                        'name'      => $guestName,
                        'phone'     => $import->guest_phone ?? '',
                        'email'     => null,
                        'id_type'   => null,
                        'id_number' => null,
                        'address'   => null,
                    ]);
                }

                $hotelName     = DB::table('hotels')->where('id', $hotelId)->value('name') ?? 'HOT';
                $prefix        = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $hotelName), 0, 3));
                $bookingNumber = $prefix . '-OTA-' . strtoupper(substr(uniqid(), -6));

                $room = null;
                if ($import->room_type) {
                    $room = DB::table('rooms')
                        ->where('hotel_id', $hotelId)
                        ->where(function ($q) use ($import) {
                            $q->whereRaw('LOWER(type) LIKE ?', ['%' . strtolower($import->room_type) . '%'])
                              ->orWhereRaw('LOWER(room_number) LIKE ?', ['%' . strtolower($import->room_type) . '%']);
                        })
                        ->first();
                }

                $adultsRaw = $import->guests_count ?? '1';
                $adults    = (int) preg_replace('/[^0-9]/', '', explode(' ', $adultsRaw)[0] ?? '1') ?: 1;
                $checkin   = $import->checkin  ?? now()->toDateString();
                $checkout  = $import->checkout ?? now()->addDay()->toDateString();
                $nights    = max(1, (int) now()->parse($checkin)->diffInDays($checkout));

                $booking = Booking::create([
                    'hotel_id'        => $hotelId,
                    'booking_number'  => $bookingNumber,
                    'customer_id'     => $customer->id,
                    'room_id'         => $room?->id,
                    'check_in_date'   => $checkin,
                    'check_out_date'  => $checkout,
                    'nights'          => $nights,
                    'adults'          => $adults,
                    'children'        => 0,
                    'total_amount'    => $import->amount ?? 0,
                    'advance_payment' => 0,
                    'balance_due'     => $import->amount ?? 0,
                    'special_requests'=> $import->special_request,
                    'source'          => 'ota',
                    'ota_ref'         => $import->booking_ref,
                    'ota_name'        => $import->ota_name,
                    'status'          => 'confirmed',
                    'payment_status'  => 'pending',
                ]);

                $import->update([
                    'status'     => 'confirmed',
                    'booking_id' => $booking->id,
                ]);

                ActivityLogger::log('Created', 'Booking', 'OTA booking #' . $bookingNumber . ' imported from ' . $import->ota_name . ' for ' . $guestName);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate') {
                return response()->json(['success' => false, 'message' => 'Duplicate booking reference — import marked as duplicate.']);
            }
            throw $e;
        }

        return response()->json(['success' => true, 'message' => 'Booking created successfully.']);
    }

    public function reject(Request $request, OtaImportedBooking $import)
    {
        $hotelId = $this->resolveHotelId($request);

        if (!$hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!Module::isEnabledForHotel('ota_whatsapp_sync', $hotelId)) {
            return response()->json(['success' => false, 'message' => 'Module not enabled.'], 403);
        }

        if ((int) $import->hotel_id !== $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        $import->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Import rejected.']);
    }

    public function update(Request $request, OtaImportedBooking $import)
    {
        $hotelId = $this->resolveHotelId($request);

        if (!$hotelId || (int) $import->hotel_id !== $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        $data = $request->validate([
            'guest_name'      => 'nullable|string|max:200',
            'guest_phone'     => 'nullable|string|max:30',
            'checkin'         => 'nullable|date',
            'checkout'        => 'nullable|date',
            'room_type'       => 'nullable|string|max:200',
            'guests_count'    => 'nullable|string|max:50',
            'amount'          => 'nullable|numeric|min:0',
            'special_request' => 'nullable|string|max:1000',
        ]);

        $import->update($data);

        return response()->json(['success' => true, 'message' => 'Import updated.']);
    }
}
