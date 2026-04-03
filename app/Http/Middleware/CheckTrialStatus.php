<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
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
        if ($request->routeIs([
            'login', 'login.post', 'logout',
            'password.*', 'register',
            'select.hotel', 'select.hotel.post',
            'upgrade', 'upgrade.request',
        ])) {
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

        $now = Carbon::now();

        // ── Trial plan enforcement ──────────────────────────────────────────
        if ($hotel->plan === 'trial') {
            $trialEnds = $hotel->trial_ends_at ? Carbon::parse($hotel->trial_ends_at) : null;

            if ($trialEnds) {
                if ($trialEnds->isPast()) {
                    session(['crm_plan_locked' => true, 'crm_lock_reason' => 'trial_expired']);
                    session()->forget(['trial_warning', 'trial_days_left']);
                    return redirect()->route('upgrade')->with('trial_expired', true);
                }

                $daysLeft = (int) $now->diffInDays($trialEnds, false);
                session()->forget('crm_plan_locked');
                if ($daysLeft <= 1) {
                    session(['trial_warning' => 'urgent', 'trial_days_left' => $daysLeft]);
                } elseif ($daysLeft <= 7) {
                    session(['trial_warning' => 'soon', 'trial_days_left' => $daysLeft]);
                } else {
                    session()->forget(['trial_warning', 'trial_days_left']);
                }
            } else {
                session()->forget(['crm_plan_locked', 'trial_warning', 'trial_days_left']);
            }

            return $next($request);
        }

        // ── Paid plan expiry enforcement ───────────────────────────────────
        if ($hotel->plan_expires_at) {
            $planExpires = Carbon::parse($hotel->plan_expires_at);

            if ($planExpires->isPast()) {
                session(['crm_plan_locked' => true, 'crm_lock_reason' => 'plan_expired']);
                session()->forget(['trial_warning', 'trial_days_left']);
                return redirect()->route('upgrade')->with('plan_expired', true);
            }

            $daysLeft = (int) $now->diffInDays($planExpires, false);
            session()->forget('crm_plan_locked');
            if ($daysLeft <= 1) {
                session(['trial_warning' => 'urgent', 'trial_days_left' => $daysLeft]);
            } elseif ($daysLeft <= 7) {
                session(['trial_warning' => 'soon', 'trial_days_left' => $daysLeft]);
            } else {
                session()->forget(['trial_warning', 'trial_days_left']);
            }
        } else {
            session()->forget(['crm_plan_locked', 'trial_warning', 'trial_days_left']);
        }

        return $next($request);
    }
}
