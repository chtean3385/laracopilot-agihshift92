<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalize a phone number for storage.
     *
     * Rules (in order):
     *  1. Strip every non-digit character (+, spaces, dashes, parentheses …)
     *  2. If the result is 12 digits AND starts with "91"  → strip the leading 91
     *     (handles  +918460765785  /  918460765785  →  8460765785)
     *  3. Otherwise keep the digits as-is.
     *     - 10-digit Indian mobile      → stored as 10 digits
     *     - 11-digit UK (07911…)        → stored as-is
     *     - 12-digit international (447…) → stored as-is
     */
    public static function normalize(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Return a WhatsApp-ready number (no + sign, country code included).
     *
     *  - 10-digit number     → prepend "91"  (Indian mobile)
     *  - Anything else       → use as-is     (already has country code)
     */
    public static function forWhatsApp(?string $phone): string
    {
        $n = static::normalize($phone);

        if ($n === '') {
            return '';
        }

        if (strlen($n) === 10) {
            return '91' . $n;
        }

        return $n;
    }

    /**
     * Quick check: is this a valid Indian 10-digit mobile (after normalization)?
     */
    public static function isIndianMobile(?string $phone): bool
    {
        return strlen(static::normalize($phone)) === 10;
    }
}
