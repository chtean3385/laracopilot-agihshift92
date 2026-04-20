<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SlotSearchController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        $currentHotelId = (int) session('crm_hotel_id');

        // Module guard with parent-hotel fallback
        if (!Module::isEnabledForHotel('slot-search-engine', $currentHotelId)) {
            return redirect()->route('dashboard')->with('warning', 'Slot Search Engine is not enabled for your hotel.');
        }

        // Determine available hotels for this session (multi-hotel support)
        $hotelOptions    = session('crm_hotel_options', []);
        $availableHotels = collect();
        if (!empty($hotelOptions)) {
            $hotelIds        = array_column($hotelOptions, 'hotel_id');
            $availableHotels = Hotel::whereIn('id', $hotelIds)->where('status', 'active')->get(['id', 'name']);
        }
        if ($availableHotels->isEmpty()) {
            $availableHotels = Hotel::where('id', $currentHotelId)->get(['id', 'name']);
        }

        $isMultiHotel = $availableHotels->count() > 1;
        $allHotelIds  = $availableHotels->pluck('id')->all();

        // ── QUERY 1: All rooms across available hotels (for filter UI + matrix) ──
        $allRooms = Room::whereIn('hotel_id', $allHotelIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('hotel_id')->orderBy('room_number')
            ->get()
            ->groupBy('hotel_id');

        // ── QUERY 2: All active slots across available hotels (for filter UI + matrix) ──
        $allSlots = HotelTimeSlot::whereIn('hotel_id', $allHotelIds)
            ->where('is_active', true)
            ->ordered()
            ->get()
            ->groupBy('hotel_id');

        // Flat collections for filter dropdowns
        $flatRooms = $allRooms->flatten()->values();
        $flatSlots = $allSlots->flatten()->values();

        if (!$request->has('date_from')) {
            return view('admin.slot-search', compact(
                'flatRooms', 'flatSlots', 'availableHotels', 'isMultiHotel',
                'allRooms', 'allSlots'
            ) + [
                'dateFrom'      => Carbon::today()->toDateString(),
                'dateTo'        => Carbon::today()->addDays(6)->toDateString(),
                'slotIds'       => [],
                'filterHotelIds'=> [],
                'statusFilter'  => 'all',
                'matrix'        => null,
                'summary'       => null,
            ]);
        }

        // Filter params
        $dateFrom       = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo         = $request->input('date_to',   Carbon::today()->addDays(6)->toDateString());
        $slotIds        = array_filter(array_map('intval', (array) $request->input('slot_ids', [])));
        $filterHotelIds = array_filter(array_map('intval', (array) $request->input('hotel_ids', [])));
        $statusFilter   = $request->input('status', 'all'); // all / free / booked

        try {
            $from = Carbon::parse($dateFrom);
            $to   = Carbon::parse($dateTo);
        } catch (\Exception $e) {
            $from = Carbon::today();
            $to   = Carbon::today()->addDays(6);
        }
        if ($to->lt($from)) $to = $from->copy()->addDays(6);
        if ($to->diffInDays($from) > 90) $to = $from->copy()->addDays(90);
        $dateFrom = $from->toDateString();
        $dateTo   = $to->toDateString();

        // Which hotels to search
        $searchHotelIds = $allHotelIds;
        if (!empty($filterHotelIds)) {
            $searchHotelIds = array_values(array_intersect($searchHotelIds, $filterHotelIds));
        }

        // Cache key using per-hotel version bump (invalidated on booking changes)
        $versionParts = array_map(
            fn($hid) => Cache::get(Module::cacheVersionKey($hid), 0),
            $searchHotelIds
        );
        $cacheKey = 'slot_search_' . md5(
            implode(',', $searchHotelIds) . '|' . $dateFrom . '|' . $dateTo . '|' .
            implode(',', $slotIds) . '|' . $statusFilter . '|' . implode(',', $versionParts)
        );

        // Pass already-loaded rooms/slots to avoid extra queries in buildMatrix
        $searchRooms = $allRooms->only($searchHotelIds);
        $searchSlots = $allSlots->only($searchHotelIds);

        $matrix = Cache::remember($cacheKey, 60, function () use (
            $from, $to, $slotIds, $searchHotelIds, $statusFilter,
            $availableHotels, $searchRooms, $searchSlots
        ) {
            return $this->buildMatrix(
                $from, $to, $slotIds, $searchHotelIds, $statusFilter,
                $availableHotels, $searchRooms, $searchSlots
            );
        });

        $summary = $this->buildSummary($matrix);

        return view('admin.slot-search', compact(
            'flatRooms', 'flatSlots', 'availableHotels', 'isMultiHotel',
            'allRooms', 'allSlots',
            'dateFrom', 'dateTo', 'slotIds', 'filterHotelIds', 'statusFilter',
            'matrix', 'summary'
        ));
    }

    /**
     * Build availability matrix using ONLY 2 additional DB queries (rooms+slots already loaded).
     * Total for full search: 4 queries (rooms, slots, slot-bookings, wh-bookings).
     */
    private function buildMatrix(
        Carbon $from, Carbon $to,
        array  $slotIds,
        array  $searchHotelIds,
        string $statusFilter,
        $availableHotels,
        $searchRooms,   // already-fetched, grouped by hotel_id
        $searchSlots    // already-fetched, grouped by hotel_id
    ): array {
        $period   = CarbonPeriod::create($from, $to);
        $lastDate = $to->toDateString();
        $prevDate = $from->copy()->subDay()->toDateString();

        // ── QUERY 3: Slot bookings in date range (incl. prev day for overnight bleed) ──
        $allBookings = Booking::with(['room:id,room_number,hotel_id,pricing_type', 'customer:id,name', 'timeSlot'])
            ->whereNotNull('time_slot_id')
            ->whereIn('hotel_id', $searchHotelIds)
            ->whereIn('booking_date', $this->dateRange($from->copy()->subDay(), $to))
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->get()
            ->groupBy('hotel_id');

        // ── QUERY 4: Whole-hotel bookings overlapping date range ──
        $whBookings = Booking::with('customer:id,name')
            ->where('is_whole_hotel', true)
            ->whereIn('hotel_id', $searchHotelIds)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<=', $lastDate)
            ->where('check_out_date', '>', $prevDate)
            ->get()
            ->groupBy('hotel_id');

        $matrix = [];

        foreach ($searchHotelIds as $hotelId) {
            $hotel      = $availableHotels->firstWhere('id', $hotelId);
            $rooms      = $searchRooms->get($hotelId, collect());
            $slots      = $searchSlots->get($hotelId, collect());
            $bookings   = $allBookings->get($hotelId, collect());
            $whForHotel = $whBookings->get($hotelId, collect());

            if ($rooms->isEmpty() || $slots->isEmpty()) continue;

            $targetSlots = !empty($slotIds) ? $slots->whereIn('id', $slotIds) : $slots;
            if ($targetSlots->isEmpty()) continue;

            $perSlotRoomIds = $rooms->where('pricing_type', 'per_slot')->pluck('id')->all();

            $hotelData = [
                'hotel_id'   => $hotelId,
                'hotel_name' => $hotel->name ?? "Hotel #{$hotelId}",
                'rooms'      => $rooms->values(),
                'slots'      => [],
            ];

            foreach ($targetSlots as $slot) {
                $slotData = [
                    'slot_id'   => $slot->id,
                    'slot_name' => $slot->name,
                    'slot_time' => $slot->start_time . '–' . $slot->end_time,
                    'dates'     => [],
                ];

                [$tStart, $tEnd] = $this->slotRange($slot, null);

                foreach ($period as $date) {
                    $ds     = $date->toDateString();
                    $prevDs = $date->copy()->subDay()->toDateString();

                    [$tStart, $tEnd] = $this->slotRange($slot, $ds);

                    // Check whole-hotel booking for this day
                    $whForDay = $whForHotel->first(
                        fn($b) => $b->check_in_date->toDateString() <= $ds
                                  && $b->check_out_date->toDateString() > $ds
                    );

                    if ($whForDay) {
                        // whole_hotel = always "booked"
                        if ($statusFilter === 'free') continue;

                        $slotData['dates'][$ds] = [
                            'whole_hotel'  => [
                                'booking_id'  => $whForDay->id,
                                'booking_num' => $whForDay->booking_number,
                                'guest_name'  => $whForDay->customer->name ?? 'Guest',
                            ],
                            'rooms'        => [],
                            'row_status'   => 'booked',
                            'booked_count' => count($perSlotRoomIds),
                            'free_count'   => 0,
                            'total_count'  => count($perSlotRoomIds),
                        ];
                        continue;
                    }

                    // Slot overlap check
                    $dayBookings = $bookings->filter(
                        fn($b) => $b->booking_date->toDateString() === $ds
                               || $b->booking_date->toDateString() === $prevDs
                    );

                    $bookedRoomMap = [];
                    foreach ($dayBookings as $booking) {
                        $bSlot = $booking->timeSlot;
                        if (!$bSlot) continue;
                        [$bStart, $bEnd] = $this->slotRange($bSlot, $booking->booking_date->toDateString());
                        if ($tStart <= $bEnd && $bStart <= $tEnd) {
                            $rid = $booking->room_id;
                            if ($rid && !isset($bookedRoomMap[$rid]) && in_array($rid, $perSlotRoomIds)) {
                                $bookedRoomMap[$rid] = [
                                    'booking_id'  => $booking->id,
                                    'guest_name'  => $booking->customer?->name ?? 'Guest',
                                    'room_number' => $booking->room?->room_number ?? '—',
                                ];
                            }
                        }
                    }

                    $bookedCount  = count($bookedRoomMap);
                    $perSlotTotal = count($perSlotRoomIds);
                    $freeCount    = $perSlotTotal - $bookedCount;

                    // Status: "free" if no per_slot rooms booked, "booked" otherwise
                    $rowStatus = $bookedCount === 0 ? 'free' : 'booked';

                    // Apply status filter
                    if ($statusFilter === 'free'   && $rowStatus !== 'free')   continue;
                    if ($statusFilter === 'booked' && $rowStatus !== 'booked') continue;

                    // Build room cells
                    $roomCells = [];
                    foreach ($rooms as $room) {
                        if ($room->pricing_type !== 'per_slot') {
                            $roomCells[$room->id] = ['status' => 'na'];
                        } elseif (isset($bookedRoomMap[$room->id])) {
                            $roomCells[$room->id] = array_merge(['status' => 'booked'], $bookedRoomMap[$room->id]);
                        } else {
                            $roomCells[$room->id] = ['status' => 'free'];
                        }
                    }

                    $slotData['dates'][$ds] = [
                        'whole_hotel'  => null,
                        'rooms'        => $roomCells,
                        'row_status'   => $rowStatus,
                        'booked_count' => $bookedCount,
                        'free_count'   => $freeCount,
                        'total_count'  => $perSlotTotal,
                    ];
                }

                if (!empty($slotData['dates'])) {
                    $hotelData['slots'][] = $slotData;
                }
            }

            if (!empty($hotelData['slots'])) {
                $matrix[] = $hotelData;
            }
        }

        return $matrix;
    }

    private function buildSummary(array $matrix): array
    {
        $total = $free = $booked = $wh = 0;
        foreach ($matrix as $hotelData) {
            foreach ($hotelData['slots'] as $slotData) {
                foreach ($slotData['dates'] as $dateData) {
                    $total++;
                    if ($dateData['whole_hotel']) {
                        $wh++;
                        $booked++;
                    } elseif ($dateData['row_status'] === 'free') {
                        $free++;
                    } else {
                        $booked++;
                    }
                }
            }
        }
        return compact('total', 'free', 'booked', 'wh');
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

    private function slotRange(HotelTimeSlot $slot, ?string $date): array
    {
        $date  = $date ?? Carbon::today()->toDateString();
        $start = Carbon::parse($date . ' ' . $slot->start_time);
        $end   = Carbon::parse($date . ' ' . $slot->end_time);
        if ($slot->is_overnight || $end <= $start) $end->addDay();
        return [$start, $end];
    }
}
