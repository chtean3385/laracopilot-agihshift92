<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
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

        return [
            'guest_name'           => $customer->name ?? '',
            'hotel_name'           => $hotelName,
            'room_number'          => $room->room_number ?? '',
            'room_type'            => ucfirst($room->type ?? ''),
            'check_in_date'        => $booking->check_in_date ? $booking->check_in_date->format('d M Y') : '',
            'check_out_date'       => $booking->check_out_date ? $booking->check_out_date->format('d M Y') : '',
            'booking_number'       => $booking->booking_number ?? '',
            'total_amount'         => '₹' . number_format($booking->total_amount ?? 0),
            'balance_due'          => '₹' . number_format($booking->balance_due ?? 0),
            'invoice_number'       => $invoice->invoice_number ?? '',
            'amount_paid'          => $lastPayment ? '₹' . number_format($lastPayment->amount ?? 0) : '₹0',
            'payment_method'       => $lastPayment ? ucfirst(str_replace('_', ' ', $lastPayment->payment_method ?? '')) : '',
            'nights'               => (string) ($booking->nights ?? ''),
            'adults'               => (string) ($booking->adults ?? ''),
            'hotel_contact_number' => $settings->contact_number ?? '',
            'hotel_location'       => $settings->hotel_location ?? '',
            'payment_link'         => '',
        ];
    }

    public static function build(string $template, Booking $booking): string
    {
        $vars = self::buildVars($booking);
        $search  = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, $template);
    }
}
