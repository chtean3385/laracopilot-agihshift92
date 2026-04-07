<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConfig extends Model
{
    use BelongsToHotel;

    protected $table = 'whatsapp_configs';

    protected $fillable = [
        'hotel_id',
        'provider',
        'api_key',
        'access_token',
        'phone_number_id',
        'webhook_verify_token',
        'business_account_id',
        'waba_id',
        'test_phone',
        'is_active',
        'mode',
        'setup_step',
        'setup_completed',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'setup_completed' => 'boolean',
        'setup_step'      => 'integer',
    ];

    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }

    public function isSharedMode(): bool
    {
        return $this->mode === 'shared';
    }

    public function isOwnMode(): bool
    {
        return $this->mode === 'own';
    }

    public function isSetupComplete(): bool
    {
        return (bool) $this->setup_completed;
    }
}
