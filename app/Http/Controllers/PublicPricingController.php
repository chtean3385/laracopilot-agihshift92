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
        $plans = DB::table('platform_plans')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pricing', compact('plans'));
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
