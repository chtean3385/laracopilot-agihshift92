<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $settings = Setting::first();

        if (!$settings) {
            $settings = new Setting();
            $settings->resort_name         = 'Azure Paradise Resort and Spa';
            $settings->address             = '45 Beachside Boulevard, Calangute, Goa 403516 India';
            $settings->phone               = '+91 832 267 8900';
            $settings->email               = 'reservations@azureparadise.com';
            $settings->website             = 'www.azureparadise.com';
            $settings->gst_number          = '30AABCU9603R1ZX';
            $settings->tax_rate            = '12';
            $settings->currency            = 'INR';
            $settings->currency_symbol     = 'Rs';
            $settings->check_in_time       = '14:00';
            $settings->check_out_time      = '11:00';
            $settings->cancellation_policy = 'Free cancellation up to 48 hours before check-in.';
            $settings->save();
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'resort_name'     => 'required|string|max:255',
            'address'         => 'required|string',
            'phone'           => 'required|string|max:30',
            'email'           => 'required|email',
            'check_in_time'   => 'required|string',
            'check_out_time'  => 'required|string',
            'tax_rate'        => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
        ]);

        $settings = Setting::first();

        if (!$settings) {
            Setting::create($request->except('_token', '_method'));
        } else {
            $settings->update($request->except('_token', '_method'));
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully!');
    }
}