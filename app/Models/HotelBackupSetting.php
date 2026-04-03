<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBackupSetting extends Model
{
    protected $fillable = [
        'hotel_id', 'auto_backup_enabled', 'interval_hours', 'retention_count', 'last_backup_at',
    ];

    protected $casts = [
        'auto_backup_enabled' => 'boolean',
        'last_backup_at'      => 'datetime',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
