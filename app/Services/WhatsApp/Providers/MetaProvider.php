<?php

namespace App\Services\WhatsApp\Providers;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Client\RequestException;
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

    /**
     * Send a PDF document as a direct media message (requires active conversation window).
     * Best-effort: silently returns false if outside the 24h window.
     */
    public function sendDocument(string $to, string $mediaId, string $filename, string $caption = ''): bool
    {
        $to = $this->sanitizePhone($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'document',
            'document'          => [
                'id'       => $mediaId,
                'filename' => $filename,
                'caption'  => $caption,
            ],
        ];

        try {
            $response = Http::timeout(15)->connectTimeout(5)->withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v22.0/{$this->config->phone_number_id}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp document sent', ['to' => $to, 'filename' => $filename]);
                return true;
            }
            Log::warning('WhatsApp document send failed (likely outside 24h window)', [
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp sendDocument exception: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMessage(string $to, string $message): bool
    {
        $to = $this->sanitizePhone($to);

        try {
            $response = Http::timeout(15)->connectTimeout(5)->withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v22.0/{$this->config->phone_number_id}/messages", [
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
            $response = Http::timeout(15)->connectTimeout(5)->withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v22.0/{$this->config->phone_number_id}/messages", $payload);

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

    public function uploadMedia(string $pdfBytes, string $filename = 'Invoice.pdf'): ?string
    {
        try {
            $response = Http::timeout(20)->connectTimeout(5)->withToken($this->config->api_key)
                ->attach('file', $pdfBytes, $filename, ['Content-Type' => 'application/pdf'])
                ->post("https://graph.facebook.com/v22.0/{$this->config->phone_number_id}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => 'application/pdf',
                ]);

            if ($response->successful()) {
                $mediaId = $response->json('id');
                Log::info('WhatsApp media upload successful', ['media_id' => $mediaId, 'filename' => $filename]);
                return $mediaId;
            }

            $err = $response->json('error.message') ?? $response->body();
            Log::warning('WhatsApp media upload failed', ['error' => $err, 'body' => $response->body()]);
            WhatsAppService::setLastError('Media upload failed: ' . $err);
            return null;
        } catch (\Throwable $e) {
            Log::error('WhatsApp uploadMedia exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return null;
        }
    }

    public function sendDocumentTemplate(
        string $to,
        string $templateName,
        string $mediaId,
        string $filename,
        array  $bodyParams,
        string $langCode = 'en_US'
    ): bool {
        $to = $this->sanitizePhone($to);

        $bodyParameters = array_map(
            fn($val) => ['type' => 'text', 'text' => (string) $val],
            $bodyParams
        );

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $langCode],
                'components' => [
                    [
                        'type'       => 'header',
                        'parameters' => [
                            [
                                'type'     => 'document',
                                'document' => [
                                    'id'       => $mediaId,
                                    'filename' => $filename,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type'       => 'body',
                        'parameters' => $bodyParameters,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(15)->connectTimeout(5)->withToken($this->config->api_key)
                ->post("https://graph.facebook.com/v22.0/{$this->config->phone_number_id}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp document template sent', [
                    'to'       => $to,
                    'template' => $templateName,
                    'media_id' => $mediaId,
                    'msgId'    => $response->json('messages.0.id'),
                ]);
                return true;
            }

            $errMsg  = $response->json('error.message') ?? $response->body();
            $errCode = $response->json('error.code') ? ' (code ' . $response->json('error.code') . ')' : '';
            Log::warning('WhatsApp document template send failed', [
                'template' => $templateName,
                'to'       => $to,
                'body'     => $response->body(),
            ]);
            WhatsAppService::setLastError('Meta API error: ' . $errMsg . $errCode);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendDocumentTemplate exception: ' . $e->getMessage());
            WhatsAppService::setLastError($e->getMessage());
            return false;
        }
    }
}
