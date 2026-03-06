<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Payment::with(['booking.customer', 'booking.room']);
        if ($request->payment_method) $query->where('payment_method', $request->payment_method);
        if ($request->date_from)      $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)        $query->whereDate('created_at', '<=', $request->date_to);
        $payments     = $query->orderBy('created_at', 'desc')->paginate(20);
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        return view('admin.payments.index', compact('payments', 'totalRevenue'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $bookings = Booking::with('customer')->whereIn('status', ['confirmed', 'checked_in'])->get();
        return view('admin.payments.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $validated = $request->validate([
            'booking_id'     => 'required|exists:bookings,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque',
            'payment_type'   => 'required|in:advance,partial,final,refund',
            'notes'          => 'nullable|string',
        ]);
        $booking = Booking::findOrFail($validated['booking_id']);
        $payment = Payment::create(array_merge($validated, [
            'customer_id'    => $booking->customer_id,
            'status'         => 'completed',
            'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
        ]));
        $totalPaid = $booking->payments()->where('status', 'completed')->sum('amount');
        $booking->update([
            'balance_due'    => max(0, $booking->total_amount - $totalPaid),
            'payment_status' => $totalPaid >= $booking->total_amount ? 'paid' : 'partial',
        ]);
        return redirect()->route('payments.show', $payment->id)->with('success', 'Payment recorded!');
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $payment = Payment::with(['booking.customer', 'booking.room'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }
}