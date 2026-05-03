<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'item_id', 'type', 'quantity',
        'notes', 'reference_type', 'reference_id', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
