<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\HotelTimeSlot;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotBookingSeeder extends Seeder
{
    public function run(): void
    {
        // ── Add demo per-slot rooms for hotel 1 if they don't exist ──────────
        $h1SlotRooms = $this->ensureSlotRooms();

        // ── Ensure slots exist for hotel 1 ──────────────────────────────────
        $this->ensureSlots();

        // ── Reload slots for hotel 1 ─────────────────────────────────────────
        $slots = HotelTimeSlot::withoutGlobalScopes()
            ->where('hotel_id', 1)->where('is_active', true)->get();

        $daySlot     = $slots->firstWhere('name', 'Day Use');
        $eveningSlot = $slots->firstWhere('name', 'Evening Use');
        $nightSlot   = $slots->firstWhere('name', 'Night Use');

        if (!$daySlot || !$eveningSlot || !$nightSlot) {
            $this->command->warn('Could not find or create demo slots for hotel 1.');
            return;
        }

        // ── Customers for hotel 1 ────────────────────────────────────────────
        $customers = Customer::withoutGlobalScopes()->where('hotel_id', 1)->pluck('id')->toArray();
        if (empty($customers)) {
            $this->command->warn('No customers found for hotel 1.');
            return;
        }

        // ── Build booking entries ─────────────────────────────────────────────
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow  = Carbon::tomorrow();

        $entries = [
            // ── Yesterday (checked_out) ──────────────────────────────────────
            [
                'room'     => $h1SlotRooms[0],
                'slot'     => $daySlot,
                'date'     => $yesterday,
                'status'   => 'checked_out',
                'customer' => $customers[0],
            ],
            [
                'room'     => $h1SlotRooms[1] ?? $h1SlotRooms[0],
                'slot'     => $eveningSlot,
                'date'     => $yesterday,
                'status'   => 'checked_out',
                'customer' => $customers[array_key_last($customers)],
            ],
            [
                'room'     => count($h1SlotRooms) > 2 ? $h1SlotRooms[2] : $h1SlotRooms[0],
                'slot'     => $nightSlot,
                'date'     => $yesterday,
                'status'   => 'checked_out',
                'customer' => $customers[min(1, count($customers) - 1)],
            ],

            // ── Day before yesterday (checked_out) ───────────────────────────
            [
                'room'     => $h1SlotRooms[0],
                'slot'     => $eveningSlot,
                'date'     => $yesterday->copy()->subDay(),
                'status'   => 'checked_out',
                'customer' => $customers[0],
            ],
            [
                'room'     => $h1SlotRooms[1] ?? $h1SlotRooms[0],
                'slot'     => $daySlot,
                'date'     => $yesterday->copy()->subDay(),
                'status'   => 'checked_out',
                'customer' => $customers[array_key_last($customers)],
            ],

            // ── Today (active) ───────────────────────────────────────────────
            [
                'room'     => $h1SlotRooms[0],
                'slot'     => $daySlot,
                'date'     => $today,
                'status'   => 'checked_in',
                'customer' => $customers[0],
            ],
            [
                'room'     => $h1SlotRooms[1] ?? $h1SlotRooms[0],
                'slot'     => $nightSlot,
                'date'     => $today,
                'status'   => 'confirmed',
                'customer' => $customers[array_key_last($customers)],
            ],
            [
                'room'     => count($h1SlotRooms) > 2 ? $h1SlotRooms[2] : $h1SlotRooms[0],
                'slot'     => $eveningSlot,
                'date'     => $today,
                'status'   => 'confirmed',
                'customer' => $customers[min(1, count($customers) - 1)],
            ],

            // ── Tomorrow (upcoming) ──────────────────────────────────────────
            [
                'room'     => $h1SlotRooms[0],
                'slot'     => $eveningSlot,
                'date'     => $tomorrow,
                'status'   => 'confirmed',
                'customer' => $customers[0],
            ],
            [
                'room'     => $h1SlotRooms[1] ?? $h1SlotRooms[0],
                'slot'     => $daySlot,
                'date'     => $tomorrow,
                'status'   => 'confirmed',
                'customer' => $customers[array_key_last($customers)],
            ],

            // ── Day after tomorrow (upcoming) ────────────────────────────────
            [
                'room'     => $h1SlotRooms[0],
                'slot'     => $nightSlot,
                'date'     => $tomorrow->copy()->addDay(),
                'status'   => 'confirmed',
                'customer' => $customers[0],
            ],
        ];

        $created = 0;
        foreach ($entries as $entry) {
            $slot    = $entry['slot'];
            $room    = $entry['room'];
            $date    = $entry['date'];
            $isON    = $slot->is_overnight || ($slot->end_time <= $slot->start_time);
            $checkIn  = $date->toDateString();
            $checkOut = $isON ? $date->copy()->addDay()->toDateString() : $date->toDateString();

            $bnPrefix = 'SLT';
            $bn       = $bnPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));

            $conflict = Booking::withoutGlobalScopes()
                ->where('room_id', $room->id)
                ->where('booking_date', $date->toDateString())
                ->where('time_slot_id', $slot->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->exists();

            if ($conflict) {
                continue;
            }

            Booking::create([
                'booking_number'  => $bn,
                'hotel_id'        => 1,
                'customer_id'     => $entry['customer'],
                'room_id'         => $room->id,
                'time_slot_id'    => $slot->id,
                'booking_date'    => $date->toDateString(),
                'slot_start_time' => $slot->start_time,
                'slot_end_time'   => $slot->end_time,
                'check_in_date'   => $checkIn,
                'check_out_date'  => $checkOut,
                'total_amount'    => $slot->base_price,
                'status'          => $entry['status'],
                'adults'          => 1,
                'children'        => 0,
            ]);
            $created++;
        }

        $this->command->info("SlotBookingSeeder: created {$created} slot booking(s) for hotel 1.");
    }

    private function ensureSlots(): void
    {
        $demoSlots = [
            ['name' => 'Day Use',     'start_time' => '09:00', 'end_time' => '16:00', 'is_overnight' => false, 'base_price' => 600,  'description' => 'Day use — 9 AM to 4 PM'],
            ['name' => 'Evening Use', 'start_time' => '16:00', 'end_time' => '23:00', 'is_overnight' => false, 'base_price' => 800,  'description' => 'Evening use — 4 PM to 11 PM'],
            ['name' => 'Night Use',   'start_time' => '22:00', 'end_time' => '08:00', 'is_overnight' => true,  'base_price' => 1200, 'description' => 'Overnight use — 10 PM to 8 AM'],
        ];

        foreach ($demoSlots as $index => $data) {
            $exists = HotelTimeSlot::withoutGlobalScopes()
                ->where('hotel_id', 1)
                ->where('name', $data['name'])
                ->exists();
            if (!$exists) {
                HotelTimeSlot::withoutGlobalScopes()->create(array_merge($data, [
                    'hotel_id'   => 1,
                    'is_active'  => true,
                    'sort_order' => $index,
                ]));
            }
        }
    }

    private function ensureSlotRooms(): array
    {
        $existing = Room::withoutGlobalScopes()->where('hotel_id', 1)->where('pricing_type', 'per_slot')->get();

        if ($existing->count() >= 3) {
            return $existing->take(3)->all();
        }

        $toCreate = [
            ['room_number' => 'S01', 'type' => 'Deluxe', 'capacity' => 2],
            ['room_number' => 'S02', 'type' => 'Standard', 'capacity' => 2],
            ['room_number' => 'S03', 'type' => 'Suite', 'capacity' => 2],
        ];

        $rooms = $existing->toArray() ? $existing->all() : [];

        foreach ($toCreate as $data) {
            $already = Room::withoutGlobalScopes()->where('hotel_id', 1)->where('room_number', $data['room_number'])->first();
            if ($already) {
                if ($already->pricing_type !== 'per_slot') {
                    $already->update(['pricing_type' => 'per_slot']);
                }
                $rooms[] = $already->fresh();
            } else {
                $room = Room::create([
                    'hotel_id'       => 1,
                    'room_number'    => $data['room_number'],
                    'type'           => $data['type'],
                    'capacity'       => $data['capacity'],
                    'price_per_night'=> 0,
                    'pricing_type'   => 'per_slot',
                    'status'         => 'available',
                    'floor'          => 1,
                ]);
                $rooms[] = $room;
            }

            if (count($rooms) >= 3) break;
        }

        return array_slice($rooms, 0, 3);
    }
}
