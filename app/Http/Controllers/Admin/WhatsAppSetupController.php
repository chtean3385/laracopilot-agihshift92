<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\HotelContext;
use App\Services\WhatsApp\WhatsAppSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSetupController extends Controller
{
    public function index()
    {
        $moduleActive = Module::isEnabled('whatsapp');
        $platform             = PlatformWhatsAppSetting::instance();
        $saasReady            = PlatformWhatsAppSetting::isFullyConfigured();
        $embeddedSignupReady  = PlatformWhatsAppSetting::isEmbeddedSignupReady();
        $config               = WhatsAppConfig::first() ?? new WhatsAppConfig(['mode' => 'shared', 'setup_step' => 0, 'setup_completed' => false]);

        $hotel      = Hotel::find(app(HotelContext::class)->getHotel());
        $hotelPlan  = $hotel?->plan ?? 'basic';
        $canUseOwn  = in_array($hotelPlan, ['pro', 'pro_ai', 'standard', 'enterprise', 'premium', 'business']);

        return view('admin.whatsapp.setup', compact(
            'moduleActive', 'platform', 'saasReady', 'embeddedSignupReady', 'config', 'hotelPlan', 'canUseOwn'
        ));
    }

    public function activateShared(Request $request)
    {
        if (!PlatformWhatsAppSetting::isFullyConfigured()) {
            return response()->json([
                'success' => false,
                'error'   => 'The CRM\'s shared WhatsApp number is not yet configured. Please contact support to enable it for your account.',
            ]);
        }

        $config = WhatsAppConfig::firstOrNew([]);
        $config->fill([
            'mode'            => 'shared',
            'setup_step'      => 5,
            'setup_completed' => true,
            'is_active'       => true,
            'provider'        => 'meta',
        ]);
        $config->save();

        return response()->json(['success' => true]);
    }

    public function resumeSetup(Request $request)
    {
        $hotel    = Hotel::find(app(HotelContext::class)->getHotel());
        $plan     = $hotel?->plan ?? 'basic';
        $canOwn   = in_array($plan, ['pro', 'pro_ai', 'standard', 'enterprise', 'premium', 'business']);
        if (!$canOwn) {
            return response()->json(['success' => false, 'error' => 'Your current plan does not include the own-number WhatsApp option. Upgrade to Pro or higher to use this feature.']);
        }

        $config = WhatsAppConfig::first();
        if (!$config || $config->setup_step < 1) {
            return response()->json(['success' => false, 'step' => 1, 'error' => 'Setup state was lost. Please start over by clicking "Start Setup" again.']);
        }

        $service = app(WhatsAppSetupService::class);

        if ($config->setup_step < 2) {
            $webhookResult = $service->subscribeWebhook($config->waba_id, $config->access_token);
            if (!$webhookResult['success']) {
                return response()->json(['success' => false, 'step' => 2, 'error' => $webhookResult['error'], 'step1_done' => true]);
            }
            $config->update(['setup_step' => 2]);
        }

        if ($config->setup_step < 3) {
            $templateResult = $service->submitAllTemplates($config);
            if (!$templateResult['success'] && ($templateResult['submitted'] ?? 0) === 0) {
                return response()->json(['success' => false, 'step' => 3, 'error' => $templateResult['error'] ?? 'Could not submit message templates. Please try again.', 'step1_done' => true, 'step2_done' => true]);
            }
            $config->update(['setup_step' => 3]);
        }

        $config->update(['setup_step' => 5, 'setup_completed' => true, 'is_active' => true]);

        return response()->json(['success' => true]);
    }

    public function embeddedComplete(Request $request)
    {
        $hotel  = Hotel::find(app(HotelContext::class)->getHotel());
        $plan   = $hotel?->plan ?? 'basic';
        if (!in_array($plan, ['pro', 'pro_ai', 'standard', 'enterprise', 'premium', 'business'])) {
            return response()->json(['success' => false, 'error' => 'Your current plan does not include the own-number WhatsApp option. Upgrade to Pro or higher to use this feature.']);
        }

        $request->validate([
            'code'            => 'required|string',
            'waba_id'         => 'required|string|not_in:pending,resume',
            'phone_number_id' => 'required|string|not_in:pending,resume',
        ]);

        $config = WhatsAppConfig::firstOrNew([]);
        $config->fill([
            'mode'                => 'own',
            'provider'            => 'meta',
            'waba_id'             => $request->waba_id,
            'business_account_id' => $request->waba_id,
            'phone_number_id'     => $request->phone_number_id,
            'is_active'           => false,
        ]);
        if (!$config->exists || $config->setup_step < 1) {
            $config->setup_step = 0;
        }
        $config->save();

        $service = new WhatsAppSetupService();

        if ($config->setup_step < 1) {
            $tokenResult = $service->exchangeCode($request->code);
            if (!$tokenResult['success']) {
                return response()->json([
                    'success'            => false,
                    'step'               => 1,
                    'error'              => $tokenResult['error'],
                    'is_number_conflict' => ($tokenResult['error'] === 'number_already_registered'),
                ]);
            }
            $config->update([
                'access_token' => $tokenResult['access_token'],
                'api_key'      => $tokenResult['access_token'],
                'setup_step'   => 1,
            ]);
        }

        if ($config->setup_step < 2) {
            $webhookResult = $service->subscribeWebhook($config->waba_id, $config->access_token);
            if (!$webhookResult['success']) {
                return response()->json([
                    'success'    => false,
                    'step'       => 2,
                    'error'      => $webhookResult['error'],
                    'step1_done' => true,
                ]);
            }
            $config->update(['setup_step' => 2]);
        }

        if ($config->setup_step < 3) {
            $templateResult = $service->submitAllTemplates($config);

            if (!$templateResult['success'] && ($templateResult['submitted'] ?? 0) === 0) {
                return response()->json([
                    'success'    => false,
                    'step'       => 3,
                    'error'      => $templateResult['error'] ?? 'Could not submit message templates. Please try again.',
                    'step1_done' => true,
                    'step2_done' => true,
                ]);
            }

            $config->update(['setup_step' => 3]);
            Log::info('WhatsApp templates submitted', ['submitted' => $templateResult['submitted'] ?? 0]);
        }

        $config->update([
            'setup_step'      => 5,
            'setup_completed' => true,
            'is_active'       => true,
        ]);

        return response()->json([
            'success'             => true,
            'templates_submitted' => $config->fresh() ? WhatsAppTemplate::where('meta_status', 'submitted')->count() : 0,
        ]);
    }

    public function retryStep(Request $request)
    {
        $request->validate(['step' => 'required|integer|in:1,2,3']);

        $config = WhatsAppConfig::first();
        if (!$config) {
            return response()->json(['success' => false, 'error' => 'No setup in progress. Please start over.']);
        }

        $config->update(['setup_step' => $request->step - 1]);

        return response()->json(['success' => true]);
    }

    public function testShared(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:10']);

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_token || !$platform->saas_phone_number_id) {
            return response()->json([
                'success' => false,
                'error'   => 'Shared number is not fully configured. Contact the CRM administrator.',
            ]);
        }

        $to = preg_replace('/[^0-9]/', '', $request->phone);
        if (strlen($to) === 10 && !str_starts_with($to, '91')) {
            $to = '91' . $to;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US'],
                    ],
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent to +' . $to . '. Check your WhatsApp!',
                ]);
            }

            $errMsg  = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code');
            Log::warning('WhatsApp testShared failed', ['body' => $response->body()]);

            return response()->json([
                'success' => false,
                'error'   => $errMsg . ($errCode ? " (code {$errCode})" : ''),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp testShared exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function reset()
    {
        $config = WhatsAppConfig::first();
        if ($config) {
            WhatsAppTemplate::where('hotel_id', $config->hotel_id)->update([
                'meta_status'      => 'not_submitted',
                'meta_template_id' => null,
            ]);

            $config->update([
                'mode'                => 'shared',
                'setup_step'          => 0,
                'setup_completed'     => false,
                'is_active'           => false,
                'access_token'        => null,
                'api_key'             => null,
                'waba_id'             => null,
                'phone_number_id'     => null,
                'business_account_id' => null,
            ]);
        }

        return redirect()->route('whatsapp.setup')->with('success', 'WhatsApp setup has been reset. You can start fresh.');
    }

    public function saveNotifyPhones(\Illuminate\Http\Request $request)
    {
        $config = WhatsAppConfig::first();
        if (!$config) {
            return back()->with('error', 'No WhatsApp configuration found.');
        }

        $enabled = $request->boolean('notify_on_booking');

        // Collect non-empty phone entries from the submitted array
        $phones = collect($request->input('notify_phones', []))
            ->map(fn($p) => trim($p))
            ->filter(fn($p) => $p !== '')
            ->values()
            ->all();

        $config->update([
            'notify_on_booking' => $enabled,
            'notify_phones'     => $phones,
        ]);

        return back()->with('success', 'Owner alert settings saved.');
    }
}
