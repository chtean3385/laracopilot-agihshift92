<?php

namespace App\Http\Controllers;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $token     = $request->query('hub_verify_token');

        $platform = PlatformWhatsAppSetting::instance();
        $expected = $platform?->webhook_verify_token ?? config('app.key');

        if ($mode === 'subscribe' && $token === $expected) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', ['mode' => $mode, 'token' => $token]);
        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
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
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleTemplateStatusUpdate(array $value): void
    {
        $templateId = $value['message_template_id'] ?? null;
        $event      = strtolower($value['event'] ?? '');

        if (!$templateId) {
            return;
        }

        $metaStatus = match ($event) {
            'approved'          => 'approved',
            'rejected'          => 'rejected',
            'pending_deletion'  => 'rejected',
            'disabled'          => 'rejected',
            default             => null,
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

        Log::info("WhatsApp template status updated", [
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
            Log::info('WhatsApp incoming message', [
                'from' => $message['from'] ?? null,
                'type' => $message['type'] ?? null,
                'text' => $message['text']['body'] ?? null,
            ]);
        }
    }
}
