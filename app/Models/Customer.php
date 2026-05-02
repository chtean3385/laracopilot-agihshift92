<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToHotel, SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name', 'email', 'phone', 'address', 'city', 'state',
        'country', 'id_type', 'id_number', 'date_of_birth', 'age',
        'nationality', 'notes', 'signature',
        'company_name', 'gstin',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'age'           => 'integer',
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
