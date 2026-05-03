<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class RestaurantMenuItem extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'category_id',
        'name',
        'description',
        'image_path',
        'price',
        'food_type',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price'        => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(RestaurantMenuCategory::class, 'category_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function foodTypeBadge(): string
    {
        return match($this->food_type) {
            'veg'      => '<span class="badge-veg">🟢 Veg</span>',
            'nonveg'   => '<span class="badge-nonveg">🔴 Non-Veg</span>',
            'beverage' => '<span class="badge-beverage">🔵 Beverage</span>',
            default    => '',
        };
    }
}