<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use BelongsToHotel;

    protected $fillable = ['hotel_id', 'name', 'description'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }
}
