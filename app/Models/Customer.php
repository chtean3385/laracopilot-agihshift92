<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name', 'email', 'phone', 'address', 'city', 'state',
        'country', 'id_type', 'id_number', 'date_of_birth',
        'nationality', 'notes', 'signature',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}