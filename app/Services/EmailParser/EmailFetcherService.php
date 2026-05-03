<?php

namespace App\Services\EmailParser;

use App\Models\HotelEmailConfig;
use App\Models\ParsedEmail;
use Illuminate\Support\Facades\Log;

class EmailFetcherService
{
    /**
     * Connect to the IMAP server and return the connection resource.
     * Throws on failure with a friendly message (especially Gmail App Password hints).
     *
     * @return resource
     */
    public function connect(HotelEmailConfig $config)
    {
        if (!function_exists('imap_open')) {
            throw new \RuntimeException('PHP imap extension is not installed on the server.');
        }

        $this->assertSafeHost((string) $config->imap_host);

        $encryption = strtolower($config->encryption ?: 'ssl');
        if ($encryption === 'ssl') {
            $flags = '/imap/ssl';
        } elseif ($encryption === 'tls') {
            $flags = '/imap/tls';
        } else {
            $flags = '/imap/notls';
        }

        $folder    = $config->folder_to_watch ?: 'INBOX';
        $mailbox   = '{' . $config->imap_host . ':' . (int) $config->imap_port . $flags . '}' . $folder;

        // Suppress imap_open warnings; capture last error from imap_errors().
        $conn = @imap_open(
            $mailbox,
            $config->email_address,
            $config->getDecryptedPassword(),
            0,
            1
        );

        if (!$conn) {
            $err = imap_last_error() ?: 'Unknown IMAP error';
            // Friendly Gmail hint
            if (str_contains(strtolower($config->imap_host), 'gmail')
                && (stripos($err, 'authenticationfailed') !== false
                    || stripos($err, 'invalid credentials') !== false
                    || stripos($err, 'application-specific') !== false)
            ) {
                $err .= ' — Gmail requires an App Password (16 chars, no spaces). '
                      . 'Generate one at Google Account → Security → 2-Step Verification → App Passwords.';
            }
            throw new \RuntimeException($err);
        }

        return $conn;
    }

    /**
     * Fetch unseen emails for the hotel and create pending parsed_emails rows
     * (one per UID). Returns the number of newly-stored emails.
     */
    public function fetchAndStore(HotelEmailConfig $config): int
    {
        $hotelId = (int) $config->hotel_id;
        $stored  = 0;

        $conn = $this->connect($config);
        try {
            $uids = imap_search($conn, 'UNSEEN', SE_UID) ?: [];

            foreach ($uids as $uid) {
                $messageUid = (string) $uid;

                // DB-level guard
                $exists = ParsedEmail::withoutGlobalScopes()
                    ->where('hotel_id', $hotelId)
                    ->where('message_uid', $messageUid)
                    ->exists();
                if ($exists) continue;

                $headerInfo = @imap_rfc822_parse_headers(
                    @imap_fetchheader($conn, $uid, FT_UID) ?: ''
                );

                $subject = $headerInfo->subject ?? '';
                $subject = $this->decodeMime($subject);

                $sender = '';
                if (!empty($headerInfo->from[0])) {
                    $f      = $headerInfo->from[0];
                    $sender = ($f->mailbox ?? '') . '@' . ($f->host ?? '');
                }

                $body = $this->fetchBody($conn, $uid);

                try {
                    ParsedEmail::create([
                        'hotel_id'    => $hotelId,
                        'message_uid' => $messageUid,
                        'subject'     => mb_substr($subject ?? '', 0, 250),
                        'sender'      => mb_substr($sender, 0, 250),
                        'raw_body'    => $body,
                        'status'      => 'pending',
                    ]);
                    $stored++;
                } catch (\Throwable $e) {
                    // Unique-key race — skip silently
                    Log::info('EmailFetcher: skipped duplicate UID ' . $messageUid . ' — ' . $e->getMessage());
                    continue;
                }

                // Mark as Seen so it isn't refetched next cycle.
                @imap_setflag_full($conn, (string) $uid, "\\Seen", ST_UID);
            }

            $config->update(['last_synced_at' => now()]);

        } finally {
            @imap_close($conn);
        }

        return $stored;
    }

    /**
     * Try to fetch the plain text body, then HTML stripped, then the raw body.
     */
    private function fetchBody($conn, int $uid): string
    {
        $structure = @imap_fetchstructure($conn, $uid, FT_UID);

        if ($structure && !empty($structure->parts)) {
            // Multipart: prefer text/plain, fall back to text/html.
            $plain = $this->extractPart($conn, $uid, $structure, 'PLAIN');
            if ($plain !== null && trim($plain) !== '') return $this->normaliseBody($plain);

            $html = $this->extractPart($conn, $uid, $structure, 'HTML');
            if ($html !== null && trim($html) !== '') {
                return $this->normaliseBody(strip_tags($html));
            }
        }

        // Simple body
        $body = @imap_body($conn, $uid, FT_UID);
        if (!$body) return '';

        $encoding = $structure->encoding ?? 0;
        $body     = $this->decodeBody($body, $encoding);
        return $this->normaliseBody($body);
    }

    private function extractPart($conn, int $uid, $structure, string $wantSubtype, string $section = ''): ?string
    {
        if (!isset($structure->parts) || !is_array($structure->parts)) {
            return null;
        }
        foreach ($structure->parts as $i => $part) {
            $thisSection = $section === '' ? (string) ($i + 1) : $section . '.' . ($i + 1);

            if (!empty($part->parts)) {
                $sub = $this->extractPart($conn, $uid, $part, $wantSubtype, $thisSection);
                if ($sub !== null) return $sub;
                continue;
            }

            $subtype = strtoupper($part->subtype ?? '');
            if ($subtype === $wantSubtype) {
                $raw = @imap_fetchbody($conn, $uid, $thisSection, FT_UID);
                if (!$raw) return '';
                return $this->decodeBody($raw, $part->encoding ?? 0);
            }
        }
        return null;
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ((int) $encoding) {
            3       => base64_decode($body) ?: $body,                 // BASE64
            4       => quoted_printable_decode($body),                // QUOTED-PRINTABLE
            default => $body,
        };
    }

    private function normaliseBody(string $body): string
    {
        // Convert CRLF and trim whitespace.
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        return trim($body);
    }

    /**
     * Reject IMAP hosts that resolve to localhost, private, link-local, or other
     * non-routable ranges to prevent the configured tenant from probing internal
     * services through the scheduled IMAP fetcher.
     */
    private function assertSafeHost(string $host): void
    {
        $host = trim($host);
        if ($host === '') {
            throw new \RuntimeException('IMAP host is required.');
        }

        $forbiddenNames = ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'];
        if (in_array(strtolower($host), $forbiddenNames, true)) {
            throw new \RuntimeException('IMAP host "' . $host . '" is not allowed.');
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
            foreach ($records as $r) {
                if (!empty($r['ip']))   $ips[] = $r['ip'];
                if (!empty($r['ipv6'])) $ips[] = $r['ipv6'];
            }
            if (empty($ips)) {
                $resolved = @gethostbynamel($host);
                if (is_array($resolved)) $ips = $resolved;
            }
        }

        if (empty($ips)) {
            throw new \RuntimeException('Unable to resolve IMAP host "' . $host . '".');
        }

        foreach ($ips as $ip) {
            if (!filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                throw new \RuntimeException(
                    'IMAP host "' . $host . '" resolves to a non-routable address (' . $ip . ').'
                );
            }
        }
    }

    private function decodeMime(?string $subject): string
    {
        if (!$subject) return '';
        $parts = imap_mime_header_decode($subject);
        $out = '';
        foreach ($parts as $p) {
            $charset = ($p->charset ?? 'default');
            $text    = $p->text ?? '';
            if ($charset && strtolower($charset) !== 'default' && strtolower($charset) !== 'utf-8') {
                $conv = @iconv($charset, 'UTF-8//IGNORE', $text);
                $out .= $conv === false ? $text : $conv;
            } else {
                $out .= $text;
            }
        }
        return $out;
    }
}
