<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBackup extends Model
{
    protected $fillable = [
        'hotel_id', 'backup_data', 'type', 'created_by', 'size_kb', 'label',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
