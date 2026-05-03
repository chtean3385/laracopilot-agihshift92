<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'category_id', 'name', 'unit',
        'current_stock', 'reorder_level', 'cost_price', 'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'cost_price'    => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'item_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= reorder_level AND reorder_level > 0');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isLowStock(): bool
    {
        return $this->reorder_level > 0 && $this->current_stock <= $this->reorder_level;
    }
}
