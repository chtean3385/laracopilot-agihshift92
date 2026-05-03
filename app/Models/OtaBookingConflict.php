<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class OtaBookingConflict extends Model
{
    use BelongsToHotel;

    protected $table = 'ota_booking_conflicts';

    protected $fillable = [
        'hotel_id',
        'booking_id',
        'parsed_email_id',
        'conflict_type',
        'requested_room_type',
        'check_in_date',
        'check_out_date',
        'resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
        'resolved'       => 'boolean',
        'resolved_at'    => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function parsedEmail()
    {
        return $this->belongsTo(ParsedEmail::class);
    }

    public static function unresolvedCountForHotel(int $hotelId): int
    {
        return static::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('resolved', false)
            ->count();
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->conflict_type) {
            'dates_overlap'           => 'All matching rooms are booked for these dates',
            'room_type_unavailable'   => 'Requested room type unavailable',
            'no_room_matched'         => 'No room matched the requested type',
            default                   => ucfirst(str_replace('_', ' ', (string) $this->conflict_type)),
        };
    }
}
