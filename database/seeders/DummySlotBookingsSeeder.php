<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Inserts a variety of dummy per-slot bookings across both hotels
 * so the Slot Search Engine matrix shows green / amber / red cells
 * with real guest names, room pills and date breakdowns.
 *
 * Run: php artisan db:seed --class=DummySlotBookingsSeeder
 * Safe to re-run — existing slot bookings are deleted first.
 */
class DummySlotBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $now  = now()->format('Y-m-d H:i:s');
        $today = Carbon::today();

        // ── Hotels ──────────────────────────────────────────────
        // [1] Demo Hotel   slots: 26 (Day 09-16), 27 (Slot 17-16), 28 (DayTime 10-09)
        //                  rooms: 2(102), 11(S01), 12(S02)
        // [2] Beach Resort slots: 29 (Day 09-16), 30 (stay 18-09), 31 (sta2 18-16)
        //                  rooms: 9(101), 10(102)
        // ── Customers ────────────────────────────────────────────
        // hotel1: 1(Test Guest), 2(Makwana Chetan), 6(Amit)
        // hotel2: 5(Makwana Chetan)

        // Remove old dummy slot bookings (keep non-slot bookings)
        DB::table('bookings')
            ->whereNotNull('time_slot_id')
            ->whereIn('hotel_id', [1, 2])
            ->delete();

        $bookings = [];

        // ── Demo Hotel — slot 26 (Day 09:00–16:00) ──────────────
        // Today: room 102 booked, S01 & S02 free → PARTIAL
        $bookings[] = $this->slotBooking(
            hotelId: 1, customerId: 2, roomId: 2,
            date: $today->toDateString(), slotId: 26,
            slotStart: '09:00', slotEnd: '16:00',
            guestName: 'Makwana Chetan', amount: 1500, num: 'SLT-0001', now: $now
        );

        // Tomorrow: S01 + 102 booked → PARTIAL
        $bookings[] = $this->slotBooking(1, 1, 11, $today->copy()->addDay()->toDateString(), 26, '09:00', '16:00', 'Test Guest', 1200, 'SLT-0002', $now);
        $bookings[] = $this->slotBooking(1, 2, 2, $today->copy()->addDay()->toDateString(), 26, '09:00', '16:00', 'Makwana Chetan', 1500, 'SLT-0003', $now);

        // Day +2: all 3 rooms booked → FULL (RED)
        $bookings[] = $this->slotBooking(1, 1, 11, $today->copy()->addDays(2)->toDateString(), 26, '09:00', '16:00', 'Test Guest', 1200, 'SLT-0004', $now);
        $bookings[] = $this->slotBooking(1, 2, 2, $today->copy()->addDays(2)->toDateString(), 26, '09:00', '16:00', 'Makwana Chetan', 1500, 'SLT-0005', $now);
        $bookings[] = $this->slotBooking(1, 6, 12, $today->copy()->addDays(2)->toDateString(), 26, '09:00', '16:00', 'Amit Shah', 1300, 'SLT-0006', $now);

        // Day +4: only S02 booked → PARTIAL
        $bookings[] = $this->slotBooking(1, 6, 12, $today->copy()->addDays(4)->toDateString(), 26, '09:00', '16:00', 'Amit Shah', 1300, 'SLT-0007', $now);

        // ── Demo Hotel — slot 27 (Slot 17:00–16:00 overnight) ───
        // Today: none → FREE (already green by default)
        // Tomorrow: 102 booked
        $bookings[] = $this->slotBooking(1, 2, 2, $today->copy()->addDay()->toDateString(), 27, '17:00', '16:00', 'Makwana Chetan', 2000, 'SLT-0008', $now);

        // Day +3: S01 + S02 booked → PARTIAL
        $bookings[] = $this->slotBooking(1, 1, 11, $today->copy()->addDays(3)->toDateString(), 27, '17:00', '16:00', 'Test Guest', 2200, 'SLT-0009', $now);
        $bookings[] = $this->slotBooking(1, 6, 12, $today->copy()->addDays(3)->toDateString(), 27, '17:00', '16:00', 'Amit Shah', 2100, 'SLT-0010', $now);

        // ── Demo Hotel — slot 28 (Day Time 10:00–09:00 overnight) ─
        // Day +1: S01 booked → PARTIAL
        $bookings[] = $this->slotBooking(1, 1, 11, $today->copy()->addDay()->toDateString(), 28, '10:00', '09:00', 'Test Guest', 1800, 'SLT-0011', $now);

        // Day +5: all 3 rooms booked → FULL
        $bookings[] = $this->slotBooking(1, 1, 11, $today->copy()->addDays(5)->toDateString(), 28, '10:00', '09:00', 'Test Guest', 1800, 'SLT-0012', $now);
        $bookings[] = $this->slotBooking(1, 2, 2, $today->copy()->addDays(5)->toDateString(), 28, '10:00', '09:00', 'Makwana Chetan', 1900, 'SLT-0013', $now);
        $bookings[] = $this->slotBooking(1, 6, 12, $today->copy()->addDays(5)->toDateString(), 28, '10:00', '09:00', 'Amit Shah', 1700, 'SLT-0014', $now);

        // ── Beach Resort — slot 29 (Day 09:00–16:00) ────────────
        // Today: 101 booked → PARTIAL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->toDateString(), 29, '09:00', '16:00', 'Priya Sharma', 1400, 'SLT-0015', $now);

        // Day +1: both booked → FULL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->copy()->addDay()->toDateString(), 29, '09:00', '16:00', 'Priya Sharma', 1400, 'SLT-0016', $now);
        $bookings[] = $this->slotBooking(2, 5, 10, $today->copy()->addDay()->toDateString(), 29, '09:00', '16:00', 'Rahul Verma', 1600, 'SLT-0017', $now);

        // Day +3: 102 booked → PARTIAL
        $bookings[] = $this->slotBooking(2, 5, 10, $today->copy()->addDays(3)->toDateString(), 29, '09:00', '16:00', 'Rahul Verma', 1600, 'SLT-0018', $now);

        // ── Beach Resort — slot 30 (stay 18:00–09:00 overnight) ─
        // Today: 101 booked → PARTIAL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->toDateString(), 30, '18:00', '09:00', 'Priya Sharma', 2500, 'SLT-0019', $now);

        // Day +2: both booked → FULL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->copy()->addDays(2)->toDateString(), 30, '18:00', '09:00', 'Priya Sharma', 2500, 'SLT-0020', $now);
        $bookings[] = $this->slotBooking(2, 5, 10, $today->copy()->addDays(2)->toDateString(), 30, '18:00', '09:00', 'Rahul Verma', 2800, 'SLT-0021', $now);

        // ── Beach Resort — slot 31 (sta2 18:00–16:00) ───────────
        // Day +2: 101 booked → PARTIAL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->copy()->addDays(2)->toDateString(), 31, '18:00', '16:00', 'Priya Sharma', 3000, 'SLT-0022', $now);

        // Day +4: both booked → FULL
        $bookings[] = $this->slotBooking(2, 5, 9, $today->copy()->addDays(4)->toDateString(), 31, '18:00', '16:00', 'Priya Sharma', 3000, 'SLT-0023', $now);
        $bookings[] = $this->slotBooking(2, 5, 10, $today->copy()->addDays(4)->toDateString(), 31, '18:00', '16:00', 'Rahul Verma', 3200, 'SLT-0024', $now);

        DB::table('bookings')->insert($bookings);

        $this->command->info('✅ Inserted ' . count($bookings) . ' dummy slot bookings across Demo Hotel + Beach Resort.');
        $this->command->info('   Date range: ' . $today->toDateString() . ' → ' . $today->copy()->addDays(5)->toDateString());
        $this->command->info('   Search this range in /slot-search to see green / amber / red cells.');
    }

    private function slotBooking(
        int    $hotelId,
        int    $customerId,
        int    $roomId,
        string $date,
        int    $slotId,
        string $slotStart,
        string $slotEnd,
        string $guestName,
        float  $amount,
        string $num,
        string $now
    ): array {
        // For slot bookings check_in_date = booking_date, check_out = next day
        $checkOut = Carbon::parse($date)->addDay()->toDateString();
        return [
            'booking_number'  => $num,
            'hotel_id'        => $hotelId,
            'customer_id'     => $customerId,
            'room_id'         => $roomId,
            'time_slot_id'    => $slotId,
            'booking_date'    => $date,
            'check_in_date'   => $date,
            'check_out_date'  => $checkOut,
            'slot_start_time' => $slotStart,
            'slot_end_time'   => $slotEnd,
            'nights'          => 1,
            'adults'          => 2,
            'children'        => 0,
            'total_amount'    => $amount,
            'advance_payment' => 0,
            'balance_due'     => $amount,
            'status'          => 'confirmed',
            'payment_status'  => 'pending',
            'is_whole_hotel'  => false,
            'ota_conflict'    => false,
            'price_overridden'=> false,
            'meal_breakfast'  => false,
            'meal_lunch'      => false,
            'meal_dinner'     => false,
            'meal_cost'       => 0,
            'extra_beds'      => 0,
            'extra_bed_cost'  => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];
    }
}
