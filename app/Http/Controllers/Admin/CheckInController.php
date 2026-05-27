<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Jobs\SendWhatsAppEvent;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Booking::with(['customer', 'room'])
            ->where('status', 'confirmed')
            ->whereNull('group_booking_id')  // hide child bookings; check in via primary
            ->whereDate('check_in_date', '<=', Carbon::today())
            ->orderBy('check_in_date');
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%$s%")->orWhere('type', 'like', "%$s%"));
            });
        }
        $pendingCheckins = $query->paginate(12)->withQueryString();
        return view('admin.checkin.index', compact('pendingCheckins'));
    }

    public function show($bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['customer', 'room', 'payments', 'bookingGuests'])->findOrFail($bookingId);

        // Detect sibling bookings: same guest, same dates, still confirmed
        $siblingBookings = Booking::with('room')
            ->where('customer_id', $booking->customer_id)
            ->whereDate('check_in_date', $booking->check_in_date->toDateString())
            ->whereDate('check_out_date', $booking->check_out_date->toDateString())
            ->where('status', 'confirmed')
            ->where('id', '!=', $booking->id)
            ->get();

        return view('admin.checkin.show', compact('booking', 'siblingBookings'));
    }

    public function process(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $request->validate([
            'additional_payment' => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string',
            'notes'              => 'nullable|string',
            'check_in_all'       => 'nullable|boolean',
        ]);
        $booking = Booking::with('room')->findOrFail($bookingId);

        // Collect all bookings to process (primary + siblings if requested)
        $allBookings = collect([$booking]);
        if ($request->boolean('check_in_all')) {
            $siblings = Booking::with('room')
                ->where('customer_id', $booking->customer_id)
                ->whereDate('check_in_date', $booking->check_in_date->toDateString())
                ->whereDate('check_out_date', $booking->check_out_date->toDateString())
                ->where('status', 'confirmed')
                ->where('id', '!=', $booking->id)
                ->get();
            $allBookings = $allBookings->concat($siblings);
        }

        $hotelPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
        $checkedInRooms = [];

        foreach ($allBookings as $b) {
            $b->update([
                'status'           => 'checked_in',
                'actual_checkin_at'=> now(),
                'checkin_notes'    => $request->notes,
            ]);
            if ($b->room) {
                $b->room->update(['status' => 'occupied']);
            }
            $checkedInRooms[] = $b->is_whole_hotel ? 'Whole Hotel' : ($b->room?->room_number ?? '?');
        }

        // Apply payment only to the primary booking
        if ($request->additional_payment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $booking->customer_id,
                'amount'         => $request->additional_payment,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Payment at check-in',
                'transaction_id' => $hotelPrefix . '-TXN-' . strtoupper(substr(uniqid(), -8)),
            ]);
            $totalPaid = $booking->advance_payment + $request->additional_payment;
            $booking->update([
                'advance_payment' => $totalPaid,
                'balance_due'     => max(0, $booking->total_amount - $totalPaid),
                'payment_status'  => $totalPaid >= $booking->total_amount ? 'paid' : 'partial',
            ]);
        }

        $booking->load('customer');
        $roomSummary = implode(', ', array_map(fn($r) => 'Room ' . $r, $checkedInRooms));
        ActivityLogger::log('Checked In', 'Check-In', 'Checked in: ' . $booking->customer->name . ' — ' . $roomSummary . ' (Booking #' . $booking->booking_number . ')');
        SendWhatsAppEvent::dispatch('checkin.done', $booking->id, (int) $booking->hotel_id);

        $successMsg = count($checkedInRooms) > 1
            ? 'Check-in completed for ' . $booking->customer->name . ' — ' . count($checkedInRooms) . ' rooms checked in!'
            : 'Check-in completed for ' . $booking->customer->name . '!';

        return redirect()->route('checkin.index')->with('success', $successMsg);
    }
}
