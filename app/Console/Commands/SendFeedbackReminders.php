<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

class SendFeedbackReminders extends Command
{
    protected $signature   = 'whatsapp:feedback-reminders';
    protected $description = 'Send WhatsApp feedback requests to guests who checked out 2 days ago';

    public function handle(): void
    {
        $twoDaysAgo = now()->subDays(2)->toDateString();

        $bookings = Booking::with(['customer', 'room', 'invoice'])
            ->where('status', 'checked_out')
            ->whereDate('actual_checkout_at', $twoDaysAgo)
            ->get();

        $this->info("Found {$bookings->count()} booking(s) checked out 2 days ago.");

        foreach ($bookings as $booking) {
            $sent = WhatsAppService::sendForEvent('feedback.request', $booking);
            $this->line(($sent ? '✓' : '✗') . ' ' . ($booking->customer->name ?? 'Unknown') . ' — ' . $booking->booking_number);
        }

        $this->info('Done.');
    }
}
