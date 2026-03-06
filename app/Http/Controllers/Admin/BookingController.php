<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $query = Booking::with(['customer', 'room']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%$search%"))
                  ->orWhere('booking_number', 'like', "%$search%");
            });
        }
        if ($request->date_from) $query->whereDate('check_in_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('check_out_date', '<=', $request->date_to);
        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customers = Customer::orderBy('name')->get();
        $rooms     = Room::where('status', 'available')->orderBy('room_number')->get();
        return view('admin.bookings.create', compact('customers', 'rooms'));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'room_id'        => 'required|exists:rooms,id',
            'check_in_date'  => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults'         => 'required|integer|min:1',
            'children'       => 'nullable|integer|min:0',
            'advance_payment'=> 'nullable|numeric|min:0',
            'special_requests'=> 'nullable|string',
        ]);
        $room           = Room::findOrFail($validated['room_id']);
        $nights         = Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date']));
        $totalAmount    = $nights * $room->price_per_night;
        $advancePayment = $validated['advance_payment'] ?? 0;
        $booking = Booking::create([
            'booking_number'  => 'BK' . strtoupper(substr(uniqid(), -6)),
            'customer_id'     => $validated['customer_id'],
            'room_id'         => $validated['room_id'],
            'check_in_date'   => $validated['check_in_date'],
            'check_out_date'  => $validated['check_out_date'],
            'nights'          => $nights,
            'adults'          => $validated['adults'],
            'children'        => $validated['children'] ?? 0,
            'total_amount'    => $totalAmount,
            'advance_payment' => $advancePayment,
            'balance_due'     => $totalAmount - $advancePayment,
            'special_requests'=> $validated['special_requests'] ?? null,
            'status'          => 'confirmed',
            'payment_status'  => $advancePayment >= $totalAmount ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
        ]);
        if ($advancePayment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $validated['customer_id'],
                'amount'         => $advancePayment,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Advance at booking',
                'transaction_id' => 'TXN' . strtoupper(substr(uniqid(), -8)),
            ]);
        }
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Booking created! #' . $booking->booking_number);
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['customer', 'room', 'payments', 'invoice'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking   = Booking::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $rooms     = Room::orderBy('room_number')->get();
        return view('admin.bookings.edit', compact('booking', 'customers', 'rooms'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking   = Booking::findOrFail($id);
        $validated = $request->validate([
            'check_in_date'  => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults'         => 'required|integer|min:1',
            'children'       => 'nullable|integer|min:0',
            'special_requests'=> 'nullable|string',
            'status'         => 'required|in:confirmed,checked_in,checked_out,cancelled',
        ]);
        $room    = Room::findOrFail($booking->room_id);
        $nights  = Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date']));
        $total   = $nights * $room->price_per_night;
        $booking->update(array_merge($validated, [
            'nights'      => $nights,
            'total_amount'=> $total,
            'balance_due' => max(0, $total - $booking->advance_payment),
        ]));
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Booking updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return redirect()->route('bookings.index')->with('success', 'Booking cancelled.');
    }
}