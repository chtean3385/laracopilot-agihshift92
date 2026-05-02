<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Module;
use App\Models\OtaImportedBooking;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OtaBookingController extends Controller
{
    public function index()
    {
        if (!Module::isEnabled('ota_whatsapp_sync')) {
            abort(403, 'OTA WhatsApp Sync module is not enabled for this hotel.');
        }

        $counts = [
            'pending'   => OtaImportedBooking::where('status', 'pending')->count(),
            'confirmed' => OtaImportedBooking::where('status', 'confirmed')->count(),
            'rejected'  => OtaImportedBooking::where('status', 'rejected')->count(),
            'duplicate' => OtaImportedBooking::where('status', 'duplicate')->count(),
        ];

        $imports = OtaImportedBooking::with('otaSource')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.ota-bookings.index', compact('imports', 'counts'));
    }

    public function confirm(Request $request, OtaImportedBooking $import)
    {
        if (!Module::isEnabled('ota_whatsapp_sync')) {
            return response()->json(['success' => false, 'message' => 'Module not enabled.'], 403);
        }

        if ($import->status === 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Already confirmed.']);
        }

        $hotelId = session('crm_hotel_id');
        if ((int) $import->hotel_id !== (int) $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        DB::transaction(function () use ($import, $hotelId, $request) {
            $guestPhone = $import->guest_phone ?? '';
            $guestName  = $import->guest_name  ?? 'OTA Guest';

            $customer = null;
            if ($guestPhone) {
                $normalized = preg_replace('/[^0-9]/', '', $guestPhone);
                $short      = strlen($normalized) > 10 ? substr($normalized, -10) : $normalized;
                $customer   = Customer::where(function ($q) use ($normalized, $short) {
                    $q->whereRaw("regexp_replace(phone, '[^0-9]', '', 'g') = ?", [$normalized])
                      ->orWhereRaw("right(regexp_replace(phone, '[^0-9]', '', 'g'), 10) = ?", [$short]);
                })->first();
            }

            if (!$customer) {
                $customer = Customer::create([
                    'hotel_id'   => $hotelId,
                    'name'       => $guestName,
                    'phone'      => $import->guest_phone ?? '',
                    'email'      => null,
                    'id_type'    => null,
                    'id_number'  => null,
                    'address'    => null,
                ]);
            }

            $prefix        = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
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
                'hotel_id'       => $hotelId,
                'booking_number' => $bookingNumber,
                'customer_id'    => $customer->id,
                'room_id'        => $room?->id,
                'check_in_date'  => $checkin,
                'check_out_date' => $checkout,
                'nights'         => $nights,
                'adults'         => $adults,
                'children'       => 0,
                'total_amount'   => $import->amount ?? 0,
                'advance_payment'=> 0,
                'balance_due'    => $import->amount ?? 0,
                'special_requests'=> $import->special_request,
                'source'         => 'ota',
                'ota_ref'        => $import->booking_ref,
                'ota_name'       => $import->ota_name,
                'status'         => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $import->update([
                'status'     => 'confirmed',
                'booking_id' => $booking->id,
            ]);

            ActivityLogger::log('Created', 'Booking', 'OTA booking #' . $bookingNumber . ' imported from ' . $import->ota_name . ' for ' . $guestName);
        });

        return response()->json(['success' => true, 'message' => 'Booking created successfully.']);
    }

    public function reject(OtaImportedBooking $import)
    {
        if (!Module::isEnabled('ota_whatsapp_sync')) {
            return response()->json(['success' => false, 'message' => 'Module not enabled.'], 403);
        }

        $hotelId = session('crm_hotel_id');
        if ((int) $import->hotel_id !== (int) $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        $import->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Import rejected.']);
    }

    public function update(Request $request, OtaImportedBooking $import)
    {
        $hotelId = session('crm_hotel_id');
        if ((int) $import->hotel_id !== (int) $hotelId) {
            return response()->json(['success' => false, 'message' => 'Unauthorised.'], 403);
        }

        $data = $request->validate([
            'guest_name'     => 'nullable|string|max:200',
            'guest_phone'    => 'nullable|string|max:30',
            'checkin'        => 'nullable|date',
            'checkout'       => 'nullable|date',
            'room_type'      => 'nullable|string|max:200',
            'guests_count'   => 'nullable|string|max:50',
            'amount'         => 'nullable|numeric|min:0',
            'special_request'=> 'nullable|string|max:1000',
        ]);

        $import->update($data);

        return response()->json(['success' => true, 'message' => 'Import updated.']);
    }
}
