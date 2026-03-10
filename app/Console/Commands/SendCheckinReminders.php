<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

class SendCheckinReminders extends Command
{
    protected $signature   = 'whatsapp:checkin-reminders';
    protected $description = 'Send WhatsApp check-in reminders to guests checking in tomorrow';

    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::with(['customer', 'room', 'invoice'])
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', $tomorrow)
            ->get();

        $this->info("Found {$bookings->count()} booking(s) checking in tomorrow.");

        foreach ($bookings as $booking) {
            $sent = WhatsAppService::sendForEvent('checkin.tomorrow', $booking);
            $this->line(($sent ? '✓' : '✗') . ' ' . ($booking->customer->name ?? 'Unknown') . ' — ' . $booking->booking_number);
        }

        $this->info('Done.');
    }
}
