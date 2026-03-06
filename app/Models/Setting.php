<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'resort_name', 'address', 'phone', 'email', 'website',
        'gst_number', 'tax_rate', 'currency', 'currency_symbol',
        'check_in_time', 'check_out_time', 'cancellation_policy', 'logo',
    ];
}