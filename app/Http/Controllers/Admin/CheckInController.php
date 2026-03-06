<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckInController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $pendingCheckins = Booking::with(['customer', 'room'])
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', '<=', Carbon::today())
            ->orderBy('check_in_date')
            ->get();
        return view('admin.checkin.index', compact('pendingCheckins'));
    }

    public function show($bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['customer', 'room', 'payments'])->findOrFail($bookingId);
        return view('admin.checkin.show', compact('booking'));
    }

    public function process(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $request->validate([
            'additional_payment' => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);
        $booking = Booking::with('room')->findOrFail($bookingId);
        $booking->update([
            'status'           => 'checked_in',
            'actual_checkin_at'=> now(),
            'checkin_notes'    => $request->notes,
        ]);
        $booking->room->update(['status' => 'occupied']);
        if ($request->additional_payment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $booking->customer_id,
                'amount'         => $request->additional_payment,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Payment at check-in',
                'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
            ]);
            $totalPaid = $booking->advance_payment + $request->additional_payment;
            $booking->update([
                'advance_payment' => $totalPaid,
                'balance_due'     => max(0, $booking->total_amount - $totalPaid),
                'payment_status'  => $totalPaid >= $booking->total_amount ? 'paid' : 'partial',
            ]);
        }
        return redirect()->route('checkin.index')->with('success', 'Check-in completed for ' . $booking->customer->name . '!');
    }
}