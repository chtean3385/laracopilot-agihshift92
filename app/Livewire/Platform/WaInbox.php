<?php

namespace App\Livewire\Platform;

use App\Models\PlatformWhatsAppSetting;
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
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
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
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", $msgPayload);

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

    // ── Helpers ───────────────────────────────────────────────────────────

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

        $conversations = $contacts->map(function ($c) {
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

            $hotelName = null;
            if ($c->hotel_id) {
                $hotelName = DB::table('hotels')->where('id', $c->hotel_id)->value('name');
            }

            return (object) [
                'phone'       => $c->phone,
                'hotel_id'    => $c->hotel_id,
                'hotel_name'  => $hotelName,
                'name'        => $c->display_name ?? ($hotelName ?? $c->phone),
                'type'        => $c->contact_type,
                'type_label'  => $typeLabel,
                'type_color'  => $typeColor,
                'type_bg'     => $typeBg,
                'preview'     => $c->last_message_preview ?? 'No messages yet',
                'last_at'     => $c->last_message_at,
                'time_ago'    => $c->last_message_at
                    ? Carbon::parse($c->last_message_at)->diffForHumans()
                    : 'No messages',
                'unread'      => (int)($c->unread_count ?? 0),
                'consented'   => !empty($c->consented_at),
                'lead_status' => $c->lead_status ?? null,
                'bot_state'   => $c->bot_state ?? null,
                'bot_service' => $c->bot_service_interest ?? null,
                'bot_budget'  => $c->bot_budget ?? null,
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

        return view('livewire.platform.wa-inbox', [
            'conversations'   => $conversations,
            'selectedContact' => $selectedContact,
            'messages'        => $messages,
            'within24h'       => $within24h,
            'totalUnread'     => (int) $totalUnread,
        ]);
    }
}
