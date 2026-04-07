<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
use App\Models\Module;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\Providers\MetaProvider;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static string $lastError = '';

    public static function setLastError(string $msg): void
    {
        static::$lastError = $msg;
    }

    public static function getLastError(): string
    {
        return static::$lastError;
    }

    private static function providerForConfig(WhatsAppConfig $config): ?WhatsAppProviderInterface
    {
        if ($config->isSharedMode()) {
            $platform = PlatformWhatsAppSetting::instance();
            if (!$platform || !$platform->is_saas_active) {
                static::setLastError('The shared CRM WhatsApp number is not active. Contact support.');
                return null;
            }
            $sharedConfig = new WhatsAppConfig([
                'provider'        => 'meta',
                'api_key'         => $platform->saas_token,
                'phone_number_id' => $platform->saas_phone_number_id,
            ]);
            return new MetaProvider($sharedConfig);
        }

        if ($config->provider !== 'meta') {
            static::setLastError('Only Meta WhatsApp Business API is supported. Please reconfigure your WhatsApp setup.');
            Log::error('WhatsApp: non-Meta provider blocked', ['provider' => $config->provider]);
            return null;
        }

        return new MetaProvider($config);
    }

    public static function sendForEvent(string $event, Booking $booking): bool
    {
        try {
            if (!Module::isEnabled('whatsapp')) {
                return false;
            }

            $config = WhatsAppConfig::active();
            if (!$config || !$config->isSetupComplete()) {
                return false;
            }

            $template = WhatsAppTemplate::forEvent($event);
            if (!$template) {
                return false;
            }

            $booking->load(['customer', 'room', 'invoice']);
            $phone = $booking->customer->phone ?? null;
            if (!$phone) {
                return false;
            }

            $message  = MessageBuilder::build($template->message_body, $booking);
            $provider = static::providerForConfig($config);
            if (!$provider) {
                return false;
            }

            return $provider->sendMessage($phone, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendForEvent error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendRaw(string $phone, string $message): bool
    {
        static::$lastError = '';

        try {
            if (!Module::isEnabled('whatsapp')) {
                static::setLastError('WhatsApp module is not enabled for this hotel.');
                return false;
            }

            $config = WhatsAppConfig::active();
            if (!$config) {
                static::setLastError('No active WhatsApp configuration found. Complete the WhatsApp setup first.');
                return false;
            }

            $provider = static::providerForConfig($config);
            if (!$provider) {
                static::setLastError(static::$lastError ?: 'WhatsApp provider could not be initialised.');
                return false;
            }

            return $provider->sendMessage($phone, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendRaw error: ' . $e->getMessage());
            static::setLastError($e->getMessage());
            return false;
        }
    }
}
