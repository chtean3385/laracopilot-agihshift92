<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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

        // Can the hotel still extend their trial? Only if:
        // - hotel exists
        // - they are on a trial plan (trial_ends_at is set)
        // - they have not already extended once
        $canExtendTrial = $hotel
            && !empty($hotel->trial_ends_at)
            && empty($hotel->trial_extended_once);

        return view('upgrade', compact('hotel', 'plans', 'canExtendTrial'));
    }

    public function request(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $request->validate([
            'plan_slug'        => 'required|string|max:100',
            'message'          => 'nullable|string|max:1000',
            'contact_name'     => 'nullable|string|max:150',
            'hotel_name_input' => 'nullable|string|max:200',
        ]);

        $planSlug = $request->input('plan_slug');
        $plan     = DB::table('platform_plans')
                        ->where('slug', $planSlug)
                        ->where('is_active', true)
                        ->first();

        if (!$plan) {
            return back()->withErrors(['plan_slug' => 'Selected plan is not valid. Please choose from the available plans.']);
        }

        $planLabel = $plan->label . ' (₹' . number_format($plan->yearly_price) . '/वर्ष)';
        $hotelName = $request->input('hotel_name_input') ?: session('crm_hotel_name', 'Unknown Hotel');
        $userName  = $request->input('contact_name')     ?: session('crm_user_name', 'Unknown User');
        $msg       = $request->input('message', '');

        $waText = "नमस्ते! मैं अपने होटल का CRM प्लान अपग्रेड करना चाहता/चाहती हूं।\n\n"
                . "*होटल:* {$hotelName}\n"
                . "*संपर्क:* {$userName}\n"
                . "*इच्छित प्लान:* {$planLabel}\n"
                . ($msg ? "*संदेश:* {$msg}" : '');

        $waUrl = 'https://wa.me/919725225519?text=' . rawurlencode($waText);

        return redirect($waUrl);
    }

    public function extendTrial(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $hotelId = session('crm_hotel_id');
        if (!$hotelId) {
            return redirect()->route('upgrade')->with('error', 'Hotel not found.');
        }

        $hotel = DB::table('hotels')->where('id', $hotelId)->first();

        if (!$hotel) {
            return redirect()->route('upgrade')->with('error', 'Hotel not found.');
        }

        // Block if already extended
        if (!empty($hotel->trial_extended_once)) {
            return redirect()->route('upgrade')->with('error', 'आप पहले ही एक बार ट्रायल बढ़ा चुके हैं। कृपया प्लान चुनें।');
        }

        // Block if not on a trial
        if (empty($hotel->trial_ends_at)) {
            return redirect()->route('upgrade')->with('error', 'यह विकल्प केवल ट्रायल अकाउंट के लिए है।');
        }

        // Extend: if trial already expired, extend from today; otherwise from current expiry
        $currentExpiry = Carbon::parse($hotel->trial_ends_at);
        $base          = $currentExpiry->isPast() ? Carbon::now() : $currentExpiry;
        $newExpiry     = $base->addDays(3);

        DB::table('hotels')->where('id', $hotelId)->update([
            'trial_ends_at'       => $newExpiry,
            'trial_extended_once' => true,
            'updated_at'          => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', '🎉 आपका ट्रायल 3 दिन के लिए बढ़ा दिया गया है! नया समापन: ' . $newExpiry->format('d M Y'));
    }
}
