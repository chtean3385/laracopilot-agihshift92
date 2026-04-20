<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\HotelTimeSlot;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotAvailabilityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hotelId = 1;

        $slotRooms = Room::where('hotel_id', $hotelId)
            ->where('pricing_type', 'per_slot')
            ->get();

        $slots = HotelTimeSlot::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->get();

        if ($slotRooms->isEmpty() || $slots->isEmpty()) {
            $this->command->warn('No per-slot rooms or time slots found for hotel 1. Run RoomSeeder first.');
            return;
        }

        $customer = Customer::where('hotel_id', $hotelId)->first();
        if (!$customer) {
            $customer = Customer::create([
                'hotel_id' => $hotelId,
                'name'     => 'Demo Guest',
                'phone'    => '9999999999',
            ]);
        }

        $today = Carbon::today();
        $bookingNumber = 1;

        $demoBookings = [
            ['days_offset' => 0,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 0,  'room_idx' => 0, 'slot_idx' => 1 % max($slots->count(), 1)],
            ['days_offset' => 1,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 2,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 3,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 4,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 5,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 7,  'room_idx' => 0, 'slot_idx' => 0],
            ['days_offset' => 10, 'room_idx' => 0, 'slot_idx' => 0],
        ];

        $created = 0;
        foreach ($demoBookings as $demo) {
            $room = $slotRooms->values()->get($demo['room_idx'] % $slotRooms->count());
            $slot = $slots->values()->get($demo['slot_idx'] % $slots->count());

            if (!$room || !$slot) continue;

            $bookingDate = $today->copy()->addDays($demo['days_offset'])->toDateString();

            $alreadyExists = Booking::where('hotel_id', $hotelId)
                ->where('room_id', $room->id)
                ->where('time_slot_id', $slot->id)
                ->where('booking_date', $bookingDate)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->exists();

            if ($alreadyExists) continue;

            $num = DB::table('bookings')->max('id') + 1 + $bookingNumber;
            $bookingNumber++;

            Booking::create([
                'hotel_id'       => $hotelId,
                'booking_number' => 'DEMO-' . str_pad($num, 4, '0', STR_PAD_LEFT),
                'customer_id'    => $customer->id,
                'room_id'        => $room->id,
                'time_slot_id'   => $slot->id,
                'booking_date'   => $bookingDate,
                'slot_start_time'=> $slot->start_time,
                'slot_end_time'  => $slot->end_time,
                'check_in_date'  => $bookingDate,
                'check_out_date' => $bookingDate,
                'nights'         => 0,
                'adults'         => 1,
                'children'       => 0,
                'total_amount'   => $slot->base_price ?? 500,
                'advance_payment'=> 0,
                'balance_due'    => $slot->base_price ?? 500,
                'status'         => 'confirmed',
                'payment_status' => 'pending',
            ]);
            $created++;
        }

        $this->command->info("SlotAvailabilityDemoSeeder: Created {$created} demo slot bookings for hotel {$hotelId}.");
    }
}
