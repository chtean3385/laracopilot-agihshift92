<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'category_id', 'name', 'description', 'price',
        'is_available', 'image_path', 'sort_order',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'is_available' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(InventoryItem::class, 'food_item_ingredients', 'food_item_id', 'inventory_item_id')
                    ->withPivot('quantity_per_unit')
                    ->withTimestamps();
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}
