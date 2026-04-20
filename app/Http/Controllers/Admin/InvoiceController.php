<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Invoice::with(['booking.room', 'customer']);
        if ($request->status)    $query->where('status', $request->status);
        if ($request->date_from) $query->whereDate('issued_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('issued_at', '<=', $request->date_to);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%$s%")
                  ->orWhere('total_amount', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%"))
                  ->orWhereHas('booking.room', fn($r) => $r->where('room_number', 'like', "%$s%"));
            });
        }
        $invoices = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::first();
        return view('admin.invoices.show', compact('invoice', 'settings'));
    }

    public function print($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::first();

        // Branch on hotel's invoice style setting
        $style = $settings->invoice_style ?? 'modern';
        $view  = $style === 'gst' ? 'admin.invoices.print-gst' : 'admin.invoices.print';

        return view($view, compact('invoice', 'settings'));
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice = Invoice::with('booking')->findOrFail($id);
        $number  = $invoice->invoice_number;
        $booking = $invoice->booking;

        ActivityLogger::log('Deleted', 'Invoice', 'Deleted invoice ' . $number . ' (₹' . number_format($invoice->total_amount, 2) . ') for Booking #' . ($booking->booking_number ?? '—'));

        $invoice->delete();

        if ($booking) {
            $totalPaid = $booking->payments()->where('status', 'completed')->sum('amount');
            $booking->update([
                'payment_status' => $totalPaid > 0 ? 'partial' : 'pending',
                'balance_due'    => max(0, $booking->total_amount - $totalPaid),
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted. Booking balance restored to pending.');
    }
}
