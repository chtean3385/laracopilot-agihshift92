<?php

namespace App\Http\Controllers;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $challenge = $request->query('hub_challenge')    ?? $request->query('hub.challenge');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');

        // Browser visit with no Meta params — show a helpful info page
        if (!$mode && !$challenge && !$token) {
            $platform    = PlatformWhatsAppSetting::instance();
            $webhookUrl  = url('/webhook/whatsapp');
            $tokenStatus = $platform?->webhook_verify_token ? '✅ Configured' : '❌ NOT SET — go to Platform Admin → WhatsApp Settings';
            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>WhatsApp Webhook</title>'
                . '<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:60px auto;padding:0 24px;color:#111827;line-height:1.6;}'
                . 'code{background:#f1f5f9;color:#7c3aed;padding:2px 8px;border-radius:6px;font-size:14px;font-family:monospace;}'
                . '.ok{color:#15803d;font-weight:700;} .warn{color:#b45309;font-weight:700;}'
                . 'h1{color:#1d4ed8;} .box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:16px 0;}'
                . 'ol li{margin-bottom:8px;}'
                . '</style></head><body>'
                . '<h1>⚡ WhatsApp Webhook — Active</h1>'
                . '<p>This endpoint receives real-time events from Meta (message delivery status, template approvals, incoming messages).</p>'
                . '<div class="box"><strong>📋 Webhook URL (copy into Meta Business Manager):</strong><br><br>'
                . '<code>' . $webhookUrl . '</code></div>'
                . '<div class="box"><strong>🔑 Verify Token:</strong> ' . $tokenStatus . '</div>'
                . '<h2>How to configure in Meta</h2><ol>'
                . '<li>Open <a href="https://developers.facebook.com" target="_blank">Meta for Developers</a> → Your App → WhatsApp → Configuration</li>'
                . '<li>Under <strong>Webhook</strong>, click <strong>Edit</strong></li>'
                . '<li>Paste the Webhook URL above in the <em>Callback URL</em> field</li>'
                . '<li>Enter your <em>Verify Token</em> (from Platform Admin → WhatsApp Settings)</li>'
                . '<li>Subscribe to fields: <code>messages</code> and <code>message_template_status_update</code></li>'
                . '<li>Click <strong>Verify and Save</strong></li>'
                . '</ol>'
                . '<p style="color:#6b7280;font-size:13px;margin-top:32px;">View all webhook activity: Platform Admin → WhatsApp → Webhook Logs</p>'
                . '</body></html>',
                200
            )->header('Content-Type', 'text/html');
        }

        $platform = PlatformWhatsAppSetting::instance();
        $expected = $platform?->webhook_verify_token;

        if (!$expected) {
            Log::warning('WhatsApp webhook: no verify token configured on platform');
            WhatsAppLog::record('incoming', 'verification', 'error', [], null, null, 'No verify token configured on platform');
            return response('Webhook verify token not configured', 500);
        }

        if ($mode === 'subscribe' && $token === $expected) {
            WhatsAppLog::record('incoming', 'verification', 'ok', ['challenge' => $challenge], null, null, 'Webhook verified successfully by Meta');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', ['mode' => $mode]);
        WhatsAppLog::record('incoming', 'verification', 'error', ['mode' => $mode], null, null, 'Verification failed — wrong token or mode');
        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook: invalid signature — request rejected');
            WhatsAppLog::record('incoming', 'signature_check', 'error', [], null, null, 'Invalid HMAC signature — request rejected');
            return response()->json(['status' => 'forbidden'], 403);
        }

        try {
            $payload = $request->all();
            Log::info('WhatsApp webhook received', ['payload' => $payload]);

            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $field = $change['field'] ?? '';
                    $value = $change['value'] ?? [];

                    if ($field === 'message_template_status_update') {
                        $this->handleTemplateStatusUpdate($value);
                    }

                    if ($field === 'messages') {
                        $this->handleIncomingMessages($value);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook processing error: ' . $e->getMessage());
            WhatsAppLog::record('incoming', 'error', 'error', $request->all(), null, null, $e->getMessage());
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleTemplateStatusUpdate(array $value): void
    {
        $templateId = $value['message_template_id'] ?? null;
        $event      = strtolower($value['event'] ?? '');
        $name       = $value['message_template_name'] ?? '(unknown)';

        WhatsAppLog::record(
            'incoming',
            'template_status_update',
            'ok',
            $value,
            null,
            null,
            "Template '{$name}' (ID: {$templateId}) event: {$event}"
        );

        if (!$templateId) {
            return;
        }

        $metaStatus = match ($event) {
            'approved'         => 'approved',
            'rejected'         => 'rejected',
            'pending_deletion' => 'rejected',
            'disabled'         => 'rejected',
            default            => null,
        };

        if (!$metaStatus) {
            return;
        }

        $approvalStatus = $metaStatus === 'approved' ? 'approved' : 'rejected';

        $updated = WhatsAppTemplate::where('meta_template_id', (string) $templateId)
            ->update([
                'meta_status'     => $metaStatus,
                'approval_status' => $approvalStatus,
            ]);

        Log::info('WhatsApp template status updated', [
            'meta_template_id' => $templateId,
            'event'            => $event,
            'meta_status'      => $metaStatus,
            'rows_updated'     => $updated,
        ]);
    }

    protected function handleIncomingMessages(array $value): void
    {
        $messages = $value['messages'] ?? [];
        foreach ($messages as $message) {
            $phone = $message['from'] ?? null;
            $type  = $message['type'] ?? 'unknown';
            $text  = $message['text']['body'] ?? null;

            Log::info('WhatsApp incoming message', [
                'from' => $phone,
                'type' => $type,
                'text' => $text,
            ]);

            WhatsAppLog::record(
                'incoming',
                'message_received',
                'ok',
                $message,
                $phone,
                null,
                $text ? mb_substr($text, 0, 200) : "Type: {$type}"
            );
        }

        // Delivery status updates also come inside 'messages' field
        $statuses = $value['statuses'] ?? [];
        foreach ($statuses as $status) {
            $phone   = $status['recipient_id'] ?? null;
            $state   = $status['status'] ?? 'unknown';
            $msgId   = $status['id'] ?? null;

            WhatsAppLog::record(
                'outgoing',
                'delivery_status',
                'ok',
                $status,
                $phone,
                null,
                "Msg {$msgId} → {$state}"
            );
        }
    }

    protected function verifySignature(Request $request): bool
    {
        $platform  = PlatformWhatsAppSetting::instance();
        $appSecret = $platform?->meta_app_secret;

        if (!$appSecret) {
            Log::error('WhatsApp webhook: no app secret configured — rejecting request');
            return false;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        $rawBody  = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signature);
    }
}
