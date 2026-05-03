<?php

namespace App\Http\Controllers;

use App\Models\OtaEmailSource;
use App\Models\OtaSource;
use App\Services\OtaBookingParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtaEmailWebhookController extends Controller
{
    /**
     * Mailgun Inbound Parse webhook.
     *
     * Mailgun POSTs a multipart/form-data payload with these key fields:
     *   recipient     — the "To" address (the hotel's configured inbound email)
     *   sender        — the actual SMTP envelope sender (may be hotel admin when forwarding)
     *   subject       — email subject line
     *   From          — original From header (may be the OTA address even in forwarded mail)
     *   stripped-text — body text with quoted replies removed
     *   body-plain    — full plain-text body
     *   body-html     — HTML body
     *   timestamp     — Unix timestamp (for signature verification)
     *   token         — random token (for signature verification)
     *   signature     — HMAC-SHA256(timestamp + token, MAILGUN_WEBHOOK_SIGNING_KEY)
     *
     * Resolution strategy:
     *   0. Verify Mailgun signature (if MAILGUN_WEBHOOK_SIGNING_KEY is configured).
     *   1. Match "recipient" against ota_email_sources → get hotel_id.
     *   2. Extract the best non-empty body text.
     *   3. Detect OTA using (in order): original From header domain, subject keywords,
     *      forwarded-body "From:" line, sender domain, then body content patterns.
     *   4. Run OtaBookingParserService::handleFromEmail().
     */
    public function receive(Request $request)
    {
        // ── 0. Mailgun signature verification ────────────────────────────────
        if (!$this->verifyMailgunSignature($request)) {
            Log::warning('OtaEmailWebhook: invalid Mailgun signature — request rejected');
            return response('forbidden', 403);
        }

        Log::info('OtaEmailWebhook: received inbound email', [
            'recipient' => $request->input('recipient'),
            'sender'    => $request->input('sender'),
            'subject'   => $request->input('subject'),
        ]);

        // ── 1. Resolve hotel from recipient address ───────────────────────────
        $recipient = trim($request->input('recipient', '') ?: $request->input('To', ''));
        if (!$recipient) {
            Log::warning('OtaEmailWebhook: missing recipient field');
            return response('ok', 200);
        }

        $emailSource = OtaEmailSource::findByRecipient($recipient);
        if (!$emailSource) {
            Log::warning('OtaEmailWebhook: no active hotel found for recipient: ' . $recipient);
            return response('ok', 200);
        }

        $hotelId = (int) $emailSource->hotel_id;

        // ── 2. Collect all non-empty body candidates in priority order ───────
        // stripped-text is tried first (quoted replies removed), then body-plain
        // (full text), then HTML stripped of tags.  All non-empty values are kept
        // so the parser can fall back if the first candidate does not parse.
        $bodyCandidates = [];
        foreach (['stripped-text', 'body-plain'] as $field) {
            $val = trim($request->input($field, ''));
            if ($val !== '') $bodyCandidates[] = $val;
        }
        $htmlStripped = trim(strip_tags($request->input('body-html', '')));
        if ($htmlStripped !== '') $bodyCandidates[] = $htmlStripped;

        // Deduplicate while preserving order (body-plain often equals stripped-text)
        $bodyCandidates = array_values(array_unique($bodyCandidates));

        if (empty($bodyCandidates)) {
            Log::warning('OtaEmailWebhook: empty body for hotel #' . $hotelId);
            return response('ok', 200);
        }

        // ── 3. Detect OTA ─────────────────────────────────────────────────────
        // Use multiple signals in priority order so forwarded emails are handled correctly.
        // When a hotel admin forwards a booking email, the Mailgun "sender" will be the
        // admin's email — the OTA is identified via the original From header, subject line,
        // or "From:" line embedded in the forwarded body.
        $subject     = $request->input('subject', '');
        $fromHeader  = $request->input('From', '') ?: $request->input('from', '');
        $senderEmail = strtolower(trim($request->input('sender', '')));

        // Use the longest body candidate for OTA detection (most context)
        $longestBody = collect($bodyCandidates)->sortByDesc('strlen')->first() ?? '';
        $otaSource   = $this->detectOtaSource($fromHeader, $senderEmail, $subject, $longestBody);

        // ── 4. Run parser (with body-candidate fallback) ──────────────────────
        try {
            (new OtaBookingParserService())->handleFromEmail(
                senderEmail:    $senderEmail ?: $fromHeader,
                subject:        $subject,
                bodyCandidates: $bodyCandidates,
                otaSource:      $otaSource,
                hotelId:        $hotelId
            );
        } catch (\Throwable $e) {
            Log::error('OtaEmailWebhook: parser error — ' . $e->getMessage(), [
                'hotel_id' => $hotelId,
                'sender'   => $senderEmail,
                'trace'    => $e->getTraceAsString(),
            ]);
        }

        return response('ok', 200);
    }

    // ── Signature verification ────────────────────────────────────────────────

    /**
     * Verify the Mailgun webhook signature using HMAC-SHA256.
     *
     * Mailgun sends: timestamp, token, signature.
     * Expected: HMAC-SHA256(timestamp . token, MAILGUN_WEBHOOK_SIGNING_KEY) == signature
     *
     * If MAILGUN_WEBHOOK_SIGNING_KEY is not configured, verification is skipped with a warning
     * so the webhook still works during initial setup. In production the key MUST be set.
     *
     * Also rejects replays: timestamp must be within ±5 minutes of current time.
     */
    private function verifyMailgunSignature(Request $request): bool
    {
        $signingKey = env('MAILGUN_WEBHOOK_SIGNING_KEY');

        if (!$signingKey) {
            // In production, fail closed — a missing key means the endpoint is unprotected.
            // In local/dev environments it is acceptable to skip verification for testing.
            if (app()->environment('production')) {
                Log::error('OtaEmailWebhook: MAILGUN_WEBHOOK_SIGNING_KEY not set in production — request rejected. Set this secret in Replit production secrets.');
                return false;
            }
            Log::warning('OtaEmailWebhook: MAILGUN_WEBHOOK_SIGNING_KEY not set — skipping signature verification in non-production environment. Set this secret before going live.');
            return true;
        }

        $timestamp = $request->input('timestamp', '');
        $token     = $request->input('token', '');
        $signature = $request->input('signature', '');

        if (!$timestamp || !$token || !$signature) {
            return false;
        }

        // Replay-protection: reject requests older or newer than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('OtaEmailWebhook: timestamp out of window (possible replay)');
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $token, $signingKey);

        return hash_equals($expected, $signature);
    }

    // ── OTA detection ─────────────────────────────────────────────────────────

    /**
     * Detect the best-matching OtaSource for an inbound (or forwarded) email.
     *
     * Checks signals in this priority order:
     *   1. Original "From" header domain (set even in forwarded emails if Mailgun preserves headers).
     *   2. Subject line keyword matching (e.g. "booking.com", "airbnb").
     *   3. Forwarded-body "From:" line — the original sender embedded in quoted text.
     *   4. Mailgun envelope sender domain (direct OTA → webhook, not forwarded).
     *   5. Content-pattern generic match (generic OTA format with Property: / Booking Ref:).
     *   6. Any active generic source (catch-all).
     *   7. Transient fallback object.
     */
    private function detectOtaSource(string $fromHeader, string $senderEmail, string $subject, string $body): OtaSource
    {
        $domainMap = [
            'booking.com'         => 'booking_com',
            'guest.booking.com'   => 'booking_com',
            'noreply.booking.com' => 'booking_com',
            'reply.booking.com'   => 'booking_com',
            'airbnb.com'          => 'airbnb',
            'airbnb.co.uk'        => 'airbnb',
            'agoda.com'           => 'agoda',
            'agoda-en.com'        => 'agoda',
            'makemytrip.com'      => 'makemytrip',
            'goibibo.com'         => 'goibibo',
            'expedia.com'         => 'expedia',
            'hotels.com'          => 'expedia',
        ];

        // Subject keyword → pattern key mapping (for forwarded emails where subject says "Booking.com reservation")
        $subjectKeywords = [
            'booking.com'  => 'booking_com',
            'booking . com'=> 'booking_com',
            'airbnb'       => 'airbnb',
            'agoda'        => 'agoda',
            'makemytrip'   => 'makemytrip',
            'goibibo'      => 'goibibo',
            'expedia'      => 'expedia',
            'hotels.com'   => 'expedia',
        ];

        // ── Signal 1: original From header domain ─────────────────────────────
        $patternKey = $this->patternKeyFromEmail($fromHeader, $domainMap);

        // ── Signal 2: subject line keywords ──────────────────────────────────
        if (!$patternKey) {
            $subjectLower = strtolower($subject);
            foreach ($subjectKeywords as $keyword => $key) {
                if (str_contains($subjectLower, $keyword)) {
                    $patternKey = $key;
                    break;
                }
            }
        }

        // ── Signal 3: "From:" line embedded in forwarded body ─────────────────
        if (!$patternKey) {
            // Match "From: ... @booking.com" or "From: Booking.com <...>" inside quoted body
            if (preg_match('/^From:\s*[^\n]*?([\w.\-]+@[\w.\-]+)/im', $body, $m)) {
                $patternKey = $this->patternKeyFromEmail($m[1], $domainMap);
            }
        }

        // ── Signal 4: Mailgun envelope sender domain ──────────────────────────
        if (!$patternKey) {
            $patternKey = $this->patternKeyFromEmail($senderEmail, $domainMap);
        }

        // ── Resolve OtaSource from pattern key ───────────────────────────────
        if ($patternKey) {
            $source = OtaSource::where('is_active', true)
                ->where('message_pattern_key', $patternKey)
                ->orderBy('id')
                ->first();
            if ($source) return $source;
        }

        // ── Signal 5: generic content pattern ────────────────────────────────
        $source = OtaSource::findByContentPattern($body);
        if ($source) return $source;

        // ── Signal 6: any active generic source ──────────────────────────────
        $source = OtaSource::where('is_active', true)
            ->where('message_pattern_key', 'generic')
            ->orderBy('id')
            ->first();
        if ($source) return $source;

        // ── Signal 7: transient fallback ─────────────────────────────────────
        $fallback                      = new OtaSource();
        $fallback->name                = 'Email (Unknown OTA)';
        $fallback->message_pattern_key = 'generic';
        $fallback->is_active           = true;
        return $fallback;
    }

    /**
     * Extract the OTA pattern key from an email address (or partial address string)
     * using the given domain → pattern-key map.
     * Returns null if no known OTA domain is found.
     */
    private function patternKeyFromEmail(string $emailOrHeader, array $domainMap): ?string
    {
        // Accept full email "Some Name <user@domain.com>" or plain "user@domain.com"
        if (!preg_match('/@([\w.\-]+)/i', $emailOrHeader, $m)) {
            return null;
        }
        $domain = strtolower(trim($m[1]));

        foreach ($domainMap as $knownDomain => $key) {
            if ($domain === $knownDomain || str_ends_with($domain, '.' . $knownDomain)) {
                return $key;
            }
        }
        return null;
    }
}
