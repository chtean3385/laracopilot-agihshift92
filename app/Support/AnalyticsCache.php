<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AnalyticsCache
{
    public const TTL = 300;

    public static function versionKey(int $hotelId): string
    {
        return "analytics_v_{$hotelId}";
    }

    public static function version(int $hotelId): int
    {
        try {
            return (int) Cache::get(self::versionKey($hotelId), 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function bump(?int $hotelId): void
    {
        if (!$hotelId) return;
        try {
            $key = self::versionKey($hotelId);
            if (Cache::increment($key) === false) {
                Cache::add($key, 1, 86400);
                Cache::increment($key);
            }
        } catch (\Throwable $e) {
            // non-critical
        }
    }

    public static function key(int $hotelId, string $scope, array $parts = []): string
    {
        $v = self::version($hotelId);
        $suffix = empty($parts) ? '' : ':' . md5(json_encode($parts));
        return "analytics:{$hotelId}:v{$v}:{$scope}{$suffix}";
    }

    public static function remember(int $hotelId, string $scope, array $parts, \Closure $callback, ?int $ttl = null)
    {
        try {
            return Cache::remember(self::key($hotelId, $scope, $parts), $ttl ?? self::TTL, $callback);
        } catch (\Throwable $e) {
            return $callback();
        }
    }
}
