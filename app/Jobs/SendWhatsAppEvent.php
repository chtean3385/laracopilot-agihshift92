<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\HotelContext;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    public function __construct(
        public readonly string $event,
        public readonly int    $bookingId,
        public readonly int    $hotelId,
    ) {}

    public function handle(): void
    {
        // Restore hotel context so Module::isEnabled() and WhatsAppConfig::active()
        // resolve correctly — there is no HTTP session in a queue worker.
        app(HotelContext::class)->setHotel($this->hotelId);

        $booking = Booking::with(['customer', 'room', 'payments'])->find($this->bookingId);

        if (!$booking) {
            Log::warning("SendWhatsAppEvent: booking #{$this->bookingId} not found, skipping.");
            return;
        }

        if ($this->event === 'owner.alert') {
            WhatsAppService::sendOwnerAlert($booking);
        } else {
            WhatsAppService::sendForEvent($this->event, $booking);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendWhatsAppEvent permanently failed for booking #{$this->bookingId} / event {$this->event}: " . $exception->getMessage());
    }
}
