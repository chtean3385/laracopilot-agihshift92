<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestCheckinRequest extends Model
{
    protected $fillable = [
        'hotel_id', 'name', 'phone', 'email',
        'id_type', 'id_number', 'address', 'date_of_birth',
        'id_document_path', 'signature_data',
        'additional_guests', 'requested_check_in', 'requested_check_out',
        'guests_count', 'status', 'customer_id', 'booking_id', 'notes',
    ];

    protected $casts = [
        'additional_guests'   => 'array',
        'requested_check_in'  => 'date',
        'requested_check_out' => 'date',
        'date_of_birth'       => 'date',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
