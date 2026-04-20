<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'resort_name', 'tagline', 'address', 'phone', 'email', 'website',
        'gst_number', 'tax_rate', 'food_tax_rate', 'currency', 'currency_symbol',
        'check_in_time', 'check_out_time', 'cancellation_policy', 'logo',
        'invoice_style', 'contact_number', 'state_code',
        'hsn_room', 'hsn_food',
        'bank_name', 'bank_account_number', 'bank_ifsc',
    ];
}
