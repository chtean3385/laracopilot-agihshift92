<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaSource extends Model
{
    protected $table = 'ota_sources';

    protected $fillable = [
        'name',
        'sender_number',
        'waba_id',
        'message_pattern_key',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function findBySender(string $phone): ?static
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        if (!$normalized) return null;

        return static::where('is_active', true)
            ->whereNotNull('sender_number')
            ->whereRaw("regexp_replace(sender_number, '[^0-9]', '', 'g') = ?", [$normalized])
            ->first();
    }

    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }
}
