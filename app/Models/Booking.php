<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'booking_number', 'customer_id', 'room_id',
        'time_slot_id', 'booking_date', 'slot_start_time', 'slot_end_time', 'hours_booked',
        'check_in_date', 'check_out_date',
        'actual_checkin_at', 'actual_checkout_at',
        'nights', 'adults', 'children',
        'total_amount', 'advance_payment', 'balance_due',
        'special_requests', 'status', 'payment_status',
        'checkin_notes', 'checkout_notes',
        'meal_breakfast', 'meal_lunch', 'meal_dinner', 'meal_cost',
        'extra_beds', 'extra_bed_cost',
    ];

    protected $casts = [
        'check_in_date'      => 'date',
        'check_out_date'     => 'date',
        'booking_date'       => 'date',
        'actual_checkin_at'  => 'datetime',
        'actual_checkout_at' => 'datetime',
        'total_amount'       => 'decimal:2',
        'advance_payment'    => 'decimal:2',
        'balance_due'        => 'decimal:2',
        'meal_cost'          => 'decimal:2',
        'meal_breakfast'     => 'boolean',
        'meal_lunch'         => 'boolean',
        'meal_dinner'        => 'boolean',
        'extra_beds'         => 'integer',
        'extra_bed_cost'     => 'decimal:2',
        'hours_booked'       => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(HotelTimeSlot::class, 'time_slot_id')->withoutGlobalScopes();
    }

    public function bookingAddOns()
    {
        return $this->hasMany(BookingAddOn::class);
    }

    public function bookingGuests()
    {
        return $this->hasMany(BookingGuest::class)->orderBy('id');
    }

    public function isSlotBooking(): bool { return !is_null($this->time_slot_id); }
    public function isHourlyBooking(): bool { return !is_null($this->hours_booked) && $this->hours_booked > 0; }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'confirmed'   => 'blue',
            'checked_in'  => 'green',
            'checked_out' => 'gray',
            'cancelled'   => 'red',
            default       => 'gray',
        };
    }
}