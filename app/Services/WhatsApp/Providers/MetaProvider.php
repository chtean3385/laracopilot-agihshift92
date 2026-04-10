<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaProvider implements WhatsAppProviderInterface
{
    public function __construct(private WhatsAppConfig $config) {}

    private function sanitizePhone(string $to): string
    {
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!str_starts_with($to, '91') && strlen($to) === 10) {
            $to = '91' . $to;
        }
        return $to;
    }

    public function sendMessage(string $to, string $message): bool
    {
        $to = $this->sanitizePhone($to);

        try {
            $response = Http::withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v18.0/{$this->config->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            if ($response->successful()) {
                return true;
            }
            $errMsg  = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code') ? ' (code ' . $response->json('error.code') . ')' : '';
            Log::warning('WhatsApp Meta send failed', ['body' => $response->body()]);
            WhatsAppService::setLastError('Meta API error: ' . $errMsg . $errCode);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Meta exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }

    public function sendTemplate(string $to, string $templateName, array $bodyParams, string $langCode = 'en_US'): bool
    {
        $to = $this->sanitizePhone($to);

        $parameters = array_map(fn($val) => ['type' => 'text', 'text' => (string) $val], $bodyParams);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $langCode],
                'components' => [
                    ['type' => 'body', 'parameters' => $parameters],
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v19.0/{$this->config->phone_number_id}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp template sent', [
                    'to'       => $to,
                    'template' => $templateName,
                    'msgId'    => $response->json('messages.0.id'),
                ]);
                return true;
            }
            $errMsg  = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code') ? ' (code ' . $response->json('error.code') . ')' : '';
            Log::warning('WhatsApp Meta template send failed', [
                'template' => $templateName,
                'to'       => $to,
                'body'     => $response->body(),
            ]);
            WhatsAppService::setLastError('Meta API error: ' . $errMsg . $errCode);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Meta sendTemplate exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }
}
