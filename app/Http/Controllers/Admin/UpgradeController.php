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

        $request->validate([
            'plan_slug'        => 'required|string|max:100',
            'message'          => 'nullable|string|max:1000',
            'contact_name'     => 'nullable|string|max:150',
            'hotel_name_input' => 'nullable|string|max:200',
        ]);

        // Resolve plan from DB using submitted slug — reject unknown slugs
        $planSlug = $request->input('plan_slug');
        $plan     = DB::table('platform_plans')
                        ->where('slug', $planSlug)
                        ->where('is_active', true)
                        ->first();

        if (!$plan) {
            return back()->withErrors(['plan_slug' => 'Selected plan is not valid. Please choose from the available plans.']);
        }

        $planLabel = $plan->label . ' (₹' . number_format($plan->monthly_price) . '/माह)';
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
}
