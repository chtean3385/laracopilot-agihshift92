<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataCleanupController extends Controller
{
    private function hotelId(): int
    {
        return (int) session('crm_hotel_id');
    }

    public function index()
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        return view('admin.data-cleanup.index');
    }

    public function truncate(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $request->validate([
            'tables'    => 'required|array|min:1',
            'tables.*'  => 'in:guests,bookings,invoices,payments,rooms,food',
            'confirm'   => 'required|in:DELETE',
        ], [
            'confirm.in'    => 'You must type DELETE exactly to confirm.',
            'tables.required' => 'Select at least one data group.',
        ]);

        $hotelId = $this->hotelId();
        $tables  = $request->input('tables');
        $deleted = [];

        DB::transaction(function () use ($hotelId, $tables, &$deleted) {

            if (in_array('food', $tables)) {
                $bookingIds = DB::table('bookings')->where('hotel_id', $hotelId)->pluck('id');
                $n = DB::table('booking_extra_charges')
                    ->whereIn('booking_id', $bookingIds)
                    ->where('category', 'food')
                    ->delete();
                $deleted[] = "Food & Beverage charges ({$n} records)";
            }

            if (in_array('payments', $tables)) {
                $n = DB::table('payments')->where('hotel_id', $hotelId)->delete();
                $deleted[] = "Payments ({$n} records)";
            }

            if (in_array('invoices', $tables)) {
                $n = DB::table('invoices')->where('hotel_id', $hotelId)->delete();
                $deleted[] = "Invoices ({$n} records)";
            }

            if (in_array('bookings', $tables)) {
                $bookingIds = DB::table('bookings')->where('hotel_id', $hotelId)->pluck('id');
                DB::table('channel_bookings')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('ota_booking_conflicts')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('booking_guests')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('booking_add_ons')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('booking_extra_charges')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('booking_payment_references')->whereIn('booking_id', $bookingIds)->delete();
                $n = DB::table('bookings')->where('hotel_id', $hotelId)->delete();
                $deleted[] = "Bookings incl. check-in/check-out records ({$n} records)";
            }

            if (in_array('guests', $tables)) {
                $n = DB::table('customers')->where('hotel_id', $hotelId)->delete();
                $deleted[] = "Guests ({$n} records)";
            }

            if (in_array('rooms', $tables)) {
                $roomIds = DB::table('rooms')->where('hotel_id', $hotelId)->pluck('id');
                DB::table('room_add_ons')->whereIn('room_id', $roomIds)->delete();
                $n = DB::table('rooms')->where('hotel_id', $hotelId)->delete();
                $deleted[] = "Rooms ({$n} records)";
            }
        });

        $summary = implode(', ', $deleted);
        ActivityLogger::log('data_truncate', "Cleared: {$summary}");

        return redirect()->route('data-cleanup.index')
            ->with('success', 'Data cleared successfully: ' . $summary);
    }
}
