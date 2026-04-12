<?php

namespace App\Livewire\Platform;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WaInboxConversation;
use App\Models\WhatsAppLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class WaInbox extends Component
{
    public int    $selectedConversationId = 0;
    public string $replyText              = '';
    public string $sendResult             = '';
    public bool   $sending                = false;

    public function selectConversation(int $id): void
    {
        $this->selectedConversationId = $id;
        $this->replyText              = '';
        $this->sendResult             = '';

        WaInboxConversation::where('id', $id)->update(['unread_count' => 0]);
    }

    public function backToList(): void
    {
        $this->selectedConversationId = 0;
        $this->replyText              = '';
        $this->sendResult             = '';
    }

    public function sendReply(): void
    {
        $text = trim($this->replyText);
        if ($text === '') {
            $this->sendResult = 'error:Please type a message first.';
            return;
        }

        $convo = WaInboxConversation::find($this->selectedConversationId);
        if (!$convo) {
            $this->sendResult = 'error:Conversation not found.';
            return;
        }

        if (!$convo->isWithin24hWindow()) {
            $this->sendResult = 'error:Cannot send — outside the 24-hour Meta messaging window.';
            return;
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->sendResult = 'error:Platform WhatsApp credentials are not configured.';
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $convo->phone);

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
                WhatsAppLog::record(
                    'outgoing',
                    'message_sent',
                    'ok',
                    $body,
                    $convo->phone,
                    $convo->hotel_id,
                    $text
                );

                $preview = mb_substr($text, 0, 200);
                $convo->update([
                    'last_message_preview' => $preview,
                    'last_message_at'      => now(),
                ]);

                $this->replyText  = '';
                $this->sendResult = 'ok:Message sent successfully.';
            } else {
                $errMsg           = $body['error']['message'] ?? 'Unknown error';
                $this->sendResult = 'error:Meta API error: ' . $errMsg;
                Log::warning('WaInbox send failed', ['body' => $body, 'phone' => $phone]);
            }
        } catch (\Throwable $e) {
            $this->sendResult = 'error:Send failed: ' . $e->getMessage();
            Log::error('WaInbox send exception: ' . $e->getMessage());
        } finally {
            $this->sending = false;
        }
    }

    public function render()
    {
        $conversations = WaInboxConversation::with('hotel')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($c) {
                $hotel = $c->hotel ?? DB::table('hotels')->where('id', $c->hotel_id)->first();
                return (object) [
                    'id'                => $c->id,
                    'hotel_id'          => $c->hotel_id,
                    'hotel_name'        => $hotel?->name ?? 'Unknown Hotel',
                    'phone'             => $c->phone,
                    'last_message_at'   => $c->last_message_at,
                    'time_ago'          => $c->last_message_at ? Carbon::parse($c->last_message_at)->diffForHumans() : 'No messages',
                    'last_message_preview' => $c->last_message_preview,
                    'unread_count'      => $c->unread_count,
                    'within_24h'        => $c->isWithin24hWindow(),
                ];
            });

        $selectedConvo   = null;
        $messages        = collect();
        $within24h       = false;

        if ($this->selectedConversationId) {
            $selectedConvo = $conversations->firstWhere('id', $this->selectedConversationId);

            if ($selectedConvo) {
                $within24h = $selectedConvo->within_24h;

                $messages = WhatsAppLog::where('phone', $selectedConvo->phone)
                    ->whereIn('event_type', ['message_received', 'message_sent'])
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($log) {
                        return (object) [
                            'id'         => $log->id,
                            'direction'  => $log->direction,
                            'text'       => $log->notes ?? '',
                            'created_at' => $log->created_at,
                            'time'       => Carbon::parse($log->created_at)->format('d M, H:i'),
                        ];
                    });
            }
        }

        $totalUnread = WaInboxConversation::sum('unread_count');

        return view('livewire.platform.wa-inbox', [
            'conversations'   => $conversations,
            'selectedConvo'   => $selectedConvo,
            'messages'        => $messages,
            'within24h'       => $within24h,
            'totalUnread'     => $totalUnread,
        ]);
    }
}
