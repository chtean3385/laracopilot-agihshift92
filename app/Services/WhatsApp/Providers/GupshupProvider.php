<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GupshupProvider implements WhatsAppProviderInterface
{
    public function __construct(private WhatsAppConfig $config) {}

    public function sendMessage(string $to, string $message): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!str_starts_with($to, '91') && strlen($to) === 10) {
            $to = '91' . $to;
        }

        try {
            $response = Http::asForm()->post('https://api.gupshup.io/sm/api/v1/msg', [
                'channel'  => 'whatsapp',
                'source'   => $this->config->phone_number_id,
                'destination' => $to,
                'message'  => json_encode(['type' => 'text', 'text' => $message]),
                'src.name' => 'YourApp',
            ])->withHeaders(['apikey' => $this->config->api_key]);

            if ($response->successful()) {
                return true;
            }
            Log::warning('WhatsApp Gupshup send failed', ['body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Gupshup exception: ' . $e->getMessage());
            return false;
        }
    }
}
