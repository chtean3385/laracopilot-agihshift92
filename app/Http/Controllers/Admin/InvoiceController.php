<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();
        return view('admin.invoices.show', compact('invoice', 'settings'));
    }

    public function print($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();

        $style = $settings->invoice_style ?? 'modern';
        $view  = $style === 'gst' ? 'admin.invoices.print-gst' : 'admin.invoices.print';

        return view($view, compact('invoice', 'settings'));
    }

    public function printGst($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();

        return view('admin.invoices.print-gst', compact('invoice', 'settings'));
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();
        return view('admin.invoices.edit', compact('invoice', 'settings'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'issued_at'    => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount'  => 'required|numeric|min:0',
            'status'       => 'required|in:paid,partial,unpaid',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $invoice     = Invoice::with('booking')->findOrFail($id);
        $total       = (float) $request->total_amount;
        $paid        = (float) $request->paid_amount;
        $balance     = max(0, $total - $paid);

        $invoice->update([
            'issued_at'    => Carbon::parse($request->issued_at),
            'total_amount' => $total,
            'paid_amount'  => $paid,
            'balance'      => $balance,
            'status'       => $request->status,
            'notes'        => $request->notes,
        ]);

        if ($invoice->booking) {
            $invoice->booking->update([
                'balance_due'    => $balance,
                'payment_status' => $request->status,
            ]);
        }

        ActivityLogger::log('Updated', 'Invoice', 'Edited invoice ' . $invoice->invoice_number . ' — total ₹' . number_format($total, 2) . ', paid ₹' . number_format($paid, 2));

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice = Invoice::with('booking')->findOrFail($id);
        $number  = $invoice->invoice_number;
        $booking = $invoice->booking;

        $userName  = session('crm_user_name', 'Unknown');
        $userEmail = session('crm_user_email', '');
        $deletedBy = $userName . ($userEmail ? ' (' . $userEmail . ')' : '');
        ActivityLogger::log('Deleted', 'Invoice', 'Invoice ' . $number . ' deleted by ' . $deletedBy . ' — ₹' . number_format($invoice->total_amount, 2) . ' for Booking #' . ($booking->booking_number ?? '—'));

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
