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
    public int    $selectedHotelId = 0;
    public string $replyText       = '';
    public string $sendResult      = '';
    public bool   $sending         = false;

    public function mount(): void
    {
        // Pre-select hotel from ?hotel=X query param (e.g. dashboard quick-chat)
        $hotelId = (int) request()->query('hotel', 0);
        if ($hotelId > 0) {
            $this->selectedHotelId = $hotelId;
        }
    }

    // Called from dashboard quick-chat widget
    public function selectHotel(int $hotelId): void
    {
        $this->selectedHotelId = $hotelId;
        $this->replyText       = '';
        $this->sendResult      = '';
        $this->dispatch('wa-scroll-to-bottom');
    }

    public function backToList(): void
    {
        $this->selectedHotelId = 0;
        $this->replyText       = '';
        $this->sendResult      = '';
    }

    public function sendReply(): void
    {
        $text = trim($this->replyText);
        if ($text === '') {
            $this->sendResult = 'error:Please type a message first.';
            return;
        }

        $hotel = DB::table('hotels')->where('id', $this->selectedHotelId)->first();
        if (!$hotel) {
            $this->sendResult = 'error:Hotel not found.';
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $hotel->phone ?? '');
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        if (empty($phone)) {
            $this->sendResult = 'error:This hotel has no WhatsApp number configured.';
            return;
        }

        $platform = PlatformWhatsAppSetting::instance();
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->sendResult = 'error:Platform WhatsApp credentials are not configured.';
            return;
        }

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
                    'phone'      => $phone,
                    'hotel_id'   => $hotel->id,
                    'status'     => 'ok',
                    'payload'    => json_encode($body),
                    'notes'      => $text,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->replyText  = '';
                $this->sendResult = 'ok:Sent to ' . $hotel->name . '.';
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
        // Bootstrap conversations from hotels — no webhook dependency
        $hotels = DB::table('hotels')
            ->where('status', '!=', 'suspended')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'status', 'plan']);

        $hotelIds = $hotels->pluck('id');

        // Last message preview per hotel
        $lastMsgPerHotel = DB::table('whatsapp_logs')
            ->whereIn('event_type', ['message_received', 'message_sent'])
            ->whereIn('hotel_id', $hotelIds)
            ->orderByDesc('created_at')
            ->get(['hotel_id', 'notes', 'direction', 'created_at'])
            ->groupBy('hotel_id')
            ->map(fn($msgs) => $msgs->first());

        // Unread = incoming messages in last 24h not replied
        $unreadPerHotel = DB::table('whatsapp_logs')
            ->where('direction', 'incoming')
            ->where('event_type', 'message_received')
            ->whereIn('hotel_id', $hotelIds)
            ->where('created_at', '>=', now()->subHours(24))
            ->select('hotel_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('hotel_id')
            ->pluck('cnt', 'hotel_id');

        $conversations = $hotels->map(function ($h) use ($lastMsgPerHotel, $unreadPerHotel) {
            $last = $lastMsgPerHotel[$h->id] ?? null;
            return (object) [
                'hotel_id'   => $h->id,
                'hotel_name' => $h->name,
                'phone'      => $h->phone ?? '',
                'plan'       => strtoupper($h->plan ?? ''),
                'last_at'    => $last?->created_at,
                'time_ago'   => $last ? Carbon::parse($last->created_at)->diffForHumans() : 'No messages',
                'preview'    => $last ? mb_substr($last->notes ?? '', 0, 55) : 'No messages yet',
                'preview_dir'=> $last?->direction,
                'unread'     => $unreadPerHotel[$h->id] ?? 0,
            ];
        })->sortByDesc('last_at')->values();

        $selectedHotel = null;
        $messages      = collect();
        $within24h     = false;

        if ($this->selectedHotelId) {
            $selectedHotel = $conversations->firstWhere('hotel_id', $this->selectedHotelId);

            if ($selectedHotel) {
                $messages = DB::table('whatsapp_logs')
                    ->where('hotel_id', $this->selectedHotelId)
                    ->whereIn('event_type', ['message_received', 'message_sent'])
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($log) {
                        return (object) [
                            'id'        => $log->id,
                            'direction' => $log->direction,
                            'tag'       => $log->direction === 'outgoing' ? 'You' : 'Owner',
                            'text'      => $log->notes ?? '',
                            'time'      => Carbon::parse($log->created_at)->format('d M, H:i'),
                        ];
                    });

                // 24h window — last incoming from this hotel
                $lastIncoming = DB::table('whatsapp_logs')
                    ->where('hotel_id', $this->selectedHotelId)
                    ->where('direction', 'incoming')
                    ->where('event_type', 'message_received')
                    ->max('created_at');

                $within24h = $lastIncoming && Carbon::parse($lastIncoming)->diffInHours(now()) < 24;
            }
        }

        $totalUnread = $unreadPerHotel->sum();

        return view('livewire.platform.wa-inbox', [
            'conversations' => $conversations,
            'selectedHotel' => $selectedHotel,
            'messages'      => $messages,
            'within24h'     => $within24h,
            'totalUnread'   => $totalUnread,
        ]);
    }
}
