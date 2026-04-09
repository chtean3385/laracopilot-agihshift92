<?php

namespace App\Services;

use App\Models\PlatformFirebaseSetting;
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

    public function isEnabled(): bool
    {
        return $this->config?->push_enabled && !empty($this->config->fcm_server_key);
    }

    /**
     * Send push notification to specific FCM token(s).
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $extra = []): array
    {
        if (!$this->isEnabled() || empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        $successCount = 0;
        $failureCount = 0;

        // FCM v1 API requires per-token sends; use legacy API for batch
        foreach (array_chunk($tokens, 500) as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'icon'  => $extra['icon'] ?? '/icon-192.png',
                    'click_action' => $extra['url'] ?? '/',
                ],
                'data' => array_merge([
                    'title'      => $title,
                    'body'       => $body,
                    'click_url'  => $extra['url'] ?? '/',
                    'notif_id'   => $extra['notif_id'] ?? '',
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

                // Remove stale tokens
                if (!empty($json['results'])) {
                    foreach ($json['results'] as $i => $result) {
                        if (isset($result['registration_id'])) {
                            // Token was refreshed — update DB
                            DB::table('fcm_tokens')
                                ->where('token', $chunk[$i])
                                ->update(['token' => $result['registration_id']]);
                        }
                        if (isset($result['error']) && in_array($result['error'], ['NotRegistered', 'InvalidRegistration'])) {
                            DB::table('fcm_tokens')->where('token', $chunk[$i])->delete();
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('FCM send error: ' . $e->getMessage());
                $failureCount += count($chunk);
            }
        }

        return ['success' => $successCount, 'failure' => $failureCount];
    }

    /**
     * Get all FCM tokens for a hotel.
     */
    public function getTokensForHotel(int $hotelId): array
    {
        return DB::table('fcm_tokens')
            ->where('hotel_id', $hotelId)
            ->pluck('token')
            ->toArray();
    }

    /**
     * Get all FCM tokens across the platform.
     */
    public function getAllTokens(): array
    {
        return DB::table('fcm_tokens')->pluck('token')->toArray();
    }

    /**
     * Get tokens for specific plan.
     */
    public function getTokensForPlan(string $plan): array
    {
        return DB::table('fcm_tokens')
            ->join('hotels', 'fcm_tokens.hotel_id', '=', 'hotels.id')
            ->where('hotels.plan', $plan)
            ->pluck('fcm_tokens.token')
            ->toArray();
    }
}
