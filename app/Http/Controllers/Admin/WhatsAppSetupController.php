<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppSetupController extends Controller
{
    public function index()
    {
        $moduleActive = Module::isEnabled('whatsapp');
        $platform     = PlatformWhatsAppSetting::instance();
        $saasReady    = PlatformWhatsAppSetting::isFullyConfigured();
        $config       = WhatsAppConfig::first() ?? new WhatsAppConfig(['mode' => 'shared', 'setup_step' => 0, 'setup_completed' => false]);

        $hotel      = Hotel::find(session('crm_hotel_id'));
        $hotelPlan  = $hotel?->plan ?? 'basic';
        $canUseOwn  = in_array($hotelPlan, ['pro', 'enterprise', 'premium', 'business']);

        return view('admin.whatsapp.setup', compact(
            'moduleActive', 'platform', 'saasReady', 'config', 'hotelPlan', 'canUseOwn'
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

    public function embeddedComplete(Request $request)
    {
        $request->validate([
            'code'            => 'required|string',
            'waba_id'         => 'required|string',
            'phone_number_id' => 'required|string',
        ]);

        $config = WhatsAppConfig::firstOrNew([]);
        $config->fill([
            'mode'            => 'own',
            'provider'        => 'meta',
            'waba_id'         => $request->waba_id,
            'business_account_id' => $request->waba_id,
            'phone_number_id' => $request->phone_number_id,
            'is_active'       => false,
        ]);
        $config->save();

        $service = new WhatsAppSetupService();
        $results = [
            'step1' => null,
            'step2' => null,
            'step3' => null,
        ];

        if ($config->setup_step < 1) {
            $tokenResult = $service->exchangeCode($request->code);
            if (!$tokenResult['success']) {
                return response()->json([
                    'success'   => false,
                    'step'      => 1,
                    'error'     => $tokenResult['error'],
                    'is_number_conflict' => ($tokenResult['error'] === 'number_already_registered'),
                ]);
            }
            $config->update([
                'access_token' => $tokenResult['access_token'],
                'api_key'      => $tokenResult['access_token'],
                'setup_step'   => 1,
            ]);
            $results['step1'] = true;
        } else {
            $results['step1'] = true;
        }

        if ($config->setup_step < 2) {
            $webhookResult = $service->subscribeWebhook($config->waba_id, $config->access_token);
            if (!$webhookResult['success']) {
                return response()->json([
                    'success'     => false,
                    'step'        => 2,
                    'error'       => $webhookResult['error'],
                    'step1_done'  => true,
                ]);
            }
            $config->update(['setup_step' => 2]);
            $results['step2'] = true;
        } else {
            $results['step2'] = true;
        }

        if ($config->setup_step < 3) {
            $templateResult = $service->submitAllTemplates($config);
            $config->update(['setup_step' => 3]);
            $results['step3'] = ['submitted' => $templateResult['submitted'] ?? 0];

            if (!$templateResult['success']) {
                Log::warning('WhatsApp template submission partially failed', $templateResult);
            }
        } else {
            $results['step3'] = true;
        }

        $config->update([
            'setup_step'      => 5,
            'setup_completed' => true,
            'is_active'       => true,
        ]);

        return response()->json([
            'success'          => true,
            'steps_completed'  => $results,
            'templates_submitted' => is_array($results['step3']) ? $results['step3']['submitted'] : 0,
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

        return response()->json(['success' => true, 'message' => 'Ready to retry from step ' . $request->step]);
    }

    public function reset()
    {
        $config = WhatsAppConfig::first();
        if ($config) {
            $config->update([
                'mode'            => 'shared',
                'setup_step'      => 0,
                'setup_completed' => false,
                'is_active'       => false,
                'access_token'    => null,
                'api_key'         => null,
                'waba_id'         => null,
                'phone_number_id' => null,
                'business_account_id' => null,
            ]);

            WhatsAppTemplate::where('hotel_id', $config->hotel_id)->update([
                'meta_status'      => 'not_submitted',
                'meta_template_id' => null,
            ]);
        }

        return redirect()->route('whatsapp.setup')->with('success', 'WhatsApp setup has been reset. You can start fresh.');
    }
}
