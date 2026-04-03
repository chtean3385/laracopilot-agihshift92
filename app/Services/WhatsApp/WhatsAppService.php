<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
use App\Models\Module;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\Providers\MetaProvider;
use App\Services\WhatsApp\Providers\WatiProvider;
use App\Services\WhatsApp\Providers\InteraktProvider;
use App\Services\WhatsApp\Providers\GupshupProvider;
use App\Services\WhatsApp\Providers\TwilioProvider;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /** Last human-readable error from a failed send (cleared on each sendRaw call). */
    public static string $lastError = '';

    public static function setLastError(string $msg): void
    {
        static::$lastError = $msg;
    }

    public static function getLastError(): string
    {
        return static::$lastError;
    }

    private static function provider(WhatsAppConfig $config): ?WhatsAppProviderInterface
    {
        return match ($config->provider) {
            'meta'      => new MetaProvider($config),
            'wati'      => new WatiProvider($config),
            'interakt'  => new InteraktProvider($config),
            'gupshup'   => new GupshupProvider($config),
            'twilio'    => new TwilioProvider($config),
            default     => null,
        };
    }

    public static function sendForEvent(string $event, Booking $booking): bool
    {
        try {
            if (!Module::isEnabled('whatsapp')) {
                return false;
            }

            $config = WhatsAppConfig::active();
            if (!$config) {
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
            $provider = static::provider($config);
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
                static::setLastError('No active WhatsApp configuration found. Save your credentials and tick "Active".');
                return false;
            }

            $provider = static::provider($config);
            if (!$provider) {
                static::setLastError('Unknown WhatsApp provider: ' . $config->provider);
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
