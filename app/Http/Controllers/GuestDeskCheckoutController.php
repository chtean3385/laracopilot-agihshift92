<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\PaymentLinkConfig;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuestDeskCheckoutController extends Controller
{
    // ── Show desk checkout landing page ─────────────────────────────────────
    public function show(string $slug)
    {
        $hotel    = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $settings = Setting::where('hotel_id', $hotel->id)->first();
        $upiConfig = PaymentLinkConfig::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->first();

        return view('guest.desk-checkout', compact('hotel', 'settings', 'upiConfig', 'slug'));
    }

    // ── AJAX: find active booking by phone ──────────────────────────────────
    public function lookup(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $phone = trim($request->query('phone', ''));

        if (strlen($phone) < 5) {
            return response()->json(['found' => false]);
        }

        $customer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('phone', $phone)
            ->whereNull('deleted_at')
            ->first();

        if (!$customer) {
            return response()->json(['found' => false, 'message' => 'No account found with this number.']);
        }

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['room', 'extraCharges'])
            ->orderByDesc('id')
            ->first();

        if (!$booking) {
            return response()->json(['found' => false, 'message' => 'No active booking found for this number.']);
        }

        $settings  = Setting::where('hotel_id', $hotel->id)->first();
        $upiConfig = PaymentLinkConfig::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->first();

        // ── Compute bill (mirrors GuestCheckoutController logic) ─────────────
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
            $subtotal = $roomCost + $mealCost + $extraBedCost + $extraChargesTotal;
        } else {
            $actualNights = 0;
            $mealCost     = 0;
            $extraBedCost = 0;
            $roomCost     = (float) $booking->total_amount;
            $subtotal     = $roomCost + $extraChargesTotal;
        }

        $taxRate    = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float) $settings->tax_rate : 0;
        $gstAmount  = round($subtotal * ($taxRate / 100), 2);
        $grandTotal = $subtotal + $gstAmount;
        $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');

        // Use stored balance_due as authoritative figure (matches staff view)
        $computedBalance = max(0, $grandTotal - $totalPaid);
        $balanceDue = ($booking->balance_due !== null && $booking->balance_due > 0)
            ? (float) $booking->balance_due
            : $computedBalance;

        // Build bill rows for display
        $billRows = [];
        if ($roomCost > 0) {
            $label = $pricingType === 'per_night' && $actualNights > 0
                ? $actualNights . ' night' . ($actualNights > 1 ? 's' : '') . ' × ₹' . number_format($booking->room?->price_per_night ?? 0)
                : 'Room Charge';
            $billRows[] = ['label' => $label, 'amount' => $roomCost];
        }
        if ($mealCost > 0)     $billRows[] = ['label' => 'Meal Plan',  'amount' => $mealCost];
        if ($extraBedCost > 0) $billRows[] = ['label' => 'Extra Bed',  'amount' => $extraBedCost];
        foreach ($booking->extraCharges as $ec) {
            $lbl = $ec->name . ($ec->quantity != 1 ? ' ×' . $ec->quantity : '');
            $billRows[] = ['label' => $lbl, 'amount' => $ec->total_price];
        }
        if ($gstAmount > 0) {
            $billRows[] = ['label' => 'GST (' . $taxRate . '%)', 'amount' => $gstAmount];
        }

        // Ensure checkout_token exists
        if (!$booking->checkout_token) {
            $booking->update(['checkout_token' => (string) \Illuminate\Support\Str::uuid()]);
            $booking->refresh();
        }

        return response()->json([
            'found'            => true,
            'token'            => $booking->checkout_token,
            'guest_name'       => $customer->name,
            'room'             => $booking->room?->room_number ?? 'Whole Hotel',
            'booking_number'   => $booking->booking_number,
            'check_in'         => $booking->check_in_date,
            'check_out'        => $booking->check_out_date,
            'bill_rows'        => $billRows,
            'grand_total'      => $grandTotal,
            'total_paid'       => $totalPaid,
            'balance_due'      => $balanceDue,
            'upi_id'           => ($upiConfig && $upiConfig->upi_enabled && $upiConfig->upi_id) ? $upiConfig->upi_id : null,
            'upi_name'         => $upiConfig?->upi_name ?? '',
            'razorpay_enabled' => ($upiConfig && $upiConfig->razorpay_enabled && $upiConfig->razorpay_key_id) ? true : false,
        ]);
    }

    // ── Submit payment intent (UPI screenshot / cash / card) ────────────────
    public function submit(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $request->validate([
            'checkout_token'  => 'required|string',
            'payment_method'  => 'required|in:upi,cash,card',
            'payment_ref'     => 'nullable|string|max:200',
        ]);

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('checkout_token', $request->checkout_token)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['customer', 'room'])
            ->firstOrFail();

        $booking->update([
            'guest_payment_method'        => $request->payment_method,
            'guest_payment_ref'           => $request->payment_ref,
            'guest_checkout_submitted_at' => now(),
        ]);

        // Push notification to hotel staff
        try {
            $fcm    = app(FcmService::class);
            $tokens = $fcm->getTokensForHotel($hotel->id);
            if (!empty($tokens)) {
                $guestName  = $booking->customer?->name ?? 'Guest';
                $roomNum    = $booking->room?->room_number ?? '—';
                $method     = strtoupper($request->payment_method);
                $fcm->sendToTokens(
                    $tokens,
                    '🚪 Guest Checkout Request',
                    $guestName . ' (Room ' . $roomNum . ') submitted checkout — ' . $method . '. Please verify and check out.',
                    ['url' => url('/checkout/' . $booking->id)]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[DeskCheckout] FCM push failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    // ── Create Razorpay payment link for auto-checkout ───────────────────────
    public function createRazorpayLink(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $request->validate(['checkout_token' => 'required|string']);

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('checkout_token', $request->checkout_token)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['customer', 'room'])
            ->firstOrFail();

        $config = PaymentLinkConfig::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->first();

        if (!$config || !$config->razorpay_enabled || !$config->razorpay_key_id || !$config->razorpay_key_secret) {
            return response()->json(['error' => 'Online payment is not configured.'], 422);
        }

        $settings   = Setting::where('hotel_id', $hotel->id)->first();
        $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');
        $balanceDue = ($booking->balance_due !== null && $booking->balance_due > 0)
            ? (float) $booking->balance_due
            : max(0, (float) $booking->total_amount - $totalPaid);

        if ($balanceDue <= 0) {
            return response()->json(['error' => 'No balance due.'], 422);
        }

        $callbackUrl = url('/g/desk-checkout/' . $slug . '/razorpay-callback');

        $payload = [
            'amount'        => (int) round($balanceDue * 100), // paise
            'currency'      => 'INR',
            'accept_partial'=> false,
            'reference_id'  => $booking->checkout_token,
            'description'   => 'Checkout: ' . $booking->booking_number . ' — Room ' . ($booking->room?->room_number ?? ''),
            'customer'      => [
                'name'  => $booking->customer?->name ?? 'Guest',
                'email' => $booking->customer?->email ?? '',
                'contact' => $booking->customer?->phone ?? '',
            ],
            'notify'        => ['sms' => false, 'email' => false],
            'reminder_enable' => false,
            'callback_url'  => $callbackUrl,
            'callback_method' => 'get',
        ];

        $response = Http::withBasicAuth($config->razorpay_key_id, $config->razorpay_key_secret)
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        if (!$response->successful()) {
            $err = $response->json('error.description') ?? 'Payment gateway error.';
            return response()->json(['error' => $err], 422);
        }

        $data = $response->json();
        return response()->json(['payment_url' => $data['short_url']]);
    }

    // ── Razorpay callback — verify and auto-checkout ─────────────────────────
    public function razorpayCallback(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $linkId    = $request->query('razorpay_payment_link_id');
        $refId     = $request->query('razorpay_payment_link_reference_id');
        $status    = $request->query('razorpay_payment_link_status');
        $paymentId = $request->query('razorpay_payment_id');
        $signature = $request->query('razorpay_signature');

        if ($status !== 'paid' || !$paymentId || !$signature) {
            return redirect('/g/desk-checkout/' . $slug)->with('error', 'Payment was not completed.');
        }

        $config = PaymentLinkConfig::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->first();

        if (!$config || !$config->razorpay_key_secret) {
            return redirect('/g/desk-checkout/' . $slug)->with('error', 'Configuration error.');
        }

        // Verify Razorpay signature
        $expectedSig = hash_hmac('sha256',
            $linkId . '|' . $refId . '|' . $status . '|' . $paymentId,
            $config->razorpay_key_secret
        );

        if (!hash_equals($expectedSig, $signature)) {
            Log::warning('[DeskCheckout] Razorpay signature mismatch for token: ' . $refId);
            return redirect('/g/desk-checkout/' . $slug)->with('error', 'Payment verification failed.');
        }

        // Find and auto-checkout the booking
        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('checkout_token', $refId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['customer', 'room'])
            ->first();

        if (!$booking) {
            return redirect('/g/desk-checkout/' . $slug)->with('error', 'Booking not found.');
        }

        $settings   = Setting::where('hotel_id', $hotel->id)->first();
        $totalPaid  = $booking->payments()->where('status', 'completed')->sum('amount');
        $balanceDue = ($booking->balance_due !== null && $booking->balance_due > 0)
            ? (float) $booking->balance_due
            : max(0, (float) $booking->total_amount - $totalPaid);

        // Record the Razorpay payment
        Payment::create([
            'hotel_id'       => $hotel->id,
            'booking_id'     => $booking->id,
            'customer_id'    => $booking->customer_id,
            'amount'         => $balanceDue,
            'payment_method' => 'upi',
            'payment_type'   => 'checkout',
            'status'         => 'completed',
            'notes'          => 'Guest self-checkout via Razorpay (online). Payment ID: ' . $paymentId,
            'transaction_id' => $paymentId,
        ]);

        // Auto-checkout: update booking + free the room
        $booking->update([
            'status'                      => 'checked_out',
            'actual_checkout_at'          => now(),
            'balance_due'                 => 0,
            'payment_status'              => 'paid',
            'guest_payment_method'        => 'upi',
            'guest_payment_ref'           => $paymentId,
            'guest_checkout_submitted_at' => now(),
        ]);

        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }

        return redirect('/g/desk-checkout/' . $slug . '/success?name=' . urlencode($booking->customer?->name ?? 'Guest'));
    }

    // ── Success page ─────────────────────────────────────────────────────────
    public function success(string $slug)
    {
        $hotel    = Hotel::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $settings = Setting::where('hotel_id', $hotel->id)->first();
        $name     = request()->query('name', 'Guest');
        return view('guest.desk-checkout-success', compact('hotel', 'settings', 'name'));
    }
}
