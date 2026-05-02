<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConfig extends Model
{
    use BelongsToHotel;

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    protected $table = 'whatsapp_configs';

    protected $fillable = [
        'hotel_id',
        'provider',
        'api_key',
        'access_token',
        'phone_number_id',
        'phone_number',
        'managed_display_name',
        'managed_otp_status',
        'webhook_verify_token',
        'business_account_id',
        'waba_id',
        'test_phone',
        'is_active',
        'mode',
        'setup_step',
        'setup_completed',
        'use_platform_templates',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'setup_completed'         => 'boolean',
        'setup_step'              => 'integer',
        'use_platform_templates'  => 'boolean',
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

    public function isManagedMode(): bool
    {
        return $this->mode === 'managed';
    }

    public function isSetupComplete(): bool
    {
        return (bool) $this->setup_completed;
    }
}
