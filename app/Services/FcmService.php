<?php

namespace App\Services;

use App\Models\PlatformFirebaseSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private ?PlatformFirebaseSetting $config;

    public function __construct()
    {
        $this->config = PlatformFirebaseSetting::instance();
    }

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * True when at least one send method is configured AND push is enabled.
     */
    public function isEnabled(): bool
    {
        if (!$this->config?->push_enabled) {
            return false;
        }
        return $this->hasV1Auth() || !empty($this->config->fcm_server_key);
    }

    /**
     * Returns "v1" | "legacy" | null — the active send method.
     */
    public function activeMethod(): ?string
    {
        if (!$this->config?->push_enabled) return null;
        if ($this->hasV1Auth())                return 'v1';
        if (!empty($this->config->fcm_server_key)) return 'legacy';
        return null;
    }

    /**
     * Send push notification to one or more FCM tokens.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $extra = []): array
    {
        if (!$this->isEnabled() || empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        return $this->hasV1Auth()
            ? $this->sendViaV1($tokens, $title, $body, $extra)
            : $this->sendViaLegacy($tokens, $title, $body, $extra);
    }

    public function getTokensForHotel(int $hotelId): array
    {
        return DB::table('fcm_tokens')->where('hotel_id', $hotelId)->pluck('token')->toArray();
    }

    public function getAllTokens(): array
    {
        return DB::table('fcm_tokens')->pluck('token')->toArray();
    }

    public function getTokensForPlan(string $plan): array
    {
        return DB::table('fcm_tokens')
            ->join('hotels', 'fcm_tokens.hotel_id', '=', 'hotels.id')
            ->where('hotels.plan', $plan)
            ->pluck('fcm_tokens.token')
            ->toArray();
    }

    // -----------------------------------------------------------------------
    // FCM HTTP v1 API  (Service Account JSON + OAuth 2.0)
    // -----------------------------------------------------------------------

    private function hasV1Auth(): bool
    {
        $json = $this->config->service_account_json ?? '';
        if (empty($json)) return false;
        $decoded = json_decode($json, true);
        return isset($decoded['client_email'], $decoded['private_key'], $decoded['project_id']);
    }

    /**
     * Get an OAuth 2.0 Bearer token for FCM v1.
     * The token is cached for 55 minutes (Firebase issues 60-min tokens).
     */
    private function getV1AccessToken(): ?string
    {
        return Cache::remember('fcm_v1_access_token', 55 * 60, function () {
            $sa = json_decode($this->config->service_account_json, true);
            if (!$sa) return null;

            $clientEmail = $sa['client_email'];
            $privateKey  = $sa['private_key'];
            $now         = time();

            // Build JWT header + payload
            $header  = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $this->b64url(json_encode([
                'iss'   => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signingInput = "$header.$payload";
            $signature    = '';
            $key          = openssl_pkey_get_private($privateKey);

            if (!$key || !openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
                Log::error('FCM v1: JWT signing failed');
                return null;
            }

            $jwt = "$signingInput." . $this->b64url($signature);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->ok()) {
                Log::error('FCM v1: token exchange failed — ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Send via FCM HTTP v1 API (one request per token).
     */
    private function sendViaV1(array $tokens, string $title, string $body, array $extra): array
    {
        $accessToken = $this->getV1AccessToken();
        if (!$accessToken) {
            Log::error('FCM v1: could not obtain access token');
            return ['success' => 0, 'failure' => count($tokens)];
        }

        $sa        = json_decode($this->config->service_account_json, true);
        $projectId = $sa['project_id'] ?? $this->config->firebase_project_id;
        $endpoint  = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            $message = [
                'message' => [
                    'token'        => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'webpush' => [
                        'notification' => [
                            'icon'  => $extra['icon'] ?? '/icon-192.png',
                            'click_action' => $extra['url'] ?? '/',
                        ],
                        'fcm_options' => [
                            'link' => $extra['url'] ?? '/',
                        ],
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'click_action' => $extra['url'] ?? '/',
                        ],
                    ],
                    'data' => array_map('strval', array_merge([
                        'title'     => $title,
                        'body'      => $body,
                        'click_url' => $extra['url'] ?? '/',
                        'notif_id'  => (string)($extra['notif_id'] ?? ''),
                    ], $extra['data'] ?? [])),
                ],
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type'  => 'application/json',
                ])->post($endpoint, $message);

                if ($response->ok()) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $error = $response->json('error.details.0.errorCode') ?? $response->json('error.message') ?? 'unknown';

                    // Remove stale / invalid tokens
                    if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                        DB::table('fcm_tokens')->where('token', $token)->delete();
                    }

                    Log::warning("FCM v1 send failed for token [" . substr($token, 0, 20) . "...]: $error");
                }
            } catch (\Throwable $e) {
                $failureCount++;
                Log::error('FCM v1 exception: ' . $e->getMessage());
            }
        }

        return ['success' => $successCount, 'failure' => $failureCount];
    }

    // -----------------------------------------------------------------------
    // FCM Legacy HTTP API  (Server Key — fallback for older projects)
    // -----------------------------------------------------------------------

    private function sendViaLegacy(array $tokens, string $title, string $body, array $extra): array
    {
        $successCount = 0;
        $failureCount = 0;

        foreach (array_chunk($tokens, 500) as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title'        => $title,
                    'body'         => $body,
                    'icon'         => $extra['icon'] ?? '/icon-192.png',
                    'click_action' => $extra['url'] ?? '/',
                ],
                'data' => array_merge([
                    'title'     => $title,
                    'body'      => $body,
                    'click_url' => $extra['url'] ?? '/',
                    'notif_id'  => (string)($extra['notif_id'] ?? ''),
                ], $extra['data'] ?? []),
                'priority' => 'high',
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $this->config->fcm_server_key,
                    'Content-Type'  => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', $payload);

                $json = $response->json();
                $successCount += (int)($json['success'] ?? 0);
                $failureCount += (int)($json['failure'] ?? 0);

                if (!empty($json['results'])) {
                    foreach ($json['results'] as $i => $result) {
                        if (isset($result['registration_id'])) {
                            DB::table('fcm_tokens')
                                ->where('token', $chunk[$i])
                                ->update(['token' => $result['registration_id']]);
                        }
                        if (isset($result['error']) &&
                            in_array($result['error'], ['NotRegistered', 'InvalidRegistration'])) {
                            DB::table('fcm_tokens')->where('token', $chunk[$i])->delete();
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('FCM legacy send error: ' . $e->getMessage());
                $failureCount += count($chunk);
            }
        }

        return ['success' => $successCount, 'failure' => $failureCount];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
