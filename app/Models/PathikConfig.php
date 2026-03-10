<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PathikConfig extends Model
{
    protected $table = 'pathik_configs';

    protected $fillable = ['api_token', 'is_active'];

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
