<?php

namespace App\Console\Commands;

use App\Models\HotelEmailConfig;
use App\Models\Module;
use App\Services\EmailParser\BookingSyncService;
use App\Services\EmailParser\EmailFetcherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EmailsSync extends Command
{
    protected $signature   = 'emails:sync';
    protected $description = 'Fetch unseen OTA confirmation emails (IMAP) and create CRM bookings.';

    public function handle(EmailFetcherService $fetcher, BookingSyncService $sync): int
    {
        $configs = HotelEmailConfig::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->get();

        $totalFetched   = 0;
        $totalProcessed = 0;
        $hotelsRun      = 0;

        foreach ($configs as $config) {
            $hotelId = (int) $config->hotel_id;

            if (!Module::isEnabledForHotel('email-parser', $hotelId)) {
                continue;
            }

            $hotelsRun++;

            try {
                $fetched = $fetcher->fetchAndStore($config);
                $totalFetched += $fetched;
                $this->line("Hotel #{$hotelId}: fetched {$fetched} new email(s).");
            } catch (\Throwable $e) {
                Log::warning('emails:sync — fetch failed for hotel #' . $hotelId . ' — ' . $e->getMessage());
                $this->warn("Hotel #{$hotelId}: fetch error — " . $e->getMessage());
                continue;
            }

            try {
                $counts = $sync->processPendingForHotel($hotelId);
                $totalProcessed += ($counts['processed'] ?? 0);
                $this->line("Hotel #{$hotelId}: processed=" . ($counts['processed'] ?? 0)
                    . ' duplicate=' . ($counts['duplicate'] ?? 0)
                    . ' failed=' . ($counts['failed'] ?? 0));
            } catch (\Throwable $e) {
                Log::error('emails:sync — sync failed for hotel #' . $hotelId . ' — ' . $e->getMessage());
                $this->warn("Hotel #{$hotelId}: sync error — " . $e->getMessage());
            }
        }

        $this->info("Done. hotels={$hotelsRun} fetched={$totalFetched} processed={$totalProcessed}");
        return self::SUCCESS;
    }
}
