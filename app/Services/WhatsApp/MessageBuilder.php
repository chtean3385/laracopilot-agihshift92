<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Setting;

class MessageBuilder
{
    public static function buildVars(Booking $booking): array
    {
        $settings    = Setting::first();
        $hotelName   = $settings->resort_name ?? config('app.name');
        $customer    = $booking->customer;
        $room        = $booking->room;
        $invoice     = $booking->invoice;
        $lastPayment = $booking->payments()->where('status', 'completed')->latest()->first();

        // Compute GST-inclusive totals for WhatsApp messages
        $taxRate    = ($settings && !empty($settings->gst_number) && ($settings->tax_rate ?? 0) > 0)
                        ? (float) $settings->tax_rate : 0;
        $baseAmt    = (float) ($booking->total_amount ?? 0);
        $gstAmt     = round($baseAmt * ($taxRate / 100), 2);
        $grandTotal = $baseAmt + $gstAmt;
        $advPaid    = (float) ($booking->advance_payment ?? 0);
        $balanceDue = max(0, $grandTotal - $advPaid);

        // hotel_contact_number: prefer settings, fall back to hotel.phone
        $hotel = Hotel::withoutGlobalScopes()->find($booking->hotel_id);
        $contactNumber = $settings->contact_number
            ?? $hotel?->phone
            ?? '';

        return [
            'guest_name'           => $customer->name ?? '',
            'hotel_name'           => $hotelName,
            'room_number'          => $room->room_number ?? '',
            'room_type'            => ucfirst($room->type ?? ''),
            'check_in_date'        => $booking->check_in_date ? $booking->check_in_date->format('d M Y') : '',
            'check_out_date'       => $booking->check_out_date ? $booking->check_out_date->format('d M Y') : '',
            'booking_number'       => $booking->booking_number ?? '',
            'total_amount'         => '₹' . number_format($grandTotal) . ($taxRate > 0 ? ' (incl. ' . $taxRate . '% GST)' : ''),
            'balance_due'          => '₹' . number_format($balanceDue),
            'invoice_number'       => $invoice->invoice_number ?? '',
            'amount_paid'          => $lastPayment ? '₹' . number_format($lastPayment->amount ?? 0) : '₹0',
            'payment_method'       => $lastPayment ? ucfirst(str_replace('_', ' ', $lastPayment->payment_method ?? '')) : '',
            'nights'               => (string) ($booking->nights ?? ''),
            'adults'               => (string) ($booking->adults ?? ''),
            'hotel_contact_number' => $contactNumber,
            'hotel_location'       => $settings->hotel_location ?? '',
            'payment_link'         => '',
            'guest_checkin_link'   => self::buildCheckinLink($booking),
        ];
    }

    public static function buildCheckinLink(Booking $booking): string
    {
        $hotel = Hotel::withoutGlobalScopes()->find($booking->hotel_id);
        if (!$hotel || empty($hotel->slug)) {
            return '';
        }

        // Build a publicly accessible URL.
        // Production: APP_URL secret = https://resort.dreamstechnology.in (set in Replit secrets).
        // Dev (Replit): APP_URL may still be localhost/0.0.0.0 — fall back to REPLIT_DEV_DOMAIN.
        // Use getenv() as the primary read (works in queue workers + CLI, not just web requests).
        $base = config('app.url', '');
        if (empty($base) || str_contains($base, 'localhost') || str_contains($base, '0.0.0.0')) {
            $replitDomain = getenv('REPLIT_DEV_DOMAIN') ?: env('REPLIT_DEV_DOMAIN', '');
            if ($replitDomain) {
                $base = 'https://' . ltrim($replitDomain, 'https://');
            }
        }
        $base = rtrim($base, '/');

        // Append booking number so the guest form links back to this specific booking.
        return $base . '/g/checkin/' . $hotel->slug . '?ref=' . urlencode($booking->booking_number);
    }

    public static function build(string $template, Booking $booking): string
    {
        $vars = self::buildVars($booking);
        $search  = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, $template);
    }
}
