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
            session()->forget(['crm_hotel_suspended', 'crm_trial_expired', 'crm_trial_extended_once']);
            return $next($request);
        }

        // Skip auth/logout/upgrade/password routes
        if ($request->routeIs([
            'login', 'login.post', 'logout',
            'password.*', 'register',
            'select.hotel', 'select.hotel.post',
            'upgrade', 'upgrade.request', 'upgrade.extend-trial',
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

        // ── Suspended hotel ─────────────────────────────────────────────────
        // Don't lock access — let them through everywhere.
        // Dashboard shows a suspension banner (reads crm_hotel_suspended).
        if ($hotel->status === 'suspended') {
            session(['crm_hotel_suspended' => true]);
            session()->forget(['crm_trial_expired', 'crm_trial_extended_once', 'crm_plan_locked', 'trial_warning', 'trial_days_left']);
            return $next($request);
        }
        session()->forget('crm_hotel_suspended');

        $now = Carbon::now();

        // ── Trial plan enforcement ──────────────────────────────────────────
        if ($hotel->plan === 'trial') {
            $trialEnds = $hotel->trial_ends_at ? Carbon::parse($hotel->trial_ends_at) : null;

            if ($trialEnds) {
                if ($trialEnds->isPast()) {
                    // Trial expired — set flags so dashboard can show the right banner.
                    // Redirect any non-dashboard page back to dashboard (not upgrade page).
                    session([
                        'crm_trial_expired'        => true,
                        'crm_trial_extended_once'  => (bool) $hotel->trial_extended_once,
                    ]);
                    session()->forget(['crm_plan_locked', 'trial_warning', 'trial_days_left']);

                    if (!$request->routeIs('dashboard')) {
                        return redirect()->route('dashboard');
                    }
                    return $next($request);
                }

                // Trial still active — clear expired flags
                session()->forget(['crm_trial_expired', 'crm_trial_extended_once', 'crm_plan_locked']);

                $daysLeft = (int) $now->diffInDays($trialEnds, false);
                if ($daysLeft <= 1) {
                    session(['trial_warning' => 'urgent', 'trial_days_left' => $daysLeft]);
                } elseif ($daysLeft <= 7) {
                    session(['trial_warning' => 'soon', 'trial_days_left' => $daysLeft]);
                } else {
                    session()->forget(['trial_warning', 'trial_days_left']);
                }
            } else {
                session()->forget(['crm_plan_locked', 'crm_trial_expired', 'crm_trial_extended_once', 'trial_warning', 'trial_days_left']);
            }

            return $next($request);
        }

        // Not trial — clear trial/suspended flags
        session()->forget(['crm_trial_expired', 'crm_trial_extended_once', 'crm_hotel_suspended']);

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
