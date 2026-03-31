<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class ChannelBooking extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'channel', 'ota_booking_id', 'guest_name', 'guest_phone', 'guest_email',
        'room_id', 'check_in_date', 'check_out_date', 'nights',
        'rate_per_night', 'total_amount', 'commission_pct', 'net_amount',
        'status', 'converted_booking_id', 'notes', 'raw_data',
    ];

    protected $casts = [
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
        'raw_data'       => 'array',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function convertedBooking()
    {
        return $this->belongsTo(Booking::class, 'converted_booking_id');
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'booking_com'   => 'Booking.com',
            'airbnb'        => 'Airbnb',
            'expedia'       => 'Expedia',
            'goibibo'       => 'Goibibo',
            'makemytrip'    => 'MakeMyTrip',
            'agoda'         => 'Agoda',
            'tripadvisor'   => 'TripAdvisor',
            'yatra'         => 'Yatra',
            'direct'        => 'Direct',
            default         => 'Other',
        };
    }

    public function channelColor(): string
    {
        return match ($this->channel) {
            'booking_com'   => '#003580',
            'airbnb'        => '#ff385c',
            'expedia'       => '#ffc72c',
            'goibibo'       => '#e8333c',
            'makemytrip'    => '#e74c3c',
            'agoda'         => '#e3003e',
            'tripadvisor'   => '#00aa6c',
            'yatra'         => '#e25822',
            'direct'        => '#0891b2',
            default         => '#64748b',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'confirmed'  => '#16a34a',
            'cancelled'  => '#ef4444',
            'converted'  => '#7c3aed',
            default      => '#f59e0b',
        };
    }
}
