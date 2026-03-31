<?php

namespace App\Http\Middleware;

use App\Services\HotelContext;
use Closure;
use Illuminate\Http\Request;

class SetHotelContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip installer, health, Pathik extension, and Platform Admin routes
        if ($request->is('install*') || $request->is('health') || $request->is('up') || $request->is('platform*')) {
            return $next($request);
        }

        // Skip the Pathik extension fetch endpoint (api_token auth, no session)
        if ($request->is('pathik/pending') && $request->isMethod('GET')) {
            return $next($request);
        }

        $hotelId = session('crm_hotel_id');

        if ($hotelId) {
            app(HotelContext::class)->setHotel((int) $hotelId);
            return $next($request);
        }

        // Super Admin hotel-scoped view (optional filter stored in session)
        if (session('crm_user_role') === 'Super Admin') {
            $saFilter = session('crm_sa_hotel_filter');
            if ($saFilter) {
                app(HotelContext::class)->setHotel((int) $saFilter);
            }
            return $next($request);
        }

        // Logged-in regular user with no hotel selected → force hotel picker
        if (session('crm_logged_in') && session('crm_user_role') !== 'Super Admin') {
            if (!$request->routeIs(['select.hotel', 'select.hotel.post', 'login', 'login.post', 'logout', 'password.*', 'register'])) {
                return redirect()->route('select.hotel');
            }
        }

        return $next($request);
    }
}
