<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppLog;
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
            'webhook_verify_token'   => 'nullable|string|max:255',
            'is_saas_active'         => 'nullable|boolean',
            'skip_signature_check'   => 'nullable|boolean',
        ]);

        $data['is_saas_active']       = $request->boolean('is_saas_active');
        $data['skip_signature_check'] = $request->boolean('skip_signature_check');

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
                $data      = $response->json();
                $msgId     = $data['messages'][0]['id'] ?? null;
                $waId      = $data['contacts'][0]['wa_id'] ?? $phone;
                $msgIdNote = $msgId ? " Message ID: {$msgId}" : '';

                Log::info('Platform WhatsApp test send succeeded', [
                    'to'    => $waId,
                    'msgId' => $msgId,
                ]);

                if ($waId !== $phone) {
                    return back()->with('warning', "Meta accepted the message for +{$waId} (you entered +{$phone}).{$msgIdNote} If you don't receive it, ensure the number is registered as a test recipient in Meta Business Manager.");
                }

                return back()->with('success', "hello_world template queued for +{$waId}.{$msgIdNote} — Check your WhatsApp. If it doesn't arrive, add the number as a test recipient in Meta Business Manager → WhatsApp → Test Numbers.");
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
        $allTemplates = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->orderBy('id')
            ->get();

        $allEvents = WhatsAppTemplate::allEvents();

        // Standard event templates: all rows that match a known event.
        // For events with both text and PDF templates (e.g. checkout.done),
        // we flatten them into a single ordered collection so the view can
        // iterate over all of them — keyed list would silently drop duplicates.
        $standardTemplates = $allTemplates->whereIn('trigger_event', array_keys($allEvents))
            ->sortBy([['trigger_event', 'asc'], ['has_document_attachment', 'asc'], ['id', 'asc']])
            ->values();

        // Keep a keyBy for the primary (non-PDF) template per event, used for
        // event-slot indicators (showing which events have a template set).
        $standardKeyed = $standardTemplates->where('has_document_attachment', false)->keyBy('trigger_event');

        $customTemplates = $allTemplates->whereNotIn('trigger_event', array_keys($allEvents))->values();
        $platform        = PlatformWhatsAppSetting::instance();

        return view('platform.whatsapp.templates', compact(
            'standardTemplates', 'standardKeyed', 'customTemplates', 'allEvents', 'platform'
        ))->with('templates', $standardKeyed);
    }

    public function templateStore(Request $request)
    {
        $customEvent = trim($request->input('custom_trigger_event', ''));
        $triggerEvent = ($request->input('trigger_event') === '__custom__' && $customEvent !== '')
            ? strtolower(preg_replace('/[^a-z0-9._]+/', '_', $customEvent))
            : $request->input('trigger_event');

        $data = $request->merge(['trigger_event' => $triggerEvent])->validate([
            'trigger_event' => 'required|string|max:120',
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

    public function syncFromMeta()
    {
        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_waba_id || !$platform->saas_token) {
            return back()->with('error', 'WABA ID or access token not configured in Platform Settings.');
        }

        try {
            $response = Http::timeout(20)
                ->withToken($platform->saas_token)
                ->get("https://graph.facebook.com/v19.0/{$platform->saas_waba_id}/message_templates", [
                    'fields' => 'name,status,id',
                    'limit'  => 200,
                ]);

            if (!$response->successful()) {
                $err = $response->json('error.message') ?? $response->body();
                return back()->with('error', 'Meta API error: ' . $err);
            }

            $metaTemplates = collect($response->json('data') ?? [])
                ->keyBy(fn($t) => strtolower($t['name']));

            $dbTemplates = WhatsAppTemplate::withoutGlobalScopes()
                ->whereNull('hotel_id')
                ->get();

            $updated = 0;
            foreach ($dbTemplates as $tmpl) {
                $key  = strtolower($tmpl->template_name);
                $meta = $metaTemplates->get($key);
                if (!$meta) continue;

                $newStatus     = strtolower($meta['status']) === 'approved' ? 'approved'
                    : (strtolower($meta['status']) === 'rejected' ? 'rejected' : 'pending');
                $newMetaStatus = strtolower($meta['status']) === 'approved' ? 'approved' : 'submitted';

                $tmpl->update([
                    'approval_status'  => $newStatus,
                    'meta_status'      => $newMetaStatus,
                    'meta_template_id' => $meta['id'] ?? $tmpl->meta_template_id,
                ]);
                $updated++;
            }

            return back()->with('success', "Synced {$updated} template(s) from Meta. Statuses are now up to date.");
        } catch (\Throwable $e) {
            Log::error('Platform WhatsApp syncFromMeta error: ' . $e->getMessage());
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function templateSave(Request $request, int $id)
    {
        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->findOrFail($id);

        $data = $request->validate([
            'template_name'           => 'required|string|max:120',
            'message_body'            => 'required|string',
            'approval_status'         => 'nullable|in:pending,approved,rejected',
            'is_active'               => 'nullable|boolean',
            'has_document_attachment' => 'nullable|boolean',
        ]);

        $data['is_active']               = $request->boolean('is_active');
        $data['has_document_attachment'] = ($data['trigger_event'] ?? $template->trigger_event) === 'checkout.done'
            ? $request->boolean('has_document_attachment')
            : false;

        // ── Auto-version when body text changes ─────────────────────────
        $bodyChanged = trim($data['message_body']) !== trim($template->message_body ?? '');
        if ($bodyChanged) {
            // Strip any existing _v{N} suffix to get the stable base name
            $baseName = preg_replace('/_v\d+$/', '', $template->template_name);
            $baseName = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $baseName), '_'));

            // Find next available version number (v2, v3, …)
            $version = 2;
            while (WhatsAppTemplate::withoutGlobalScopes()
                ->whereNull('hotel_id')
                ->where('template_name', $baseName . '_v' . $version)
                ->where('id', '!=', $id)
                ->exists()) {
                $version++;
            }

            $data['template_name']  = $baseName . '_v' . $version;
            $data['approval_status'] = 'pending';
            $data['meta_status']    = 'not_submitted';
        } else {
            $data['approval_status'] = $data['approval_status'] ?? $template->approval_status;
        }

        $template->update($data);

        $msg = $bodyChanged
            ? 'Template body updated. Name auto-versioned to <strong>' . $data['template_name'] . '</strong> — status reset to Pending. Click "Submit to Meta" to send for review.'
            : 'Template updated successfully.';

        return redirect()->route('platform.whatsapp.templates')->with('success', $msg);
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

        // Pre-validate: Meta rejects templates that start or end with a variable
        $trimmed = trim($metaBody);
        if (preg_match('/^\{\{\d+\}\}/', $trimmed)) {
            return response()->json([
                'success' => false,
                'error'   => 'Template body cannot start with a variable (e.g. {{guest_name}}). Add some text before the first variable and try again.',
            ]);
        }
        if (preg_match('/\{\{\d+\}\}\s*$/', $trimmed)) {
            // Find which named variable is at the end
            $origTrimmed = trim($template->message_body ?? '');
            preg_match('/\{\{(\w+)\}\}\s*$/', $origTrimmed, $endMatch);
            $endVar = $endMatch[1] ?? 'variable';
            return response()->json([
                'success' => false,
                'error'   => 'Template body cannot end with a variable. The last item is {{' . $endVar . '}}. '
                           . 'Edit the template and add some text after it (e.g. change "Thank you! — {{hotel_name}}" to "Thank you for choosing {{hotel_name}}!").',
            ]);
        }

        preg_match_all('/\{\{\d+\}\}/', $metaBody, $matches);
        $varCount = count(array_unique($matches[0]));

        $bodyComponent = ['type' => 'BODY', 'text' => $metaBody];
        if ($varCount > 0) {
            $bodyComponent['example'] = ['body_text' => [array_fill(0, $varCount, 'sample_value')]];
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
            return response()->json(['success' => true, 'message' => 'Template submitted to Meta for review.', 'meta_id' => $result['id']]);
        }

        // Show Meta's detailed user-facing message when available
        $errMsg = $result['error']['error_user_msg']
               ?? $result['error']['message']
               ?? 'Meta API returned an error.';
        Log::warning('Platform Meta template submission failed', ['id' => $id, 'response' => $result]);
        return response()->json(['success' => false, 'error' => $errMsg]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Webhook Logs Viewer
    // ──────────────────────────────────────────────────────────────────────

    public function webhookLogs(Request $request)
    {
        $query = WhatsAppLog::orderByDesc('created_at');

        if ($filter = $request->query('type')) {
            $query->where('event_type', $filter);
        }
        if ($dir = $request->query('direction')) {
            $query->where('direction', $dir);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $logs      = $query->paginate(50)->withQueryString();
        $webhookUrl = url('/webhook/whatsapp');
        $platform   = PlatformWhatsAppSetting::instance();

        return view('platform.whatsapp.logs', compact('logs', 'webhookUrl', 'platform'));
    }

    public function clearLogs()
    {
        WhatsAppLog::where('created_at', '<', now()->subDays(30))->delete();
        return back()->with('success', 'Logs older than 30 days cleared.');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Media Upload — uploads file to Meta and returns media_id
    // File is NEVER stored locally; streamed directly to Meta's API.
    // ──────────────────────────────────────────────────────────────────────

    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:jpeg,jpg,png,pdf',
        ]);

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['error' => 'Platform WhatsApp credentials not configured.'], 422);
        }

        $file     = $request->file('file');
        $mime     = $file->getMimeType();
        $origName = $file->getClientOriginalName();

        try {
            $response = Http::timeout(30)
                ->withToken($platform->saas_token)
                ->attach('file', file_get_contents($file->getRealPath()), $origName, ['Content-Type' => $mime])
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mime,
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['id'])) {
                return response()->json([
                    'media_id' => $body['id'],
                    'mime'     => $mime,
                    'name'     => $origName,
                    'type'     => str_starts_with($mime, 'image/') ? 'image' : 'document',
                ]);
            }

            $err = $body['error']['message'] ?? 'Unknown Meta API error';
            Log::warning('WA media upload failed', ['body' => $body]);
            return response()->json(['error' => 'Meta upload error: ' . $err], 422);
        } catch (\Throwable $e) {
            Log::error('WA media upload exception: ' . $e->getMessage());
            return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------------------
    // Managed Numbers — add hotel's own number under the platform's WABA
    // -----------------------------------------------------------------------

    public function numbers()
    {
        $hotels  = Hotel::orderBy('name')->get();
        $configs = WhatsAppConfig::where('mode', 'managed')->get()->keyBy('hotel_id');
        $platform = PlatformWhatsAppSetting::instance();
        return view('platform.whatsapp.numbers', compact('hotels', 'configs', 'platform'));
    }

    public function registerNumber(Request $request)
    {
        $request->validate([
            'hotel_id'     => 'required|integer|exists:hotels,id',
            'phone_number' => 'required|string|min:10|max:15',
            'display_name' => 'required|string|max:100',
            'country_code' => 'required|string|max:5',
        ]);

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_token || !$platform->saas_waba_id) {
            return response()->json(['success' => false, 'error' => 'Platform Meta credentials are not configured. Save the platform WhatsApp settings first.']);
        }

        $phone = preg_replace('/[^0-9]/', '', $request->phone_number);
        $cc    = preg_replace('/[^0-9]/', '', $request->country_code);

        // Step 1: Add phone number to WABA
        try {
            $addResp = Http::timeout(20)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_waba_id}/phone_numbers", [
                    'verified_name' => $request->display_name,
                    'code_method'   => 'SMS',
                    'language'      => 'en',
                    'cc'            => $cc,
                    'phone_number'  => $phone,
                ]);

            $addBody = $addResp->json();

            if (!$addResp->successful() || empty($addBody['id'])) {
                $errMsg  = $addBody['error']['message'] ?? $addResp->body();
                $errCode = $addBody['error']['code'] ?? null;
                Log::warning('WA managed: add number failed', ['body' => $addBody]);
                return response()->json(['success' => false, 'error' => $errMsg . ($errCode ? " (code {$errCode})" : '')]);
            }

            $phoneNumberId = $addBody['id'];

        } catch (\Throwable $e) {
            Log::error('WA managed: add number exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Meta API error: ' . $e->getMessage()]);
        }

        // Step 2: Request OTP to that number
        try {
            $otpResp = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/request_code", [
                    'code_method' => 'SMS',
                    'language'    => 'en',
                ]);

            if (!$otpResp->successful()) {
                $otpBody = $otpResp->json();
                $errMsg  = $otpBody['error']['message'] ?? $otpResp->body();
                Log::warning('WA managed: request OTP failed', ['body' => $otpBody]);
                return response()->json(['success' => false, 'error' => 'Number registered but OTP request failed: ' . $errMsg]);
            }
        } catch (\Throwable $e) {
            Log::error('WA managed: request OTP exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Number registered but OTP request failed: ' . $e->getMessage()]);
        }

        // Save config
        $config = WhatsAppConfig::firstOrNew(['hotel_id' => $request->hotel_id]);
        $config->fill([
            'mode'                 => 'managed',
            'provider'             => 'meta',
            'phone_number_id'      => $phoneNumberId,
            'phone_number'         => $cc . $phone,
            'managed_display_name' => $request->display_name,
            'managed_otp_status'   => 'pending',
            'setup_step'           => 1,
            'setup_completed'      => false,
            'is_active'            => false,
        ]);
        $config->save();

        return response()->json([
            'success'         => true,
            'phone_number_id' => $phoneNumberId,
            'config_id'       => $config->id,
            'message'         => "OTP sent via SMS to +{$cc}{$phone}. Ask the hotel owner for the code.",
        ]);
    }

    public function requestOtp(Request $request, int $configId)
    {
        $config = WhatsAppConfig::findOrFail($configId);

        if ($config->mode !== 'managed' || !$config->phone_number_id) {
            return response()->json(['success' => false, 'error' => 'Invalid config or number not registered yet.']);
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_token) {
            return response()->json(['success' => false, 'error' => 'Platform credentials not configured.']);
        }

        try {
            $resp = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$config->phone_number_id}/request_code", [
                    'code_method' => 'SMS',
                    'language'    => 'en',
                ]);

            if ($resp->successful()) {
                $config->update(['managed_otp_status' => 'pending']);
                return response()->json(['success' => true, 'message' => 'OTP re-sent via SMS.']);
            }

            $err = $resp->json('error.message') ?? $resp->body();
            return response()->json(['success' => false, 'error' => $err]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function verifyOtp(Request $request, int $configId)
    {
        $request->validate(['code' => 'required|string|min:4|max:8']);

        $config = WhatsAppConfig::findOrFail($configId);

        if ($config->mode !== 'managed' || !$config->phone_number_id) {
            return response()->json(['success' => false, 'error' => 'Invalid config.']);
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_token) {
            return response()->json(['success' => false, 'error' => 'Platform credentials not configured.']);
        }

        try {
            $resp = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$config->phone_number_id}/verify_code", [
                    'code' => $request->code,
                ]);

            $body = $resp->json();

            if ($resp->successful() && ($body['success'] ?? false)) {
                $config->update([
                    'managed_otp_status' => 'verified',
                    'setup_completed'    => true,
                    'setup_step'         => 5,
                    'is_active'          => true,
                ]);
                return response()->json(['success' => true, 'message' => 'Number verified and activated! The hotel can now send messages from their own number.']);
            }

            $err = $body['error']['message'] ?? 'Invalid OTP. Please check the code and try again.';
            return response()->json(['success' => false, 'error' => $err]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function removeNumber(int $configId)
    {
        $config = WhatsAppConfig::findOrFail($configId);

        if ($config->mode !== 'managed') {
            return response()->json(['success' => false, 'error' => 'This is not a managed number.']);
        }

        $config->update([
            'mode'                 => 'shared',
            'phone_number_id'      => null,
            'phone_number'         => null,
            'managed_display_name' => null,
            'managed_otp_status'   => null,
            'setup_completed'      => false,
            'setup_step'           => 0,
            'is_active'            => false,
        ]);

        return response()->json(['success' => true]);
    }
}
