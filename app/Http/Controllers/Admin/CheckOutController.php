<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\WhatsApp\WhatsAppService;
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

        $booking = Booking::with(['customer', 'room', 'payments', 'timeSlot', 'bookingAddOns'])->findOrFail($bookingId);

        $pricingType = $booking->room->pricing_type ?? 'per_night';

        if ($pricingType === 'per_night') {
            $checkinDate  = Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->startOfDay();
            $checkoutDate = Carbon::parse($booking->check_out_date)->startOfDay();
            $actualNights = $checkinDate->diffInDays($checkoutDate);
            // Same-day checkout counts as 1 night minimum
            if ($actualNights < 1) $actualNights = 1;
            $roomCost     = $actualNights * $booking->room->price_per_night;
            $mealCost     = (float)($booking->meal_cost ?? 0);
            $extraBedCost = (float)($booking->extra_bed_cost ?? 0);
            $actualTotal  = $roomCost + $mealCost + $extraBedCost;
            $hoursBooked  = null;
        } elseif ($pricingType === 'per_hour') {
            // Calculate actual hours using system clock from check-in time to now
            $actualNights = 0;
            $checkinAt    = $booking->actual_checkin_at
                            ?? Carbon::parse($booking->check_in_date . ' ' . ($booking->slot_start_time ?? '00:00'));
            $actualMinutes = Carbon::parse($checkinAt)->diffInMinutes(now());
            $hoursBooked  = max(1, (int) ceil($actualMinutes / 60));
            $addOnTotal   = $booking->bookingAddOns()->sum('price');
            $roomCost     = $hoursBooked * ($booking->room->hourly_rate ?? 0) + $addOnTotal;
            $mealCost     = 0;
            $extraBedCost = 0;
            $actualTotal  = $roomCost;
        } else {
            // per_slot: total_amount stored at booking time
            $actualNights = 0;
            $hoursBooked  = null;
            $roomCost     = (float) $booking->total_amount;
            $mealCost     = 0;
            $extraBedCost = 0;
            $actualTotal  = $roomCost;
        }

        $totalPaid  = $booking->payments->where('status', 'completed')->sum('amount');
        $balanceDue = max(0, $actualTotal - $totalPaid);

        return view('admin.checkout.show', compact(
            'booking', 'pricingType', 'actualNights', 'hoursBooked',
            'actualTotal', 'roomCost', 'mealCost', 'extraBedCost', 'totalPaid', 'balanceDue'
        ));
    }

    public function process(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'final_payment'  => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes'          => 'nullable|string',
            'override_hours' => 'nullable|integer|min:1|max:72',
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
                'transaction_id' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3)) . '-TXN-' . strtoupper(substr(uniqid(), -8)),
            ]);
        }

        // For per_hour rooms, use override_hours from form (admin-confirmed) or fall back to elapsed time
        if (($booking->room->pricing_type ?? 'per_night') === 'per_hour') {
            if ($request->filled('override_hours') && (int) $request->override_hours >= 1) {
                $actualHours = (int) $request->override_hours;
            } else {
                $checkinAt     = $booking->actual_checkin_at
                                 ?? Carbon::parse($booking->check_in_date . ' ' . ($booking->slot_start_time ?? '00:00'));
                $actualMinutes = Carbon::parse($checkinAt)->diffInMinutes(now());
                $actualHours   = max(1, (int) ceil($actualMinutes / 60));
            }
            $addOnTotal      = $booking->bookingAddOns()->sum('price');
            $calculatedTotal = $actualHours * ($booking->room->hourly_rate ?? 0) + $addOnTotal;
            $booking->update(['total_amount' => $calculatedTotal, 'hours_booked' => $actualHours]);
            $booking->refresh();
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
            'invoice_number' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3)) . '-INV-' . strtoupper(substr(uniqid(), -6)),
            'booking_id'     => $booking->id,
            'customer_id'    => $booking->customer_id,
            'total_amount'   => $booking->total_amount,
            'paid_amount'    => $totalPaid,
            'balance'        => $balance,
            'status'         => $isPaid ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
            'issued_at'      => now(),
        ]);

        ActivityLogger::log('Checked Out', 'Check-Out', 'Checked out: ' . $booking->customer->name . ' — Room ' . $booking->room->room_number . ' (Invoice #' . $invoice->invoice_number . ')');
        WhatsAppService::sendForEvent('checkout.done', $booking);

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Check-out complete! Invoice generated.');
    }

    public function void(Request $request, $bookingId)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $booking = Booking::with(['room', 'customer'])->findOrFail($bookingId);

        if (!in_array($booking->status, ['checked_in', 'confirmed', 'pending'])) {
            return redirect()->route('checkout.index')
                ->with('error', 'This booking cannot be cancelled (status: ' . $booking->status . ').');
        }

        $booking->update([
            'status'             => 'cancelled',
            'actual_checkout_at' => now(),
            'checkout_notes'     => 'Voided/Cancelled: ' . ($request->input('reason') ?: 'No reason provided'),
        ]);

        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }

        ActivityLogger::log(
            'Booking Voided',
            'Check-Out',
            'Booking #' . $booking->booking_number . ' for ' . ($booking->customer->name ?? 'Guest') .
            ' (Room ' . ($booking->room->room_number ?? '?') . ') was voided/cancelled.'
        );

        return redirect()->route('checkout.index')
            ->with('success', 'Booking #' . $booking->booking_number . ' has been cancelled and the room is now available.');
    }
}
