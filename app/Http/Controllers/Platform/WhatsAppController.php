<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformWhatsAppSetting;
use App\Services\WhatsApp\Providers\MetaProvider;
use App\Models\WhatsAppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function settings()
    {
        $settings = PlatformWhatsAppSetting::instance() ?? new PlatformWhatsAppSetting();
        return view('platform.whatsapp.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'meta_app_id'        => 'nullable|string|max:255',
            'meta_app_secret'    => 'nullable|string|max:255',
            'meta_config_id'     => 'nullable|string|max:255',
            'saas_token'         => 'nullable|string',
            'saas_phone_number_id' => 'nullable|string|max:255',
            'saas_waba_id'       => 'nullable|string|max:255',
            'webhook_verify_token' => 'nullable|string|max:255',
            'is_saas_active'     => 'nullable|boolean',
        ]);

        $data['is_saas_active'] = $request->boolean('is_saas_active');

        PlatformWhatsAppSetting::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Platform WhatsApp settings saved successfully.');
    }

    public function testSharedNumber(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $settings = PlatformWhatsAppSetting::instance();
        if (!$settings || !$settings->saas_token || !$settings->saas_phone_number_id) {
            return back()->with('error', 'Shared WhatsApp number is not configured yet.');
        }

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (!str_starts_with($phone, '91') && strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        try {
            $response = Http::withToken($settings->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$settings->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => 'Test message from ' . config('app.name') . ' — your WhatsApp integration is working!'],
                ]);

            if ($response->successful()) {
                return back()->with('success', "Test message sent to {$request->phone} successfully!");
            }

            $err = $response->json('error.message') ?? $response->body();
            Log::warning('Platform WhatsApp test send failed', ['body' => $response->body()]);
            return back()->with('error', 'Test failed: ' . $err);
        } catch (\Throwable $e) {
            Log::error('Platform WhatsApp test exception: ' . $e->getMessage());
            return back()->with('error', 'Could not reach Meta\'s servers. Check your internet connection.');
        }
    }
}
