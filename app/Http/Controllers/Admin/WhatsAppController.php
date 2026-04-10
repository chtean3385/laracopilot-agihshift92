<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\HotelContext;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function config()
    {
        $config = WhatsAppConfig::first() ?? new WhatsAppConfig();
        return view('admin.whatsapp.config', compact('config'));
    }

    public function configSave(Request $request)
    {
        $data = $request->validate([
            'provider'              => 'required|in:meta,wati,interakt,gupshup,twilio',
            'api_key'               => 'nullable|string',
            'phone_number_id'       => 'nullable|string',
            'webhook_verify_token'  => 'nullable|string',
            'business_account_id'   => 'nullable|string',
            'test_phone'            => 'nullable|string',
            'is_active'             => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        WhatsAppConfig::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'WhatsApp configuration saved successfully.');
    }

    public function templates()
    {
        $allEvents   = WhatsAppTemplate::allEvents();
        $config      = WhatsAppConfig::first();
        $hotelId     = app(HotelContext::class)->getHotel();
        $hotel       = Hotel::find($hotelId);
        $isSaasAdmin = session('crm_user_role') === 'Super Admin';
        $isBasicPlan = in_array($hotel?->plan ?? 'basic', ['basic', 'trial']);
        $platform    = PlatformWhatsAppSetting::instance();
        $canEdit     = $isSaasAdmin || !$isBasicPlan;

        if ($isBasicPlan || !$hotelId) {
            $templates = WhatsAppTemplate::withoutGlobalScopes()
                ->whereNull('hotel_id')
                ->orderBy('id')
                ->get()
                ->keyBy('trigger_event');
        } else {
            $templates = WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->orderBy('id')
                ->get()
                ->keyBy('trigger_event');
        }

        return view('admin.whatsapp.templates', compact(
            'templates', 'allEvents', 'config',
            'hotel', 'isSaasAdmin', 'isBasicPlan', 'canEdit', 'platform'
        ));
    }

    public function templateCreate()
    {
        $allEvents = WhatsAppTemplate::allEvents();
        $statuses  = WhatsAppTemplate::approvalStatuses();
        return view('admin.whatsapp.template-create', compact('allEvents', 'statuses'));
    }

    public function templateStore(Request $request)
    {
        $data = $request->validate([
            'trigger_event'   => 'required|string|in:' . implode(',', array_keys(WhatsAppTemplate::allEvents())),
            'template_name'   => 'required|string|max:120',
            'message_body'    => 'required|string',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['approval_status'] = $data['approval_status'] ?? 'pending';

        WhatsAppTemplate::create($data);

        return redirect()->route('whatsapp.templates')->with('success', 'Template created successfully.');
    }

    public function templateEdit(WhatsAppTemplate $template)
    {
        $statuses = WhatsAppTemplate::approvalStatuses();
        return view('admin.whatsapp.template-edit', compact('template', 'statuses'));
    }

    public function templateSave(Request $request, WhatsAppTemplate $template)
    {
        $data = $request->validate([
            'template_name'           => 'required|string|max:120',
            'message_body'            => 'required|string',
            'approval_status'         => 'nullable|in:pending,approved,rejected',
            'is_active'               => 'nullable|boolean',
            'has_document_attachment' => 'nullable|boolean',
        ]);
        $data['is_active']               = $request->boolean('is_active');
        $data['has_document_attachment'] = $request->boolean('has_document_attachment');
        $data['approval_status']         = $data['approval_status'] ?? $template->approval_status;
        $template->update($data);
        return redirect()->route('whatsapp.templates')->with('success', 'Template saved.');
    }

    public function templateDestroy(WhatsAppTemplate $template)
    {
        $template->delete();
        return redirect()->route('whatsapp.templates')->with('success', 'Template deleted.');
    }

    public function templateToggle(WhatsAppTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        return response()->json(['is_active' => $template->is_active]);
    }

    public function submitToMeta(WhatsAppTemplate $template)
    {
        $config   = WhatsAppConfig::first();
        $platform = PlatformWhatsAppSetting::instance();

        $wabaId = ($config?->mode === 'own' && $config?->business_account_id)
            ? $config->business_account_id
            : $platform->saas_waba_id;
        $token  = $platform->saas_token;

        if (!$wabaId || !$token) {
            return response()->json(['success' => false, 'error' => 'WhatsApp Business Account ID or access token is not configured in platform settings.']);
        }

        $body     = $template->message_body;
        $varMap   = [];
        $counter  = 0;
        $metaBody = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use (&$varMap, &$counter) {
            if (!isset($varMap[$m[1]])) {
                $varMap[$m[1]] = ++$counter;
            }
            return '{{' . $varMap[$m[1]] . '}}';
        }, $body);

        $templateName = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $template->template_name), '_'));

        $bodyComponent = ['type' => 'BODY', 'text' => $metaBody];
        if (!empty($varMap)) {
            $bodyComponent['example'] = ['body_text' => [array_fill(0, count($varMap), 'sample_value')]];
        }

        $components = [];

        // For PDF document templates, include a DOCUMENT header component.
        // Meta requires this at submission time for DOCUMENT-header templates.
        if ($template->has_document_attachment) {
            $components[] = [
                'type'   => 'HEADER',
                'format' => 'DOCUMENT',
            ];
        }

        $components[] = $bodyComponent;

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$wabaId}/message_templates", [
                'name'       => $templateName,
                'language'   => 'en_US',
                'category'   => 'UTILITY',
                'components' => $components,
            ]);

        $result = $response->json();

        if ($response->successful() && isset($result['id'])) {
            $template->update([
                'meta_template_id' => $result['id'],
                'meta_status'      => 'submitted',
                'approval_status'  => 'pending',
                'template_name'    => $templateName,
            ]);
            return response()->json(['success' => true, 'message' => 'Template submitted to Meta for review. Status will update once Meta approves it.', 'meta_id' => $result['id']]);
        }

        $errMsg = $result['error']['message'] ?? 'Meta API returned an error.';
        Log::warning('Meta template submission failed', ['template' => $template->id, 'response' => $result]);
        return response()->json(['success' => false, 'error' => $errMsg]);
    }

    public function syncWati()
    {
        $config = WhatsAppConfig::first();

        if (!$config || $config->provider !== 'wati' || !$config->api_key || !$config->phone_number_id) {
            return back()->with('error', 'WATI is not configured as your active provider. Please save your WATI credentials first.');
        }

        try {
            $serverId = preg_replace('/[^a-zA-Z0-9]/', '', $config->phone_number_id);
            $token    = trim(preg_replace('/^Bearer\s+/i', '', $config->api_key));
            $url      = "https://live-server-{$serverId}.wati.io/api/v1/getMessageTemplates";

            $response = Http::withToken($token)->get($url);

            if (!$response->successful()) {
                Log::warning('WATI template sync failed', ['status' => $response->status(), 'body' => $response->body()]);
                return back()->with('error', 'Could not connect to WATI. Check your API key and Server ID.');
            }

            $watiTemplates = $response->json('messageTemplates') ?? $response->json('templates') ?? [];

            $synced = 0;
            foreach ($watiTemplates as $wt) {
                $name   = $wt['elementName'] ?? $wt['name'] ?? null;
                $status = strtolower($wt['status'] ?? 'pending');

                if (!$name) {
                    continue;
                }

                $mapped = match ($status) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    default    => 'pending',
                };

                $synced += WhatsAppTemplate::where('template_name', $name)->update(['approval_status' => $mapped]);
            }

            return back()->with('success', "Synced {$synced} template(s) from WATI. Approval statuses updated.");
        } catch (\Throwable $e) {
            Log::error('WATI sync exception: ' . $e->getMessage());
            return back()->with('error', 'WATI sync failed. Check your credentials and try again.');
        }
    }

    public function testSend(Request $request)
    {
        $request->validate(['phone' => 'required|string', 'message' => 'required|string']);

        $sent = WhatsAppService::sendRaw($request->phone, $request->message);

        if ($sent) {
            return back()->with('success', 'Test message sent successfully!');
        }

        $generic = 'Failed to send. Check your API credentials and logs.';
        $detail  = WhatsAppService::getLastError();

        $errorMsg = (config('app.debug') && $detail)
            ? $generic . ' — ' . $detail
            : $generic;

        return back()->with('error', $errorMsg);
    }
}
