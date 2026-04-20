<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Platform\HotelController;

class DashboardController extends Controller
{
    public function index()
    {
        // Load ALL DB plans (active + inactive) for display/pricing lookups
        $dbPlansAll = DB::table('platform_plans')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug');

        $configPlans = config('plans', []);

        $totalHotels     = DB::table('hotels')->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count();

        // Per-hotel revenue — exclude hotels included in a parent subscription
        $activeHotels2 = DB::table('hotels')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('billing_included_in_parent')
                  ->orWhere('billing_included_in_parent', false);
            })
            ->select('plan', 'billing_cycle', 'custom_monthly_price', 'custom_yearly_price')
            ->get();

        $mrr = 0;
        $arr = 0;
        foreach ($activeHotels2 as $h) {
            $planMonthly = isset($dbPlansAll[$h->plan])
                ? (int) $dbPlansAll[$h->plan]->monthly_price
                : (int) ($configPlans[$h->plan]['monthly_price'] ?? 0);
            $planYearly  = isset($dbPlansAll[$h->plan])
                ? (int) $dbPlansAll[$h->plan]->yearly_price
                : (int) ($configPlans[$h->plan]['yearly_price'] ?? 0);

            $effectiveMonthly = ($h->custom_monthly_price > 0) ? (int) $h->custom_monthly_price : $planMonthly;
            $effectiveYearly  = ($h->custom_yearly_price  > 0) ? (int) $h->custom_yearly_price  : $planYearly;

            if ($h->billing_cycle === 'yearly') {
                $arr += $effectiveYearly;
                $mrr += round($effectiveYearly / 12);
            } else {
                $mrr += $effectiveMonthly;
                $arr += $effectiveMonthly * 12;
            }
        }

        $nextMonthRevenue = $mrr;
        // Active subscriptions = active hotels NOT included in a parent subscription
        $activeSubscriptions = DB::table('hotels')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('billing_included_in_parent')
                  ->orWhere('billing_included_in_parent', false);
            })
            ->count();
        $totalUsers       = DB::table('hotel_users')->where('status', 'active')->count();

        $hotelStats = DB::table('hotels')
            ->select(
                'hotels.id',
                'hotels.name',
                'hotels.slug',
                'hotels.plan',
                'hotels.status',
                'hotels.billing_cycle',
                'hotels.custom_monthly_price',
                'hotels.custom_yearly_price',
                'hotels.max_rooms',
                'hotels.max_users',
                'hotels.created_at',
                'hotels.trial_ends_at',
                'hotels.plan_expires_at',
                'hotels.phone',
                'hotels.owner_wa_consent',
                'hotels.billing_included_in_parent',
                'hotels.parent_hotel_id',
            )
            ->selectRaw("(SELECT COUNT(*) FROM hotel_users WHERE hotel_users.hotel_id = hotels.id AND hotel_users.status = 'active') as user_count")
            ->selectRaw("(SELECT MAX(created_at) FROM activity_logs WHERE activity_logs.hotel_id = hotels.id) as last_activity")
            ->orderByDesc('hotels.created_at')
            ->get();

        // Merged plans (all DB plans + config fallback) for display/badge rendering
        $plans = $this->mergePlans($dbPlansAll, $configPlans);

        // ── Expiry Alert popup ─────────────────────────────────────────────
        $today = Carbon::today();
        $in7   = Carbon::today()->addDays(7);

        $expiryAlerts = DB::table('hotels')
            ->whereIn('status', ['active', 'suspended'])
            ->where(function ($q) use ($in7) {
                $q->where(function ($q2) use ($in7) {
                    // Trial ending within 7 days (or already past)
                    $q2->whereNotNull('trial_ends_at')
                       ->where('plan', 'trial')
                       ->where('trial_ends_at', '<=', $in7->toDateTimeString());
                })->orWhere(function ($q2) use ($in7) {
                    // Paid plan expiring within 7 days (or already past)
                    $q2->whereNotNull('plan_expires_at')
                       ->where('plan_expires_at', '<=', $in7->toDateTimeString());
                });
            })
            ->select('id', 'name', 'slug', 'plan', 'status', 'trial_ends_at', 'plan_expires_at')
            ->orderByRaw("LEAST(COALESCE(trial_ends_at, '9999-01-01'), COALESCE(plan_expires_at, '9999-01-01')) ASC")
            ->get()
            ->map(function ($h) use ($today) {
                $expiryDate = null;
                $type       = null;
                if ($h->plan === 'trial' && $h->trial_ends_at) {
                    $expiryDate = Carbon::parse($h->trial_ends_at);
                    $type       = 'trial';
                } elseif ($h->plan_expires_at) {
                    $expiryDate = Carbon::parse($h->plan_expires_at);
                    $type       = 'plan';
                }
                if (!$expiryDate) return null;

                $daysLeft = (int) $today->diffInDays($expiryDate, false);
                $urgency  = $daysLeft < 0 ? 'expired' : ($daysLeft === 0 ? 'today' : ($daysLeft <= 3 ? 'critical' : 'soon'));

                return (object) [
                    'id'          => $h->id,
                    'name'        => $h->name,
                    'slug'        => $h->slug,
                    'plan'        => $h->plan,
                    'status'      => $h->status,
                    'type'        => $type,
                    'expiry_date' => $expiryDate->format('d M Y'),
                    'days_left'   => $daysLeft,
                    'urgency'     => $urgency,
                ];
            })
            ->filter()
            ->values();

        // Show the popup once per login — cleared after first dashboard load
        $showExpiryPopup = session('platform_show_expiry_popup', false) && $expiryAlerts->isNotEmpty();
        session(['platform_show_expiry_popup' => false]);

        $ownerWaTemplates = HotelController::platformWaTemplates();

        return view('platform.dashboard', compact(
            'totalHotels', 'activeHotels', 'suspendedHotels', 'trialHotels',
            'mrr', 'arr', 'nextMonthRevenue', 'activeSubscriptions',
            'totalUsers',
            'plans',
            'hotelStats',
            'expiryAlerts',
            'showExpiryPopup',
            'ownerWaTemplates'
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

    private function mergePlans($dbPlansAll, array $configPlans): array
    {
        $merged = [];

        foreach ($dbPlansAll as $slug => $dbPlan) {
            $features    = is_string($dbPlan->features) ? json_decode($dbPlan->features, true) : ($dbPlan->features ?? []);
            $isUnlimited = $dbPlan->max_rooms >= 9999;
            $merged[$slug] = [
                'label'         => $dbPlan->label,
                'color'         => $dbPlan->color,
                'badge_bg'      => '#f1f5f9',
                'badge_text'    => '#475569',
                'monthly_price' => $dbPlan->monthly_price,
                'yearly_price'  => $dbPlan->yearly_price,
                'max_rooms'     => $isUnlimited ? PHP_INT_MAX : (int) $dbPlan->max_rooms,
                'max_users'     => ($dbPlan->max_users >= 9999) ? PHP_INT_MAX : (int) $dbPlan->max_users,
                'features'      => $features,
                'limits_note'   => ($isUnlimited ? 'Unlimited' : 'Up to ' . number_format($dbPlan->max_rooms)) . ' rooms, '
                                 . (($dbPlan->max_users >= 9999) ? 'Unlimited' : number_format($dbPlan->max_users)) . ' users',
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
