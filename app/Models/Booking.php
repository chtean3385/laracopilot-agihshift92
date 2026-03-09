<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_number', 'customer_id', 'room_id',
        'check_in_date', 'check_out_date',
        'actual_checkin_at', 'actual_checkout_at',
        'nights', 'adults', 'children',
        'total_amount', 'advance_payment', 'balance_due',
        'special_requests', 'status', 'payment_status',
        'checkin_notes', 'checkout_notes',
        'meal_breakfast', 'meal_lunch', 'meal_dinner', 'meal_cost',
    ];

    protected $casts = [
        'check_in_date'      => 'date',
        'check_out_date'     => 'date',
        'actual_checkin_at'  => 'datetime',
        'actual_checkout_at' => 'datetime',
        'total_amount'       => 'decimal:2',
        'advance_payment'    => 'decimal:2',
        'balance_due'        => 'decimal:2',
        'meal_cost'          => 'decimal:2',
        'meal_breakfast'     => 'boolean',
        'meal_lunch'         => 'boolean',
        'meal_dinner'        => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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