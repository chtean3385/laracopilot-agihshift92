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
}
