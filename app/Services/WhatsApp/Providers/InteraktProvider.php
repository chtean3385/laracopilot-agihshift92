<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InteraktProvider implements WhatsAppProviderInterface
{
    public function __construct(private WhatsAppConfig $config) {}

    public function sendMessage(string $to, string $message): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!str_starts_with($to, '91') && strlen($to) === 10) {
            $to = '91' . $to;
        }

        try {
            $response = Http::withHeaders(['api_key' => $this->config->api_key])
                ->post('https://api.interakt.ai/v1/public/message/', [
                    'countryCode' => '+91',
                    'phoneNumber' => $to,
                    'type'        => 'Text',
                    'data'        => ['message' => $message],
                ]);

            if ($response->successful()) {
                return true;
            }
            $errMsg = $response->json('message') ?? $response->json('error') ?? $response->body();
            Log::warning('WhatsApp Interakt send failed', ['body' => $response->body()]);
            WhatsAppService::setLastError('Interakt error (HTTP ' . $response->status() . '): ' . $errMsg);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Interakt exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }
}
