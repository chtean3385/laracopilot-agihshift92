<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
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
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'customer'])->findOrFail($id);
        $settings = Setting::first();
        return view('admin.invoices.show', compact('invoice', 'settings'));
    }

    public function print($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $invoice  = Invoice::with(['booking.room', 'booking.payments', 'customer'])->findOrFail($id);
        $settings = Setting::first();
        return view('admin.invoices.print', compact('invoice', 'settings'));
    }
}