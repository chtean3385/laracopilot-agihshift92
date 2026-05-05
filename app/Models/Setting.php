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
        'check_in_time', 'check_out_time', 'cancellation_policy', 'logo', 'logo_data',
        'invoice_style', 'contact_number', 'state_code',
        'hsn_room', 'hsn_food',
        'bank_name', 'bank_account_number', 'bank_ifsc',
    ];

    /**
     * Returns a usable URL/data-URI for the hotel logo.
     * Priority: base64 data stored in DB → storage file → null.
     * Using DB storage means the logo survives every deployment.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_data) {
            return $this->logo_data;
        }
        if ($this->logo && file_exists(public_path('storage/' . $this->logo))) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    public function getHasLogoAttribute(): bool
    {
        return !empty($this->logo_url);
    }
}
