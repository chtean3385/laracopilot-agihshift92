<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrderItem extends Model
{
    protected $fillable = ['order_id', 'food_item_id', 'name', 'price', 'quantity', 'total'];

    protected $casts = [
        'price'    => 'decimal:2',
        'total'    => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'order_id');
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class);
    }
}
