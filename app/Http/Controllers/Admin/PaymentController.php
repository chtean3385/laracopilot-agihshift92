<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\WhatsApp\WhatsAppService;
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
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_id', 'like', "%$s%")
                  ->orWhere('amount', 'like', "%$s%")
                  ->orWhereHas('booking', fn($b) => $b->where('booking_number', 'like', "%$s%"))
                  ->orWhereHas('booking.customer', fn($c) => $c->where('name', 'like', "%$s%"));
            });
        }
        $payments     = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        return view('admin.payments.index', compact('payments', 'totalRevenue'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $bookings = Booking::with('customer')
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('payment_status', '!=', 'paid')
            ->get();
        $prefillBookingId = request('booking_id');
        $prefillAmount    = request('amount');
        return view('admin.payments.create', compact('bookings', 'prefillBookingId', 'prefillAmount'));
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
            'transaction_id' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3)) . '-TXN-' . strtoupper(substr(uniqid(), -8)),
        ]));

        $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');
        $settings   = Setting::first();
        $taxRate    = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float) $settings->tax_rate : 0;
        $gstAmount  = round($booking->total_amount * ($taxRate / 100), 2);
        $grandTotal = $booking->total_amount + $gstAmount;
        $isPaid     = $totalPaid >= $grandTotal;
        $balance    = max(0, $grandTotal - $totalPaid);

        $booking->update([
            'balance_due'    => $balance,
            'payment_status' => $isPaid ? 'paid' : 'partial',
        ]);

        $invoice = Invoice::where('booking_id', $booking->id)->latest()->first();
        if ($invoice) {
            $invoice->update([
                'paid_amount' => $totalPaid,
                'balance'     => $balance,
                'status'      => $isPaid ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
            ]);
        }

        $booking->load(['customer', 'room', 'invoice', 'payments']);
        ActivityLogger::log('Created', 'Payment', 'Payment of ₹' . number_format($payment->amount, 2) . ' recorded for Booking #' . $booking->booking_number . ' (' . $booking->customer->name . ')');
        WhatsAppService::sendForEvent('payment.received', $booking);
        return redirect()->route('payments.show', $payment->id)->with('success', 'Payment recorded!');
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $payment = Payment::with(['booking.customer', 'booking.room'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $payment = Payment::with(['booking'])->findOrFail($id);
        $txn     = $payment->transaction_id;
        $amount  = $payment->amount;
        $booking = $payment->booking;

        ActivityLogger::log('Deleted', 'Payment', 'Deleted payment ₹' . number_format($amount, 2) . ' (TXN: ' . $txn . ') for Booking #' . ($booking->booking_number ?? '—'));

        $payment->delete();

        // Recalculate and sync booking + invoice after payment removal
        if ($booking) {
            $settings   = Setting::first();
            $taxRate    = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float) $settings->tax_rate : 0;
            $gstAmount  = round($booking->total_amount * ($taxRate / 100), 2);
            $grandTotal = $booking->total_amount + $gstAmount;
            $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');
            $balance    = max(0, $grandTotal - $totalPaid);
            $isPaid     = $totalPaid >= $grandTotal && $grandTotal > 0;

            $booking->update([
                'balance_due'    => $balance,
                'payment_status' => $isPaid ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
            ]);

            $invoice = Invoice::where('booking_id', $booking->id)->latest()->first();
            if ($invoice) {
                $invoice->update([
                    'paid_amount' => $totalPaid,
                    'balance'     => $balance,
                    'status'      => $isPaid ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
                ]);
            }
        }

        return redirect()->route('payments.index')->with('success', 'Payment deleted and balances updated.');
    }
}
