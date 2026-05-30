<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Customer;
use App\Models\GuestCheckinRequest;
use App\Models\Setting;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuestCheckinController extends Controller
{
    public function show(string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $settings = Setting::where('hotel_id', $hotel->id)->first();
        return view('guest.checkin', compact('hotel', 'settings', 'slug'));
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

        $existingCustomer = null;
        if ($reuseDocRequested || $reuseSigRequested) {
            $existingCustomer = Customer::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('phone', trim($request->input('phone', '')))
                ->whereNull('deleted_at')
                ->first();
        }

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
            'id_document'         => ($reuseDoc ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf,heic,heif|max:5120',
            'signature_data'      => ($reuseSig ? 'nullable' : 'required') . '|string',
            'requested_check_in'  => 'nullable|date',
            'requested_check_out' => 'nullable|date|after_or_equal:requested_check_in',
            'guests_count'        => 'nullable|integer|min:1|max:50',
            'additional_guests'   => 'nullable|array',
            'additional_guests.*.name'      => 'required_with:additional_guests|string|max:255',
            'additional_guests.*.id_type'   => 'nullable|string|max:100',
            'additional_guests.*.id_number' => 'nullable|string|max:100',
        ]);

        // Resolve ID document path — new upload takes priority, otherwise reuse from customer profile
        if ($request->hasFile('id_document')) {
            $docPath = $request->file('id_document')->store('guest-checkin-docs/' . $hotel->id, 'public');
        } elseif ($reuseDoc) {
            $existing = Customer::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('phone', $validated['phone'])
                ->whereNull('deleted_at')
                ->value('id_document_path');
            $docPath = $existing ?? null;
        } else {
            $docPath = null;
        }

        // Resolve signature — new drawing takes priority, otherwise reuse from customer profile
        if (!empty($validated['signature_data'])) {
            $sigData = $validated['signature_data'];
        } elseif ($reuseSig) {
            $sigData = Customer::withoutGlobalScopes()
                ->where('hotel_id', $hotel->id)
                ->where('phone', $validated['phone'])
                ->whereNull('deleted_at')
                ->value('signature') ?? null;
        } else {
            $sigData = null;
        }

        GuestCheckinRequest::create([
            'hotel_id'            => $hotel->id,
            'name'                => $validated['name'],
            'phone'               => $validated['phone'],
            'email'               => $validated['email'] ?? null,
            'id_type'             => $validated['id_type'] ?? null,
            'id_number'           => $validated['id_number'] ?? null,
            'address'             => $validated['address'] ?? null,
            'date_of_birth'       => $validated['date_of_birth'] ?? null,
            'id_document_path'    => $docPath,
            'signature_data'      => $sigData,
            'additional_guests'   => $validated['additional_guests'] ?? null,
            'requested_check_in'  => $validated['requested_check_in'] ?? null,
            'requested_check_out' => $validated['requested_check_out'] ?? null,
            'guests_count'        => $validated['guests_count'] ?? 1,
            'status'              => 'pending',
        ]);

        // Fire push notification to all hotel staff devices
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

        $settings = Setting::where('hotel_id', $hotel->id)->first();
        $refId = GuestCheckinRequest::where('hotel_id', $hotel->id)
            ->where('phone', $validated['phone'])
            ->orderByDesc('id')
            ->value('id');

        return view('guest.checkin-success', compact('hotel', 'settings', 'validated', 'refId'));
    }
}
