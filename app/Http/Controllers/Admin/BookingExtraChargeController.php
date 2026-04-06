<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingExtraCharge;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingExtraChargeController extends Controller
{
    private function guardModule(): void
    {
        abort_unless(Module::isEnabled('extra-billing'), 403, 'Extra Billing module is not enabled.');
    }

    public function store(Request $request, Booking $booking)
    {
        $this->guardModule();

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'category'   => 'required|string|max:50',
            'quantity'   => 'required|numeric|min:0.01|max:9999',
            'unit_price' => 'required|numeric|min:0|max:999999',
            'notes'      => 'nullable|string|max:500',
        ]);

        abort_unless(
            in_array($booking->status, ['confirmed', 'checked_in']),
            422,
            'Extra charges can only be added to confirmed or checked-in bookings.'
        );

        $totalPrice = round($data['quantity'] * $data['unit_price'], 2);

        DB::transaction(function () use ($booking, $data, $totalPrice) {
            BookingExtraCharge::create([
                'booking_id' => $booking->id,
                'name'       => $data['name'],
                'category'   => $data['category'],
                'quantity'   => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price'=> $totalPrice,
                'notes'      => $data['notes'] ?? null,
                'added_by'   => auth()->id(),
            ]);

            $booking->increment('total_amount', $totalPrice);
            $booking->increment('balance_due',  $totalPrice);

            if ($booking->invoice) {
                $booking->invoice->increment('total_amount', $totalPrice);
                $booking->invoice->increment('balance', $totalPrice);
            }
        });

        return back()->with('success', 'Extra charge of ₹' . number_format($totalPrice) . ' added successfully.');
    }

    public function destroy(Booking $booking, BookingExtraCharge $charge)
    {
        $this->guardModule();

        abort_unless($charge->booking_id === $booking->id, 404);

        abort_unless(
            in_array($booking->status, ['confirmed', 'checked_in']),
            422,
            'Cannot remove charges from a checked-out or cancelled booking.'
        );

        DB::transaction(function () use ($booking, $charge) {
            $amount = (float) $charge->total_price;

            $charge->delete();

            $booking->decrement('total_amount', $amount);
            $newBalance = max(0, $booking->balance_due - $amount);
            $booking->update(['balance_due' => $newBalance]);

            if ($booking->invoice) {
                $booking->invoice->decrement('total_amount', $amount);
                $newInvBalance = max(0, $booking->invoice->balance - $amount);
                $booking->invoice->update(['balance' => $newInvBalance]);
            }
        });

        return back()->with('success', 'Extra charge removed.');
    }
}
