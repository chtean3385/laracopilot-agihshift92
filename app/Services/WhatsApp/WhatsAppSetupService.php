<?php

namespace App\Services\WhatsApp;

use App\Models\PlatformWhatsAppSetting;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSetupService
{
    protected PlatformWhatsAppSetting $platform;

    public function __construct()
    {
        $this->platform = PlatformWhatsAppSetting::instance() ?? new PlatformWhatsAppSetting();
    }

    public function exchangeCode(string $code): array
    {
        if (!$this->platform->meta_app_id || !$this->platform->meta_app_secret) {
            return ['success' => false, 'error' => 'Platform WhatsApp credentials are not configured. Please contact support.'];
        }

        try {
            $response = Http::timeout(20)->get('https://graph.facebook.com/v22.0/oauth/access_token', [
                'client_id'     => $this->platform->meta_app_id,
                'client_secret' => $this->platform->meta_app_secret,
                'code'          => $code,
            ]);

            if (!$response->successful()) {
                $err = $response->json('error') ?? [];
                return ['success' => false, 'error' => $this->mapMetaError($err, 'token_exchange')];
            }

            $data = $response->json();
            return [
                'success'      => true,
                'access_token' => $data['access_token'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp code exchange error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Could not reach Meta\'s servers. Check your internet connection and try again.'];
        }
    }

    public function subscribeWebhook(string $wabaId, string $token): array
    {
        if (!$this->platform->meta_app_id) {
            return ['success' => false, 'error' => 'Platform WhatsApp App ID is not configured. Please contact support.'];
        }

        if (!$this->platform->webhook_verify_token) {
            return ['success' => false, 'error' => 'The webhook verification token has not been set up yet. Please contact support to complete platform configuration.'];
        }

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->post("https://graph.facebook.com/v22.0/{$wabaId}/subscribed_apps", [
                    'override_callback_and_verify_token' => true,
                    'callback_url'                        => route('whatsapp.webhook.receive'),
                    'verify_token'                        => $this->platform->webhook_verify_token,
                ]);

            if (!$response->successful()) {
                $err = $response->json('error') ?? [];
                return ['success' => false, 'error' => $this->mapMetaError($err, 'webhook_subscribe')];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook subscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Could not reach Meta\'s servers while setting up webhook. Try again.'];
        }
    }

    public function submitAllTemplates(WhatsAppConfig $config): array
    {
        $templates = WhatsAppTemplate::where('is_active', true)->get();

        if ($templates->isEmpty()) {
            return ['success' => true, 'submitted' => 0, 'results' => []];
        }

        $token  = $config->access_token;
        $wabaId = $config->waba_id;
        $results = [];
        $errors  = [];

        foreach ($templates as $template) {
            $result = $this->submitTemplate($wabaId, $token, $template);
            $results[$template->id] = $result;
            if (!$result['success']) {
                $errors[] = $result['error'];
            }
        }

        if (!empty($errors) && count($errors) === count($results)) {
            return ['success' => false, 'error' => 'All template submissions failed: ' . implode('; ', array_slice($errors, 0, 3)), 'results' => $results, 'submitted' => 0];
        }

        $submitted = collect($results)->where('success', true)->count();
        return ['success' => true, 'submitted' => $submitted, 'results' => $results];
    }

    public function submitTemplate(string $wabaId, string $token, WhatsAppTemplate $template): array
    {
        try {
            $bodyText = $template->convertBodyForMeta();

            $response = Http::timeout(20)
                ->withToken($token)
                ->post("https://graph.facebook.com/v22.0/{$wabaId}/message_templates", [
                    'name'       => str_replace([' ', '-'], '_', strtolower($template->template_name)),
                    'language'   => 'en',
                    'category'   => 'UTILITY',
                    'components' => [
                        ['type' => 'BODY', 'text' => $bodyText],
                    ],
                ]);

            if (!$response->successful()) {
                $err = $response->json('error') ?? [];
                $errorMsg = $this->mapMetaError($err, 'template_submit', $template->template_name);
                $template->update(['meta_status' => 'rejected']);
                return ['success' => false, 'error' => $errorMsg, 'template_id' => $template->id];
            }

            $data = $response->json();
            $template->update([
                'meta_template_id' => $data['id'] ?? null,
                'meta_status'      => 'submitted',
            ]);

            return ['success' => true, 'template_id' => $template->id, 'meta_id' => $data['id'] ?? null];
        } catch (\Throwable $e) {
            Log::error('WhatsApp template submit error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Could not reach Meta\'s servers while submitting template "' . $template->template_name . '". Try again.', 'template_id' => $template->id];
        }
    }

    public function mapMetaError(array $error, string $context = '', string $templateName = ''): string
    {
        $code    = (int) ($error['code'] ?? 0);
        $subcode = (int) ($error['error_subcode'] ?? 0);
        $msg     = $error['message'] ?? '';

        if ($code === 190 || $code === 102 || $code === 101) {
            return 'Your Meta session has expired or is invalid. Please reconnect your account by clicking the button below.';
        }

        if ($code === 4 || $code === 32 || $code === 613) {
            return 'Meta is temporarily busy (rate limit reached). Please wait a few minutes and try again.';
        }

        if (str_contains($msg, 'already registered') || $subcode === 33000 || $code === 33000) {
            return 'number_already_registered';
        }

        if (str_contains($msg, 'Invalid OAuth') || str_contains($msg, 'code')) {
            return 'The connection attempt failed. Please try clicking the connect button again.';
        }

        if ($context === 'template_submit') {
            if (str_contains($msg, 'Duplicate') || $code === 2388085) {
                return "The template \"{$templateName}\" already exists in Meta — it will be reused and updated automatically.";
            }
            if ($code === 2388059 || str_contains($msg, 'variable') || str_contains($msg, 'parameter')) {
                return "The template \"{$templateName}\" has a formatting issue with its variables. Please edit the template and resubmit.";
            }
            if (str_contains($msg, 'phone') || str_contains($msg, 'URL') || str_contains($msg, 'url')) {
                return "The template \"{$templateName}\" was rejected because it contains phone numbers or links, which are not allowed. Please edit and resubmit.";
            }
            return "The template \"{$templateName}\" could not be submitted. Please check the template content and try again.";
        }

        if ($context === 'webhook_subscribe') {
            return 'Could not configure delivery notifications. Please try the setup again. If the problem continues, contact support.';
        }

        return 'An unexpected error occurred. Please try again. If the problem continues, contact your support team.';
    }
}
