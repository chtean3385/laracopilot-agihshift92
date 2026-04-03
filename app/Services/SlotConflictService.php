<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\HotelTimeSlot;
use Carbon\Carbon;

/**
 * Determines which rooms are unavailable for a given time slot on a given date,
 * taking into account overnight bookings from the previous day that bleed over.
 *
 * Two slots conflict when their datetime ranges overlap:
 *   A.start < B.end  AND  B.start < A.end
 */
class SlotConflictService
{
    /**
     * Returns the array of room IDs that cannot be booked for $targetSlot on $date,
     * because an existing active booking on that room overlaps the slot's time range.
     *
     * @param  HotelTimeSlot  $targetSlot
     * @param  string         $date   Y-m-d
     * @param  int|null       $excludeBookingId  (optional) booking to ignore — useful when editing
     * @return int[]
     */
    public function getConflictingRoomIds(HotelTimeSlot $targetSlot, string $date, ?int $excludeBookingId = null): array
    {
        [$tStart, $tEnd] = $this->slotRange($targetSlot, $date);

        // Pull bookings on $date AND the previous day (overnight bleed-over)
        $prevDate = Carbon::parse($date)->subDay()->toDateString();

        $candidates = Booking::with('timeSlot')
            ->whereNotNull('time_slot_id')
            ->whereIn('booking_date', [$prevDate, $date])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->get();

        $conflicting = [];
        foreach ($candidates as $booking) {
            $slot = $booking->timeSlot;
            if (!$slot) continue;

            [$bStart, $bEnd] = $this->slotRange($slot, $booking->booking_date->toDateString());

            // Overlap check: A.start < B.end AND B.start < A.end
            if ($tStart < $bEnd && $bStart < $tEnd) {
                $conflicting[] = $booking->room_id;
            }
        }

        return array_values(array_unique($conflicting));
    }

    /**
     * Returns an array of available slot IDs for a given room on a given date.
     * A slot is available if the room is NOT in the conflicting room IDs for that slot.
     *
     * @param  int     $roomId
     * @param  string  $date   Y-m-d
     * @return int[]  available slot IDs
     */
    public function availableSlotIdsForRoom(int $roomId, string $date): array
    {
        $slots = HotelTimeSlot::where('is_active', true)->ordered()->get();
        $available = [];
        foreach ($slots as $slot) {
            $conflicting = $this->getConflictingRoomIds($slot, $date);
            if (!in_array($roomId, $conflicting)) {
                $available[] = $slot->id;
            }
        }
        return $available;
    }

    /**
     * Converts a slot + date into a [Carbon $start, Carbon $end] pair,
     * where $end is on the next calendar day if the slot is overnight.
     *
     * @return Carbon[]  [$start, $end]
     */
    private function slotRange(HotelTimeSlot $slot, string $date): array
    {
        $start = Carbon::parse($date . ' ' . $slot->start_time);
        $end   = Carbon::parse($date . ' ' . $slot->end_time);

        if ($slot->is_overnight || $end <= $start) {
            // end crosses midnight — add one day
            $end->addDay();
        }

        return [$start, $end];
    }
}
