<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckTrialStatus
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Only apply to logged-in hotel staff (not platform, not guests, not install)
        if (!session('crm_logged_in')) {
            return $next($request);
        }

        // Super Admin bypasses trial enforcement entirely
        if (session('crm_user_role') === 'Super Admin') {
            return $next($request);
        }

        // Skip auth/logout/upgrade/password routes
        if ($request->routeIs(['login', 'login.post', 'logout', 'password.*', 'register', 'select.hotel', 'select.hotel.post', 'upgrade', 'upgrade.request'])) {
            return $next($request);
        }

        $hotelId = session('crm_hotel_id');
        if (!$hotelId) {
            return $next($request);
        }

        $hotel = DB::table('hotels')->where('id', $hotelId)->first();
        if (!$hotel) {
            return $next($request);
        }

        $now = now();

        // ── Trial plan enforcement ──────────────────────────────────────────
        if ($hotel->plan === 'trial') {
            $trialEnds = $hotel->trial_ends_at ? \Carbon\Carbon::parse($hotel->trial_ends_at) : null;

            if ($trialEnds) {
                $daysLeft = (int) $now->diffInDays($trialEnds, false);

                if ($daysLeft < 0) {
                    // Trial expired → redirect to upgrade (locked)
                    if (!$request->routeIs(['upgrade', 'upgrade.request', 'logout'])) {
                        return redirect()->route('upgrade')->with('trial_expired', true);
                    }
                } elseif ($daysLeft <= 1) {
                    // Last 2 days of trial → banner warning
                    session(['trial_warning' => 'urgent', 'trial_days_left' => max(0, $daysLeft)]);
                } elseif ($daysLeft <= 3) {
                    // 3 days or less → warning banner
                    session(['trial_warning' => 'warning', 'trial_days_left' => $daysLeft]);
                } else {
                    session()->forget(['trial_warning', 'trial_days_left']);
                }
            }

            return $next($request);
        }

        // ── Paid plan expiry enforcement ───────────────────────────────────
        if ($hotel->plan_expires_at) {
            $planExpires = \Carbon\Carbon::parse($hotel->plan_expires_at);
            $daysLeft    = (int) $now->diffInDays($planExpires, false);

            if ($daysLeft < 0) {
                // Plan expired
                if (!$request->routeIs(['upgrade', 'upgrade.request', 'logout'])) {
                    return redirect()->route('upgrade')->with('plan_expired', true);
                }
            } elseif ($daysLeft <= 3) {
                session(['trial_warning' => $daysLeft <= 1 ? 'urgent' : 'warning', 'trial_days_left' => max(0, $daysLeft)]);
            } else {
                session()->forget(['trial_warning', 'trial_days_left']);
            }
        }

        return $next($request);
    }
}
