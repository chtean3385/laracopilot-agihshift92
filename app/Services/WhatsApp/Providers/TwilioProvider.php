<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioProvider implements WhatsAppProviderInterface
{
    public function __construct(private WhatsAppConfig $config) {}

    public function sendMessage(string $to, string $message): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!str_starts_with($to, '91') && strlen($to) === 10) {
            $to = '91' . $to;
        }

        $accountSid = $this->config->business_account_id;
        $authToken  = $this->config->api_key;
        $from       = 'whatsapp:+' . $this->config->phone_number_id;

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $from,
                    'To'   => 'whatsapp:+' . $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }
            $errMsg = $response->json('message') ?? $response->json('code') ?? $response->body();
            Log::warning('WhatsApp Twilio send failed', ['body' => $response->body()]);
            WhatsAppService::setLastError('Twilio error (HTTP ' . $response->status() . '): ' . $errMsg);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Twilio exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }
}
