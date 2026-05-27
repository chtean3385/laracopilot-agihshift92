<?php

namespace App\Livewire\Platform;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class WaInbox extends Component
{
    public string $selectedPhone = '';
    public string $replyText     = '';
    public string $sendResult    = '';
    public bool   $sending       = false;

    // Contact edit modal state
    public bool   $editingContact  = false;
    public string $editName        = '';
    public string $editType        = 'unknown';

    // Lead info popup state
    public bool   $showLeadInfo   = false;
    public string $leadInfoPhone  = '';
    public array  $leadInfo       = [];

    // ── Bulk Blast state ──────────────────────────────────────────────────
    public bool   $showBlast        = false;
    public string $blastNumbers     = '';
    public int    $blastTemplateId  = 0;
    public array  $blastVars        = [];
    public array  $blastVarNames    = [];
    public string $blastPreview     = '';
    public string $blastHeaderUrl   = '';   // image/video/document URL for media-header templates
    public string $blastHeaderFormat = '';  // none|text|image|video|document
    public array  $blastResults     = [];
    public bool   $blasting         = false;
    public bool   $blastDone        = false;
    public string $blastError       = '';

    public function mount(): void
    {
        $hotelId = (int) request()->query('hotel', 0);
        if ($hotelId > 0) {
            $hotel = DB::table('hotels')->where('id', $hotelId)->first(['phone']);
            if ($hotel?->phone) {
                $this->selectedPhone = $hotel->phone;
            }
        }
    }

    public function selectContact(string $phone): void
    {
        $this->selectedPhone  = $phone;
        $this->replyText      = '';
        $this->sendResult     = '';
        $this->editingContact = false;

        DB::table('wa_contacts')->where('phone', $phone)->update(['unread_count' => 0]);
        $this->dispatch('wa-scroll-to-bottom');
    }

    public function backToList(): void
    {
        $this->selectedPhone  = '';
        $this->replyText      = '';
        $this->sendResult     = '';
        $this->editingContact = false;
    }

    // ── Contact editing ───────────────────────────────────────────────────

    public function openEditContact(): void
    {
        $c = DB::table('wa_contacts')->where('phone', $this->selectedPhone)->first();
        if (!$c) return;
        $this->editName       = $c->display_name ?? '';
        $this->editType       = $c->contact_type ?? 'unknown';
        $this->editingContact = true;
    }

    public function saveContact(): void
    {
        $name = trim($this->editName);
        if ($name === '') {
            $this->sendResult = 'error:Name cannot be empty.';
            return;
        }

        $allowed = ['owner', 'guest', 'unknown'];
        $type    = in_array($this->editType, $allowed) ? $this->editType : 'unknown';

        DB::table('wa_contacts')->where('phone', $this->selectedPhone)->update([
            'display_name' => $name,
            'contact_type' => $type,
            'updated_at'   => now(),
        ]);

        $this->editingContact = false;
        $this->sendResult     = 'ok:Contact saved.';
    }

    public function cancelEdit(): void
    {
        $this->editingContact = false;
    }

    // ── Lead info popup ───────────────────────────────────────────────────

    public function openLeadInfo(string $phone): void
    {
        $this->leadInfoPhone = $phone;
        $lead = DB::table('whatsapp_leads')->where('phone', $phone)->first();

        if ($lead) {
            $statusLabel = match($lead->lead_status) {
                'hot'       => '🔥 HOT',
                'warm'      => '🟡 WARM',
                'cold'      => '❄️ COLD',
                'nurture'   => '💤 NURTURE',
                'opted_out' => '🚫 OPTED OUT',
                'completed' => '✅ COMPLETED',
                default     => '🆕 NEW',
            };
            $this->leadInfo = [
                'found'       => true,
                'name'        => $lead->name ?? '—',
                'hotel_name'  => $lead->hotel_name ?? '—',
                'room_count'  => $lead->room_count ?? '—',
                'software'    => $lead->current_system ?? '—',
                'role'        => $lead->role ?? '—',
                'city'        => $lead->city ?? '—',
                'timeline'    => $lead->implementation_timeline ?? '—',
                'demo'        => $lead->demo_datetime ?? '—',
                'status'      => $statusLabel,
                'raw_status'  => $lead->lead_status ?? 'new',
                'lead_score'  => $lead->lead_score ?? null,
                'step'        => $lead->current_step ?? '—',
                'last_seen'   => $lead->last_message_at
                    ? \Carbon\Carbon::parse($lead->last_message_at)->diffForHumans()
                    : '—',
            ];
        } else {
            $this->leadInfo = ['found' => false];
        }

        $this->showLeadInfo = true;
    }

    public function closeLeadInfo(): void
    {
        $this->showLeadInfo  = false;
        $this->leadInfoPhone = '';
        $this->leadInfo      = [];
    }

    // ── Subscription toggle ───────────────────────────────────────────────

    public function toggleSubscription(): void
    {
        if (empty($this->selectedPhone)) return;

        $contact = DB::table('wa_contacts')->where('phone', $this->selectedPhone)->first();
        if (!$contact) return;

        $newState = !($contact->subscribed ?? true);

        DB::table('wa_contacts')->where('phone', $this->selectedPhone)->update([
            'subscribed'      => $newState,
            'unsubscribed_at' => $newState ? null : now(),
            'updated_at'      => now(),
        ]);

        $this->sendResult = $newState
            ? 'ok:Contact re-subscribed. Bot messages will resume.'
            : 'ok:Contact unsubscribed. Bot messages are paused for this contact.';
    }

    // ── Text reply ────────────────────────────────────────────────────────

    public function sendReply(): void
    {
        $text = trim($this->replyText);
        if ($text === '') {
            $this->sendResult = 'error:Please type a message first.';
            return;
        }
        if (empty($this->selectedPhone)) {
            $this->sendResult = 'error:No contact selected.';
            return;
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->sendResult = 'error:Platform WhatsApp credentials are not configured.';
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->selectedPhone);
        if (strlen($phone) === 10) $phone = '91' . $phone;

        $contact = DB::table('wa_contacts')->where('phone', $this->selectedPhone)->first();
        $hotelId = $contact?->hotel_id;

        $this->sending = true;

        try {
            $response = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => $text, 'preview_url' => false],
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'])) {
                $this->logOutgoing($this->selectedPhone, $hotelId, $text, $body);
                $this->updateContactPreview($this->selectedPhone, $text);
                $this->replyText  = '';
                $this->sendResult = 'ok:Message sent.';
                $this->dispatch('wa-scroll-to-bottom');
            } else {
                $errMsg           = $body['error']['message'] ?? 'Unknown API error';
                $this->sendResult = 'error:Meta error: ' . $errMsg;
            }
        } catch (\Throwable $e) {
            $this->sendResult = 'error:Send failed: ' . $e->getMessage();
            Log::error('WaInbox sendReply exception: ' . $e->getMessage());
        } finally {
            $this->sending = false;
        }
    }

    // ── Attachment send (called from JS after upload) ─────────────────────

    public function sendAttachment(string $mediaId, string $mediaType, string $fileName = '', string $caption = ''): void
    {
        if (empty($this->selectedPhone) || empty($mediaId)) {
            $this->sendResult = 'error:Missing attachment data.';
            return;
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->sendResult = 'error:Platform WhatsApp credentials are not configured.';
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->selectedPhone);
        if (strlen($phone) === 10) $phone = '91' . $phone;

        $contact = DB::table('wa_contacts')->where('phone', $this->selectedPhone)->first();
        $hotelId = $contact?->hotel_id;

        $this->sending = true;

        try {
            if ($mediaType === 'image') {
                $msgPayload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'image',
                    'image'             => array_filter([
                        'id'      => $mediaId,
                        'caption' => $caption ?: null,
                    ]),
                ];
            } else {
                $msgPayload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'document',
                    'document'          => array_filter([
                        'id'       => $mediaId,
                        'filename' => $fileName ?: null,
                        'caption'  => $caption ?: null,
                    ]),
                ];
            }

            $response = Http::timeout(15)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", $msgPayload);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'])) {
                $note = ($mediaType === 'image' ? '📷 Image' : '📄 Document') . ($fileName ? ": {$fileName}" : '');
                $this->logOutgoing($this->selectedPhone, $hotelId, $note, $body);
                $this->updateContactPreview($this->selectedPhone, $note);
                $this->sendResult = 'ok:' . ($mediaType === 'image' ? 'Image' : 'Document') . ' sent.';
                $this->dispatch('wa-scroll-to-bottom');
                $this->dispatch('wa-clear-attachment');
            } else {
                $errMsg           = $body['error']['message'] ?? 'Unknown API error';
                $this->sendResult = 'error:Meta error: ' . $errMsg;
            }
        } catch (\Throwable $e) {
            $this->sendResult = 'error:Send failed: ' . $e->getMessage();
            Log::error('WaInbox sendAttachment exception: ' . $e->getMessage());
        } finally {
            $this->sending = false;
        }
    }

    // ── Bulk Blast ────────────────────────────────────────────────────────

    public function openBlast(): void
    {
        $this->showBlast          = true;
        $this->blastNumbers       = '';
        $this->blastTemplateId    = 0;
        $this->blastVars          = [];
        $this->blastVarNames      = [];
        $this->blastPreview       = '';
        $this->blastHeaderUrl     = '';
        $this->blastHeaderFormat  = '';
        $this->blastResults       = [];
        $this->blasting           = false;
        $this->blastDone          = false;
        $this->blastError         = '';
    }

    public function closeBlast(): void
    {
        $this->showBlast = false;

        // Auto-open the first successfully sent contact so messages are immediately visible
        $firstSent = collect($this->blastResults)->firstWhere('status', 'sent');
        if ($firstSent) {
            $phone = preg_replace('/[^0-9]/', '', $firstSent['phone']);
            if (strlen($phone) === 10) $phone = '91' . $phone;
            $this->selectedPhone = $phone;
            $this->dispatch('wa-scroll-to-bottom');
        }
    }

    public function selectBlastTemplate(int $id): void
    {
        if ($id === 0) {
            $this->blastTemplateId   = 0;
            $this->blastPreview      = '';
            $this->blastVarNames     = [];
            $this->blastVars         = [];
            $this->blastHeaderUrl    = '';
            $this->blastHeaderFormat = '';
            return;
        }

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->find($id);

        if (!$template) return;

        $this->blastTemplateId   = $id;
        $this->blastPreview      = $template->message_body ?? '';
        $this->blastHeaderFormat = $template->header_format ?? 'none';
        $this->blastHeaderUrl    = $template->header_media_url ?? '';

        // Extract variable names from the body
        $this->blastVarNames = $this->extractVarNames($template->message_body ?? '');
        $this->blastVars     = array_fill(0, count($this->blastVarNames), '');
    }

    public function sendBlast(): void
    {
        $this->blastError  = '';
        $this->blastResults = [];

        // Validate template
        if ($this->blastTemplateId === 0) {
            $this->blastError = 'Please select a template.';
            return;
        }

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->find($this->blastTemplateId);

        if (!$template) {
            $this->blastError = 'Template not found or no longer approved.';
            return;
        }

        // Validate phone numbers
        $lines = array_filter(array_map('trim', explode("\n", $this->blastNumbers)));
        if (empty($lines)) {
            $this->blastError = 'Please enter at least one phone number.';
            return;
        }
        if (count($lines) > 200) {
            $this->blastError = 'Maximum 200 numbers per blast. Please split into smaller batches.';
            return;
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->blastError = 'Platform WhatsApp credentials are not configured.';
            return;
        }

        // Build Meta template components
        $metaBody   = $template->convertBodyForMeta();
        preg_match_all('/\{\{(\d+)\}\}/', $metaBody, $posMatches);
        $paramCount = empty($posMatches[1]) ? 0 : max(array_map('intval', $posMatches[1]));

        $components    = [];
        $headerFmt     = $template->header_format ?? 'none';
        $headerMediaTypes = ['image', 'video', 'document'];

        // Add header component for media-header templates (image/video/document)
        // Meta requires this even when the media is "baked into" the template
        if (in_array($headerFmt, $headerMediaTypes)) {
            $mediaUrl = trim($this->blastHeaderUrl);
            if ($mediaUrl) {
                $components[] = [
                    'type'       => 'header',
                    'parameters' => [
                        [
                            'type'        => $headerFmt,
                            $headerFmt    => ['link' => $mediaUrl],
                        ],
                    ],
                ];
            }
        }

        if ($paramCount > 0) {
            $params = [];
            for ($i = 0; $i < $paramCount; $i++) {
                $val      = trim($this->blastVars[$i] ?? '');
                $params[] = ['type' => 'text', 'text' => $val ?: '-'];
            }
            $components[] = ['type' => 'body', 'parameters' => $params];
        }

        $this->blasting = true;
        $results        = [];

        foreach ($lines as $rawPhone) {
            $phone = preg_replace('/[^0-9]/', '', $rawPhone);
            if (strlen($phone) === 10) $phone = '91' . $phone;

            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $results[] = ['phone' => $rawPhone, 'status' => 'skip', 'msg' => 'Invalid number'];
                continue;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $template->template_name,
                    'language'   => ['code' => 'en_US'],
                ],
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
                    $results[] = ['phone' => $rawPhone, 'status' => 'sent', 'msg' => 'Sent'];

                    // Log the outgoing message
                    $preview = '📨 Blast: ' . $template->template_name;
                    DB::table('whatsapp_logs')->insert([
                        'direction'  => 'outgoing',
                        'event_type' => 'message_sent',
                        'phone'      => $phone,
                        'hotel_id'   => null,
                        'status'     => 'ok',
                        'payload'    => json_encode($body),
                        'notes'      => $preview,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Upsert wa_contacts so it appears in inbox
                    $existing = DB::table('wa_contacts')->where('phone', $phone)->first();
                    if ($existing) {
                        DB::table('wa_contacts')->where('phone', $phone)->update([
                            'last_message_preview' => $preview,
                            'last_message_at'      => now(),
                            'updated_at'           => now(),
                        ]);
                    } else {
                        DB::table('wa_contacts')->insert([
                            'phone'                => $phone,
                            'contact_type'        => 'unknown',
                            'display_name'        => $phone,
                            'subscribed'          => true,
                            'last_message_preview'=> $preview,
                            'last_message_at'     => now(),
                            'unread_count'        => 0,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                    }
                } else {
                    $errMsg    = $body['error']['error_user_msg'] ?? $body['error']['message'] ?? 'API error';
                    $results[] = ['phone' => $rawPhone, 'status' => 'fail', 'msg' => $errMsg];
                }
            } catch (\Throwable $e) {
                $results[]  = ['phone' => $rawPhone, 'status' => 'fail', 'msg' => $e->getMessage()];
                Log::error('WaInbox blast exception for ' . $phone . ': ' . $e->getMessage());
            }

            // Small pause to avoid Meta rate limits
            usleep(150000); // 150 ms
        }

        $this->blastResults = $results;
        $this->blasting     = false;
        $this->blastDone    = true;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Extract human-readable variable names from a template body.
     * Supports both {{guest_name}} (named) and {{1}}, {{2}} (positional) styles.
     * Returns an ordered, de-duplicated list of labels.
     */
    private function extractVarNames(string $body): array
    {
        // Named variables: {{guest_name}}, {{hotel_name}}, etc.
        preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $body, $namedMatches);
        if (!empty($namedMatches[1])) {
            return array_values(array_unique($namedMatches[1]));
        }

        // Positional: {{1}}, {{2}}, ...
        preg_match_all('/\{\{(\d+)\}\}/', $body, $numMatches);
        if (!empty($numMatches[1])) {
            $max = max(array_map('intval', $numMatches[1]));
            return array_map(fn ($i) => "Variable {$i}", range(1, $max));
        }

        return [];
    }

    private function logOutgoing(string $phone, ?int $hotelId, string $note, array $body): void
    {
        DB::table('whatsapp_logs')->insert([
            'direction'  => 'outgoing',
            'event_type' => 'message_sent',
            'phone'      => $phone,
            'hotel_id'   => $hotelId,
            'status'     => 'ok',
            'payload'    => json_encode($body),
            'notes'      => $note,
            'created_at' => now(),
            'updated_at' => now(),
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

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        $contacts = DB::table('wa_contacts')
            ->orderByDesc('last_message_at')
            ->get();

        // Pre-fetch all whatsapp_leads keyed by phone for O(1) lookup
        $leadsByPhone = DB::table('whatsapp_leads')
            ->get()
            ->keyBy('phone');

        $conversations = $contacts->map(function ($c) use ($leadsByPhone) {
            $typeLabel = match($c->contact_type) {
                'owner'   => 'Owner',
                'guest'   => 'Guest',
                default   => 'Unknown',
            };
            $typeColor = match($c->contact_type) {
                'owner'   => '#7c3aed',
                'guest'   => '#0891b2',
                default   => '#64748b',
            };
            $typeBg = match($c->contact_type) {
                'owner'   => '#ede9fe',
                'guest'   => '#e0f2fe',
                default   => '#f1f5f9',
            };

            // Try to get hotel name from whatsapp_leads first (collected by bot),
            // then fall back to hotel_id lookup (for owner/guest contacts)
            $lead      = $leadsByPhone->get($c->phone);
            $hotelName = $lead?->hotel_name ?? null;
            if (!$hotelName && $c->hotel_id) {
                $hotelName = DB::table('hotels')->where('id', $c->hotel_id)->value('name');
            }

            // Role from whatsapp_leads answers (set by the bot)
            $leadRole = $lead?->role ?? null;

            // Lead status: prefer wa_contacts.lead_status (set after scoring),
            // but also keep lead row's value in sync
            $leadStatus = $c->lead_status ?? $lead?->lead_status ?? null;

            return (object) [
                'phone'        => $c->phone,
                'hotel_id'     => $c->hotel_id,
                'hotel_name'   => $hotelName,
                'lead_role'    => $leadRole,
                'name'         => $c->display_name ?? ($hotelName ?? $c->phone),
                'type'         => $c->contact_type,
                'type_label'   => $typeLabel,
                'type_color'   => $typeColor,
                'type_bg'      => $typeBg,
                'preview'      => $c->last_message_preview ?? 'No messages yet',
                'last_at'      => $c->last_message_at,
                'time_ago'     => $c->last_message_at
                    ? Carbon::parse($c->last_message_at)->diffForHumans()
                    : 'No messages',
                'unread'       => (int)($c->unread_count ?? 0),
                'consented'    => !empty($c->consented_at),
                'subscribed'   => (bool)($c->subscribed ?? true),
                'lead_status'  => $leadStatus,
                'bot_state'    => $c->bot_state ?? null,
                'bot_service'  => $c->bot_service_interest ?? null,
                'bot_budget'   => $c->bot_budget ?? null,
            ];
        });

        $selectedContact = null;
        $messages        = collect();
        $within24h       = false;

        if ($this->selectedPhone) {
            $selectedContact = $conversations->firstWhere('phone', $this->selectedPhone);

            if ($selectedContact) {
                $messages = DB::table('whatsapp_logs')
                    ->where('phone', $this->selectedPhone)
                    ->whereIn('event_type', ['message_received', 'message_sent'])
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($log) use ($selectedContact) {
                        $isOutgoing = $log->direction === 'outgoing';
                        return (object) [
                            'id'        => $log->id,
                            'direction' => $log->direction,
                            'tag'       => $isOutgoing ? 'You (Platform)' : $selectedContact->type_label,
                            'tag_color' => $isOutgoing ? '#7c3aed' : $selectedContact->type_color,
                            'text'      => $log->notes ?? '',
                            'time'      => Carbon::parse($log->created_at)->format('d M, H:i'),
                            'is_bot'    => !$isOutgoing ? false : (str_contains($log->notes ?? '', 'Bot:') || false),
                        ];
                    });

                $lastIncoming = DB::table('whatsapp_logs')
                    ->where('phone', $this->selectedPhone)
                    ->where('direction', 'incoming')
                    ->where('event_type', 'message_received')
                    ->max('created_at');

                $within24h = $lastIncoming && Carbon::parse($lastIncoming)->diffInHours(now()) < 24;
            }
        }

        $totalUnread = DB::table('wa_contacts')->sum('unread_count');

        // Approved templates for blast modal
        $approvedTemplates = WhatsAppTemplate::withoutGlobalScopes()
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'message_body', 'trigger_event', 'header_format', 'header_media_url', 'has_buttons']);

        return view('livewire.platform.wa-inbox', [
            'conversations'     => $conversations,
            'selectedContact'   => $selectedContact,
            'messages'          => $messages,
            'within24h'         => $within24h,
            'totalUnread'       => (int) $totalUnread,
            'approvedTemplates' => $approvedTemplates,
        ]);
    }
}
