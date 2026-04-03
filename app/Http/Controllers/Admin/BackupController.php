<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\BackupHotels;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelBackup;
use App\Models\HotelBackupSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    private function hotelId(): ?int
    {
        return session('crm_hotel_id');
    }

    private function guard()
    {
        if (!session('crm_logged_in')) {
            abort(redirect()->route('login'));
        }
    }

    public function index()
    {
        $this->guard();
        $hotelId = $this->hotelId();
        $hotel   = Hotel::findOrFail($hotelId);

        $setting = HotelBackupSetting::firstOrCreate(
            ['hotel_id' => $hotelId],
            ['auto_backup_enabled' => false, 'interval_hours' => 24, 'retention_count' => 10]
        );

        $backups = HotelBackup::where('hotel_id', $hotelId)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.settings.backup', compact('setting', 'backups', 'hotel'));
    }

    public function saveSettings(Request $request)
    {
        $this->guard();
        $hotelId = $this->hotelId();

        $data = $request->validate([
            'auto_backup_enabled' => 'nullable|boolean',
            'interval_hours'      => 'required|integer|in:6,12,24,48,72,168',
            'retention_count'     => 'required|integer|min:3|max:30',
        ]);

        $data['auto_backup_enabled'] = $request->boolean('auto_backup_enabled');

        HotelBackupSetting::updateOrCreate(
            ['hotel_id' => $hotelId],
            $data
        );

        ActivityLogger::log('Updated', 'Backup Settings', 'Backup settings updated');

        return redirect()->route('settings.backup')->with('success', 'Backup settings saved.');
    }

    public function store(Request $request)
    {
        $this->guard();
        $hotelId = $this->hotelId();
        $hotel   = Hotel::findOrFail($hotelId);

        $backup = BackupHotels::createBackup($hotel, 'manual', session('crm_user_id'));

        ActivityLogger::log('Created', 'Backup', "Manual backup created ({$backup->size_kb} KB)");

        return redirect()->route('settings.backup')
            ->with('success', "Backup created successfully ({$backup->size_kb} KB).");
    }

    public function destroy(int $id)
    {
        $this->guard();
        $hotelId = $this->hotelId();

        $backup = HotelBackup::where('id', $id)->where('hotel_id', $hotelId)->firstOrFail();
        $backup->delete();

        ActivityLogger::log('Deleted', 'Backup', "Backup #{$id} deleted");

        return redirect()->route('settings.backup')->with('success', 'Backup deleted.');
    }
}
