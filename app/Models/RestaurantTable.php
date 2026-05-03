<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class RestaurantTable extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'capacity',
        'status',
        'section',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(RestaurantOrder::class, 'table_id');
    }

    public function activeOrder()
    {
        // Pending guest-QR orders are NOT yet active — staff must approve
        // them first. Excluding them prevents the table-map from showing
        // a session before approval and avoids accidental attachment.
        return $this->hasOne(RestaurantOrder::class, 'table_id')
            ->whereIn('status', ['open', 'kotted', 'served'])
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', '!=', 'pending');
            });
    }

    // Helpers
    public function isFree(): bool
    {
        return $this->status === 'free';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

   public function statusColor(): string
{
    return match($this->status) {
        'free'        => 'green',
        'occupied'    => 'orange',
        'dirty'       => 'red',
        'unavailable' => 'black',
        default       => 'gray',
    };
}

public function statusLabel(): string
{
    return match($this->status) {
        'free'        => 'Free',
        'occupied'    => 'Occupied',
        'dirty'       => 'Needs Cleaning',
        'unavailable' => 'Not Available',
        default       => 'Unknown',
    };
}
}