<?php

namespace App\Http\Controllers\Platform;

use App\Console\Commands\BackupHotels;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelBackup;
use App\Models\HotelBackupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $query = HotelBackup::with('hotel')
            ->orderByDesc('created_at');

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        $backups = $query->paginate(30)->withQueryString();
        $hotels  = Hotel::orderBy('name')->get(['id', 'name']);

        return view('platform.backups.index', compact('backups', 'hotels'));
    }

    public function restore(int $id)
    {
        $backup = HotelBackup::findOrFail($id);
        $data   = json_decode($backup->backup_data, true);

        if (!$data || !isset($data['hotel'])) {
            return back()->withErrors(['error' => 'Backup data is corrupt or unreadable.']);
        }

        DB::transaction(function () use ($data, $backup) {
            $hotelId = $backup->hotel_id;

            if (!empty($data['settings'])) {
                DB::table('settings')->where('hotel_id', $hotelId)->delete();
                foreach ($data['settings'] as $row) {
                    $row = (array) $row;
                    unset($row['id']);
                    $row['hotel_id'] = $hotelId;
                    DB::table('settings')->insert($row);
                }
            }

            if (!empty($data['rooms'])) {
                DB::table('rooms')->where('hotel_id', $hotelId)->delete();
                foreach ($data['rooms'] as $row) {
                    $row = (array) $row;
                    unset($row['id']);
                    $row['hotel_id'] = $hotelId;
                    DB::table('rooms')->insert($row);
                }
            }

            if (!empty($data['customers'])) {
                DB::table('customers')->where('hotel_id', $hotelId)->delete();
                foreach ($data['customers'] as $row) {
                    $row = (array) $row;
                    unset($row['id']);
                    $row['hotel_id'] = $hotelId;
                    DB::table('customers')->insert($row);
                }
            }

            if (!empty($data['bookings'])) {
                $bookingIdMap = [];
                $oldBookings = $data['bookings'];
                DB::table('bookings')->where('hotel_id', $hotelId)->delete();
                foreach ($oldBookings as $row) {
                    $row = (array) $row;
                    $oldId = $row['id'];
                    unset($row['id']);
                    $row['hotel_id'] = $hotelId;
                    $newId = DB::table('bookings')->insertGetId($row);
                    $bookingIdMap[$oldId] = $newId;
                }

                if (!empty($data['payments'])) {
                    DB::table('payments')
                        ->whereIn('booking_id', array_keys($bookingIdMap))
                        ->delete();
                    foreach ($data['payments'] as $row) {
                        $row = (array) $row;
                        $oldBId = $row['booking_id'];
                        unset($row['id']);
                        $row['booking_id'] = $bookingIdMap[$oldBId] ?? $oldBId;
                        DB::table('payments')->insert($row);
                    }
                }

                if (!empty($data['invoices'])) {
                    DB::table('invoices')
                        ->whereIn('booking_id', array_keys($bookingIdMap))
                        ->delete();
                    foreach ($data['invoices'] as $row) {
                        $row = (array) $row;
                        $oldBId = $row['booking_id'];
                        unset($row['id']);
                        $row['booking_id'] = $bookingIdMap[$oldBId] ?? $oldBId;
                        DB::table('invoices')->insert($row);
                    }
                }
            }
        });

        return redirect()->route('platform.backups.index')
            ->with('success', "Hotel data restored from backup #{$id} ({$backup->label}).");
    }
}
