<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\PaymentLinkConfig;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GuestCheckoutController extends Controller
{
    public function show(string $token)
    {
        $booking = Booking::where('checkout_token', $token)
            ->with(['customer', 'room', 'extraCharges', 'bookingAddOns'])
            ->firstOrFail();

        // Only allow checkout view for active bookings
        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            abort(410, 'This booking has already been checked out.');
        }

        $settings   = Setting::where('hotel_id', $booking->hotel_id)->first();
        $upiConfig  = PaymentLinkConfig::withoutGlobalScopes()
            ->where('hotel_id', $booking->hotel_id)
            ->first();

        // Compute bill summary (mirrors CheckOutController::show logic)
        $pricingType       = $booking->room?->pricing_type ?? ($booking->whole_hotel_pricing_type ?? 'per_night');
        $extraChargesTotal = $booking->extraCharges->sum('total_price');

        if ($pricingType === 'per_night') {
            $checkinDate  = Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->startOfDay();
            $checkoutDate = Carbon::parse($booking->check_out_date)->startOfDay();
            $actualNights = max(1, $checkinDate->diffInDays($checkoutDate));
            if ($booking->price_overridden || $booking->is_whole_hotel) {
                $roomCost     = max(0, (float) $booking->total_amount - $extraChargesTotal);
                $mealCost     = 0;
                $extraBedCost = 0;
            } else {
                $roomCost     = $actualNights * ($booking->room?->price_per_night ?? 0);
                $mealCost     = (float) ($booking->meal_cost ?? 0);
                $extraBedCost = (float) ($booking->extra_bed_cost ?? 0);
            }
            $actualTotal = $roomCost + $mealCost + $extraBedCost + $extraChargesTotal;
        } elseif ($pricingType === 'per_hour') {
            $actualNights = 0;
            $mealCost     = 0;
            $extraBedCost = 0;
            $roomCost     = (float) $booking->total_amount;
            $actualTotal  = $roomCost + $extraChargesTotal;
        } else {
            $actualNights = 0;
            $mealCost     = 0;
            $extraBedCost = 0;
            $roomCost     = max(0, (float) $booking->total_amount - $extraChargesTotal);
            $actualTotal  = $roomCost + $extraChargesTotal;
        }

        // Note: restaurant charges billed to a room flow through booking_extra_charges
        // (pushed there by RestaurantBillController) and are already included in
        // $extraChargesTotal above — no separate aggregation needed.

        $taxRate    = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float) $settings->tax_rate : 0;
        $gstAmount  = round($actualTotal * ($taxRate / 100), 2);
        $grandTotal = $actualTotal + $gstAmount;
        $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');

        // Use the stored balance_due from the booking as the authoritative due amount so
        // the guest always sees the same figure staff see (which may include overrides).
        // Fall back to computed value only when the stored field is null/zero.
        $computedBalance = max(0, $grandTotal - $totalPaid);
        $balanceDue = ($booking->balance_due !== null && $booking->balance_due > 0)
            ? (float) $booking->balance_due
            : $computedBalance;

        return view('guest.checkout', compact(
            'booking', 'settings', 'upiConfig', 'token',
            'pricingType', 'roomCost', 'mealCost', 'extraBedCost',
            'extraChargesTotal',
            'actualTotal', 'taxRate', 'gstAmount',
            'grandTotal', 'totalPaid', 'balanceDue'
        ));
    }

    public function submit(Request $request, string $token)
    {
        $booking = Booking::where('checkout_token', $token)
            ->with(['customer', 'room'])
            ->firstOrFail();

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            abort(410, 'This booking has already been checked out.');
        }

        $request->validate([
            'payment_method' => 'required|in:upi,cash,card',
            'payment_ref'    => 'nullable|string|max:200',
        ]);

        $settings      = Setting::where('hotel_id', $booking->hotel_id)->first();
        $totalPaid     = $booking->payments()->where('status', 'completed')->sum('amount');
        $balanceDue    = ($booking->balance_due !== null && $booking->balance_due > 0)
                         ? (float) $booking->balance_due
                         : max(0, (float) $booking->total_amount - $totalPaid);

        // ── Auto-checkout: UPI with a transaction ID = payment verified by guest ──
        $autoCheckedOut = false;
        if ($request->payment_method === 'upi' && trim($request->payment_ref ?? '') !== '') {
            // Record the payment
            if ($balanceDue > 0) {
                Payment::create([
                    'hotel_id'       => $booking->hotel_id,
                    'booking_id'     => $booking->id,
                    'customer_id'    => $booking->customer_id,
                    'amount'         => $balanceDue,
                    'payment_method' => 'upi',
                    'payment_type'   => 'checkout',
                    'status'         => 'completed',
                    'notes'          => 'Guest self-checkout via UPI scan. Transaction ID: ' . $request->payment_ref,
                    'transaction_id' => $request->payment_ref,
                ]);
            }

            // Auto-checkout the booking
            $booking->update([
                'status'                      => 'checked_out',
                'actual_checkout_at'          => now(),
                'balance_due'                 => 0,
                'payment_status'              => 'paid',
                'guest_payment_method'        => 'upi',
                'guest_payment_ref'           => $request->payment_ref,
                'guest_checkout_submitted_at' => now(),
            ]);

            // Free the room
            if ($booking->room) {
                $booking->room->update(['status' => 'available']);
            }

            $autoCheckedOut = true;
        } else {
            // Cash / card — just record intent; staff confirms manually
            $booking->update([
                'guest_payment_method'        => $request->payment_method,
                'guest_payment_ref'           => $request->payment_ref,
                'guest_checkout_submitted_at' => now(),
            ]);
        }

        // Notify staff via FCM push
        try {
            $fcm    = app(FcmService::class);
            $tokens = $fcm->getTokensForHotel($booking->hotel_id);
            if (!empty($tokens)) {
                $guestName = $booking->customer?->name ?? 'Guest';
                $roomNum   = $booking->room?->room_number ?? '—';
                $fcm->sendToTokens(
                    $tokens,
                    $autoCheckedOut ? '✅ Guest Auto-Checked Out' : '🚪 Checkout Request',
                    $autoCheckedOut
                        ? $guestName . ' (Room ' . $roomNum . ') paid via UPI and has been auto checked-out.'
                        : $guestName . ' (Room ' . $roomNum . ') submitted checkout — ' . strtoupper($request->payment_method) . '. Please confirm.',
                    ['url' => url('/checkout/' . $booking->id)]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[GuestCheckout] FCM push failed: ' . $e->getMessage());
        }

        return view('guest.checkout-success', compact('booking', 'settings', 'autoCheckedOut'));
    }
}
