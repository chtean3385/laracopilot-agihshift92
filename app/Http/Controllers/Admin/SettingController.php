<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'hotel_id'            => session('crm_hotel_id'),
                'resort_name'         => 'Azure Paradise Resort and Spa',
                'tagline'             => 'Resort & Spa CRM',
                'address'             => '45 Beachside Boulevard, Calangute, Goa 403516 India',
                'phone'               => '+91 832 267 8900',
                'email'               => 'reservations@azureparadise.com',
                'website'             => 'www.azureparadise.com',
                'gst_number'          => '30AABCU9603R1ZX',
                'tax_rate'            => '12',
                'food_tax_rate'       => '5',
                'currency'            => 'INR',
                'currency_symbol'     => 'Rs',
                'check_in_time'       => '14:00',
                'check_out_time'      => '11:00',
                'cancellation_policy' => 'Free cancellation up to 48 hours before check-in.',
            ]);
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $request->validate([
            'resort_name'     => 'required|string|max:255',
            'tagline'         => 'nullable|string|max:150',
            'address'         => 'required|string',
            'phone'           => 'required|string|max:30',
            'email'           => 'required|email',
            'check_in_time'   => 'required|string',
            'check_out_time'  => 'required|string',
            'tax_rate'        => 'required|string|max:10',
            'food_tax_rate'   => 'nullable|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'logo'            => 'nullable|file|max:2048|mimes:jpg,jpeg,png,gif,svg,webp',
        ]);

        $settings = Setting::first() ?? new Setting(['hotel_id' => session('crm_hotel_id')]);

        $data = $request->except(['_token', '_method', 'logo']);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $file     = $request->file('logo');
            $fileName = 'resort_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $data['logo'] = $file->storeAs('logos', $fileName, 'public');

            // Also store as base64 in the database so the logo survives deployments.
            // The logo_url accessor on Setting returns this data URI on every request.
            $data['logo_data'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        if ($settings->exists) {
            $settings->update($data);
        } else {
            Setting::create($data);
        }

        ActivityLogger::log('Updated', 'Settings', 'Resort settings updated by ' . session('crm_user_name'));

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully!');
    }
}
