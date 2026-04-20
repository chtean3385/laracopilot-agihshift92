<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'slug', 'name', 'description', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];

    public static function isEnabled(string $slug): bool
    {
        try {
            $module = static::where('slug', $slug)->first();
            return $module ? (bool) $module->is_enabled : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function isEnabledForHotel(string $slug, int $hotelId): bool
    {
        try {
            $module = static::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->where('slug', $slug)
                ->first();

            if ($module && $module->is_enabled) {
                return true;
            }

            // Parent fallback: if module is disabled or missing, check the parent hotel
            $parentId = \DB::table('hotels')->where('id', $hotelId)->value('parent_hotel_id');
            if ($parentId) {
                $parentModule = static::withoutGlobalScopes()
                    ->where('hotel_id', (int) $parentId)
                    ->where('slug', $slug)
                    ->first();
                return $parentModule ? (bool) $parentModule->is_enabled : false;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function cacheVersionKey(int $hotelId): string
    {
        return "slot_search_v_{$hotelId}";
    }

    public static function bumpSearchCache(int $hotelId): void
    {
        try {
            $key     = static::cacheVersionKey($hotelId);
            $current = \Illuminate\Support\Facades\Cache::get($key, 0);
            \Illuminate\Support\Facades\Cache::put($key, $current + 1, 3600);
        } catch (\Throwable $e) {
            // non-critical
        }
    }
}
