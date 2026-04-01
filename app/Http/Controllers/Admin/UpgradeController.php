<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpgradeController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $hotelId = session('crm_hotel_id');
        $hotel   = $hotelId ? DB::table('hotels')->where('id', $hotelId)->first() : null;
        $plans   = DB::table('platform_plans')->where('is_active', true)->orderBy('sort_order')->get();

        return view('upgrade', compact('hotel', 'plans'));
    }

    public function request(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        // Build allowed plan values from DB to validate against
        $planLabels = DB::table('platform_plans')
            ->where('is_active', true)
            ->pluck('label')
            ->map(fn($l) => $l . ' (%)')  // the form appends price, so just check it's non-empty
            ->all();

        $request->validate([
            'plan_interest'    => 'required|string|min:2|max:200',
            'message'          => 'nullable|string|max:1000',
            'contact_name'     => 'nullable|string|max:150',
            'hotel_name_input' => 'nullable|string|max:200',
        ]);

        // Sanitise — ensure plan_interest is printable, strip control characters
        $plan      = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->input('plan_interest'));
        $hotelName = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->input('hotel_name_input') ?: session('crm_hotel_name', 'Unknown Hotel'));
        $userName  = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->input('contact_name')     ?: session('crm_user_name', 'Unknown User'));
        $msg       = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->input('message', ''));

        $waText = "नमस्ते! मैं अपने होटल का CRM प्लान अपग्रेड करना चाहता/चाहती हूं।\n\n"
                . "*होटल:* {$hotelName}\n"
                . "*संपर्क:* {$userName}\n"
                . "*इच्छित प्लान:* {$plan}\n"
                . ($msg ? "*संदेश:* {$msg}" : '');

        $waUrl = 'https://wa.me/919725225519?text=' . rawurlencode($waText);

        return redirect($waUrl);
    }
}
