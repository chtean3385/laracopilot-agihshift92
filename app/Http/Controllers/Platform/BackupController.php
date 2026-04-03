<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelBackup;
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
        $raw    = is_string($backup->backup_data)
            ? json_decode($backup->backup_data, true)
            : (array) $backup->backup_data;

        if (!$raw || !isset($raw['hotel'])) {
            return back()->withErrors(['error' => 'Backup data is corrupt or unreadable.']);
        }

        $hotelId = $backup->hotel_id;

        DB::transaction(function () use ($raw, $hotelId) {

            // ── 1. Settings (no relational deps) ────────────────────────────
            DB::table('settings')->where('hotel_id', $hotelId)->delete();
            foreach ((array)($raw['settings'] ?? []) as $row) {
                $row = (array) $row;
                unset($row['id']);
                $row['hotel_id'] = $hotelId;
                DB::table('settings')->insert($row);
            }

            // ── 2. Rooms (no relational deps within hotel) ──────────────────
            $roomIdMap = [];
            DB::table('rooms')->where('hotel_id', $hotelId)->delete();
            foreach ((array)($raw['rooms'] ?? []) as $row) {
                $row    = (array) $row;
                $oldId  = $row['id'];
                unset($row['id']);
                $row['hotel_id'] = $hotelId;
                $newId  = DB::table('rooms')->insertGetId($row);
                $roomIdMap[$oldId] = $newId;
            }

            // ── 3. Customers (no relational deps within hotel) ──────────────
            $customerIdMap = [];
            DB::table('customers')->where('hotel_id', $hotelId)->delete();
            foreach ((array)($raw['customers'] ?? []) as $row) {
                $row    = (array) $row;
                $oldId  = $row['id'];
                unset($row['id']);
                $row['hotel_id'] = $hotelId;
                $newId  = DB::table('customers')->insertGetId($row);
                $customerIdMap[$oldId] = $newId;
            }

            // ── 4. Bookings (depends on rooms + customers) ──────────────────
            $bookingIdMap = [];
            DB::table('bookings')->where('hotel_id', $hotelId)->delete();
            foreach ((array)($raw['bookings'] ?? []) as $row) {
                $row    = (array) $row;
                $oldId  = $row['id'];
                unset($row['id']);
                $row['hotel_id'] = $hotelId;

                if (isset($row['customer_id']) && isset($customerIdMap[$row['customer_id']])) {
                    $row['customer_id'] = $customerIdMap[$row['customer_id']];
                }
                if (isset($row['room_id']) && isset($roomIdMap[$row['room_id']])) {
                    $row['room_id'] = $roomIdMap[$row['room_id']];
                }

                $newId  = DB::table('bookings')->insertGetId($row);
                $bookingIdMap[$oldId] = $newId;
            }

            // ── 5. Payments (depends on bookings) ───────────────────────────
            $oldBookingIds = array_keys($bookingIdMap);
            if (!empty($oldBookingIds)) {
                DB::table('payments')->whereIn('booking_id', array_values($bookingIdMap))->delete();
            }
            foreach ((array)($raw['payments'] ?? []) as $row) {
                $row = (array) $row;
                unset($row['id']);
                $oldBId = $row['booking_id'] ?? null;
                if ($oldBId !== null && isset($bookingIdMap[$oldBId])) {
                    $row['booking_id'] = $bookingIdMap[$oldBId];
                    DB::table('payments')->insert($row);
                }
            }

            // ── 6. Invoices (depends on bookings) ───────────────────────────
            if (!empty($bookingIdMap)) {
                DB::table('invoices')->whereIn('booking_id', array_values($bookingIdMap))->delete();
            }
            foreach ((array)($raw['invoices'] ?? []) as $row) {
                $row = (array) $row;
                unset($row['id']);
                $oldBId = $row['booking_id'] ?? null;
                if ($oldBId !== null && isset($bookingIdMap[$oldBId])) {
                    $row['booking_id'] = $bookingIdMap[$oldBId];
                    DB::table('invoices')->insert($row);
                }
            }
        });

        return redirect()->route('platform.backups.index')
            ->with('success', "Hotel data restored from backup #{$id} ({$backup->label}).");
    }
}
