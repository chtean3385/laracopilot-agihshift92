<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class RestaurantMenuCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(RestaurantMenuItem::class, 'category_id')
            ->orderBy('sort_order');
    }

    public function activeItems()
    {
        return $this->hasMany(RestaurantMenuItem::class, 'category_id')
            ->where('is_available', true)
            ->orderBy('sort_order');
    }
}