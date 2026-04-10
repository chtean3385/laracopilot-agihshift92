<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
use App\Models\Module;
use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\InvoicePdf;
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

        if ($config->provider !== 'meta') {
            static::setLastError('Only Meta WhatsApp Business API is supported. Please reconfigure your WhatsApp setup.');
            Log::error('WhatsApp: non-Meta provider blocked', ['provider' => $config->provider]);
            return null;
        }

        return new MetaProvider($config);
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
            $phone = $booking->customer->phone ?? null;
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
                // Own-number hotel: use hotel-specific template
                $template = WhatsAppTemplate::forEvent($event);
                if (!$template) {
                    Log::info('WhatsApp sendForEvent skipped: no active approved hotel template for event', $context);
                    return false;
                }
                if ($template->meta_status === 'approved' && !empty($template->template_name)) {
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
        // If the template has a document attachment flag, try the PDF path first.
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
                    // sendDocumentTemplate failed — fall through to text template
                    Log::warning('WhatsApp: sendDocumentTemplate failed, falling back to text template', array_merge($context, [
                        'error' => static::$lastError,
                    ]));
                } else {
                    // Media upload failed — fall through to text template
                    Log::warning('WhatsApp: PDF upload failed, falling back to text template', array_merge($context, [
                        'error' => static::$lastError,
                    ]));
                }
            } else {
                Log::warning('WhatsApp: PDF generation failed, falling back to text template', $context);
            }
        }

        // Standard text-only template send.
        // If the current template is a DOCUMENT-header template (PDF), it cannot be sent
        // as a plain body-only template — Meta would reject it. Instead, find and send
        // the approved text-only template for the same event as the fallback.
        if ($template->has_document_attachment) {
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
                return $provider->sendTemplate($phone, $textTemplate->template_name, $textParams);
            }

            // No approved text template found — cannot send anything
            static::setLastError('PDF send failed and no approved text-only checkout template found for fallback.');
            Log::error('WhatsApp: PDF fallback failed — no approved text template', $context);
            return false;
        }

        return $provider->sendTemplate($phone, $template->template_name, $params);
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
