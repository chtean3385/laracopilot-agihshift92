<?php

namespace App\Http\Controllers;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // ── Bot question definitions ──────────────────────────────────────────

    private const WEBSITE_LINK = 'https://dreams-technology.com/';

    private const BOT_GREETING = "👋 Hello! Welcome to *Dreams Technology*.\n\nWe help businesses grow with cutting-edge technology solutions.\n\nMay I know your *good name*, please?";

    private const BOT_SERVICE_Q = "Great to meet you, *{name}*! 😊\n\nHow can we help you today?\n\nPlease reply with the number:\n1️⃣ Website Design\n2️⃣ Mobile Application\n3️⃣ ERP / CRM\n4️⃣ Digital Marketing\n5️⃣ Others";

    private const BOT_TIMELINE_Q = "Perfect choice! 👍\n\nHow quickly would you like to get started?\n\nPlease reply with the number:\n1️⃣ Immediately (within 1 week)\n2️⃣ Soon (1–4 weeks)\n3️⃣ Planning ahead (1–3 months)\n4️⃣ Just exploring for now";

    private const BOT_BUDGET_Q = "Excellent! Almost done 🙌\n\nWhat is your approximate budget for this project?\n\nPlease reply with the number:\n1️⃣ Less than ₹25,000\n2️⃣ ₹25,000 – ₹1,00,000\n3️⃣ ₹1,00,000 – ₹5,00,000\n4️⃣ ₹5,00,000 and above";

    private const BOT_SERVICE_OPTIONS = [
        '1' => 'Website Design',
        '2' => 'Mobile Application',
        '3' => 'ERP / CRM',
        '4' => 'Digital Marketing',
        '5' => 'Others',
    ];

    private const BOT_TIMELINE_OPTIONS = [
        '1' => 'Immediately (within 1 week)',
        '2' => 'Soon (1–4 weeks)',
        '3' => 'Planning ahead (1–3 months)',
        '4' => 'Just exploring for now',
    ];

    private const BOT_BUDGET_OPTIONS = [
        '1' => 'Less than ₹25,000',
        '2' => '₹25,000 – ₹1,00,000',
        '3' => '₹1,00,000 – ₹5,00,000',
        '4' => '₹5,00,000 and above',
    ];

    // ── Webhook verification ──────────────────────────────────────────────

    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $challenge = $request->query('hub_challenge')    ?? $request->query('hub.challenge');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');

        if (!$mode && !$challenge && !$token) {
            $platform    = PlatformWhatsAppSetting::instance();
            $webhookUrl  = url('/webhook/whatsapp');
            $tokenStatus = $platform?->webhook_verify_token ? '✅ Configured' : '❌ NOT SET — go to Platform Admin → WhatsApp Settings';
            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>WhatsApp Webhook</title>'
                . '<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:60px auto;padding:0 24px;color:#111827;line-height:1.6;}'
                . 'code{background:#f1f5f9;color:#7c3aed;padding:2px 8px;border-radius:6px;font-size:14px;font-family:monospace;}'
                . 'h1{color:#1d4ed8;} .box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:16px 0;}'
                . 'ol li{margin-bottom:8px;}'
                . '</style></head><body>'
                . '<h1>⚡ WhatsApp Webhook — Active</h1>'
                . '<p>This endpoint receives real-time events from Meta.</p>'
                . '<div class="box"><strong>📋 Webhook URL:</strong><br><br><code>' . $webhookUrl . '</code></div>'
                . '<div class="box"><strong>🔑 Verify Token:</strong> ' . $tokenStatus . '</div>'
                . '</body></html>',
                200
            )->header('Content-Type', 'text/html');
        }

        $platform = PlatformWhatsAppSetting::instance();
        $expected = $platform?->webhook_verify_token;

        if (!$expected) {
            WhatsAppLog::record('incoming', 'verification', 'error', [], null, null, 'No verify token configured on platform');
            return response('Webhook verify token not configured', 500);
        }

        if ($mode === 'subscribe' && $token === $expected) {
            WhatsAppLog::record('incoming', 'verification', 'ok', ['challenge' => $challenge], null, null, 'Webhook verified successfully by Meta');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        WhatsAppLog::record('incoming', 'verification', 'error', ['mode' => $mode], null, null, 'Verification failed — wrong token or mode');
        return response('Forbidden', 403);
    }

    // ── Webhook receiver ──────────────────────────────────────────────────

    public function receive(Request $request)
    {
        $platform = PlatformWhatsAppSetting::instance();
        $skipSig  = $platform?->skip_signature_check ?? false;

        if (!$skipSig && !$this->verifySignature($request, $platform)) {
            Log::warning('WhatsApp webhook: invalid signature — request rejected');
            WhatsAppLog::record('incoming', 'signature_check', 'error', [], null, null, 'Invalid HMAC signature — request rejected');
            return response()->json(['status' => 'forbidden'], 403);
        }

        try {
            $payload = $request->all();
            Log::info('WhatsApp webhook received', ['payload' => $payload]);

            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                // WABA ID is the top-level entry ID (Meta WhatsApp Business Account ID)
                $wabaId  = $entry['id'] ?? null;
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $field = $change['field'] ?? '';
                    $value = $change['value'] ?? [];

                    if ($field === 'message_template_status_update') {
                        $this->handleTemplateStatusUpdate($value);
                    }

                    if ($field === 'messages') {
                        $this->handleIncomingMessages($value, $platform, $wabaId);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook processing error: ' . $e->getMessage());
            WhatsAppLog::record('incoming', 'error', 'error', $request->all(), null, null, $e->getMessage());
        }

        return response()->json(['status' => 'ok']);
    }

    // ── Template status handler ───────────────────────────────────────────

    protected function handleTemplateStatusUpdate(array $value): void
    {
        $templateId = $value['message_template_id'] ?? null;
        $event      = strtolower($value['event'] ?? '');
        $name       = $value['message_template_name'] ?? '(unknown)';

        WhatsAppLog::record('incoming', 'template_status_update', 'ok', $value, null, null,
            "Template '{$name}' (ID: {$templateId}) event: {$event}");

        if (!$templateId) return;

        $metaStatus = match ($event) {
            'approved'         => 'approved',
            'rejected'         => 'rejected',
            'pending_deletion' => 'rejected',
            'disabled'         => 'rejected',
            default            => null,
        };

        if (!$metaStatus) return;

        WhatsAppTemplate::where('meta_template_id', (string) $templateId)
            ->update([
                'meta_status'     => $metaStatus,
                'approval_status' => $metaStatus === 'approved' ? 'approved' : 'rejected',
            ]);
    }

    // ── Incoming message handler ──────────────────────────────────────────

    protected function handleIncomingMessages(array $value, ?object $platform = null, ?string $wabaId = null): void
    {
        // Meta webhook metadata identifies the RECEIVING WA Business number
        $recipientPhoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        $messages = $value['messages'] ?? [];
        foreach ($messages as $message) {
            $phone = $message['from'] ?? null;
            $type  = $message['type'] ?? 'unknown';
            $text  = null;

            // Extract text from various message types
            if ($type === 'text') {
                $text = $message['text']['body'] ?? null;
            } elseif ($type === 'interactive') {
                $text = $message['interactive']['button_reply']['title']
                     ?? $message['interactive']['list_reply']['title']
                     ?? null;
            } elseif ($type === 'button') {
                $text = $message['button']['text'] ?? null;
            }

            Log::info('WhatsApp incoming message', ['from' => $phone, 'type' => $type, 'text' => $text]);

            $preview = $text ? mb_substr($text, 0, 200) : "Type: {$type}";

            // Resolve hotel_id by matching phone
            $hotelId = null;
            if ($phone) {
                $hotel = DB::table('hotels')
                    ->where('phone', $phone)
                    ->orWhere('phone', ltrim($phone, '91'))
                    ->first();
                $hotelId = $hotel?->id;
            }

            WhatsAppLog::record('incoming', 'message_received', 'ok', $message, $phone, $hotelId, $preview);

            if ($phone) {
                $this->upsertWaContact($phone, $preview, $text);

                // Handle STOP / START opt-out before bot flow
                if ($platform && $text !== null) {
                    $cmd = strtoupper(trim(preg_replace('/[^a-zA-Z]/', '', $text)));
                    if ($cmd === 'STOP') {
                        $this->handleOptOut($phone, $platform);
                        return; // Do NOT run bot after opt-out
                    }
                    if ($cmd === 'START' || $cmd === 'SUBSCRIBE') {
                        $this->handleOptIn($phone, $platform);
                        return;
                    }
                }

                // OTA booking sync:
                // 1. Try matching by sender phone number OR WABA business account ID
                // 2. Fall back to content-pattern detection (generic format, e.g. demo testing)
                if ($text !== null) {
                    $otaSource = \App\Models\OtaSource::findBySender($phone, $wabaId)
                              ?? \App\Models\OtaSource::findByContentPattern($text);
                    if ($otaSource) {
                        (new \App\Services\OtaBookingParserService())->handle(
                            $phone,
                            $text,
                            $otaSource,
                            $recipientPhoneNumberId
                        );
                        return; // Do NOT run bot flow for OTA messages
                    }
                }

                // Skip bot messages for unsubscribed contacts
                $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
                if ($platform && ($contact?->subscribed ?? true)) {
                    $this->runBotFlow($phone, $text, $platform);
                }
            }
        }

        $statuses = $value['statuses'] ?? [];
        foreach ($statuses as $status) {
            $phone = $status['recipient_id'] ?? null;
            $state = $status['status'] ?? 'unknown';
            $msgId = $status['id'] ?? null;

            WhatsAppLog::record('outgoing', 'delivery_status', 'ok', $status, $phone, null,
                "Msg {$msgId} → {$state}");
        }
    }

    // ── Bot flow state machine ────────────────────────────────────────────

    protected function runBotFlow(string $phone, ?string $text, object $platform): void
    {
        if (!$platform->saas_token || !$platform->saas_phone_number_id) return;

        $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
        if (!$contact) return;

        // Skip bot for hotel owners and already-completed flows
        if ($contact->contact_type === 'owner') return;
        if ($contact->bot_state === 'completed')  return;

        $state   = $contact->bot_state;
        $input   = trim($text ?? '');

        try {
            if ($state === null || $state === '') {
                // First ever message — send greeting
                $this->botSend($platform, $phone, self::BOT_GREETING);
                DB::table('wa_contacts')->where('phone', $phone)
                    ->update(['bot_state' => 'awaiting_name', 'updated_at' => now()]);
                return;
            }

            if ($state === 'awaiting_name') {
                if ($input === '') return;
                $name = ucwords(strtolower($input));
                $msg  = str_replace('{name}', $name, self::BOT_SERVICE_Q);
                $this->botSend($platform, $phone, $msg);
                DB::table('wa_contacts')->where('phone', $phone)->update([
                    'display_name' => $name,
                    'bot_state'    => 'awaiting_service',
                    'updated_at'   => now(),
                ]);
                return;
            }

            if ($state === 'awaiting_service') {
                $choice = self::BOT_SERVICE_OPTIONS[$input] ?? null;
                if (!$choice) {
                    // Nudge with partial text match
                    foreach (self::BOT_SERVICE_OPTIONS as $k => $v) {
                        if (stripos($input, $v) !== false || stripos($input, (string)$k) !== false) {
                            $choice = $v;
                            break;
                        }
                    }
                }
                if (!$choice) {
                    $this->botSend($platform, $phone, "Please reply with a number *1 to 5* to choose your service. 😊");
                    return;
                }
                $this->botSend($platform, $phone, self::BOT_TIMELINE_Q);
                DB::table('wa_contacts')->where('phone', $phone)->update([
                    'bot_service_interest' => $choice,
                    'bot_state'            => 'awaiting_timeline',
                    'updated_at'           => now(),
                ]);
                return;
            }

            if ($state === 'awaiting_timeline') {
                $choice = self::BOT_TIMELINE_OPTIONS[$input] ?? null;
                if (!$choice) {
                    foreach (self::BOT_TIMELINE_OPTIONS as $k => $v) {
                        if (stripos($input, $v) !== false || stripos($input, (string)$k) !== false) {
                            $choice = $v;
                            break;
                        }
                    }
                }
                if (!$choice) {
                    $this->botSend($platform, $phone, "Please reply with a number *1 to 4* for your preferred timeline. 😊");
                    return;
                }
                $this->botSend($platform, $phone, self::BOT_BUDGET_Q);
                DB::table('wa_contacts')->where('phone', $phone)->update([
                    'bot_timeline' => $choice,
                    'bot_state'    => 'awaiting_budget',
                    'updated_at'   => now(),
                ]);
                return;
            }

            if ($state === 'awaiting_budget') {
                $resolvedKey = null;
                $choice      = null;

                if (isset(self::BOT_BUDGET_OPTIONS[$input])) {
                    $resolvedKey = $input;
                    $choice      = self::BOT_BUDGET_OPTIONS[$input];
                } else {
                    foreach (self::BOT_BUDGET_OPTIONS as $k => $v) {
                        if (stripos($input, $v) !== false || stripos($input, (string)$k) !== false) {
                            $resolvedKey = $k;
                            $choice      = $v;
                            break;
                        }
                    }
                }

                if (!$choice) {
                    $this->botSend($platform, $phone, "Please reply with a number *1 to 4* for your budget range. 😊");
                    return;
                }

                // Hot lead if resolved key is 3 or 4 (₹1 lac+) — derived from resolved option, not raw input
                $leadStatus = in_array($resolvedKey, ['3', '4']) ? 'hot' : 'warm';

                $displayName = $contact->display_name ?? 'there';
                $service     = $contact->bot_service_interest ?? 'your project';
                $link        = self::WEBSITE_LINK;

                $confirmation = "🎉 Thank you, *{$displayName}*!\n\nOur team will be in touch with you shortly to discuss your *{$service}* project.\n\nMeanwhile, you can explore our services here:\n{$link}\n\nHave a great day! 😊\n\n_Reply *STOP* anytime to unsubscribe from our messages._";

                $this->botSend($platform, $phone, $confirmation);
                DB::table('wa_contacts')->where('phone', $phone)->update([
                    'bot_budget'  => $choice,
                    'lead_status' => $leadStatus,
                    'bot_state'   => 'completed',
                    'updated_at'  => now(),
                ]);
                return;
            }

        } catch (\Throwable $e) {
            Log::error('Bot flow error for ' . $phone . ': ' . $e->getMessage());
        }
    }

    // ── Opt-out / Opt-in handlers ─────────────────────────────────────────

    protected function handleOptOut(string $phone, object $platform): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'subscribed'      => false,
            'unsubscribed_at' => now(),
            'updated_at'      => now(),
        ]);

        $msg = "You have been *unsubscribed* from Dreams Technology messages. 🚫\n\nWe will no longer send you proactive messages.\n\n_Reply *START* anytime to re-subscribe._";
        $this->botSend($platform, $phone, $msg);
        Log::info("WA opt-out: {$phone} has unsubscribed.");
    }

    protected function handleOptIn(string $phone, object $platform): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'subscribed'      => true,
            'unsubscribed_at' => null,
            'updated_at'      => now(),
        ]);

        $msg = "You've been *re-subscribed* to Dreams Technology messages. ✅\n\nWelcome back! Reply *STOP* anytime to unsubscribe again.";
        $this->botSend($platform, $phone, $msg);
        Log::info("WA opt-in: {$phone} has re-subscribed.");
    }

    // ── Bot send helper ───────────────────────────────────────────────────

    protected function botSend(object $platform, string $phone, string $text): void
    {
        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) === 10) {
            $numericPhone = '91' . $numericPhone;
        }

        $response = Http::timeout(10)
            ->withToken($platform->saas_token)
            ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $numericPhone,
                'type'              => 'text',
                'text'              => ['body' => $text, 'preview_url' => false],
            ]);

        $body = $response->json();

        WhatsAppLog::record('outgoing', 'message_sent', $response->successful() ? 'ok' : 'error',
            $body, $phone, null, mb_substr($text, 0, 200));

        if (!$response->successful()) {
            Log::warning('Bot send failed', ['phone' => $phone, 'error' => $body]);
        }
    }

    // ── Upsert wa_contact ─────────────────────────────────────────────────

    protected function upsertWaContact(string $phone, string $preview, ?string $text): void
    {
        try {
            $normalPhone = preg_replace('/[^0-9]/', '', $phone);
            $shortPhone  = strlen($normalPhone) > 10 ? substr($normalPhone, -10) : $normalPhone;

            $hotel = DB::table('hotels')
                ->where('phone', $phone)
                ->orWhere(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), $normalPhone)
                ->orWhere(DB::raw("right(regexp_replace(phone, '[^0-9]', '', 'g'), 10)"), $shortPhone)
                ->first();

            $hotelId     = $hotel?->id;
            $contactType = 'unknown';
            $displayName = null;

            if ($hotel) {
                $contactType = 'owner';
                $displayName = $hotel->name . ' (Owner)';
            } else {
                $customer = DB::table('customers')
                    ->where(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), $normalPhone)
                    ->orWhere(DB::raw("right(regexp_replace(phone, '[^0-9]', '', 'g'), 10)"), $shortPhone)
                    ->first();

                if ($customer) {
                    $contactType = 'guest';
                    $hotelId     = $customer->hotel_id ?? $hotelId;
                    $hotelName   = $hotelId
                        ? (DB::table('hotels')->where('id', $hotelId)->value('name') ?? '')
                        : '';
                    $displayName = $customer->name . ($hotelName ? " — {$hotelName}" : '');
                }
            }

            $grantedConsent = $text && strtolower(trim($text)) === 'yes';
            $existing       = DB::table('wa_contacts')->where('phone', $phone)->first();

            if ($existing) {
                $update = [
                    'last_message_preview' => $preview,
                    'last_message_at'      => now(),
                    'unread_count'         => $existing->unread_count + 1,
                    'updated_at'           => now(),
                ];
                if ($grantedConsent && !$existing->consented_at) {
                    $update['consented_at'] = now();
                }
                if (!$existing->hotel_id && $hotelId) {
                    $update['hotel_id']     = $hotelId;
                    $update['contact_type'] = $contactType;
                    $update['display_name'] = $displayName;
                }
                DB::table('wa_contacts')->where('phone', $phone)->update($update);
            } else {
                DB::table('wa_contacts')->insert([
                    'phone'                => $phone,
                    'hotel_id'             => $hotelId,
                    'contact_type'         => $contactType,
                    'display_name'         => $displayName,
                    'consented_at'         => now(),
                    'last_message_at'      => now(),
                    'last_message_preview' => $preview,
                    'unread_count'         => 1,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('upsertWaContact failed: ' . $e->getMessage());
        }
    }

    // ── HMAC signature verification ───────────────────────────────────────

    protected function verifySignature(Request $request, ?object $platform = null): bool
    {
        $platform  = $platform ?? PlatformWhatsAppSetting::instance();
        $appSecret = $platform?->meta_app_secret;

        if (!$appSecret) {
            Log::error('WhatsApp webhook: no app secret configured — rejecting request');
            return false;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            Log::warning('WhatsApp webhook: X-Hub-Signature-256 header missing');
            return false;
        }

        $rawBody  = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signature);
    }
}
