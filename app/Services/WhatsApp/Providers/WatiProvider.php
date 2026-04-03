<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WatiProvider implements WhatsAppProviderInterface
{
    public function __construct(private WhatsAppConfig $config) {}

    public function sendMessage(string $to, string $message): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!str_starts_with($to, '91') && strlen($to) === 10) {
            $to = '91' . $to;
        }

        try {
            $serverId = preg_replace('/[^a-zA-Z0-9]/', '', $this->config->phone_number_id);
            $token    = trim(preg_replace('/^Bearer\s+/i', '', $this->config->api_key));
            $url      = "https://live-server-{$serverId}.wati.io/api/v1/sendSessionMessage/{$to}"
                      . '?messageText=' . urlencode($message);

            $response = Http::withToken($token)->post($url);

            Log::info('WhatsApp WATI response', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'url'    => $url,
            ]);

            if ($response->successful()) {
                $json   = $response->json();
                $result = $json['result'] ?? true;
                if (!$result) {
                    $errMsg = $json['info'] ?? $json['error'] ?? '200 OK but result=false';
                    Log::warning('WhatsApp WATI: 200 OK but result=false', ['json' => $json]);
                    WhatsAppService::setLastError('WATI error: ' . $errMsg);
                    return false;
                }
                return true;
            }
            $errMsg = $response->json('error') ?? $response->json('message') ?? $response->body();
            Log::warning('WhatsApp WATI send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'url'    => $url,
            ]);
            WhatsAppService::setLastError('WATI error (HTTP ' . $response->status() . '): ' . $errMsg);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp WATI exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }
}
