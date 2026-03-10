<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
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
            $response = Http::withToken($this->config->api_key)
                ->post("https://live-server-{$serverId}.wati.io/api/v1/sendSessionMessage/{$to}", [
                    'messageText' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::warning('WhatsApp WATI send failed', ['body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp WATI exception: ' . $e->getMessage());
            return false;
        }
    }
}
