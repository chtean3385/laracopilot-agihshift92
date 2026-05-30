<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Module;
use App\Services\WhatsApp\WhatsAppService;

class FoodBillingController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        abort_unless(Module::isEnabled('extra-billing'), 403, 'Extra Billing module is not enabled.');

        $hotelId = session('crm_hotel_id') ?: session('crm_sa_hotel_filter');

        $bookings = Booking::withoutGlobalScopes()
            ->with(['room', 'customer', 'extraCharges'])
            ->whereHas('room', fn($q) => $q->withoutGlobalScopes()->where('hotel_id', $hotelId))
            ->where('status', 'checked_in')
            ->orderBy('check_in_date')
            ->get();

        return view('admin.food-billing.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        abort_unless(Module::isEnabled('extra-billing'), 403, 'Extra Billing module is not enabled.');
        $hotelId = session('crm_hotel_id') ?: session('crm_sa_hotel_filter');
        $booking->loadMissing('room');
        $bookingHotelId = $booking->hotel_id ?? $booking->room?->hotel_id;
        abort_unless((int)($bookingHotelId ?? 0) === (int)$hotelId, 403);

        $booking->load(['room', 'customer', 'extraCharges']);

        $categories = [
            'food'         => 'Food & Beverage',
            'laundry'      => 'Laundry',
            'transport'    => 'Transport',
            'spa'          => 'Spa & Wellness',
            'room_service' => 'Room Service',
            'minibar'      => 'Mini Bar',
            'other'        => 'Other',
        ];

        return view('admin.food-billing.show', compact('booking', 'categories'));
    }

    public function sendWhatsApp(Booking $booking)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        abort_unless(Module::isEnabled('extra-billing'), 403);
        $hotelId = session('crm_hotel_id') ?: session('crm_sa_hotel_filter');
        $bookingHotelId = $booking->hotel_id ?? $booking->room?->hotel_id;
        abort_unless((int)($bookingHotelId ?? 0) === (int)$hotelId, 403);

        $booking->load(['room', 'customer', 'extraCharges', 'invoice', 'payments']);

        $sent = WhatsAppService::sendForEvent('restaurant.bill', $booking);

        if ($sent) {
            return redirect()->route('food-billing.show', $booking)
                ->with('success', 'Restaurant bill sent to ' . ($booking->customer?->name ?? 'guest') . ' via WhatsApp!');
        }

        return redirect()->route('food-billing.show', $booking)
            ->with('error', 'WhatsApp not sent — check WhatsApp module settings or template approval status.');
    }
}
