<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $dbPlans = DB::table('platform_plans')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug');

        $configPlans = config('plans', []);

        $totalHotels     = DB::table('hotels')->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count();

        $activeHotelPlans = DB::table('hotels')
            ->where('status', 'active')
            ->select('plan', DB::raw('COUNT(*) as cnt'))
            ->groupBy('plan')
            ->get();

        $mrr = 0;
        foreach ($activeHotelPlans as $row) {
            if (isset($dbPlans[$row->plan])) {
                $monthlyPrice = $dbPlans[$row->plan]->monthly_price;
            } else {
                $monthlyPrice = $configPlans[$row->plan]['monthly_price'] ?? 0;
            }
            $mrr += $monthlyPrice * $row->cnt;
        }

        $arr              = $mrr * 12;
        $nextMonthRevenue = $mrr;
        $activeSubscriptions = $activeHotels;
        $totalUsers       = DB::table('hotel_users')->where('status', 'active')->count();

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

        $plans = $this->mergePlans($dbPlans, $configPlans);

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

    private function mergePlans($dbPlans, array $configPlans): array
    {
        $merged = [];

        foreach ($dbPlans as $slug => $dbPlan) {
            $features = is_string($dbPlan->features) ? json_decode($dbPlan->features, true) : ($dbPlan->features ?? []);
            $isUnlimited = $dbPlan->max_rooms >= 9999;
            $merged[$slug] = [
                'label'         => $dbPlan->label,
                'color'         => $dbPlan->color,
                'badge_bg'      => '#f1f5f9',
                'badge_text'    => '#475569',
                'monthly_price' => $dbPlan->monthly_price,
                'yearly_price'  => $dbPlan->yearly_price,
                'max_rooms'     => $isUnlimited ? PHP_INT_MAX : $dbPlan->max_rooms,
                'max_users'     => ($dbPlan->max_users >= 9999) ? PHP_INT_MAX : $dbPlan->max_users,
                'features'      => $features,
                'limits_note'   => ($isUnlimited ? 'Unlimited' : 'Up to ' . number_format($dbPlan->max_rooms)) . ' rooms, ' . (($dbPlan->max_users >= 9999) ? 'Unlimited' : number_format($dbPlan->max_users)) . ' users',
                'is_active'     => (bool) $dbPlan->is_active,
            ];
        }

        foreach ($configPlans as $slug => $cfg) {
            if (!isset($merged[$slug])) {
                $merged[$slug] = $cfg;
            }
        }

        return $merged;
    }
}
