<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelManagerConfig extends Model
{
    protected $fillable = [
        'provider', 'api_key', 'api_secret', 'hotel_code',
        'property_id', 'is_active', 'last_synced_at', 'extra_config',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'extra_config'   => 'array',
    ];

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public static function current(): self
    {
        return static::firstOrNew(['id' => 1]);
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'ezee'       => 'eZee Centrix',
            'staah'      => 'STAAH',
            'siteminder' => 'SiteMinder',
            'rategain'   => 'RateGain',
            default      => ucfirst($this->provider),
        };
    }
}
