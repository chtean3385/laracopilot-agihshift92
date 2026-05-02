<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

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
}
