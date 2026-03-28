<?php

namespace App\Services\WhatsApp;

use App\Models\Booking;
use App\Models\Setting;

class MessageBuilder
{
    public static function build(string $template, Booking $booking): string
    {
        $settings   = Setting::first();
        $hotelName  = $settings->resort_name ?? config('app.name');
        $customer   = $booking->customer;
        $room       = $booking->room;
        $invoice    = $booking->invoice;

        $lastPayment = $booking->payments()->where('status', 'completed')->latest()->first();

        $vars = [
            '{{guest_name}}'     => $customer->name ?? '',
            '{{hotel_name}}'     => $hotelName,
            '{{room_number}}'    => $room->room_number ?? '',
            '{{room_type}}'      => ucfirst($room->type ?? ''),
            '{{check_in_date}}'  => $booking->check_in_date ? $booking->check_in_date->format('d M Y') : '',
            '{{check_out_date}}' => $booking->check_out_date ? $booking->check_out_date->format('d M Y') : '',
            '{{booking_number}}' => $booking->booking_number ?? '',
            '{{total_amount}}'   => '₹' . number_format($booking->total_amount ?? 0),
            '{{balance_due}}'    => '₹' . number_format($booking->balance_due ?? 0),
            '{{invoice_number}}' => $invoice->invoice_number ?? '',
            '{{amount_paid}}'    => $lastPayment ? '₹' . number_format($lastPayment->amount ?? 0) : '₹0',
            '{{payment_method}}' => $lastPayment ? ucfirst(str_replace('_', ' ', $lastPayment->payment_method ?? '')) : '',
            '{{nights}}'         => $booking->nights ?? '',
            '{{adults}}'         => $booking->adults ?? '',
            '{{payment_link}}'   => '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $template);
    }
}
