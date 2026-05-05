<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $activityLogs = PermissionService::check('reports.view')
            ? ActivityLog::where('module', 'Invoice')
                ->where('description', 'like', '%' . $invoice->invoice_number . '%')
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();
        return view('admin.invoices.show', compact('invoice', 'settings', 'activityLogs'));
    }

    public function print($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();

        $style = $settings->invoice_style ?? 'modern';
        $view  = match($style) {
            'gst'     => 'admin.invoices.print-gst',
            'compact' => 'admin.invoices.print-compact',
            default   => 'admin.invoices.print',
        };

        return view($view, compact('invoice', 'settings'));
    }

    public function printGst($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();

        return view('admin.invoices.print-gst', compact('invoice', 'settings'));
    }

    public function downloadPdf($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'booking.extraCharges', 'customer'])->findOrFail($id);
        $settings = Setting::where('hotel_id', $invoice->booking?->hotel_id ?? session('crm_hotel_id'))->first();

        // Compute line items
        $isWH       = (bool) $invoice->booking->is_whole_hotel;
        $extraTotal = $invoice->booking->extraCharges->sum('total_price');
        if ($isWH || $invoice->booking->price_overridden) {
            $roomCost = max(0, (float) $invoice->booking->total_amount - $extraTotal);
        } else {
            $roomCost = ($invoice->booking->nights ?? 0) * ($invoice->booking->room?->price_per_night ?? 0);
        }
        $mealCost     = (float) ($invoice->booking->meal_cost ?? 0);
        $extraBedCost = $invoice->booking->extra_beds > 0 ? (float) ($invoice->booking->extra_bed_cost ?? 0) : 0;
        $subtotal     = $roomCost + $mealCost + $extraBedCost + $extraTotal;
        $foodBase     = $extraTotal;
        $roomBase     = $roomCost + $mealCost + $extraBedCost;
        $taxRate      = (float) ($settings->tax_rate ?? 0);
        $foodTaxRate  = (float) ($settings->food_tax_rate ?? 5);
        $roomGst      = ($settings && $settings->gst_number) ? round($roomBase * $taxRate / 100, 2) : 0;
        $foodGst      = ($settings && $settings->gst_number && $foodBase > 0) ? round($foodBase * $foodTaxRate / 100, 2) : 0;
        $grandTotal   = $subtotal + $roomGst + $foodGst;
        $balance      = max(0, $grandTotal - $invoice->paid_amount);
        $overpayment  = max(0, $invoice->paid_amount - $grandTotal);

        // Logo as base64 so DomPDF can embed it
        $logoBase64 = null;
        if ($settings && $settings->logo) {
            $logoPath = public_path('storage/' . $settings->logo);
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('admin.invoices.pdf-download', compact(
            'invoice', 'settings', 'isWH', 'roomCost', 'mealCost', 'extraBedCost',
            'extraTotal', 'subtotal', 'roomGst', 'foodGst', 'grandTotal', 'balance',
            'overpayment', 'taxRate', 'foodTaxRate', 'logoBase64'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
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

        return redirect()->route('invoices.index')->with('success', 'Invoice moved to trash. It can be restored within 30 days.');
    }

    public function trash(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $query = Invoice::onlyTrashed()->with(['booking.room', 'customer']);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->withTrashed()->where('name', 'like', "%$s%"));
            });
        }

        $invoices = $query->orderBy('deleted_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.invoices.trash', compact('invoices'));
    }

    public function restore($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $invoice = Invoice::onlyTrashed()->findOrFail($id);

        if ($invoice->deleted_at->lt(now()->subDays(30))) {
            return redirect()->route('invoices.trash')->with('error', 'This invoice has passed the 30-day restore window and cannot be recovered.');
        }

        $invoice->restore();

        $booking = $invoice->booking;
        if ($booking) {
            $totalPaid = $booking->payments()->where('status', 'completed')->sum('amount');
            $balance   = max(0, $invoice->total_amount - $totalPaid);
            $booking->update([
                'payment_status' => $totalPaid >= $invoice->total_amount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
                'balance_due'    => $balance,
            ]);
        }

        $userName  = session('crm_user_name', 'Unknown');
        $userEmail = session('crm_user_email', '');
        $restoredBy = $userName . ($userEmail ? ' (' . $userEmail . ')' : '');
        ActivityLogger::log('Restored', 'Invoice', 'Invoice ' . $invoice->invoice_number . ' restored by ' . $restoredBy . ' — ₹' . number_format($invoice->total_amount, 2));

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice restored successfully.');
    }
}
