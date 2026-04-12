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
    public string $selectedPhone  = '';
    public string $replyText      = '';
    public string $sendResult     = '';
    public bool   $sending        = false;

    public function mount(): void
    {
        // Pre-select hotel from ?hotel=X query param (dashboard quick-chat)
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
        $this->selectedPhone = $phone;
        $this->replyText     = '';
        $this->sendResult    = '';
        DB::table('wa_contacts')->where('phone', $phone)->update(['unread_count' => 0]);
        $this->dispatch('wa-scroll-to-bottom');
    }

    public function backToList(): void
    {
        $this->selectedPhone = '';
        $this->replyText     = '';
        $this->sendResult    = '';
    }

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
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

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
                    'text'              => ['body' => $text],
                ]);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'])) {
                DB::table('whatsapp_logs')->insert([
                    'direction'  => 'outgoing',
                    'event_type' => 'message_sent',
                    'phone'      => $this->selectedPhone,
                    'hotel_id'   => $hotelId,
                    'status'     => 'ok',
                    'payload'    => json_encode($body),
                    'notes'      => $text,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update wa_contacts preview
                DB::table('wa_contacts')->where('phone', $this->selectedPhone)->update([
                    'last_message_preview' => mb_substr($text, 0, 200),
                    'last_message_at'      => now(),
                    'updated_at'           => now(),
                ]);

                $this->replyText  = '';
                $this->sendResult = 'ok:Message sent.';
                $this->dispatch('wa-scroll-to-bottom');
            } else {
                $errMsg           = $body['error']['message'] ?? 'Unknown API error';
                $this->sendResult = 'error:Meta error: ' . $errMsg;
                Log::warning('WaInbox send failed', ['body' => $body, 'phone' => $phone]);
            }
        } catch (\Throwable $e) {
            $this->sendResult = 'error:Send failed: ' . $e->getMessage();
            Log::error('WaInbox exception: ' . $e->getMessage());
        } finally {
            $this->sending = false;
        }
    }

    public function render()
    {
        // ── Conversations from wa_contacts only (phone-based, real messages only) ──
        // Only contacts who have actually sent a message appear here.
        // wa_contacts.phone is unique → automatically deduplicates multi-hotel admins
        // with the same phone number.
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

            // Hotel name for context
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
                'consented_at'=> $c->consented_at ?? null,
            ];
        });

        // ── Selected contact thread ───────────────────────────────────────
        $selectedContact = null;
        $messages        = collect();
        $within24h       = false;

        if ($this->selectedPhone) {
            $selectedContact = $conversations->firstWhere('phone', $this->selectedPhone);

            if ($selectedContact) {
                // Fetch messages by phone (both logged by hotel_id and by phone field)
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
                        ];
                    });

                // 24h window — last incoming from this phone
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
            'conversations'  => $conversations,
            'selectedContact'=> $selectedContact,
            'messages'       => $messages,
            'within24h'      => $within24h,
            'totalUnread'    => (int) $totalUnread,
        ]);
    }
}
