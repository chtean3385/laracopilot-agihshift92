<?php

namespace App\Services\EmailParser;

class EmailParserService
{
    /**
     * Detect which OTA parser matches and extract structured fields.
     * Returns: ['ota_key' => 'booking_com', 'ota_label' => 'Booking.com', 'data' => [...]]
     * or null when no parser matches OR no fields are extracted.
     */
    public function parse(string $sender, string $subject, string $body): ?array
    {
        $parsers = config('email_parsers.parsers', []);
        if (!$parsers) return null;

        $senderLc  = strtolower($sender);
        $subjectLc = strtolower($subject);

        $matchedKey = null;
        foreach ($parsers as $key => $def) {
            if ($this->subjectOrFromMatches($subjectLc, $senderLc, $def)) {
                $matchedKey = $key;
                break;
            }
        }

        if (!$matchedKey) return null;

        $def    = $parsers[$matchedKey];
        $fields = $this->runRegexes($body, $def['regexes'] ?? []);

        if (empty($fields)) return null;

        // Normalise dates
        if (!empty($fields['check_in']))  $fields['check_in']  = $this->normaliseDate($fields['check_in']);
        if (!empty($fields['check_out'])) $fields['check_out'] = $this->normaliseDate($fields['check_out']);

        // Normalise amount
        if (!empty($fields['amount'])) {
            $fields['amount'] = (float) str_replace(',', '', $fields['amount']);
        }

        // Normalise phone
        if (!empty($fields['guest_phone'])) {
            $fields['guest_phone'] = preg_replace('/[^0-9+]/', '', $fields['guest_phone']);
        }

        return [
            'ota_key'   => $matchedKey,
            'ota_label' => $def['label'] ?? ucfirst(str_replace('_', '.', $matchedKey)),
            'data'      => $fields,
        ];
    }

    private function subjectOrFromMatches(string $subjectLc, string $senderLc, array $def): bool
    {
        foreach ($def['subject_contains'] ?? [] as $needle) {
            if ($needle && str_contains($subjectLc, strtolower($needle))) return true;
        }
        foreach ($def['from_contains'] ?? [] as $needle) {
            if ($needle && str_contains($senderLc, strtolower($needle))) return true;
        }
        return false;
    }

    private function runRegexes(string $body, array $regexes): array
    {
        $result = [];
        foreach ($regexes as $field => $pattern) {
            try {
                if (preg_match($pattern, $body, $m) && isset($m[1])) {
                    $result[$field] = trim($m[1]);
                }
            } catch (\Throwable $e) {
                // Skip malformed pattern, continue with the others.
            }
        }
        return $result;
    }

    private function normaliseDate(string $str): ?string
    {
        $str = trim($str);
        if (!$str) return null;

        foreach ([
            'd M Y', 'd F Y', 'Y-m-d', 'd/m/Y', 'm/d/Y',
            'd-m-Y', 'D, d M Y', 'j M Y', 'j F Y',
            'M d, Y', 'F d, Y',
        ] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $str);
            if ($d) return $d->format('Y-m-d');
        }

        try {
            return (new \DateTime($str))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
