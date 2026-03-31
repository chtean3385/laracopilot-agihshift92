<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $plans = config('plans', []);

        // ── Tenant counts ─────────────────────────────────────────────────────
        $totalHotels     = DB::table('hotels')->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count();

        // ── SaaS Revenue Metrics (subscription-based, not hotel booking revenue) ──
        // MRR: sum of monthly plan prices for all ACTIVE hotels
        $activeHotelPlans = DB::table('hotels')
            ->where('status', 'active')
            ->select('plan', DB::raw('COUNT(*) as cnt'))
            ->groupBy('plan')
            ->get();

        $mrr = 0;
        foreach ($activeHotelPlans as $row) {
            $monthlyPrice = $plans[$row->plan]['monthly_price'] ?? 0;
            $mrr += $monthlyPrice * $row->cnt;
        }

        // ARR (Annual Run Rate)
        $arr = $mrr * 12;

        // Next-month expected revenue: same as MRR (subscription renews monthly)
        // Could differ if some plans have trial_ends_at expiring — keep simple for now
        $nextMonthRevenue = $mrr;

        // Active subscriptions = active hotels on a paid plan
        $activeSubscriptions = $activeHotels;

        // Users count (platform-level — how many people use the SaaS)
        $totalUsers = DB::table('hotel_users')->where('status', 'active')->count();

        // ── Per-hotel summary (lean query — no booking/payment data) ─────────
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
            ->selectRaw('(SELECT COUNT(*) FROM hotel_users WHERE hotel_users.hotel_id = hotels.id AND hotel_users.status = "active") as user_count')
            ->orderByDesc('hotels.created_at')
            ->get();

        return view('platform.dashboard', compact(
            'totalHotels', 'activeHotels', 'suspendedHotels', 'trialHotels',
            'mrr', 'arr', 'nextMonthRevenue', 'activeSubscriptions',
            'totalUsers',
            'plans',
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
