<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConfig extends Model
{
    protected $table = 'whatsapp_configs';

    protected $fillable = [
        'provider',
        'api_key',
        'phone_number_id',
        'webhook_verify_token',
        'business_account_id',
        'test_phone',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }
}
