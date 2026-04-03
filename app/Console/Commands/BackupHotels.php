<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\HotelBackup;
use App\Models\HotelBackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupHotels extends Command
{
    protected $signature = 'hotels:backup {hotel_id? : Specific hotel ID to backup, or omit for all due hotels}';
    protected $description = 'Create data backups for hotels (auto or specific)';

    public function handle(): int
    {
        $hotelId = $this->argument('hotel_id');

        if ($hotelId) {
            $hotel = Hotel::find($hotelId);
            if (!$hotel) {
                $this->error("Hotel #{$hotelId} not found.");
                return 1;
            }
            $this->backupHotel($hotel, 'manual');
            $this->info("Backup created for hotel: {$hotel->name}");
            return 0;
        }

        $settings = HotelBackupSetting::where('auto_backup_enabled', true)->get();

        $count = 0;
        foreach ($settings as $setting) {
            $dueAt = $setting->last_backup_at
                ? $setting->last_backup_at->addHours($setting->interval_hours)
                : now()->subMinute();

            if (now()->greaterThanOrEqualTo($dueAt)) {
                $hotel = Hotel::find($setting->hotel_id);
                if ($hotel) {
                    $this->backupHotel($hotel, 'auto');
                    $this->info("Auto-backup: {$hotel->name}");
                    $count++;
                }
            }
        }

        $this->info("Auto-backup run complete. {$count} hotel(s) backed up.");
        return 0;
    }

    public static function createBackup(Hotel $hotel, string $type = 'manual', ?int $createdBy = null): HotelBackup
    {
        $data = [
            'meta' => [
                'hotel_id'    => $hotel->id,
                'hotel_name'  => $hotel->name,
                'backup_type' => $type,
                'backed_up_at'=> now()->toIso8601String(),
                'version'     => '1.0',
            ],
            'hotel'     => $hotel->toArray(),
            'settings'  => DB::table('settings')->where('hotel_id', $hotel->id)->get()->toArray(),
            'rooms'     => DB::table('rooms')->where('hotel_id', $hotel->id)->get()->toArray(),
            'customers' => DB::table('customers')->where('hotel_id', $hotel->id)->get()->toArray(),
            'bookings'  => DB::table('bookings')->where('hotel_id', $hotel->id)->get()->toArray(),
            'payments'  => DB::table('payments')
                ->whereIn('booking_id', DB::table('bookings')->where('hotel_id', $hotel->id)->pluck('id'))
                ->get()->toArray(),
            'invoices'  => DB::table('invoices')
                ->whereIn('booking_id', DB::table('bookings')->where('hotel_id', $hotel->id)->pluck('id'))
                ->get()->toArray(),
        ];

        $json   = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sizeKb = (int) ceil(strlen($json) / 1024);

        $backup = HotelBackup::create([
            'hotel_id'    => $hotel->id,
            'backup_data' => $json,
            'type'        => $type,
            'created_by'  => $createdBy,
            'size_kb'     => $sizeKb,
            'label'       => now()->format('d M Y, h:i A') . ' — ' . ucfirst($type),
        ]);

        $setting = HotelBackupSetting::firstOrCreate(
            ['hotel_id' => $hotel->id],
            ['interval_hours' => 24, 'retention_count' => 10]
        );
        $setting->update(['last_backup_at' => now()]);

        $retention = $setting->retention_count ?? 10;
        $allIds = HotelBackup::where('hotel_id', $hotel->id)
            ->orderByDesc('created_at')
            ->pluck('id');
        $oldIds = $allIds->slice($retention);
        if ($oldIds->isNotEmpty()) {
            HotelBackup::whereIn('id', $oldIds)->delete();
        }

        return $backup;
    }

    private function backupHotel(Hotel $hotel, string $type): void
    {
        self::createBackup($hotel, $type, null);
    }
}
