<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpgradeController extends Controller
{
    public function index()
    {
        $hotelId   = session('crm_hotel_id');
        $hotel     = $hotelId ? DB::table('hotels')->where('id', $hotelId)->first() : null;
        $plans     = DB::table('platform_plans')->where('is_active', true)->orderBy('sort_order')->get();

        return view('upgrade', compact('hotel', 'plans'));
    }

    public function request(Request $request)
    {
        $request->validate([
            'plan_interest' => 'required|string|max:100',
            'message'       => 'nullable|string|max:500',
        ]);

        $hotelName = session('crm_hotel_name', 'Unknown Hotel');
        $userName  = session('crm_user_name', 'Unknown User');
        $plan      = $request->plan_interest;
        $msg       = $request->message ?? '';

        $waText = "Hello! I would like to upgrade my hotel plan.\n\n"
                . "*Hotel:* {$hotelName}\n"
                . "*Contact:* {$userName}\n"
                . "*Interested Plan:* {$plan}\n"
                . ($msg ? "*Message:* {$msg}" : '');

        $waUrl = 'https://wa.me/919725225519?text=' . rawurlencode($waText);

        return redirect($waUrl);
    }
}
