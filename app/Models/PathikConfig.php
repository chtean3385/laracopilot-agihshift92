<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PathikConfig extends Model
{
    use BelongsToHotel;

    protected $table = 'pathik_configs';

    protected $fillable = ['hotel_id', 'api_token', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function current(): static
    {
        $config = static::first();
        if (!$config) {
            $config = static::create([
                'api_token' => Str::random(32),
                'is_active' => false,
            ]);
        }
        return $config;
    }
}
