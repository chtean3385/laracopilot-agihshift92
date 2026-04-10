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
        $context = ['event' => $event, 'booking_id' => $booking->id, 'hotel_id' => $booking->hotel_id];

        try {
            if (!Module::isEnabled('whatsapp')) {
                Log::info('WhatsApp sendForEvent skipped: module not enabled', $context);
                return false;
            }

            $config = WhatsAppConfig::active();
            if (!$config) {
                Log::info('WhatsApp sendForEvent skipped: no active WhatsApp config', $context);
                return false;
            }
            if (!$config->isSetupComplete()) {
                Log::info('WhatsApp sendForEvent skipped: setup not complete', $context);
                return false;
            }

            $template = WhatsAppTemplate::forEvent($event);
            if (!$template) {
                Log::info('WhatsApp sendForEvent skipped: no active template for event', $context);
                return false;
            }

            if ($template->is_active && $template->approval_status !== 'approved') {
                Log::warning('WhatsApp sendForEvent skipped: template not yet approved by Meta', array_merge($context, [
                    'template_id'       => $template->id,
                    'template_name'     => $template->template_name,
                    'approval_status'   => $template->approval_status,
                    'meta_status'       => $template->meta_status,
                    'action_required'   => 'Submit the template to Meta via Platform Admin → WhatsApp → Message Templates and wait for approval.',
                ]));
                return false;
            }

            $booking->load(['customer', 'room', 'invoice']);
            $phone = $booking->customer->phone ?? null;
            if (!$phone) {
                Log::info('WhatsApp sendForEvent skipped: customer has no phone number', $context);
                return false;
            }

            $message  = MessageBuilder::build($template->message_body, $booking);
            $provider = static::providerForConfig($config);
            if (!$provider) {
                Log::info('WhatsApp sendForEvent skipped: provider unavailable — ' . static::$lastError, $context);
                return false;
            }

            $sent = $provider->sendMessage($phone, $message);
            if ($sent) {
                Log::info('WhatsApp sendForEvent sent', array_merge($context, ['phone' => $phone]));
            } else {
                Log::warning('WhatsApp sendForEvent failed to send', array_merge($context, [
                    'phone' => $phone,
                    'error' => static::$lastError,
                ]));
            }
            return $sent;

        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendForEvent exception: ' . $e->getMessage(), $context);
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
