<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckOutController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Booking::with(['customer', 'room'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date');
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%$s%")->orWhere('type', 'like', "%$s%"));
            });
        }
        $pendingCheckouts = $query->paginate(12)->withQueryString();
        return view('admin.checkout.index', compact('pendingCheckouts'));
    }

    public function show($bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $booking = Booking::with(['customer', 'room', 'payments'])->findOrFail($bookingId);

        $checkinDate  = Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->startOfDay();
        $checkoutDate = Carbon::parse($booking->check_out_date)->startOfDay();
        $actualNights = $checkinDate->diffInDays($checkoutDate);

        if ($actualNights < 1) {
            $actualNights = $booking->nights;
        }

        $actualTotal = $actualNights * $booking->room->price_per_night;
        $totalPaid   = $booking->payments->where('status', 'completed')->sum('amount');
        $balanceDue  = max(0, $actualTotal - $totalPaid);

        return view('admin.checkout.show', compact(
            'booking', 'actualNights', 'actualTotal', 'totalPaid', 'balanceDue'
        ));
    }

    public function process(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'final_payment'  => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $booking = Booking::with(['room', 'payments', 'customer'])->findOrFail($bookingId);

        if ($request->final_payment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $booking->customer_id,
                'amount'         => $request->final_payment,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'final',
                'status'         => 'completed',
                'notes'          => 'Final payment at check-out',
                'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
            ]);
        }

        $totalPaid = $booking->payments()->where('status', 'completed')->sum('amount');

        $settings   = Setting::first();
        $taxRate    = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float) $settings->tax_rate : 0;
        $gstAmount  = round($booking->total_amount * ($taxRate / 100), 2);
        $grandTotal = $booking->total_amount + $gstAmount;

        $isPaid     = $totalPaid >= $grandTotal;
        $balance    = max(0, $grandTotal - $totalPaid);

        $booking->update([
            'status'             => 'checked_out',
            'actual_checkout_at' => now(),
            'payment_status'     => $isPaid ? 'paid' : 'partial',
            'balance_due'        => $balance,
            'checkout_notes'     => $request->notes,
        ]);

        $booking->room->update(['status' => 'available']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV' . strtoupper(substr(uniqid(), -6)),
            'booking_id'     => $booking->id,
            'customer_id'    => $booking->customer_id,
            'total_amount'   => $booking->total_amount,
            'paid_amount'    => $totalPaid,
            'balance'        => $balance,
            'status'         => $isPaid ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
            'issued_at'      => now(),
        ]);

        ActivityLogger::log('Checked Out', 'Check-Out', 'Checked out: ' . $booking->customer->name . ' — Room ' . $booking->room->room_number . ' (Invoice #' . $invoice->invoice_number . ')');

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Check-out complete! Invoice generated.');
    }
}
