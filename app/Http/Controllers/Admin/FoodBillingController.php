<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Module;

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
        abort_unless((int)($booking->room?->hotel_id ?? 0) === (int)$hotelId, 403);

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
}
