<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SaHotelFilterController extends Controller
{
    public function filter(Request $request): mixed
    {
        if (session('crm_user_role') !== 'Super Admin') {
            return redirect()->route('dashboard');
        }

        $hotelId = $request->input('hotel_id');
        session(['crm_sa_hotel_filter' => $hotelId ? (int) $hotelId : null]);

        return back();
    }
}
