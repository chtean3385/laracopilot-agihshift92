<?php

namespace App\Http\Controllers;

use App\Mail\PricingEnquiryMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PublicPricingController extends Controller
{
    public function index()
    {
        $meta = [
            'basic'    => [
                'extra_price'          => 3000,
                'all_modules_included' => false,
                'subtitle'             => 'Perfect for small hotels & startups',
                'icon'                 => 'fa-paper-plane',
                'card_color'           => '#15803d',
                'include_text'         => null,
                'popular'              => false,
            ],
            'standard' => [
                'extra_price'          => 2000,
                'all_modules_included' => false,
                'subtitle'             => 'For growing hotels & better management',
                'icon'                 => 'fa-building',
                'card_color'           => '#0369a1',
                'include_text'         => 'ALL BASIC PLAN FEATURES',
                'popular'              => false,
            ],
            'premium'  => [
                'extra_price'          => 1000,
                'all_modules_included' => false,
                'subtitle'             => 'Advanced features for your hotel',
                'icon'                 => 'fa-crown',
                'card_color'           => '#7c3aed',
                'include_text'         => 'ALL STANDARD PLAN FEATURES',
                'popular'              => true,
            ],
            'pro_ai'   => [
                'extra_price'          => 0,
                'all_modules_included' => true,
                'subtitle'             => 'AI Powered. Smarter Operations.',
                'icon'                 => 'fa-robot',
                'card_color'           => '#d97706',
                'include_text'         => 'ALL PREMIUM PLAN FEATURES',
                'popular'              => false,
            ],
        ];

        $plans = DB::table('platform_plans')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) use ($meta) {
                $m = $meta[$plan->slug] ?? [
                    'extra_price'          => 2000,
                    'all_modules_included' => false,
                    'subtitle'             => '',
                    'icon'                 => 'fa-star',
                    'card_color'           => '#4f46e5',
                    'include_text'         => null,
                    'popular'              => false,
                ];
                $plan->features            = json_decode($plan->features, true) ?? [];
                $plan->extra_price         = $m['extra_price'];
                $plan->all_modules_included = $m['all_modules_included'];
                $plan->subtitle            = $m['subtitle'];
                $plan->icon                = $m['icon'];
                $plan->card_color          = $m['card_color'];
                $plan->include_text        = $m['include_text'];
                $plan->popular             = $m['popular'];
                // Compute MRP = original price + 20%, rounded up to nearest ₹100
                $plan->original_price      = (int)(ceil($plan->yearly_price * 1.2 / 100) * 100);
                return $plan;
            });

        $modules = [
            ['name' => 'WhatsApp Automation',       'icon' => 'fa-whatsapp',       'brand' => true,  'desc' => 'Auto confirmations & reminders'],
            ['name' => 'Payment Links',              'icon' => 'fa-credit-card',    'brand' => false, 'desc' => 'UPI QR & Razorpay links'],
            ['name' => 'Pathik Autofill',            'icon' => 'fa-id-card',        'brand' => false, 'desc' => 'Gujarat Pathik portal'],
            ['name' => 'OTA Channel Manager',        'icon' => 'fa-globe',          'brand' => false, 'desc' => 'Booking.com, MakeMyTrip…'],
            ['name' => 'Time Slot & Hourly Pricing', 'icon' => 'fa-clock',          'brand' => false, 'desc' => 'Flexible slot-based pricing'],
            ['name' => 'Extra Billing',              'icon' => 'fa-file-invoice',   'brand' => false, 'desc' => 'Post-stay charge management'],
            ['name' => 'Restaurant Management',      'icon' => 'fa-utensils',       'brand' => false, 'desc' => 'Tables, KOT & room billing'],
            ['name' => 'Booking Widget',             'icon' => 'fa-calendar-check', 'brand' => false, 'desc' => 'Website booking form'],
            ['name' => 'Whole Hotel Booking',        'icon' => 'fa-hotel',          'brand' => false, 'desc' => 'Book entire property'],
            ['name' => 'Slot Search Engine',         'icon' => 'fa-search',         'brand' => false, 'desc' => 'Multi-filter availability'],
            ['name' => 'OTA WhatsApp Sync',          'icon' => 'fa-sync-alt',       'brand' => false, 'desc' => 'Import OTA bookings via WA'],
        ];

        return view('pricing', compact('plans', 'modules'));
    }

    public function enquire(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:120',
            'hotel'      => 'required|string|max:200',
            'phone'      => 'required|string|max:30',
            'plan_slug'  => 'required|string|max:60',
            'plan_label' => 'required|string|max:100',
            'plan_price' => 'required|numeric|min:0',
            'rooms'      => 'nullable|string|max:10',
            'message'    => 'nullable|string|max:500',
        ]);

        Mail::to('chetanmakwana3385@gmail.com')->send(new PricingEnquiryMail($validated));

        return response()->json(['success' => true]);
    }
}
