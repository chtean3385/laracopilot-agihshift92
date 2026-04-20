<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Module;
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

        if (!Module::isEnabledForHotel('slot-search-engine', $currentHotelId)) {
            return redirect()->route('dashboard')->with('warning', 'Slot Search Engine is not enabled for your hotel.');
        }

        // Determine hotels available in this session
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
        $allRooms = DB::table('rooms')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('hotel_id')->orderBy('room_number')
            ->get(['id', 'hotel_id', 'room_number', 'pricing_type', 'type'])
            ->groupBy('hotel_id');

        // ── QUERY 2: All active slots across available hotels (for filter UI + matrix) ──
        $allSlots = DB::table('hotel_time_slots')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('is_active', true)
            ->orderBy('hotel_id')->orderBy('sort_order')->orderBy('start_time')
            ->get(['id', 'hotel_id', 'name', 'start_time', 'end_time', 'is_overnight'])
            ->groupBy('hotel_id');

        $flatRooms = $allRooms->flatten()->values();
        $flatSlots = $allSlots->flatten()->values();

        if (!$request->has('date_from')) {
            return view('admin.slot-search', compact(
                'flatRooms', 'flatSlots', 'availableHotels', 'isMultiHotel',
                'allRooms', 'allSlots'
            ) + [
                'dateFrom'       => Carbon::today()->toDateString(),
                'dateTo'         => Carbon::today()->addDays(6)->toDateString(),
                'slotIds'        => [],
                'filterHotelIds' => [],
                'statusFilter'   => 'all',
                'matrix'         => null,
                'summary'        => null,
            ]);
        }

        // Parse filters
        $dateFrom       = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo         = $request->input('date_to',   Carbon::today()->addDays(6)->toDateString());
        $slotIds        = array_filter(array_map('intval', (array) $request->input('slot_ids', [])));
        $filterHotelIds = array_filter(array_map('intval', (array) $request->input('hotel_ids', [])));
        $statusFilter   = in_array($request->input('status'), ['free', 'booked']) ? $request->input('status') : 'all';

        try {
            $from = Carbon::parse($dateFrom);
            $to   = Carbon::parse($dateTo);
        } catch (\Exception $e) {
            $from = Carbon::today();
            $to   = Carbon::today()->addDays(6);
        }
        if ($to->lt($from))                  $to = $from->copy()->addDays(6);
        if ($to->diffInDays($from) > 90)     $to = $from->copy()->addDays(90);
        $dateFrom = $from->toDateString();
        $dateTo   = $to->toDateString();

        $searchHotelIds = $allHotelIds;
        if (!empty($filterHotelIds)) {
            $searchHotelIds = array_values(array_intersect($searchHotelIds, $filterHotelIds));
        }

        // Cache key versioned per-hotel (invalidated by Module::bumpSearchCache on booking change)
        $versionParts = array_map(fn($hid) => Cache::get(Module::cacheVersionKey($hid), 0), $searchHotelIds);
        $cacheKey = 'slot_search_' . md5(
            implode(',', $searchHotelIds) . '|' . $dateFrom . '|' . $dateTo . '|' .
            implode(',', $slotIds) . '|' . $statusFilter . '|' . implode(',', $versionParts)
        );

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
     * Build matrix using exactly 2 additional DB queries (rooms+slots already loaded = 4 total).
     *
     * Q3: slot bookings with room + customer + slot info via JOINs (one query, no eager loading).
     * Q4: whole-hotel bookings with customer name via JOIN (one query).
     */
    private function buildMatrix(
        Carbon $from, Carbon $to,
        array  $slotIds,
        array  $searchHotelIds,
        string $statusFilter,
        $availableHotels,
        $searchRooms,
        $searchSlots
    ): array {
        $dateFrom = $from->copy()->subDay()->toDateString(); // include prev day for overnight bleed
        $dateTo   = $to->toDateString();
        $lastDate = $to->toDateString();

        // ── QUERY 3: Slot bookings with room + customer + slot data via JOINs (single query) ──
        $rawSlotBookings = DB::table('bookings as b')
            ->join('rooms as r',            'r.id',   '=', 'b.room_id')
            ->join('customers as c',         'c.id',   '=', 'b.customer_id')
            ->join('hotel_time_slots as ts', 'ts.id',  '=', 'b.time_slot_id')
            ->whereIn('b.hotel_id', $searchHotelIds)
            ->whereNotNull('b.time_slot_id')
            ->whereBetween('b.booking_date', [$dateFrom, $dateTo])
            ->whereIn('b.status', ['confirmed', 'checked_in'])
            ->get([
                'b.id as booking_id', 'b.hotel_id', 'b.booking_date', 'b.room_id',
                'b.time_slot_id',
                'r.room_number', 'r.pricing_type',
                'c.name as guest_name',
                'ts.start_time as slot_start', 'ts.end_time as slot_end',
                'ts.is_overnight as slot_overnight',
            ])
            ->groupBy('hotel_id');

        // ── QUERY 4: Whole-hotel bookings with customer name via JOIN (single query) ──
        $rawWhBookings = DB::table('bookings as b')
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->where('b.is_whole_hotel', true)
            ->whereIn('b.hotel_id', $searchHotelIds)
            ->whereNotIn('b.status', ['cancelled', 'checked_out'])
            ->where('b.check_in_date', '<=', $lastDate)
            ->where('b.check_out_date', '>', $dateFrom)
            ->get([
                'b.id as booking_id', 'b.hotel_id', 'b.booking_number',
                'b.check_in_date', 'b.check_out_date',
                'c.name as guest_name',
            ])
            ->groupBy('hotel_id');

        $period = CarbonPeriod::create($from, $to);
        $matrix = [];

        foreach ($searchHotelIds as $hotelId) {
            $hotel      = $availableHotels->firstWhere('id', $hotelId);
            $rooms      = $searchRooms->get($hotelId, collect());
            $slots      = $searchSlots->get($hotelId, collect());
            $slotBks    = $rawSlotBookings->get($hotelId, collect());
            $whBks      = $rawWhBookings->get($hotelId, collect());

            if ($rooms->isEmpty() || $slots->isEmpty()) continue;

            $perSlotRoomIds  = $rooms->where('pricing_type', 'per_slot')->pluck('id')->values()->all();
            if (empty($perSlotRoomIds)) continue; // skip hotels with no per_slot rooms

            $targetSlots     = !empty($slotIds) ? $slots->whereIn('id', $slotIds) : $slots;
            if ($targetSlots->isEmpty()) continue;

            $hotelData = [
                'hotel_id'   => $hotelId,
                'hotel_name' => $hotel->name ?? "Hotel #{$hotelId}",
                'rooms'      => $rooms->values(),
                'slots'      => [],
            ];

            foreach ($targetSlots as $slot) {
                [$tStart, $tEnd] = $this->slotRangeFromRow($slot, null);

                $slotData = [
                    'slot_id'   => $slot->id,
                    'slot_name' => $slot->name,
                    'slot_time' => $slot->start_time . '–' . $slot->end_time,
                    'dates'     => [],
                ];

                foreach ($period as $date) {
                    $ds     = $date->toDateString();
                    $prevDs = $date->copy()->subDay()->toDateString();

                    // Whole-hotel check for this day
                    $whForDay = $whBks->first(
                        fn($b) => $b->check_in_date <= $ds && $b->check_out_date > $ds
                    );

                    if ($whForDay) {
                        // Whole-hotel = always "booked" — skip if filter is "free"
                        if ($statusFilter === 'free') continue;

                        $slotData['dates'][$ds] = [
                            'whole_hotel'  => [
                                'booking_id'    => $whForDay->booking_id,
                                'booking_num'   => $whForDay->booking_number,
                                'guest_name'    => $whForDay->guest_name,
                                'check_in_date' => $whForDay->check_in_date,
                                'check_out_date'=> $whForDay->check_out_date,
                            ],
                            'rooms'        => [],
                            'row_status'   => 'booked',
                            'booked_count' => count($perSlotRoomIds),
                            'free_count'   => 0,
                            'total_count'  => count($perSlotRoomIds),
                        ];
                        continue;
                    }

                    // Per-slot booking overlap detection using already-loaded data
                    [$tStart, $tEnd] = $this->slotRangeFromRow($slot, $ds);

                    $dayBookings = $slotBks->filter(
                        fn($b) => $b->booking_date === $ds || $b->booking_date === $prevDs
                    );

                    $bookedRoomMap = [];
                    foreach ($dayBookings as $b) {
                        [$bStart, $bEnd] = $this->slotRangeFromRaw($b->slot_start, $b->slot_end, (bool) $b->slot_overnight, $b->booking_date);
                        if ($tStart <= $bEnd && $bStart <= $tEnd) {
                            if ($b->room_id && !isset($bookedRoomMap[$b->room_id]) && in_array($b->room_id, $perSlotRoomIds)) {
                                $bookedRoomMap[$b->room_id] = [
                                    'booking_id'  => $b->booking_id,
                                    'guest_name'  => $b->guest_name,
                                    'room_number' => $b->room_number,
                                ];
                            }
                        }
                    }

                    $bookedCount  = count($bookedRoomMap);
                    $freeCount    = count($perSlotRoomIds) - $bookedCount;
                    $rowStatus    = $bookedCount === 0 ? 'free' : 'booked';

                    // Apply status filter
                    if ($statusFilter === 'free'   && $rowStatus !== 'free')   continue;
                    if ($statusFilter === 'booked' && $rowStatus !== 'booked') continue;

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
                        'total_count'  => count($perSlotRoomIds),
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

    /** Build slot time window from an Eloquent/stdClass slot row */
    private function slotRangeFromRow($slot, ?string $date): array
    {
        $date  = $date ?? Carbon::today()->toDateString();
        $start = Carbon::parse($date . ' ' . $slot->start_time);
        $end   = Carbon::parse($date . ' ' . $slot->end_time);
        if ($slot->is_overnight || $end <= $start) $end->addDay();
        return [$start, $end];
    }

    /** Build slot time window from raw string fields (used for booking overlap checks) */
    private function slotRangeFromRaw(string $startTime, string $endTime, bool $isOvernight, string $date): array
    {
        $start = Carbon::parse($date . ' ' . $startTime);
        $end   = Carbon::parse($date . ' ' . $endTime);
        if ($isOvernight || $end <= $start) $end->addDay();
        return [$start, $end];
    }
}
