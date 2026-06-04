<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\GuestCheckinRequest;
use App\Models\Setting;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuestCheckinController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $settings = Setting::where('hotel_id', $hotel->id)->first();

        // If the hotel has disabled QR self check-in, return a friendly message.
        if ($settings && $settings->qr_checkin_enabled === false) {
            return response(view('guest.qr-disabled', [
                'title'   => 'Self Check-In Not Available',
                'message' => 'This hotel handles check-in at the front desk. Please speak to our staff on arrival — we\'re happy to assist!',
                'icon'    => 'fa-door-open',
            ]), 200);
        }

        $bookingRef = $request->query('ref', '');
        return view('guest.checkin', compact('hotel', 'settings', 'slug', 'bookingRef'));
    }

    public function lookup(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $phone = trim($request->query('phone', ''));
        if (strlen($phone) < 5) {
            return response()->json(['found' => false]);
        }
        $customer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('phone', $phone)
            ->whereNull('deleted_at')
            ->first();

        if (!$customer) {
            return response()->json(['found' => false]);
        }

        // Return prior guest details for autofill. Sensitive fields (address, ID details)
        // are included to satisfy the returning-guest pre-fill requirement. The lookup
        // endpoint is rate-limited (10 req/min per IP) in routes/web.php to prevent
        // enumeration abuse.
        return response()->json([
            'found'         => true,
            'name'          => $customer->name,
            'email'         => $customer->email ?? '',
            'address'       => $customer->address ?? '',
            'id_type'       => $customer->id_type ?? '',
            'id_number'     => $customer->id_number ?? '',
            'dob'           => $customer->date_of_birth?->format('Y-m-d') ?? '',
            'has_id_doc'    => !empty($customer->id_document_path),
            'has_signature' => !empty($customer->signature),
            'arrival_city'  => $customer->arrival_city ?? '',
            'dispatch_city' => $customer->dispatch_city ?? '',
            'travel_reason' => $customer->travel_reason ?? '',
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        // Verify reuse flags server-side — client cannot bypass mandatory fields by
        // spoofing these flags; we only honour them if the customer actually has
        // a stored artifact in the database.
        $reuseDocRequested = $request->boolean('reuse_id_document');
        $reuseSigRequested = $request->boolean('reuse_signature');

        // Always look up existing customer by phone so we can update their profile.
        $existingCustomer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('phone', trim($request->input('phone', '')))
            ->whereNull('deleted_at')
            ->first();

        $reuseDoc = $reuseDocRequested && $existingCustomer && !empty($existingCustomer->id_document_path);
        $reuseSig = $reuseSigRequested && $existingCustomer && !empty($existingCustomer->signature);

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'phone'               => 'required|string|max:30',
            'email'               => 'nullable|email|max:255',
            'id_type'             => 'nullable|string|max:100',
            'id_number'           => 'nullable|string|max:100',
            'address'             => 'nullable|string|max:500',
            'date_of_birth'       => 'nullable|date',
            'id_document'         => ($reuseDoc ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf,heic,heif|max:1024',
            'signature_data'      => ($reuseSig ? 'nullable' : 'required') . '|string',
            'requested_check_in'  => 'nullable|date',
            'requested_check_out' => 'nullable|date|after_or_equal:requested_check_in',
            'guests_count'        => 'nullable|integer|min:1|max:50',
            'additional_guests'   => 'nullable|array',
            'additional_guests.*.name'      => 'required_with:additional_guests|string|max:255',
            'additional_guests.*.id_type'   => 'nullable|string|max:100',
            'additional_guests.*.id_number' => 'nullable|string|max:100',
            'booking_ref'         => 'nullable|string|max:50',
            'arrival_city'        => 'nullable|string|max:100',
            'dispatch_city'       => 'nullable|string|max:100',
            'travel_reason'       => 'nullable|string|max:100',
        ]);

        // Resolve ID document — store bytes in DB (survives deployments)
        $docContent = null;
        $docMime    = null;
        $docPath    = null;
        if ($request->hasFile('id_document')) {
            $idFile     = $request->file('id_document');
            $docContent = base64_encode(file_get_contents($idFile->getRealPath()));
            $docMime    = $idFile->getMimeType();
            $docPath    = '';
        } elseif ($reuseDoc && $existingCustomer) {
            $docContent = $existingCustomer->id_document_content ?? null;
            $docMime    = $existingCustomer->id_document_mime ?? null;
            $docPath    = '';
        }

        // Resolve signature — new drawing takes priority, otherwise reuse from customer profile
        if (!empty($validated['signature_data'])) {
            $sigData = $validated['signature_data'];
        } elseif ($reuseSig && $existingCustomer) {
            $sigData = $existingCustomer->signature;
        } else {
            $sigData = null;
        }

        // ── Look up existing booking by ref (from the WhatsApp link) ──────────────
        $linkedBooking  = null;
        $linkedBookingId = null;
        $bookingRef = trim($validated['booking_ref'] ?? '');
        if ($bookingRef) {
            $linkedBooking = Booking::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('booking_number', $bookingRef)
                ->first();
            $linkedBookingId = $linkedBooking?->id;
        }

        // ── Update (or create) the Customer profile immediately ───────────────────
        // This is the fix for "guest fills form but profile stays empty".
        $profileData = [
            'name'          => $validated['name'],
            'email'         => $validated['email'] ?? null,
            'id_type'       => $validated['id_type'] ?? null,
            'id_number'     => $validated['id_number'] ?? null,
            'address'       => $validated['address'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'arrival_city'  => $validated['arrival_city'] ?? null,
            'dispatch_city' => $validated['dispatch_city'] ?? null,
            'travel_reason' => $validated['travel_reason'] ?? null,
        ];
        if ($docPath !== null) $profileData['id_document_path']    = $docPath;
        if ($docContent)      $profileData['id_document_content'] = $docContent;
        if ($docMime)         $profileData['id_document_mime']    = $docMime;
        if ($sigData)         $profileData['signature']           = $sigData;

        if ($existingCustomer) {
            // Only overwrite fields the guest actually provided (don't blank out existing data).
            $updateData = array_filter($profileData, fn($v) => $v !== null && $v !== '');
            $existingCustomer->update($updateData);
            $customerId = $existingCustomer->id;
        } else {
            // New guest — create the Customer record right now.
            $newCustomer = Customer::create(array_merge($profileData, [
                'hotel_id' => $hotel->id,
                'phone'    => $validated['phone'],
            ]));
            $customerId = $newCustomer->id;
        }

        // ── Determine status: if linked to an existing booking → auto-converted ───
        // No need for staff to "assign room" — room is already in the booking.
        // Prevent duplicate QR requests for the same booking_ref.
        if ($linkedBookingId) {
            $existingQr = GuestCheckinRequest::where('hotel_id', $hotel->id)
                ->where('booking_id', $linkedBookingId)
                ->first();

            if ($existingQr) {
                // Already submitted for this booking — just update the record.
                $existingQr->update([
                    'name'             => $validated['name'],
                    'email'            => $validated['email'] ?? null,
                    'id_type'          => $validated['id_type'] ?? null,
                    'id_number'        => $validated['id_number'] ?? null,
                    'address'          => $validated['address'] ?? null,
                    'date_of_birth'    => $validated['date_of_birth'] ?? null,
                    'id_document_path'    => $docPath ?? $existingQr->id_document_path,
                    'id_document_content' => $docContent ?? $existingQr->id_document_content,
                    'id_document_mime'    => $docMime    ?? $existingQr->id_document_mime,
                    'signature_data'      => $sigData    ?? $existingQr->signature_data,
                    'customer_id'      => $customerId,
                    'status'           => 'converted',
                ]);
                $qrRecord = $existingQr;
            } else {
                $qrRecord = GuestCheckinRequest::create([
                    'hotel_id'            => $hotel->id,
                    'name'                => $validated['name'],
                    'phone'               => $validated['phone'],
                    'email'               => $validated['email'] ?? null,
                    'id_type'             => $validated['id_type'] ?? null,
                    'id_number'           => $validated['id_number'] ?? null,
                    'address'             => $validated['address'] ?? null,
                    'date_of_birth'       => $validated['date_of_birth'] ?? null,
                    'id_document_path'    => $docPath,
                    'id_document_content' => $docContent,
                    'id_document_mime'    => $docMime,
                    'signature_data'      => $sigData,
                    'additional_guests'   => $validated['additional_guests'] ?? null,
                    'requested_check_in'  => $linkedBooking->check_in_date ?? null,
                    'requested_check_out' => $linkedBooking->check_out_date ?? null,
                    'guests_count'        => $validated['guests_count'] ?? 1,
                    'customer_id'         => $customerId,
                    'booking_id'          => $linkedBookingId,
                    'status'              => 'converted', // Room already assigned in booking
                ]);
            }
        } else {
            // No booking ref — walk-in / new request; staff will assign a room.
            $qrRecord = GuestCheckinRequest::create([
                'hotel_id'            => $hotel->id,
                'name'                => $validated['name'],
                'phone'               => $validated['phone'],
                'email'               => $validated['email'] ?? null,
                'id_type'             => $validated['id_type'] ?? null,
                'id_number'           => $validated['id_number'] ?? null,
                'address'             => $validated['address'] ?? null,
                'date_of_birth'       => $validated['date_of_birth'] ?? null,
                'id_document_path'    => $docPath,
                'id_document_content' => $docContent,
                'id_document_mime'    => $docMime,
                'signature_data'      => $sigData,
                'additional_guests'   => $validated['additional_guests'] ?? null,
                'requested_check_in'  => $validated['requested_check_in'] ?? null,
                'requested_check_out' => $validated['requested_check_out'] ?? null,
                'guests_count'        => $validated['guests_count'] ?? 1,
                'customer_id'         => $customerId,
                'status'              => 'pending',
            ]);

            // Notify staff only for walk-in (pending) requests — booked guests don't need room assignment.
            try {
                $fcm    = app(FcmService::class);
                $tokens = $fcm->getTokensForHotel($hotel->id);
                if (!empty($tokens)) {
                    $fcm->sendToTokens(
                        $tokens,
                        '🛎️ New QR Check-In Request',
                        $validated['name'] . ' has submitted a self check-in form. Assign a room now.',
                        ['url' => url('/qr-arrivals')]
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[GuestCheckin] FCM push failed: ' . $e->getMessage());
            }
        }

        $settings = Setting::where('hotel_id', $hotel->id)->first();
        $refId    = $qrRecord->id;

        return view('guest.checkin-success', compact('hotel', 'settings', 'validated', 'refId'));
    }
}
