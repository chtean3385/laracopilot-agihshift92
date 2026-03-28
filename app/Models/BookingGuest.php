<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    protected $table = 'booking_guests';

    protected $fillable = [
        'booking_id', 'name', 'age', 'gender', 'nationality',
        'id_type', 'id_number', 'dob', 'relation',
        'signature', 'id_document_path', 'id_document_name', 'notes',
    ];

    protected $casts = [
        'dob' => 'date',
        'age' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public static function idTypes(): array
    {
        return [
            'aadhaar'    => 'Aadhaar Card',
            'passport'   => 'Passport',
            'voter_id'   => 'Voter ID',
            'pan'        => 'PAN Card',
            'driving'    => 'Driving Licence',
            'other'      => 'Other',
        ];
    }

    public static function relations(): array
    {
        return ['Self','Spouse','Child','Parent','Sibling','Friend','Colleague','Other'];
    }
}
