<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\HotelTimeSlot;
use App\Models\Room;
use App\Models\Hotel;
use App\Services\SlotConflictService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SlotSearchController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $hotelId = (int) session('crm_hotel_id');

        $allSlots = HotelTimeSlot::where('is_active', true)->ordered()->get();
        $allRooms = Room::where('pricing_type', 'per_slot')
            ->where('status', '!=', 'maintenance')
            ->orderBy('room_number')
            ->get();

        if (!$request->has('date_from')) {
            return view('admin.slot-search', compact('allSlots', 'allRooms'));
        }

        $dateFrom   = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo     = $request->input('date_to',   Carbon::today()->toDateString());
        $slotIds    = array_filter((array) $request->input('slot_ids', []));
        $roomIds    = array_filter((array) $request->input('room_ids', []));
        $statusFilter = $request->input('status', 'all');

        try {
            $from = Carbon::parse($dateFrom);
            $to   = Carbon::parse($dateTo);
        } catch (\Exception $e) {
            $from = Carbon::today();
            $to   = Carbon::today();
        }

        if ($to->lt($from)) $to = $from->copy();
        if ($to->diffInDays($from) > 90) $to = $from->copy()->addDays(90);

        $cacheKey = "slot_search_{$hotelId}_{$dateFrom}_{$dateTo}_" . implode(',', $slotIds) . '_' . implode(',', $roomIds) . "_{$statusFilter}";

        $results = Cache::remember($cacheKey, 60, function () use ($from, $to, $slotIds, $roomIds, $statusFilter, $allSlots, $allRooms) {
            return $this->buildResults($from, $to, $slotIds, $roomIds, $statusFilter, $allSlots, $allRooms);
        });

        $summary = $this->buildSummary($results);

        return view('admin.slot-search', compact(
            'allSlots', 'allRooms', 'results', 'summary',
            'dateFrom', 'dateTo', 'slotIds', 'roomIds', 'statusFilter'
        ));
    }

    private function buildResults(Carbon $from, Carbon $to, array $slotIds, array $roomIds, string $statusFilter, $allSlots, $allRooms): array
    {
        $period = CarbonPeriod::create($from, $to);

        $targetSlots = $slotIds ? $allSlots->whereIn('id', $slotIds) : $allSlots;
        $targetRooms = $roomIds ? $allRooms->whereIn('id', $roomIds) : $allRooms;

        if ($targetSlots->isEmpty() || $targetRooms->isEmpty()) {
            return [];
        }

        $prevDate = $from->copy()->subDay()->toDateString();
        $lastDate = $to->toDateString();

        // Batch query 1: all slot bookings in date range (including prev day for overnight)
        $allBookings = Booking::with(['room:id,room_number', 'customer:id,name', 'timeSlot'])
            ->whereNotNull('time_slot_id')
            ->whereIn('booking_date', $this->dateRange($from->copy()->subDay(), $to))
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->when($targetRooms->isNotEmpty(), fn($q) => $q->whereIn('room_id', $targetRooms->pluck('id')))
            ->get()
            ->groupBy('booking_date');

        // Batch query 2: whole-hotel bookings overlapping date range
        $whBookings = Booking::with('customer:id,name')
            ->where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<=', $lastDate)
            ->where('check_out_date', '>', $prevDate)
            ->get();

        $roomCount = $targetRooms->count();
        $roomIds_  = $targetRooms->pluck('id')->all();
        $results   = [];

        foreach ($period as $date) {
            $ds      = $date->toDateString();
            $prevDs  = $date->copy()->subDay()->toDateString();

            $whForDay = $whBookings->first(
                fn($b) => $b->check_in_date->toDateString() <= $ds && $b->check_out_date->toDateString() > $ds
            );

            $dayBookings = collect()
                ->merge($allBookings->get($ds, collect()))
                ->merge($allBookings->get($prevDs, collect()));

            foreach ($targetSlots as $slot) {
                [$tStart, $tEnd] = $this->slotRange($slot, $ds);

                if ($whForDay) {
                    $row = [
                        'date'        => $ds,
                        'date_label'  => $date->format('D, d M Y'),
                        'slot_id'     => $slot->id,
                        'slot_name'   => $slot->name,
                        'slot_time'   => $slot->start_time . '–' . $slot->end_time,
                        'status'      => 'whole_hotel',
                        'available'   => 0,
                        'booked'      => $roomCount,
                        'total'       => $roomCount,
                        'pct'         => 100,
                        'color'       => 'red',
                        'bookings'    => [],
                        'free_rooms'  => [],
                        'wh_booking'  => $whForDay->booking_number,
                        'wh_guest'    => $whForDay->customer->name ?? 'Guest',
                    ];
                } else {
                    $bookedDetails = [];
                    $bookedRoomIds = [];

                    foreach ($dayBookings as $booking) {
                        $bSlot = $booking->timeSlot;
                        if (!$bSlot) continue;
                        [$bStart, $bEnd] = $this->slotRange($bSlot, $booking->booking_date->toDateString());
                        if ($tStart <= $bEnd && $bStart <= $tEnd) {
                            $rid = $booking->room_id;
                            if ($rid && !in_array($rid, $bookedRoomIds) && in_array($rid, $roomIds_)) {
                                $bookedRoomIds[] = $rid;
                                $bookedDetails[] = [
                                    'room_id'    => $rid,
                                    'room_number'=> $booking->room?->room_number ?? '—',
                                    'guest_name' => $booking->customer?->name ?? 'Guest',
                                    'status'     => $booking->status,
                                    'booking_id' => $booking->id,
                                ];
                            }
                        }
                    }

                    $booked    = count($bookedRoomIds);
                    $available = $roomCount - $booked;
                    $pct       = $roomCount > 0 ? round($booked / $roomCount * 100) : 0;
                    $color     = $pct >= 100 ? 'red' : ($pct >= 60 ? 'amber' : 'green');
                    $freeRooms = $targetRooms->filter(fn($r) => !in_array($r->id, $bookedRoomIds))->pluck('room_number')->values()->all();

                    $slotStatus = $booked === 0 ? 'available' : ($pct >= 100 ? 'full' : 'partial');

                    $row = [
                        'date'        => $ds,
                        'date_label'  => $date->format('D, d M Y'),
                        'slot_id'     => $slot->id,
                        'slot_name'   => $slot->name,
                        'slot_time'   => $slot->start_time . '–' . $slot->end_time,
                        'status'      => $slotStatus,
                        'available'   => $available,
                        'booked'      => $booked,
                        'total'       => $roomCount,
                        'pct'         => $pct,
                        'color'       => $color,
                        'bookings'    => $bookedDetails,
                        'free_rooms'  => $freeRooms,
                        'wh_booking'  => null,
                        'wh_guest'    => null,
                    ];
                }

                if ($statusFilter !== 'all' && $row['status'] !== $statusFilter) continue;

                $results[] = $row;
            }
        }

        return $results;
    }

    private function buildSummary(array $results): array
    {
        $total     = count($results);
        $available = count(array_filter($results, fn($r) => $r['status'] === 'available'));
        $partial   = count(array_filter($results, fn($r) => $r['status'] === 'partial'));
        $full      = count(array_filter($results, fn($r) => $r['status'] === 'full'));
        $wh        = count(array_filter($results, fn($r) => $r['status'] === 'whole_hotel'));

        return compact('total', 'available', 'partial', 'full', 'wh');
    }

    private function dateRange(Carbon $from, Carbon $to): array
    {
        $dates = [];
        $cur   = $from->copy();
        while ($cur <= $to) {
            $dates[] = $cur->toDateString();
            $cur->addDay();
        }
        return $dates;
    }

    private function slotRange(HotelTimeSlot $slot, string $date): array
    {
        $start = Carbon::parse($date . ' ' . $slot->start_time);
        $end   = Carbon::parse($date . ' ' . $slot->end_time);
        if ($slot->is_overnight || $end <= $start) $end->addDay();
        return [$start, $end];
    }
}
