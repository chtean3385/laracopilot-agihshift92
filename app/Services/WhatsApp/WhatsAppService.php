<?php

namespace App\Services\WhatsApp;

use App\Helpers\PhoneHelper;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\InvoicePdf;
use App\Services\WhatsApp\Providers\MetaProvider;
use Illuminate\Support\Facades\DB;
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

    private static function providerForConfig(WhatsAppConfig $config): ?MetaProvider
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

        if ($config->isManagedMode()) {
            $platform = PlatformWhatsAppSetting::instance();
            if (!$platform || !$platform->saas_token) {
                static::setLastError('Platform WhatsApp credentials are not configured. Contact the CRM administrator.');
                return null;
            }
            if (!$config->phone_number_id) {
                static::setLastError('This hotel\'s managed number is not yet verified. Contact the CRM administrator.');
                return null;
            }
            $managedConfig = new WhatsAppConfig([
                'provider'        => 'meta',
                'api_key'         => $platform->saas_token,
                'phone_number_id' => $config->phone_number_id,
            ]);
            return new MetaProvider($managedConfig);
        }

        if ($config->provider !== 'meta') {
            static::setLastError('Only Meta WhatsApp Business API is supported. Please reconfigure your WhatsApp setup.');
            Log::error('WhatsApp: non-Meta provider blocked', ['provider' => $config->provider]);
            return null;
        }

        return new MetaProvider($config);
    }

    private static function isOverLimit(int $hotelId): bool
    {
        try {
            $hotel = Hotel::find($hotelId);
            if (!$hotel) return false;

            if ($hotel->wa_daily_limit) {
                $todayCount = WhatsAppLog::where('hotel_id', $hotelId)
                    ->where('direction', 'outgoing')
                    ->where('event_type', 'message_sent')
                    ->where('status', 'ok')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->count();
                if ($todayCount >= $hotel->wa_daily_limit) {
                    static::setLastError("Daily WhatsApp message limit ({$hotel->wa_daily_limit}) reached for this hotel.");
                    Log::warning("WhatsApp daily limit reached for hotel #{$hotelId}", ['limit' => $hotel->wa_daily_limit, 'count' => $todayCount]);
                    return true;
                }
            }

            if ($hotel->wa_monthly_limit) {
                $monthCount = WhatsAppLog::where('hotel_id', $hotelId)
                    ->where('direction', 'outgoing')
                    ->where('event_type', 'message_sent')
                    ->where('status', 'ok')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count();
                if ($monthCount >= $hotel->wa_monthly_limit) {
                    static::setLastError("Monthly WhatsApp message limit ({$hotel->wa_monthly_limit}) reached for this hotel.");
                    Log::warning("WhatsApp monthly limit reached for hotel #{$hotelId}", ['limit' => $hotel->wa_monthly_limit, 'count' => $monthCount]);
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::isOverLimit error: ' . $e->getMessage());
        }
        return false;
    }

    public static function sendForEvent(string $event, Booking $booking): bool
    {
        static::$lastError = '';
        $context = ['event' => $event, 'booking_id' => $booking->id, 'hotel_id' => $booking->hotel_id];

        try {
            if (!Module::isEnabled('whatsapp')) {
                Log::info('WhatsApp sendForEvent skipped: module not enabled', $context);
                return false;
            }

            if (static::isOverLimit($booking->hotel_id)) {
                Log::info('WhatsApp sendForEvent blocked: hotel over limit', array_merge($context, ['error' => static::$lastError]));
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

            $booking->load(['customer', 'room', 'invoice.booking.extraCharges', 'payments']);
            $phone = PhoneHelper::forWhatsApp($booking->customer->phone ?? '');
            if (!$phone) {
                Log::info('WhatsApp sendForEvent skipped: customer has no phone number', $context);
                return false;
            }

            $provider = static::providerForConfig($config);
            if (!$provider) {
                Log::info('WhatsApp sendForEvent skipped: provider unavailable — ' . static::$lastError, $context);
                return false;
            }

            // For shared-mode (Basic plan hotels), use global platform templates (hotel_id=null)
            // These are the ones synced from Meta and actually approved.
            // For own-number hotels, fall back to hotel-specific templates.
            $template = null;
            if ($config->isSharedMode()) {
                $template = WhatsAppTemplate::globalForEvent($event);
                if (!$template) {
                    Log::warning('WhatsApp sendForEvent skipped: no approved global platform template for event', $context);
                    return false;
                }
                // Send using the Meta template API with positional parameters
                $vars      = MessageBuilder::buildVars($booking);
                $varNames  = $template->extractVariableNames();
                $params    = [];
                foreach ($varNames as $name) {
                    $params[] = $vars[$name] ?? '';
                }
                $sent = static::sendWithTemplate($provider, $phone, $template, $params, $booking, $context);
            } else {
                // Own/managed hotel — try hotel-specific template first
                $template = WhatsAppTemplate::forEvent($event);

                // If no approved hotel-specific template and hotel uses platform templates,
                // fall back to the global platform template (already approved in WABA)
                if ((!$template || $template->approval_status !== 'approved')
                    && $config->isManagedMode()
                    && ($config->use_platform_templates || $config->use_platform_templates === null)
                ) {
                    $globalTemplate = WhatsAppTemplate::globalForEvent($event);
                    if ($globalTemplate) {
                        Log::info('WhatsApp sendForEvent: using global platform template fallback', $context);
                        $vars     = MessageBuilder::buildVars($booking);
                        $varNames = $globalTemplate->extractVariableNames();
                        $params   = [];
                        foreach ($varNames as $name) {
                            $params[] = $vars[$name] ?? '';
                        }
                        return static::sendWithTemplate($provider, $phone, $globalTemplate, $params, $booking, $context);
                    }
                }

                if (!$template) {
                    Log::info('WhatsApp sendForEvent skipped: no active approved hotel template for event', $context);
                    return false;
                }
                if ($template->approval_status === 'approved' && !empty($template->template_name)) {
                    // Send as Meta template if approved
                    $vars     = MessageBuilder::buildVars($booking);
                    $varNames = $template->extractVariableNames();
                    $params   = [];
                    foreach ($varNames as $name) {
                        $params[] = $vars[$name] ?? '';
                    }
                    $sent = static::sendWithTemplate($provider, $phone, $template, $params, $booking, $context);
                } else {
                    // Fallback: plain text (only works if guest messaged hotel within 24h)
                    $message = MessageBuilder::build($template->message_body, $booking);
                    $sent    = $provider->sendMessage($phone, $message);
                }
            }

            if ($sent) {
                Log::info('WhatsApp sendForEvent sent successfully', array_merge($context, [
                    'phone'    => $phone,
                    'template' => $template->template_name,
                ]));
            } else {
                Log::warning('WhatsApp sendForEvent failed', array_merge($context, [
                    'phone' => $phone,
                    'error' => static::$lastError,
                ]));
            }
            return $sent;

        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendForEvent exception: ' . $e->getMessage(), array_merge($context, [
                'trace' => $e->getTraceAsString(),
            ]));
            return false;
        }
    }

    private static function sendWithTemplate(
        MetaProvider $provider,
        string $phone,
        WhatsAppTemplate $template,
        array $params,
        Booking $booking,
        array $context
    ): bool {
        $bodyParamCount = max(1, count($params));

        // If the template has a document attachment flag, try the full PDF path first.
        $mediaId  = null;
        $filename = null;
        if ($template->has_document_attachment && $booking->invoice) {
            $invoice  = $booking->invoice;
            $pdfBytes = InvoicePdf::generate($invoice);

            if ($pdfBytes) {
                $filename = 'Invoice-' . ($invoice->invoice_number ?? 'INV') . '.pdf';
                $mediaId  = $provider->uploadMedia($pdfBytes, $filename);

                if ($mediaId) {
                    Log::info('WhatsApp: sending document template with PDF attachment', array_merge($context, [
                        'template' => $template->template_name,
                        'media_id' => $mediaId,
                        'filename' => $filename,
                    ]));
                    $sent = $provider->sendDocumentTemplate(
                        $phone,
                        $template->template_name,
                        $mediaId,
                        $filename,
                        $params
                    );
                    if ($sent) {
                        return true;
                    }
                    // sendDocumentTemplate failed (e.g. Meta template has no DOCUMENT header component).
                    // Fall through: we still have the uploaded PDF ($mediaId) — we will send the text
                    // template notification + the PDF as a separate document message below.
                    Log::warning('WhatsApp: sendDocumentTemplate failed; will send text template + PDF separately', array_merge($context, [
                        'error' => static::$lastError,
                    ]));
                } else {
                    Log::warning('WhatsApp: PDF upload failed, falling back to text template', array_merge($context, [
                        'error' => static::$lastError,
                    ]));
                }
            } else {
                Log::warning('WhatsApp: PDF generation failed, falling back to text template', $context);
            }
        }

        // Determine the text template to send.
        // If the current template's document-send already failed AND it is itself a valid
        // text-body template (no HEADER in Meta), try sending it directly first.
        // Otherwise look for a paired text-only fallback template.
        if ($template->has_document_attachment) {
            // First try: send the SAME template as a plain text template
            // (works when Meta approved the template without a DOCUMENT header component)
            $textSent = $provider->sendTemplate($phone, $template->template_name, array_slice($params, 0, $bodyParamCount));

            if ($textSent) {
                Log::info('WhatsApp: sent PDF template as text-only (no DOCUMENT header in Meta)', array_merge($context, [
                    'template' => $template->template_name,
                ]));
                // Also send PDF as a separate document message (best-effort; needs open conv window)
                if ($mediaId && $filename) {
                    $docSent = $provider->sendDocument($phone, $mediaId, $filename, 'Your invoice is attached below.');
                    Log::info('WhatsApp: separate PDF document send ' . ($docSent ? 'succeeded' : 'skipped (outside 24h window)'), $context);
                }
                return true;
            }

            // That also failed — look for the approved text-only paired template
            $textTemplate = WhatsAppTemplate::withoutGlobalScopes()
                ->when(is_null($template->hotel_id), fn ($q) => $q->whereNull('hotel_id'))
                ->when(!is_null($template->hotel_id), fn ($q) => $q->where('hotel_id', $template->hotel_id))
                ->where('trigger_event', $template->trigger_event)
                ->where('has_document_attachment', false)
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first();

            if ($textTemplate) {
                Log::info('WhatsApp: using text-only fallback template', array_merge($context, [
                    'fallback_template' => $textTemplate->template_name,
                ]));
                $textVarNames = $textTemplate->extractVariableNames();
                $textVars     = MessageBuilder::buildVars($booking);
                $textParams   = [];
                foreach ($textVarNames as $name) {
                    $textParams[] = $textVars[$name] ?? '';
                }
                $sent = $provider->sendTemplate($phone, $textTemplate->template_name, array_slice($textParams, 0, max(1, count($textParams))));
                // Also send PDF as a separate document (best-effort)
                if ($sent && $mediaId && $filename) {
                    $docSent = $provider->sendDocument($phone, $mediaId, $filename, 'Your invoice is attached below.');
                    Log::info('WhatsApp: separate PDF document send ' . ($docSent ? 'succeeded' : 'skipped (outside 24h window)'), $context);
                }
                return $sent;
            }

            // Last resort — send as plain text message
            Log::warning('WhatsApp: no approved text fallback template found; sending plain text body', $context);
            $plainText = MessageBuilder::build($template->message_body, $booking);
            return $provider->sendMessage($phone, $plainText);
        }

        return $provider->sendTemplate($phone, $template->template_name, array_slice($params, 0, $bodyParamCount));
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

    /**
     * Send a plain-text reply to an OTA sender using the WhatsApp config for a specific hotel.
     * Used to acknowledge receipt of OTA booking confirmations.
     */
    public static function sendRawForHotel(int $hotelId, string $toPhone, string $message): bool
    {
        static::$lastError = '';

        try {
            $config = WhatsAppConfig::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->first();

            if (!$config) {
                static::setLastError('No active WhatsApp config for hotel #' . $hotelId);
                Log::info('WhatsAppService::sendRawForHotel: no active config for hotel #' . $hotelId);
                return false;
            }

            $provider = static::providerForConfig($config);
            if (!$provider) {
                Log::info('WhatsAppService::sendRawForHotel: provider unavailable — ' . static::$lastError);
                return false;
            }

            return $provider->sendMessage($toPhone, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendRawForHotel error: ' . $e->getMessage());
            static::setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Send a notification to a hotel-scoped phone using an APPROVED WhatsApp template
     * (hotel-scoped first, then the global platform template) identified by trigger_event.
     * If no approved template exists, the call is logged and skipped — plain-text is never
     * sent in its place.
     *
     * @param  int    $hotelId
     * @param  string $toPhone
     * @param  string $event   trigger_event (matches whatsapp_templates.trigger_event)
     * @param  array  $params  positional params for {{1}}…{{n}}
     * @return bool
     */
    public static function sendTemplateForHotel(int $hotelId, string $toPhone, string $event, array $params): bool
    {
        static::$lastError = '';

        $config = WhatsAppConfig::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            static::setLastError('No active WhatsApp config for hotel #' . $hotelId);
            return false;
        }

        $provider = static::providerForConfig($config);
        if (!$provider) {
            return false;
        }

        $candidates = [
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->where('trigger_event', $event)
                ->where('is_active', true)
                ->first(),
            WhatsAppTemplate::globalForEvent($event),
        ];

        $template = null;
        foreach ($candidates as $cand) {
            if ($cand
                && ($cand->approval_status ?? null) === 'approved'
                && !empty($cand->template_name)) {
                $template = $cand;
                break;
            }
        }

        if (!$template) {
            Log::info('WhatsAppService::sendTemplateForHotel skipped: no approved template', [
                'event'    => $event,
                'hotel_id' => $hotelId,
            ]);
            static::setLastError('No approved WhatsApp template for event ' . $event);
            return false;
        }

        return $provider->sendTemplate($toPhone, $template->template_name, $params);
    }

    /**
     * Send a booking alert to all owner/partner phones configured on the hotel's WhatsApp config.
     * Uses the approved Meta template 'booking_alert_owner' (trigger_event: booking.alert.owner).
     * Silently skips if notify_on_booking is off, no phones configured, or no approved template.
     */
    public static function sendOwnerAlert(Booking $booking): void
    {
        try {
            // Find hotel's WA config regardless of is_active — owner alert phones can be
            // configured even on hotels using the platform's shared number (not own WABA).
            $config = WhatsAppConfig::withoutGlobalScopes()
                ->where('hotel_id', $booking->hotel_id)
                ->first();

            if (!$config || !$config->notify_on_booking) {
                return;
            }

            $phones = $config->getNotifyPhoneList();
            if (empty($phones)) {
                return;
            }

            // Prefer hotel's own active provider; fall back to platform's shared WABA.
            $provider = null;
            if ($config->is_active) {
                $provider = static::providerForConfig($config);
            }
            if (!$provider) {
                $platform = PlatformWhatsAppSetting::instance();
                if ($platform && $platform->is_saas_active) {
                    $sharedConfig = new WhatsAppConfig([
                        'provider'        => 'meta',
                        'api_key'         => $platform->saas_token,
                        'phone_number_id' => $platform->saas_phone_number_id,
                        'mode'            => 'shared',
                    ]);
                    $provider = new \App\Services\WhatsApp\Providers\MetaProvider($sharedConfig);
                }
            }
            if (!$provider) {
                Log::info('WhatsApp sendOwnerAlert: no provider available', ['hotel_id' => $booking->hotel_id]);
                return;
            }

            // Find template — hotel-specific first, then global platform template
            $template = WhatsAppTemplate::withoutGlobalScopes()
                ->where('hotel_id', $booking->hotel_id)
                ->where('trigger_event', 'booking.alert.owner')
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->first()
                ?? WhatsAppTemplate::withoutGlobalScopes()
                    ->whereNull('hotel_id')
                    ->where('trigger_event', 'booking.alert.owner')
                    ->where('is_active', true)
                    ->where('approval_status', 'approved')
                    ->first();

            if (!$template) {
                Log::info('WhatsApp sendOwnerAlert: no approved template for booking.alert.owner', [
                    'hotel_id' => $booking->hotel_id,
                ]);
                return;
            }

            $booking->loadMissing(['customer', 'room', 'invoice']);
            $vars     = MessageBuilder::buildVars($booking);
            $varNames = $template->extractVariableNames();
            $params   = array_map(fn($name) => $vars[$name] ?? '', $varNames);

            foreach ($phones as $raw) {
                $phone = PhoneHelper::forWhatsApp($raw);
                if (!$phone) {
                    continue;
                }
                $sent = $provider->sendTemplate($phone, $template->template_name, $params);
                Log::info('WhatsApp sendOwnerAlert ' . ($sent ? 'sent' : 'failed'), [
                    'hotel_id'   => $booking->hotel_id,
                    'booking_id' => $booking->id,
                    'phone'      => $phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendOwnerAlert exception: ' . $e->getMessage(), [
                'hotel_id'   => $booking->hotel_id ?? null,
                'booking_id' => $booking->id ?? null,
            ]);
        }
    }

    /**
     * Send a plain-text reply identified by the Meta phone_number_id of the RECEIVING number.
     * Used for fallback OTA replies when no hotel has been resolved yet.
     */
    public static function sendRawViaPhoneNumberId(?string $phoneNumberId, string $toPhone, string $message): bool
    {
        static::$lastError = '';

        if (!$phoneNumberId) {
            static::setLastError('No recipient phone_number_id provided.');
            return false;
        }

        try {
            $config = WhatsAppConfig::withoutGlobalScopes()
                ->where('phone_number_id', $phoneNumberId)
                ->where('is_active', true)
                ->first();

            if (!$config) {
                $platform = PlatformWhatsAppSetting::instance();
                if ($platform && $platform->saas_phone_number_id === $phoneNumberId && $platform->is_saas_active) {
                    $config = new WhatsAppConfig([
                        'provider'        => 'meta',
                        'api_key'         => $platform->saas_token,
                        'phone_number_id' => $platform->saas_phone_number_id,
                    ]);
                }
            }

            if (!$config) {
                static::setLastError('No WhatsApp config found for phone_number_id: ' . $phoneNumberId);
                Log::info('WhatsAppService::sendRawViaPhoneNumberId: no config for phone_number_id ' . $phoneNumberId);
                return false;
            }

            $provider = static::providerForConfig($config);
            if (!$provider) {
                Log::info('WhatsAppService::sendRawViaPhoneNumberId: provider unavailable — ' . static::$lastError);
                return false;
            }

            return $provider->sendMessage($toPhone, $message);
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendRawViaPhoneNumberId error: ' . $e->getMessage());
            static::setLastError($e->getMessage());
            return false;
        }
    }

}
