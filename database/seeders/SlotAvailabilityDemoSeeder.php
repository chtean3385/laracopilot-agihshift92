<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hotel;
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

        $hotel = Hotel::find($hotelId);
        if (!$hotel) {
            $this->command->warn('Hotel 1 not found. Skipping demo seeder.');
            return;
        }

        // ── 1. Ensure at least 3 active demo time slots ─────────────────────
        $slotDefs = [
            ['name' => 'Morning',   'start_time' => '06:00', 'end_time' => '12:00', 'base_price' => 800,  'sort_order' => 10],
            ['name' => 'Afternoon', 'start_time' => '12:00', 'end_time' => '18:00', 'base_price' => 1000, 'sort_order' => 20],
            ['name' => 'Evening',   'start_time' => '18:00', 'end_time' => '23:59', 'base_price' => 900,  'sort_order' => 30],
        ];

        $slots = [];
        foreach ($slotDefs as $def) {
            $existing = HotelTimeSlot::where('hotel_id', $hotelId)
                ->where('name', $def['name'])
                ->first();
            if (!$existing) {
                $existing = HotelTimeSlot::create(array_merge($def, [
                    'hotel_id'   => $hotelId,
                    'is_active'  => true,
                    'is_overnight' => false,
                    'description'  => 'Demo slot — ' . $def['name'],
                ]));
                $this->command->info("Created slot: {$def['name']}");
            }
            $slots[] = $existing;
        }

        // ── 2. Ensure at least one per-slot room ─────────────────────────────
        $slotRoom = Room::where('hotel_id', $hotelId)
            ->where('pricing_type', 'per_slot')
            ->first();
        if (!$slotRoom) {
            $slotRoom = Room::create([
                'hotel_id'       => $hotelId,
                'room_number'    => 'SLT-01',
                'type'           => 'standard',
                'capacity'       => 2,
                'price_per_night'=> 0,
                'pricing_type'   => 'per_slot',
                'status'         => 'available',
            ]);
            $this->command->info("Created per-slot room: SLT-01");
        }

        // Second per-slot room
        $slotRoom2 = Room::where('hotel_id', $hotelId)
            ->where('pricing_type', 'per_slot')
            ->where('id', '!=', $slotRoom->id)
            ->first();
        if (!$slotRoom2) {
            $slotRoom2 = Room::create([
                'hotel_id'       => $hotelId,
                'room_number'    => 'SLT-02',
                'type'           => 'deluxe',
                'capacity'       => 2,
                'price_per_night'=> 0,
                'pricing_type'   => 'per_slot',
                'status'         => 'available',
            ]);
            $this->command->info("Created per-slot room: SLT-02");
        }

        // ── 3. Demo guest customers ──────────────────────────────────────────
        $guestNames = ['Aryan Mehta', 'Priya Sharma', 'Rahul Verma', 'Sonal Patel', 'Vikram Joshi'];
        $customers  = [];
        foreach ($guestNames as $name) {
            $phone  = '98' . rand(10000000, 99999999);
            $c = Customer::firstOrCreate(
                ['hotel_id' => $hotelId, 'name' => $name],
                ['phone' => $phone, 'id_number' => '', 'id_type' => 'aadhaar', 'nationality' => 'Indian', 'country' => 'India']
            );
            $customers[] = $c;
        }

        $today = Carbon::today();
        $created = 0;

        // ── 4. 10 Demo bookings: mix of slot, standard, whole-hotel ──────────
        $demoBookings = [
            // Slot bookings for SLT-01
            ['type' => 'slot', 'room' => $slotRoom,  'slot' => $slots[0], 'offset' => 0,  'guest_idx' => 0, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom,  'slot' => $slots[1], 'offset' => 1,  'guest_idx' => 1, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom,  'slot' => $slots[0], 'offset' => 2,  'guest_idx' => 2, 'status' => 'checked_in'],
            ['type' => 'slot', 'room' => $slotRoom2, 'slot' => $slots[0], 'offset' => 0,  'guest_idx' => 3, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom2, 'slot' => $slots[2], 'offset' => 1,  'guest_idx' => 4, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom,  'slot' => $slots[2], 'offset' => 3,  'guest_idx' => 0, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom2, 'slot' => $slots[1], 'offset' => 2,  'guest_idx' => 2, 'status' => 'confirmed'],
            ['type' => 'slot', 'room' => $slotRoom,  'slot' => $slots[0], 'offset' => 5,  'guest_idx' => 1, 'status' => 'confirmed'],
            // Standard per-night bookings (to test "N/A" display)
            ['type' => 'standard', 'offset' => 0, 'nights' => 2, 'guest_idx' => 3, 'status' => 'checked_in'],
            // Whole-hotel booking (blocks everything)
            ['type' => 'whole_hotel', 'offset' => 7, 'nights' => 2, 'guest_idx' => 4, 'status' => 'confirmed'],
        ];

        // Get a per-night room for standard booking
        $nightRoom = Room::where('hotel_id', $hotelId)
            ->where('pricing_type', 'per_night')
            ->first();

        foreach ($demoBookings as $demo) {
            try {
                $bookingDate = $today->copy()->addDays($demo['offset'])->toDateString();
                $customer    = $customers[$demo['guest_idx']];
                $baseNum     = DB::table('bookings')->max('id') + 100 + $created + 1;
                $bookingNum  = 'DEMO-' . str_pad($baseNum, 5, '0', STR_PAD_LEFT);

                if ($demo['type'] === 'slot') {
                    $alreadyExists = Booking::where('hotel_id', $hotelId)
                        ->where('room_id', $demo['room']->id)
                        ->where('time_slot_id', $demo['slot']->id)
                        ->where('booking_date', $bookingDate)
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->exists();
                    if ($alreadyExists) continue;

                    Booking::create([
                        'hotel_id'       => $hotelId,
                        'booking_number' => $bookingNum,
                        'customer_id'    => $customer->id,
                        'room_id'        => $demo['room']->id,
                        'time_slot_id'   => $demo['slot']->id,
                        'booking_date'   => $bookingDate,
                        'slot_start_time'=> $demo['slot']->start_time,
                        'slot_end_time'  => $demo['slot']->end_time,
                        'check_in_date'  => $bookingDate,
                        'check_out_date' => $bookingDate,
                        'nights'         => 0,
                        'adults'         => 1,
                        'children'       => 0,
                        'total_amount'   => $demo['slot']->base_price,
                        'advance_payment'=> 0,
                        'balance_due'    => $demo['slot']->base_price,
                        'status'         => $demo['status'],
                        'payment_status' => 'pending',
                    ]);
                    $created++;
                } elseif ($demo['type'] === 'standard' && $nightRoom) {
                    $nights    = $demo['nights'] ?? 1;
                    $checkOut  = $today->copy()->addDays($demo['offset'] + $nights)->toDateString();
                    $alreadyExists = Booking::where('hotel_id', $hotelId)
                        ->where('room_id', $nightRoom->id)
                        ->where('check_in_date', $bookingDate)
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->exists();
                    if ($alreadyExists) continue;

                    Booking::create([
                        'hotel_id'        => $hotelId,
                        'booking_number'  => $bookingNum,
                        'customer_id'     => $customer->id,
                        'room_id'         => $nightRoom->id,
                        'check_in_date'   => $bookingDate,
                        'check_out_date'  => $checkOut,
                        'nights'          => $nights,
                        'adults'          => 2,
                        'children'        => 0,
                        'total_amount'    => ($nightRoom->price_per_night ?? 2000) * $nights,
                        'advance_payment' => 500,
                        'balance_due'     => (($nightRoom->price_per_night ?? 2000) * $nights) - 500,
                        'status'          => $demo['status'],
                        'payment_status'  => 'partial',
                    ]);
                    $created++;
                } elseif ($demo['type'] === 'whole_hotel') {
                    $nights   = $demo['nights'] ?? 1;
                    $checkOut = $today->copy()->addDays($demo['offset'] + $nights)->toDateString();
                    $alreadyExists = Booking::where('hotel_id', $hotelId)
                        ->where('is_whole_hotel', true)
                        ->where('check_in_date', $bookingDate)
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->exists();
                    if ($alreadyExists) continue;

                    Booking::create([
                        'hotel_id'              => $hotelId,
                        'booking_number'        => $bookingNum,
                        'customer_id'           => $customer->id,
                        'room_id'               => null,
                        'is_whole_hotel'        => true,
                        'whole_hotel_pricing_type' => 'fixed',
                        'check_in_date'         => $bookingDate,
                        'check_out_date'        => $checkOut,
                        'nights'                => $nights,
                        'adults'                => 10,
                        'children'              => 0,
                        'total_amount'          => 25000,
                        'advance_payment'       => 10000,
                        'balance_due'           => 15000,
                        'status'                => $demo['status'],
                        'payment_status'        => 'partial',
                    ]);
                    $created++;
                }
            } catch (\Exception $e) {
                $this->command->warn("Demo booking failed: " . $e->getMessage());
            }
        }

        $this->command->info("SlotAvailabilityDemoSeeder: Created {$created} demo bookings for hotel {$hotelId}.");
    }
}
