<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWhatsAppSetting extends Model
{
    protected $table = 'platform_whatsapp_settings';

    protected $fillable = [
        'meta_app_id',
        'meta_app_secret',
        'meta_config_id',
        'saas_token',
        'saas_phone_number_id',
        'saas_waba_id',
        'webhook_verify_token',
        'is_saas_active',
    ];

    protected $casts = [
        'is_saas_active' => 'boolean',
    ];

    public static function instance(): ?static
    {
        return static::first();
    }

    public static function isFullyConfigured(): bool
    {
        $s = static::first();
        return $s
            && $s->meta_app_id
            && $s->meta_app_secret
            && $s->meta_config_id
            && $s->saas_token
            && $s->saas_phone_number_id
            && $s->saas_waba_id
            && $s->is_saas_active;
    }

    public static function isEmbeddedSignupReady(): bool
    {
        $s = static::first();
        return $s && $s->meta_app_id && $s->meta_app_secret && $s->meta_config_id;
    }
}
