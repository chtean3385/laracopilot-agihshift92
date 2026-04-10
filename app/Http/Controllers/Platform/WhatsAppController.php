<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppTemplate;
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
            'meta_app_id'          => 'nullable|string|max:255',
            'meta_app_secret'      => 'nullable|string|max:255',
            'meta_config_id'       => 'nullable|string|max:255',
            'saas_token'           => 'nullable|string',
            'saas_phone_number_id' => 'nullable|string|max:255',
            'saas_waba_id'         => 'nullable|string|max:255',
            'webhook_verify_token' => 'nullable|string|max:255',
            'is_saas_active'       => 'nullable|boolean',
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
            $response = Http::timeout(15)
                ->withToken($settings->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$settings->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US'],
                    ],
                ]);

            if ($response->successful()) {
                return back()->with('success', "Test message (hello_world template) sent to +{$phone} successfully!");
            }

            $err     = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code');
            Log::warning('Platform WhatsApp test send failed', ['body' => $response->body()]);
            return back()->with('error', 'Test failed: ' . $err . ($errCode ? " (code {$errCode})" : ''));
        } catch (\Throwable $e) {
            Log::error('Platform WhatsApp test exception: ' . $e->getMessage());
            return back()->with('error', 'Could not reach Meta\'s servers: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Global Template Management (Basic Plan / Shared Number Templates)
    // ──────────────────────────────────────────────────────────────────────

    public function templates()
    {
        $templates = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->orderBy('id')
            ->get()
            ->keyBy('trigger_event');

        $allEvents = WhatsAppTemplate::allEvents();
        $platform  = PlatformWhatsAppSetting::instance();

        return view('platform.whatsapp.templates', compact('templates', 'allEvents', 'platform'));
    }

    public function templateStore(Request $request)
    {
        $data = $request->validate([
            'trigger_event' => 'required|string|in:' . implode(',', array_keys(WhatsAppTemplate::allEvents())),
            'template_name' => 'required|string|max:120',
            'message_body'  => 'required|string',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['hotel_id']        = null;
        $data['approval_status'] = 'pending';
        $data['meta_status']     = 'not_submitted';

        WhatsAppTemplate::withoutGlobalScopes()->create($data);

        return redirect()->route('platform.whatsapp.templates')->with('success', 'Global template created successfully.');
    }

    public function templateSave(Request $request, int $id)
    {
        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->findOrFail($id);

        $data = $request->validate([
            'template_name'   => 'required|string|max:120',
            'message_body'    => 'required|string',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['approval_status'] = $data['approval_status'] ?? $template->approval_status;

        $template->update($data);

        return redirect()->route('platform.whatsapp.templates')->with('success', 'Template updated successfully.');
    }

    public function templateDestroy(int $id)
    {
        WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->findOrFail($id)
            ->delete();

        return redirect()->route('platform.whatsapp.templates')->with('success', 'Template deleted.');
    }

    public function templateToggle(int $id)
    {
        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->findOrFail($id);

        $template->update(['is_active' => !$template->is_active]);

        return response()->json(['is_active' => $template->is_active]);
    }

    public function submitToMeta(int $id)
    {
        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->findOrFail($id);

        $platform = PlatformWhatsAppSetting::instance();
        $wabaId   = $platform->saas_waba_id;
        $token    = $platform->saas_token;

        if (!$wabaId || !$token) {
            return response()->json(['success' => false, 'error' => 'WABA ID or platform access token is not configured in Platform Settings.']);
        }

        $metaBody     = $template->convertBodyForMeta();
        $templateName = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $template->template_name), '_'));

        preg_match_all('/\{\{\d+\}\}/', $metaBody, $matches);
        $varCount = count(array_unique($matches[0]));

        $bodyComponent = ['type' => 'BODY', 'text' => $metaBody];
        if ($varCount > 0) {
            $bodyComponent['example'] = ['body_text' => [array_fill(0, $varCount, 'sample_value')]];
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$wabaId}/message_templates", [
                'name'       => $templateName,
                'language'   => 'en_US',
                'category'   => 'UTILITY',
                'components' => [$bodyComponent],
            ]);

        $result = $response->json();

        if ($response->successful() && isset($result['id'])) {
            $template->update([
                'meta_template_id' => $result['id'],
                'meta_status'      => 'submitted',
                'approval_status'  => 'pending',
                'template_name'    => $templateName,
            ]);
            return response()->json(['success' => true, 'message' => 'Template submitted to Meta for review.', 'meta_id' => $result['id']]);
        }

        $errMsg = $result['error']['message'] ?? 'Meta API returned an error.';
        Log::warning('Platform Meta template submission failed', ['id' => $id, 'response' => $result]);
        return response()->json(['success' => false, 'error' => $errMsg]);
    }
}
