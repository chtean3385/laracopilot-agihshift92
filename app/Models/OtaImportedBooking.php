<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class OtaImportedBooking extends Model
{
    use BelongsToHotel;

    protected $table = 'ota_imported_bookings';

    protected $fillable = [
        'hotel_id',
        'ota_source_id',
        'raw_message',
        'booking_ref',
        'guest_name',
        'guest_phone',
        'checkin',
        'checkout',
        'room_type',
        'guests_count',
        'amount',
        'special_request',
        'ota_name',
        'property_name',
        'matched_by',
        'source_channel',
        'status',
        'booking_id',
    ];

    protected $casts = [
        'checkin'  => 'date',
        'checkout' => 'date',
        'amount'   => 'decimal:2',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function otaSource()
    {
        return $this->belongsTo(OtaSource::class, 'ota_source_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public static function pendingCountForHotel(int $hotelId): int
    {
        return static::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->count();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => '#f59e0b',
            'confirmed' => '#10b981',
            'rejected'  => '#ef4444',
            'duplicate' => '#94a3b8',
            default     => '#64748b',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending Review',
            'confirmed' => 'Confirmed',
            'rejected'  => 'Rejected',
            'duplicate' => 'Duplicate',
            default     => ucfirst($this->status),
        };
    }
}
