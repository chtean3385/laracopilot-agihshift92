<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];

    public static function isEnabled(string $slug): bool
    {
        $module = static::where('slug', $slug)->first();
        return $module ? (bool) $module->is_enabled : false;
    }
}
