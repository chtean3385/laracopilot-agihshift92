<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestCheckinRequest;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Setting;
use App\Jobs\SendWhatsAppEvent;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QrCheckinAdminController extends Controller
{
    private function requireAuth()
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($r = $this->requireAuth()) return $r;

        $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');

        $query = GuestCheckinRequest::where('hotel_id', $hotelId)
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'converted' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();
        $pendingCount = GuestCheckinRequest::where('hotel_id', $hotelId)->where('status', 'pending')->count();

        return view('admin.qr-arrivals.index', compact('requests', 'pendingCount'));
    }

    public function show($id)
    {
        if ($r = $this->requireAuth()) return $r;

        $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');
        $guestRequest = GuestCheckinRequest::where('hotel_id', $hotelId)->findOrFail($id);

        $availableRooms = Room::where('hotel_id', $hotelId)
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();

        return view('admin.qr-arrivals.show', compact('guestRequest', 'availableRooms'));
    }

    public function assign(Request $request, $id)
    {
        if ($r = $this->requireAuth()) return $r;

        $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');
        $guestRequest = GuestCheckinRequest::where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->findOrFail($id);

        $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'check_in_date'    => 'required|date',
            'check_out_date'   => 'required|date|after:check_in_date',
            'adults'           => 'nullable|integer|min:1',
            'advance_payment'  => 'nullable|numeric|min:0',
            'payment_method'   => 'nullable|string',
        ]);

        $room = Room::where('hotel_id', $hotelId)->findOrFail($request->room_id);

        // Create or find customer
        $customer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('phone', $guestRequest->phone)
            ->whereNull('deleted_at')
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'hotel_id'      => $hotelId,
                'name'          => $guestRequest->name,
                'phone'         => $guestRequest->phone,
                'email'         => $guestRequest->email,
                'id_type'       => $guestRequest->id_type,
                'id_number'     => $guestRequest->id_number,
                'address'       => $guestRequest->address,
                'date_of_birth' => $guestRequest->date_of_birth,
            ]);
        }

        // Calculate total amount (per-night pricing)
        $checkin  = Carbon::parse($request->check_in_date);
        $checkout = Carbon::parse($request->check_out_date);
        $nights   = max(1, $checkin->diffInDays($checkout));
        $total    = $nights * ($room->price_per_night ?? 0);
        $advance  = (float) ($request->advance_payment ?? 0);

        $hotelPrefix  = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
        $bookingNumber = $hotelPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));

        $booking = Booking::create([
            'hotel_id'        => $hotelId,
            'booking_number'  => $bookingNumber,
            'customer_id'     => $customer->id,
            'room_id'         => $room->id,
            'check_in_date'   => $request->check_in_date,
            'check_out_date'  => $request->check_out_date,
            'booking_date'    => now()->toDateString(),
            'nights'          => $nights,
            'adults'          => $request->adults ?? $guestRequest->guests_count,
            'children'        => 0,
            'total_amount'    => $total,
            'advance_payment' => $advance,
            'balance_due'     => max(0, $total - $advance),
            'status'          => 'confirmed',
            'payment_status'  => $advance >= $total ? 'paid' : ($advance > 0 ? 'partial' : 'pending'),
            'special_requests'=> $guestRequest->notes,
            'source'          => 'qr_checkin',
            'checkout_token'  => (string) Str::uuid(),
        ]);

        // Record advance payment if any
        if ($advance > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $customer->id,
                'amount'         => $advance,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Advance from QR check-in',
                'transaction_id' => $hotelPrefix . '-TXN-' . strtoupper(substr(uniqid(), -8)),
            ]);
        }

        // Mark the room as confirmed/occupied
        $room->update(['status' => 'occupied']);

        // Mark request as converted
        $guestRequest->update([
            'status'      => 'converted',
            'customer_id' => $customer->id,
            'booking_id'  => $booking->id,
        ]);

        ActivityLogger::log(
            'QR Check-In Assigned',
            'Check-In',
            'Room ' . $room->room_number . ' assigned to ' . $customer->name . ' via QR check-in (Booking #' . $bookingNumber . ')'
        );

        // Fire WhatsApp check-in confirmation (same event as manual check-in)
        try {
            SendWhatsAppEvent::dispatch('checkin.done', $booking->id, (int) $hotelId);
        } catch (\Throwable $e) {
            // WA failure must not block room assignment
        }

        return redirect()->route('qr-arrivals.index')
            ->with('success', 'Room ' . $room->room_number . ' assigned to ' . $customer->name . '. Booking #' . $bookingNumber . ' created. WhatsApp confirmation sent.');
    }

    public function cancel(Request $request, $id)
    {
        if ($r = $this->requireAuth()) return $r;

        $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');
        $guestRequest = GuestCheckinRequest::where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->findOrFail($id);

        $guestRequest->update([
            'status' => 'cancelled',
            'notes'  => $request->reason ?: 'Cancelled by staff',
        ]);

        return redirect()->route('qr-arrivals.index')
            ->with('success', 'Check-in request for ' . $guestRequest->name . ' has been cancelled.');
    }

    public function printQr()
    {
        if ($r = $this->requireAuth()) return $r;

        $hotelId = session('crm_hotel_id') ?? session('crm_sa_hotel_filter');
        $hotel   = Hotel::findOrFail($hotelId);
        $settings = Setting::where('hotel_id', $hotelId)->first();
        $url = url('/g/checkin/' . $hotel->slug);

        return view('admin.qr-arrivals.print-qr', compact('hotel', 'settings', 'url'));
    }
}
