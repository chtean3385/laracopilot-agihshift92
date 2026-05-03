<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class FoodCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'description', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function items()
    {
        return $this->hasMany(FoodItem::class, 'category_id');
    }

    public function availableItems()
    {
        return $this->hasMany(FoodItem::class, 'category_id')->where('is_available', true)->orderBy('sort_order');
    }
}
