<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Aggregate KPIs (cross-hotel, no global scopes needed for DB facade) ──
        $totalHotels     = DB::table('hotels')->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count();

        $totalBookings   = DB::table('bookings')->count();
        $activeBookings  = DB::table('bookings')->whereIn('status', ['confirmed', 'checked_in'])->count();

        $totalGuests     = DB::table('customers')->count();

        $totalRevenue    = DB::table('payments')->where('status', 'completed')->sum('amount');

        $totalUsers      = DB::table('hotel_users')->where('status', 'active')->count();
        $totalRooms      = DB::table('rooms')->count();

        // Currency symbol — use the first hotel's setting; fall back to "Rs"
        $currencySymbol  = DB::table('settings')->value('currency_symbol') ?? 'Rs';

        // ── Per-hotel summary (single efficient query with correlated sub-selects) ──
        $hotelStats = DB::table('hotels')
            ->select(
                'hotels.id',
                'hotels.name',
                'hotels.slug',
                'hotels.plan',
                'hotels.status',
                'hotels.max_rooms',
                'hotels.max_users',
                'hotels.created_at',
                'hotels.trial_ends_at',
                'hotels.plan_expires_at',
            )
            ->selectRaw('(SELECT COUNT(*) FROM rooms WHERE rooms.hotel_id = hotels.id) as room_count')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.hotel_id = hotels.id) as booking_count')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.hotel_id = hotels.id AND bookings.status IN ("confirmed","checked_in")) as active_booking_count')
            ->selectRaw('(SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.hotel_id = hotels.id AND payments.status = "completed") as revenue')
            ->selectRaw('(SELECT COUNT(*) FROM hotel_users WHERE hotel_users.hotel_id = hotels.id AND hotel_users.status = "active") as user_count')
            ->selectRaw('(SELECT COUNT(*) FROM customers WHERE customers.hotel_id = hotels.id) as guest_count')
            ->orderByDesc('hotels.created_at')
            ->get();

        return view('platform.dashboard', compact(
            'totalHotels', 'activeHotels', 'suspendedHotels', 'trialHotels',
            'totalBookings', 'activeBookings',
            'totalGuests', 'totalRevenue',
            'totalUsers', 'totalRooms',
            'currencySymbol',
            'hotelStats'
        ));
    }

    public function viewInCrm(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.dashboard')->with('error', 'Hotel not found.');
        }

        session(['crm_sa_hotel_filter' => $id]);

        return redirect()->route('dashboard')
            ->with('success', 'Now viewing ' . $hotel->name . ' — hotel filter applied.');
    }
}
