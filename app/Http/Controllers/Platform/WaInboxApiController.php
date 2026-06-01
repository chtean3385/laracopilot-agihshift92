<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaInboxApiController extends Controller
{
    // ── Config ────────────────────────────────────────────────────────────

    public function config()
    {
        return response()->json([
            'pusher_enabled' => false,
            'pusher_key'     => null,
            'pusher_cluster' => null,
        ]);
    }

    // ── Contacts list ─────────────────────────────────────────────────────

    public function contacts(Request $request)
    {
        $archived = (bool) $request->query('archived', false);
        $search   = (string) $request->query('search', '');

        $contacts = DB::table('wa_contacts')
            ->when(!$archived, fn($q) => $q->where(fn($q2) =>
                $q2->where('is_archived', false)->orWhereNull('is_archived')
            ))
            ->when($archived, fn($q) => $q->where('is_archived', true))
            ->orderByDesc('last_message_at')
            ->get();

        // Pre-fetch all leads keyed by phone
        $leadsByPhone = DB::table('whatsapp_leads')->get()->keyBy('phone');

        $mapped = $contacts->map(fn($c) => $this->mapContact($c, $leadsByPhone));

        // Apply search filter
        if ($search !== '') {
            $s = mb_strtolower($search);
            $mapped = $mapped->filter(fn($c) =>
                str_contains(mb_strtolower($c['name']), $s) || str_contains($c['phone'], $s)
            )->values();
        }

        $archivedCount = DB::table('wa_contacts')->where('is_archived', true)->count();
        $totalUnread   = (int) DB::table('wa_contacts')->sum('unread_count');

        return response()->json([
            'contacts'      => $mapped->values(),
            'archived_count'=> $archivedCount,
            'total_unread'  => $totalUnread,
        ]);
    }

    // ── Messages for a contact ─────────────────────────────────────────────

    public function messages(Request $request, string $phone)
    {
        $phone = urldecode($phone);

        $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
        if (!$contact) {
            return response()->json(['messages' => [], 'contact' => null, 'within24h' => false]);
        }

        // Mark read
        DB::table('wa_contacts')->where('phone', $phone)->update(['unread_count' => 0]);

        $typeLabel = match($contact->contact_type) {
            'owner' => 'Owner', 'guest' => 'Guest', default => 'Unknown',
        };
        $typeColor = match($contact->contact_type) {
            'owner' => '#7c3aed', 'guest' => '#0891b2', default => '#64748b',
        };

        $msgs = DB::table('whatsapp_logs')
            ->where('phone', $phone)
            ->whereIn('event_type', ['message_received', 'message_sent'])
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'direction'  => $log->direction,
                'is_outgoing'=> $log->direction === 'outgoing',
                'tag'        => $log->direction === 'outgoing' ? 'You (Platform)' : $typeLabel,
                'tag_color'  => $log->direction === 'outgoing' ? '#7c3aed' : $typeColor,
                'text'       => $log->notes ?? '',
                'time'       => Carbon::parse($log->created_at)->format('d M, H:i'),
            ]);

        $lastIncoming = DB::table('whatsapp_logs')
            ->where('phone', $phone)
            ->where('direction', 'incoming')
            ->where('event_type', 'message_received')
            ->max('created_at');

        $within24h = $lastIncoming && Carbon::parse($lastIncoming)->diffInHours(now()) < 24;

        $leadsByPhone = DB::table('whatsapp_leads')->where('phone', $phone)->get()->keyBy('phone');
        $mappedContact = $this->mapContact($contact, $leadsByPhone);

        return response()->json([
            'messages'  => $msgs,
            'contact'   => $mappedContact,
            'within24h' => $within24h,
        ]);
    }

    // ── Send text message ─────────────────────────────────────────────────

    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'text'  => 'required|string|max:4096',
        ]);

        $phone    = $request->input('phone');
        $text     = trim($request->input('text'));
        $platform = PlatformWhatsAppSetting::instance();

        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['error' => 'Platform WhatsApp credentials not configured.'], 422);
        }

        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

        $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
        $hotelId = $contact?->hotel_id;

        try {
            $response = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $numericPhone,
                    'type'              => 'text',
                    'text'              => ['body' => $text, 'preview_url' => false],
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'])) {
                $this->logOutgoing($phone, $hotelId, $text, $body);
                $this->updateContactPreview($phone, $text);
                return response()->json(['ok' => true, 'message' => 'Message sent.']);
            }

            $errMsg = $body['error']['message'] ?? 'Unknown API error';
            return response()->json(['error' => 'Meta error: ' . $errMsg], 422);
        } catch (\Throwable $e) {
            Log::error('WaInboxApi send exception: ' . $e->getMessage());
            return response()->json(['error' => 'Send failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Send template ─────────────────────────────────────────────────────

    public function sendTemplate(Request $request)
    {
        $request->validate([
            'phone'       => 'required|string',
            'template_id' => 'required|integer',
            'vars'        => 'nullable|array',
        ]);

        $phone      = $request->input('phone');
        $vars       = $request->input('vars', []);
        $platform   = PlatformWhatsAppSetting::instance();

        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['error' => 'Platform WhatsApp credentials not configured.'], 422);
        }

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->find($request->input('template_id'));

        if (!$template) {
            return response()->json(['error' => 'Template not found or not approved.'], 422);
        }

        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

        $parameters = array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], array_values($vars));

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $numericPhone,
            'type'              => 'template',
            'template'          => [
                'name'       => $template->template_name,
                'language'   => ['code' => 'en'],   // templates submitted to Meta with 'en', not 'en_US'
                'components' => $parameters ? [['type' => 'body', 'parameters' => $parameters]] : [],
            ],
        ];

        try {
            $response = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'])) {
                $preview = '📨 Template: ' . $template->template_name;
                $this->logOutgoing($phone, DB::table('wa_contacts')->where('phone', $phone)->value('hotel_id'), $preview, $body);
                $this->updateContactPreview($phone, $preview);
                return response()->json(['ok' => true]);
            }

            $errMsg = $body['error']['message'] ?? 'Unknown API error';
            return response()->json(['error' => 'Meta error: ' . $errMsg], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Bulk blast ─────────────────────────────────────────────────────────

    public function blast(Request $request)
    {
        $request->validate([
            'template_id'    => 'required|integer',
            'leads'          => 'required|array|min:1|max:500',
            'leads.*.phone'  => 'required|string',
            'leads.*.vars'   => 'nullable|array',
            'leads.*.name'   => 'nullable|string',
            'header_url'     => 'nullable|string',
        ]);

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['error' => 'Platform WhatsApp credentials not configured.'], 422);
        }

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->find($request->input('template_id'));

        if (!$template) {
            return response()->json(['error' => 'Template not found or not approved.'], 422);
        }

        $metaBody   = $template->convertBodyForMeta();
        preg_match_all('/\{\{(\d+)\}\}/', $metaBody, $posMatches);
        $paramCount = empty($posMatches[1]) ? 0 : max(array_map('intval', $posMatches[1]));

        // Build header component once (shared across all leads)
        $headerComponents = [];
        $headerFmt        = $template->header_format ?? 'none';
        $headerUrl        = trim($request->input('header_url', ''));
        if (in_array($headerFmt, ['image', 'video', 'document']) && $headerUrl) {
            $mediaId = str_starts_with($headerUrl, 'http')
                ? $this->uploadHeaderMedia($headerUrl, $headerFmt, $platform)
                : $headerUrl;
            if ($mediaId) {
                $headerComponents[] = [
                    'type'       => 'header',
                    'parameters' => [['type' => $headerFmt, $headerFmt => ['id' => $mediaId]]],
                ];
            }
        }

        $results = [];

        foreach ($request->input('leads') as $lead) {
            $phone    = $lead['phone'];
            $name     = $lead['name'] ?? '';
            $vars     = $lead['vars'] ?? [];
            $rawPhone = $phone;

            $numericPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

            $components = $headerComponents;
            if ($paramCount > 0) {
                $params = [];
                for ($i = 0; $i < $paramCount; $i++) {
                    $params[] = ['type' => 'text', 'text' => trim($vars[$i] ?? '') ?: '-'];
                }
                $components[] = ['type' => 'body', 'parameters' => $params];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $numericPhone,
                'type'              => 'template',
                'template'          => ['name' => $template->template_name, 'language' => ['code' => 'en']],   // must match submission language
            ];
            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            try {
                $response = Http::timeout(12)
                    ->withToken($platform->saas_token)
                    ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", $payload);

                $body = $response->json();

                if ($response->successful() && isset($body['messages'])) {
                    $results[] = ['phone' => $rawPhone, 'name' => $name, 'status' => 'sent', 'msg' => 'Sent'];
                    $preview = '📨 ' . ($name ? "Campaign: {$name}" : 'Blast: ' . $template->template_name);
                    DB::table('whatsapp_logs')->insert([
                        'direction'  => 'outgoing', 'event_type' => 'message_sent',
                        'phone'      => $numericPhone, 'hotel_id' => null, 'status' => 'ok',
                        'payload'    => json_encode($body), 'notes' => '📨 Campaign: ' . $template->template_name,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $existing = DB::table('wa_contacts')->where('phone', $numericPhone)->first();
                    if ($existing) {
                        DB::table('wa_contacts')->where('phone', $numericPhone)->update([
                            'display_name'         => $name ?: ($existing->display_name ?? $numericPhone),
                            'last_message_preview' => $preview,
                            'last_message_at'      => now(),
                            'updated_at'           => now(),
                        ]);
                    } else {
                        DB::table('wa_contacts')->insert([
                            'phone' => $numericPhone, 'contact_type' => 'unknown',
                            'display_name' => $name ?: $numericPhone, 'subscribed' => true,
                            'last_message_preview' => $preview, 'last_message_at' => now(),
                            'unread_count' => 0, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                } else {
                    $errMsg    = $body['error']['error_user_msg'] ?? $body['error']['message'] ?? 'API error';
                    $results[] = ['phone' => $rawPhone, 'name' => $name, 'status' => 'fail', 'msg' => $errMsg];
                }
            } catch (\Throwable $e) {
                $results[] = ['phone' => $rawPhone, 'name' => $name, 'status' => 'fail', 'msg' => $e->getMessage()];
            }

            usleep(150000);
        }

        return response()->json(['results' => $results]);
    }

    // ── Update contact ─────────────────────────────────────────────────────

    public function updateContact(Request $request, string $phone)
    {
        $phone = urldecode($phone);
        $request->validate(['display_name' => 'required|string|min:1|max:255', 'contact_type' => 'nullable|string']);
        $allowed = ['owner', 'guest', 'unknown'];
        $type    = in_array($request->input('contact_type'), $allowed) ? $request->input('contact_type') : 'unknown';

        DB::table('wa_contacts')->where('phone', $phone)->update([
            'display_name' => trim($request->input('display_name')),
            'contact_type' => $type,
            'updated_at'   => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Archive / unarchive / delete ───────────────────────────────────────

    public function archive(string $phone)
    {
        $phone = urldecode($phone);
        DB::table('wa_contacts')->where('phone', $phone)->update(['is_archived' => true, 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function unarchive(string $phone)
    {
        $phone = urldecode($phone);
        DB::table('wa_contacts')->where('phone', $phone)->update(['is_archived' => false, 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function deleteContact(string $phone)
    {
        $phone = urldecode($phone);
        DB::table('whatsapp_logs')->where('phone', $phone)->delete();
        DB::table('whatsapp_leads')->where('phone', $phone)->delete();
        DB::table('wa_contacts')->where('phone', $phone)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Toggle subscription ───────────────────────────────────────────────

    public function toggleSubscribe(string $phone)
    {
        $phone   = urldecode($phone);
        $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
        if (!$contact) return response()->json(['error' => 'Contact not found.'], 404);

        $newState = !($contact->subscribed ?? true);
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'subscribed'      => $newState,
            'unsubscribed_at' => $newState ? null : now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'ok'         => true,
            'subscribed' => $newState,
            'message'    => $newState ? 'Contact re-subscribed.' : 'Contact unsubscribed.',
        ]);
    }

    // ── Templates list ────────────────────────────────────────────────────

    public function templates()
    {
        $templates = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'message_body', 'header_format', 'header_media_url', 'has_buttons'])
            ->map(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->template_name,
                'body'          => $t->message_body,
                'header_format' => $t->header_format ?? 'none',
                'header_url'    => $t->header_media_url ?? '',
                'var_names'     => $this->extractVarNames($t->message_body ?? ''),
            ]);

        return response()->json(['templates' => $templates]);
    }

    // ── Lead info ─────────────────────────────────────────────────────────

    public function leadInfo(string $phone)
    {
        $phone = urldecode($phone);
        $lead  = DB::table('whatsapp_leads')->where('phone', $phone)->first();

        if (!$lead) {
            return response()->json(['found' => false]);
        }

        $statusLabel = match($lead->lead_status) {
            'hot'       => '🔥 HOT',
            'warm'      => '🟡 WARM',
            'cold'      => '❄️ COLD',
            'nurture'   => '💤 NURTURE',
            'opted_out' => '🚫 OPTED OUT',
            'completed' => '✅ COMPLETED',
            default     => '🆕 NEW',
        };

        return response()->json([
            'found'      => true,
            'name'       => $lead->name ?? '—',
            'hotel_name' => $lead->hotel_name ?? '—',
            'room_count' => $lead->room_count ?? '—',
            'software'   => $lead->current_system ?? '—',
            'role'       => $lead->role ?? '—',
            'city'       => $lead->city ?? '—',
            'timeline'   => $lead->implementation_timeline ?? '—',
            'demo'       => $lead->demo_datetime ?? '—',
            'status'     => $statusLabel,
            'raw_status' => $lead->lead_status ?? 'new',
            'step'       => $lead->current_step ?? '—',
            'last_seen'  => $lead->last_message_at
                ? Carbon::parse($lead->last_message_at)->diffForHumans()
                : '—',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function mapContact(object $c, $leadsByPhone): array
    {
        $typeLabel = match($c->contact_type) {
            'owner' => 'Owner', 'guest' => 'Guest', default => 'Unknown',
        };
        $typeColor = match($c->contact_type) {
            'owner' => '#7c3aed', 'guest' => '#0891b2', default => '#64748b',
        };
        $typeBg = match($c->contact_type) {
            'owner' => '#ede9fe', 'guest' => '#e0f2fe', default => '#f1f5f9',
        };

        $lead      = $leadsByPhone->get($c->phone);
        $hotelName = $lead?->hotel_name ?? null;
        if (!$hotelName && $c->hotel_id) {
            $hotelName = DB::table('hotels')->where('id', $c->hotel_id)->value('name');
        }

        $leadStatus = $c->lead_status ?? $lead?->lead_status ?? null;

        return [
            'phone'       => $c->phone,
            'hotel_id'    => $c->hotel_id,
            'hotel_name'  => $hotelName,
            'name'        => $c->display_name ?? ($hotelName ?? $c->phone),
            'type'        => $c->contact_type,
            'type_label'  => $typeLabel,
            'type_color'  => $typeColor,
            'type_bg'     => $typeBg,
            'preview'     => $c->last_message_preview ?? 'No messages yet',
            'time_ago'    => $c->last_message_at
                ? Carbon::parse($c->last_message_at)->diffForHumans()
                : 'No messages',
            'unread'      => (int)($c->unread_count ?? 0),
            'subscribed'  => (bool)($c->subscribed ?? true),
            'lead_status' => $leadStatus,
            'bot_state'   => $c->bot_state ?? null,
            'is_archived' => (bool)($c->is_archived ?? false),
        ];
    }

    private function extractVarNames(string $body): array
    {
        preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $body, $namedMatches);
        if (!empty($namedMatches[1])) {
            return array_values(array_unique($namedMatches[1]));
        }
        preg_match_all('/\{\{(\d+)\}\}/', $body, $numMatches);
        if (!empty($numMatches[1])) {
            $max = max(array_map('intval', $numMatches[1]));
            return array_map(fn($i) => "Variable {$i}", range(1, $max));
        }
        return [];
    }

    private function uploadHeaderMedia(string $url, string $type, $platform): ?string
    {
        try {
            $download = Http::timeout(20)->get($url);
            if (!$download->successful()) return null;

            $fileContents = $download->body();
            $contentType  = trim(explode(';', $download->header('Content-Type') ?: match($type) {
                'image' => 'image/jpeg', 'video' => 'video/mp4', default => 'application/pdf',
            })[0]);

            $upload = Http::timeout(30)
                ->withToken($platform->saas_token)
                ->attach('file', $fileContents, 'header.' . explode('/', $contentType)[1])
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => $contentType,
                ]);

            return ($upload->successful() && isset($upload->json()['id']))
                ? (string) $upload->json()['id']
                : null;
        } catch (\Throwable $e) {
            Log::error('WaInboxApi uploadHeaderMedia exception: ' . $e->getMessage());
            return null;
        }
    }

    private function logOutgoing(string $phone, ?int $hotelId, string $note, array $body): void
    {
        DB::table('whatsapp_logs')->insert([
            'direction'  => 'outgoing', 'event_type' => 'message_sent',
            'phone'      => $phone, 'hotel_id' => $hotelId, 'status' => 'ok',
            'payload'    => json_encode($body), 'notes' => $note,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function updateContactPreview(string $phone, string $text): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'last_message_preview' => mb_substr($text, 0, 200),
            'last_message_at'      => now(),
            'updated_at'           => now(),
        ]);
    }
}
