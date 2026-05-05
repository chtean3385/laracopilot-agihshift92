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

        // Shared-mode and managed hotels both use the platform's WABA, so both get global templates.
        // Managed hotels can additionally override per-event with hotel-specific copies.
        $usePlatformTemplates = $config && (
            $config->isSharedMode()
            || ($config->isManagedMode() && ($config->use_platform_templates || $config->use_platform_templates === null))
        );

        // Load global (platform) templates — keyed by trigger_event for easy lookup
        $globalTemplates = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->get()
            ->groupBy('trigger_event');

        $platformApprovedNames = $globalTemplates->flatten()
            ->where('approval_status', 'approved')
            ->pluck('template_name')
            ->filter()->values()->all();

        if ($isBasicPlan || !$hotelId) {
            // Basic plan: only global templates
            $allTemplates = WhatsAppTemplate::withoutGlobalScopes()
                ->whereNull('hotel_id')
                ->orderBy('has_document_attachment')
                ->orderBy('id')
                ->get();
        } elseif ($usePlatformTemplates) {
            // Managed hotel using platform templates:
            // Hotel-specific templates OVERRIDE global ones per event; global fills the gaps.
            $hotelTemplates = WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->get()
                ->groupBy(fn($t) => $t->trigger_event . '_' . (int)$t->has_document_attachment);

            $merged = collect();
            foreach ($globalTemplates as $event => $eventGlobal) {
                foreach ($eventGlobal as $globalT) {
                    $key        = $event . '_' . (int)$globalT->has_document_attachment;
                    $hotelMatch = ($hotelTemplates[$key] ?? collect())->first();
                    // Mark global template with a flag so the view knows it's from platform
                    if ($hotelMatch) {
                        $merged->push($hotelMatch);         // hotel override wins
                    } else {
                        $globalT->is_global_fallback = true; // flag for view
                        $merged->push($globalT);
                    }
                }
            }
            // Also include any hotel-only events not in global
            $hotelOnly = WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->whereNotIn('trigger_event', $globalTemplates->keys()->all())
                ->get();
            $allTemplates = $merged->merge($hotelOnly)
                ->sortBy('has_document_attachment')->sortBy('id');
        } else {
            // Own-number hotel: hotel-specific templates take priority.
            // For events with NO hotel-specific template, fall back to global platform templates
            // so OTA / Owner-Alert events are always covered even when "Use Platform Templates" is OFF.
            $hotelTemplates = WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->get();

            $hotelEventKeys = $hotelTemplates->pluck('trigger_event')->unique()->all();

            $globalFallbacks = collect();
            foreach ($globalTemplates as $event => $eventGlobal) {
                if (in_array($event, $hotelEventKeys)) continue; // hotel has own template for this event
                foreach ($eventGlobal as $gT) {
                    $gT->is_global_fallback = true;
                    $globalFallbacks->push($gT);
                }
            }

            $allTemplates = $hotelTemplates->merge($globalFallbacks)
                ->sortBy('has_document_attachment')->sortBy('id');
        }

        // Primary (text-only) template per event
        $templates        = $allTemplates->where('has_document_attachment', false)->keyBy('trigger_event');
        $templatesByEvent = $allTemplates->groupBy('trigger_event');

        return view('admin.whatsapp.templates', compact(
            'templates', 'templatesByEvent', 'allEvents', 'config',
            'hotel', 'isSaasAdmin', 'isBasicPlan', 'canEdit', 'platform',
            'platformApprovedNames', 'usePlatformTemplates', 'globalTemplates'
        ));
    }

    public function togglePlatformTemplates(Request $request)
    {
        $config = WhatsAppConfig::active();
        if (!$config || !$config->isManagedMode()) {
            return response()->json(['success' => false, 'error' => 'Only managed-number hotels can use this setting.']);
        }
        $enabled = $request->boolean('enabled');
        $config->update(['use_platform_templates' => $enabled]);
        return response()->json(['success' => true, 'enabled' => $enabled]);
    }

    public function templateCustomize(WhatsAppTemplate $template)
    {
        // Copy a global (platform) template into a hotel-specific customizable copy
        $hotelId = app(HotelContext::class)->getHotel();
        $hotel   = Hotel::find($hotelId);
        if (!$hotelId || !$hotel || $template->hotel_id !== null) {
            return redirect()->route('whatsapp.template.edit', $template);
        }

        $hotelSlug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($hotel->slug ?: $hotel->name)), '_');
        $baseName  = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $template->template_name), '_'));
        $newName   = $baseName . '_' . $hotelSlug;

        // Check if hotel copy already exists for this event
        $existing = WhatsAppTemplate::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('trigger_event', $template->trigger_event)
            ->where('has_document_attachment', $template->has_document_attachment)
            ->first();

        if (!$existing) {
            $existing = WhatsAppTemplate::create([
                'hotel_id'                => $hotelId,
                'trigger_event'           => $template->trigger_event,
                'template_name'           => $newName,
                'message_body'            => $template->message_body,
                'variables_hint'          => $template->variables_hint,
                'is_active'               => $template->is_active,
                'has_document_attachment' => $template->has_document_attachment,
                'approval_status'         => 'pending',
                'meta_status'             => 'not_submitted',
            ]);
        }

        return redirect()->route('whatsapp.template.edit', $existing)
            ->with('info', 'Platform template copied for your hotel. Edit it and submit to Meta for approval.');
    }

    public function templateCreate()
    {
        $allEvents = WhatsAppTemplate::allEvents();
        $statuses  = WhatsAppTemplate::approvalStatuses();
        $hotelId   = app(HotelContext::class)->getHotel();
        $hotel     = Hotel::find($hotelId);
        $hotelSlug = $hotel ? \Illuminate\Support\Str::slug($hotel->name, '_') : '';
        return view('admin.whatsapp.template-create', compact('allEvents', 'statuses', 'hotel', 'hotelSlug'));
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

        // Auto-append hotel slug to template_name for custom hotel templates
        $hotelId = app(HotelContext::class)->getHotel();
        if ($hotelId) {
            $hotel     = Hotel::find($hotelId);
            $hotelSlug = $hotel ? trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($hotel->slug ?: $hotel->name)), '_') : '';
            $baseName  = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $data['template_name']), '_'));
            if ($hotelSlug && !str_ends_with($baseName, '_' . $hotelSlug)) {
                $baseName .= '_' . $hotelSlug;
            }
            $data['template_name'] = $baseName;
        }

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
        // PDF attachment is only valid for checkout.done templates
        $data['has_document_attachment'] = $template->trigger_event === 'checkout.done'
            ? $request->boolean('has_document_attachment')
            : false;
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

        // Trim body and convert named vars to positional numbers {{1}}, {{2}} ...
        $body     = trim($template->message_body);
        $varMap   = [];
        $counter  = 0;
        $metaBody = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use (&$varMap, &$counter) {
            if (!isset($varMap[$m[1]])) {
                $varMap[$m[1]] = ++$counter;
            }
            return '{{' . $varMap[$m[1]] . '}}';
        }, $body);

        // Meta rule: no variable at the start or end of ANY line
        $varPattern = '/^\s*\{\{\d+\}\}|^\{\{\d+\}\}/m';   // starts a line
        $varEndPat  = '/\{\{\d+\}\}\s*$/m';                  // ends a line
        $badLines   = [];
        foreach (explode("\n", $metaBody) as $i => $line) {
            $trimLine = trim($line);
            if (preg_match('/^\{\{\d+\}\}/', $trimLine) || preg_match('/\{\{\d+\}\}$/', $trimLine)) {
                $badLines[] = 'Line ' . ($i + 1) . ': "' . $line . '"';
            }
        }
        if (!empty($badLines)) {
            return response()->json([
                'success' => false,
                'error'   => 'Meta rejects variables at the start or end of a line. Fix these line(s) in your template — add text before/after the variable: ' . implode('; ', $badLines),
            ]);
        }

        $baseName     = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $template->template_name), '_'));
        $hotel        = $template->hotel_id ? \App\Models\Hotel::find($template->hotel_id) : null;
        $hotelSlug    = $hotel ? trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($hotel->slug ?: $hotel->name)), '_') : '';
        $templateName = $hotelSlug && !str_ends_with($baseName, '_' . $hotelSlug) ? $baseName . '_' . $hotelSlug : $baseName;

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

        $payload = [
            'name'       => $templateName,
            'language'   => 'en_US',
            'category'   => 'UTILITY',
            'components' => $components,
        ];

        Log::info('Meta template submission payload', ['template' => $template->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$wabaId}/message_templates", $payload);

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

        $err         = $result['error'] ?? [];
        $errMsg      = $err['message'] ?? 'Meta API returned an error.';
        $errUserMsg  = $err['error_user_msg'] ?? '';
        $errSubcode  = $err['error_subcode'] ?? '';
        $errCode     = $err['code'] ?? '';
        $fbtrace     = $err['fbtrace_id'] ?? '';

        Log::warning('Meta template submission failed', [
            'template' => $template->id,
            'name'     => $templateName,
            'response' => $result,
        ]);

        // Build a helpful message for the user
        $display = $errMsg;
        if ($errUserMsg) {
            $display .= ' — ' . $errUserMsg;
        }
        if ($errSubcode) {
            $display .= ' (subcode: ' . $errSubcode . ')';
        }

        return response()->json(['success' => false, 'error' => $display, 'meta_error' => $err]);
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

                $update = ['approval_status' => $mapped];
                if ($mapped === 'approved') {
                    $update['is_active'] = true;
                } elseif ($mapped === 'rejected') {
                    $update['is_active'] = false;
                }

                $synced += WhatsAppTemplate::where('template_name', $name)->update($update);
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

    public function syncFromMeta(Request $request)
    {
        $platform = \App\Models\PlatformWhatsAppSetting::instance();
        if (!$platform || !$platform->saas_waba_id || !$platform->saas_token) {
            return response()->json(['success' => false, 'error' => 'Platform WABA credentials not configured.']);
        }

        $hotelId = app(\App\Services\HotelContext::class)->getHotel();
        if (!$hotelId) {
            return response()->json(['success' => false, 'error' => 'No hotel context found.']);
        }

        try {
            $resp = Http::timeout(20)
                ->withToken($platform->saas_token)
                ->get("https://graph.facebook.com/v19.0/{$platform->saas_waba_id}/message_templates", [
                    'fields' => 'name,status,id,components',
                    'limit'  => 200,
                ]);

            if (!$resp->successful()) {
                $err = $resp->json('error.message') ?? $resp->body();
                return response()->json(['success' => false, 'error' => 'Meta API error: ' . $err]);
            }

            $metaTemplates = collect($resp->json('data') ?? [])->keyBy(fn($t) => strtolower($t['name']));

            $hotel     = Hotel::find($hotelId);
            $hotelSlug = $hotel ? trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($hotel->slug ?: $hotel->name)), '_') : '';

            // Load ALL db templates — global (null) + this hotel's own rows
            $dbTemplates = WhatsAppTemplate::withoutGlobalScopes()
                ->where(fn($q) => $q->whereNull('hotel_id')->orWhere('hotel_id', $hotelId))
                ->get();

            // Track which Meta template names we've already matched in the DB
            $matchedMetaNames = [];

            $updated = 0;
            foreach ($dbTemplates as $tmpl) {
                if (!$tmpl->template_name) continue;
                $baseName = strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', $tmpl->template_name), '_'));
                $key  = ($hotelSlug && !str_ends_with($baseName, '_' . $hotelSlug))
                    ? $baseName . '_' . $hotelSlug
                    : $baseName;
                $meta = $metaTemplates->get($key) ?? $metaTemplates->get($baseName);
                if (!$meta) continue;

                $matchedMetaNames[] = strtolower($meta['name']);

                $newStatus = match(strtolower($meta['status'])) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    default    => 'pending',
                };

                $updateData = [
                    'approval_status'  => $newStatus,
                    'meta_status'      => $newStatus === 'approved' ? 'approved' : 'submitted',
                    'meta_template_id' => $meta['id'] ?? $tmpl->meta_template_id,
                ];

                if ($newStatus === 'approved') {
                    $updateData['is_active'] = true;
                } elseif ($newStatus === 'rejected') {
                    $updateData['is_active'] = false;
                }

                $tmpl->update($updateData);
                $updated++;
            }

            // ── Import newly approved Meta templates not yet in DB ─────────────
            // Name-pattern → trigger_event mapping (most specific first)
            $nameEventMap = [
                'booking_alert_owner'    => 'booking.alert.owner',
                'owner_alert'            => 'booking.alert.owner',
                'ota_booking_confirmed'  => 'ota_booking_confirmed',
                'ota_booking_conflict'   => 'ota_booking_conflict',
                'ota_confirmed'          => 'ota_booking_confirmed',
                'ota_conflict'           => 'ota_booking_conflict',
                'website_booking'        => 'website.booking.received',
                'booking_confirm'        => 'booking.created',
                'booking_created'        => 'booking.created',
                'new_booking'            => 'booking.created',
                'check_in_reminder'      => 'checkin.tomorrow',
                'checkin_reminder'       => 'checkin.tomorrow',
                'arrival_welcome'        => 'checkin.done',
                'checkin_done'           => 'checkin.done',
                'check_out'              => 'checkout.done',
                'checkout'               => 'checkout.done',
                'feedback'               => 'feedback.request',
                'payment_receipt'        => 'payment.received',
                'payment_received'       => 'payment.received',
            ];

            $imported = 0;
            foreach ($metaTemplates as $metaName => $meta) {
                if (in_array($metaName, $matchedMetaNames)) continue;
                $status = strtolower($meta['status'] ?? '');
                if ($status !== 'approved') continue;

                // Guess trigger_event from template name
                $triggerEvent = $metaName; // default: use raw name
                foreach ($nameEventMap as $pattern => $event) {
                    if (str_contains($metaName, $pattern)) {
                        $triggerEvent = $event;
                        break;
                    }
                }

                // Extract body text from components if available
                $bodyText = '';
                foreach (($meta['components'] ?? []) as $comp) {
                    if (strtolower($comp['type'] ?? '') === 'body') {
                        $bodyText = $comp['text'] ?? '';
                        break;
                    }
                }

                WhatsAppTemplate::withoutGlobalScopes()->create([
                    'hotel_id'         => null,
                    'name'             => ucwords(str_replace('_', ' ', $meta['name'])),
                    'template_name'    => $meta['name'],
                    'trigger_event'    => $triggerEvent,
                    'message_body'     => $bodyText,
                    'approval_status'  => 'approved',
                    'meta_status'      => 'approved',
                    'meta_template_id' => $meta['id'] ?? null,
                    'is_active'        => true,
                ]);
                $imported++;
            }

            $total = $updated + $imported;
            $msg   = "Synced {$updated} existing + imported {$imported} new template(s) from Meta.";
            return response()->json(['success' => true, 'updated' => $total, 'message' => $msg]);
        } catch (\Throwable $e) {
            Log::error('Hotel WhatsApp syncFromMeta error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function testSendJson(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (!str_starts_with($phone, '91') && strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        // Resolve token + phone_number_id the same way the platform test does
        $config   = WhatsAppConfig::active();
        $platform = PlatformWhatsAppSetting::instance();

        if ($config && $config->isSharedMode()) {
            if (!$platform || !$platform->saas_token || !$platform->saas_phone_number_id) {
                return response()->json(['success' => false, 'error' => 'Shared number credentials not configured.']);
            }
            $token         = $platform->saas_token;
            $phoneNumberId = $platform->saas_phone_number_id;
        } elseif ($config && $config->isManagedMode()) {
            if (!$platform || !$platform->saas_token || !$config->phone_number_id) {
                return response()->json(['success' => false, 'error' => 'Managed number credentials not configured.']);
            }
            $token         = $platform->saas_token;
            $phoneNumberId = $config->phone_number_id;
        } elseif ($config && $config->api_key && $config->phone_number_id) {
            $token         = $config->api_key;
            $phoneNumberId = $config->phone_number_id;
        } else {
            return response()->json(['success' => false, 'error' => 'No active WhatsApp configuration found.']);
        }

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US'],
                    ],
                ]);

            if ($response->successful()) {
                $data  = $response->json();
                $msgId = $data['messages'][0]['id'] ?? null;
                $waId  = $data['contacts'][0]['wa_id'] ?? $phone;
                $note  = $msgId ? " (ID: {$msgId})" : '';
                return response()->json(['success' => true, 'message' => "hello_world template queued for +{$waId}.{$note} Check your WhatsApp!"]);
            }

            $err     = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code');
            Log::warning('Hotel WhatsApp test send failed', ['body' => $response->body()]);
            return response()->json(['success' => false, 'error' => 'Send failed: ' . $err . ($errCode ? " (code {$errCode})" : '')]);
        } catch (\Throwable $e) {
            Log::error('Hotel WhatsApp test exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Could not reach Meta: ' . $e->getMessage()]);
        }
    }
}
