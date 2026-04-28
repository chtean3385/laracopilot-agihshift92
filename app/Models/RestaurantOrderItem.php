<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'unit_price',
        'final_price',
        'quantity',
        'subtotal',
        'kot_note',
        'food_type',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'final_price' => 'decimal:2',
        'subtotal'    => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(RestaurantOrder::class, 'order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(RestaurantMenuItem::class, 'menu_item_id');
    }

    // Auto calculate subtotal
    public function calculateSubtotal(): void
    {
        $this->subtotal = $this->final_price * $this->quantity;
    }

    public function foodTypeBadge(): string
    {
        return match($this->food_type) {
            'veg'      => '🟢',
            'nonveg'   => '🔴',
            'beverage' => '🔵',
            default    => '',
        };
    }
}