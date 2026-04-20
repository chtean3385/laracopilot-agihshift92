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

        $currentHotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        if (!$currentHotelId) {
            return redirect()->route('dashboard')->with('warning', 'No hotel context available.');
        }

        if (!Module::isEnabledForHotel('slot-search-engine', $currentHotelId)) {
            return redirect()->route('dashboard')->with('warning', 'Slot Search Engine is not enabled for your hotel.');
        }

        // Determine searchable hotels from session
        $hotelOptions    = session('crm_hotel_options', []);
        $availableHotels = collect();
        if (!empty($hotelOptions)) {
            $ids             = array_column($hotelOptions, 'hotel_id');
            $availableHotels = Hotel::whereIn('id', $ids)->where('status', 'active')->get(['id', 'name']);
        }
        if ($availableHotels->isEmpty()) {
            $availableHotels = Hotel::where('id', $currentHotelId)->get(['id', 'name']);
        }

        $isMultiHotel = $availableHotels->count() > 1;
        $allHotelIds  = $availableHotels->pluck('id')->all();

        // ── Q1: All rooms across available hotels ──
        $allRooms = DB::table('rooms')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('hotel_id')->orderBy('room_number')
            ->get(['id', 'hotel_id', 'room_number', 'pricing_type', 'type'])
            ->groupBy('hotel_id');

        // ── Q2: All active slots across available hotels ──
        $allSlots = DB::table('hotel_time_slots')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('is_active', true)
            ->orderBy('hotel_id')->orderBy('sort_order')->orderBy('start_time')
            ->get(['id', 'hotel_id', 'name', 'start_time', 'end_time', 'is_overnight'])
            ->groupBy('hotel_id');

        $flatRooms = $allRooms->flatten()->values();
        $flatSlots = $allSlots->flatten()->values();

        // Default landing (no search submitted yet)
        if (!$request->has('date_from')) {
            return view('admin.slot-search', [
                'flatRooms'       => $flatRooms,
                'flatSlots'       => $flatSlots,
                'availableHotels' => $availableHotels,
                'isMultiHotel'    => $isMultiHotel,
                'allRooms'        => $allRooms,
                'allSlots'        => $allSlots,
                'dateFrom'        => Carbon::today()->toDateString(),
                'dateTo'          => Carbon::today()->addDays(6)->toDateString(),
                'slotIds'         => [],
                'filterHotelIds'  => [],
                'statusFilter'    => 'all',
                'matrix'          => null,
                'slotColumns'     => [],
                'kpi'             => null,
            ]);
        }

        // Filters
        $dateFrom       = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo         = $request->input('date_to',   Carbon::today()->addDays(6)->toDateString());
        $slotIds        = array_filter(array_map('intval', (array) $request->input('slot_ids', [])));
        $filterHotelIds = array_filter(array_map('intval', (array) $request->input('hotel_ids', [])));
        $statusFilter   = in_array($request->input('status'), ['free', 'booked', 'partial'])
                            ? $request->input('status') : 'all';

        try { $from = Carbon::parse($dateFrom); } catch (\Exception $e) { $from = Carbon::today(); }
        try { $to   = Carbon::parse($dateTo);   } catch (\Exception $e) { $to = $from->copy()->addDays(6); }
        if ($to->lt($from))              $to = $from->copy()->addDays(6);
        if ($to->diffInDays($from) > 90) $to = $from->copy()->addDays(90);
        $dateFrom = $from->toDateString();
        $dateTo   = $to->toDateString();

        $searchHotelIds = $allHotelIds;
        if (!empty($filterHotelIds)) {
            $searchHotelIds = array_values(array_intersect($searchHotelIds, $filterHotelIds));
        }

        $searchRooms = $allRooms->only($searchHotelIds);
        $searchSlots = $allSlots->only($searchHotelIds);

        // Cache keyed by version + filters
        $versionParts = array_map(fn($hid) => Cache::get(Module::cacheVersionKey($hid), 0), $searchHotelIds);
        $cacheKey = 'slot_search_v2_' . md5(
            implode(',', $searchHotelIds) . '|' . $dateFrom . '|' . $dateTo . '|' .
            implode(',', $slotIds) . '|' . $statusFilter . '|' . implode(',', $versionParts)
        );

        $result = Cache::remember($cacheKey, 60, function () use (
            $from, $to, $slotIds, $searchHotelIds, $statusFilter,
            $searchRooms, $searchSlots, $availableHotels
        ) {
            return $this->buildMatrix(
                $from, $to, $slotIds, $searchHotelIds, $statusFilter,
                $searchRooms, $searchSlots, $availableHotels
            );
        });

        return view('admin.slot-search', [
            'flatRooms'       => $flatRooms,
            'flatSlots'       => $flatSlots,
            'availableHotels' => $availableHotels,
            'isMultiHotel'    => $isMultiHotel,
            'allRooms'        => $allRooms,
            'allSlots'        => $allSlots,
            'dateFrom'        => $dateFrom,
            'dateTo'          => $dateTo,
            'slotIds'         => $slotIds,
            'filterHotelIds'  => $filterHotelIds,
            'statusFilter'    => $statusFilter,
            'matrix'          => $result['rows'],
            'slotColumns'     => $result['columns'],
            'kpi'             => $result['kpi'],
        ]);
    }

    /**
     * Build hotel-rows × slot-columns matrix.
     * Each cell (hotel × slot) has: worst_free, total_rooms, color, per-date breakdown.
     * Returns: ['columns'=>[...slot types], 'rows'=>[...hotel rows], 'kpi'=>[...]]
     */
    private function buildMatrix(
        Carbon $from, Carbon $to,
        array  $slotIds,
        array  $searchHotelIds,
        string $statusFilter,
        $searchRooms,
        $searchSlots,
        $availableHotels
    ): array {
        $lastDate = $to->toDateString();
        $prevDate = $from->copy()->subDay()->toDateString();
        $period   = CarbonPeriod::create($from, $to);
        $dateCount = $from->diffInDays($to) + 1;

        // ── Q3: Slot bookings (one query for all hotels + rooms + guest) ──
        $rawSlotBookings = DB::table('bookings as b')
            ->join('rooms as r',            'r.id',  '=', 'b.room_id')
            ->join('customers as c',         'c.id',  '=', 'b.customer_id')
            ->join('hotel_time_slots as ts', 'ts.id', '=', 'b.time_slot_id')
            ->whereIn('b.hotel_id', $searchHotelIds)
            ->whereNotNull('b.time_slot_id')
            ->whereBetween('b.booking_date', [$prevDate, $lastDate])
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

        // ── Q4: Whole-hotel bookings ──
        $rawWhBookings = DB::table('bookings as b')
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->where('b.is_whole_hotel', true)
            ->whereIn('b.hotel_id', $searchHotelIds)
            ->whereNotIn('b.status', ['cancelled', 'checked_out'])
            ->where('b.check_in_date', '<=', $lastDate)
            ->where('b.check_out_date', '>', $prevDate)
            ->get([
                'b.id as booking_id', 'b.hotel_id', 'b.booking_number',
                'b.check_in_date', 'b.check_out_date',
                'c.name as guest_name',
            ])
            ->groupBy('hotel_id');

        // ── Derive unique slot columns (across all hotels) ──
        $allFlatSlots   = $searchSlots->flatten()->values();
        $uniqueSlotTypes = $allFlatSlots->groupBy(fn($s) => $s->name . '||' . $s->start_time . '||' . $s->end_time);
        if (!empty($slotIds)) {
            $uniqueSlotTypes = $uniqueSlotTypes->filter(
                fn($g) => $g->pluck('id')->intersect($slotIds)->isNotEmpty()
            );
        }

        // Build column meta list (ordered)
        $slotColumns = [];
        foreach ($uniqueSlotTypes as $key => $g) {
            $f = $g->first();
            $slotColumns[] = ['key' => $key, 'name' => $f->name, 'time' => $f->start_time . '–' . $f->end_time];
        }

        // ── Build per-hotel rows ──
        $rows           = [];
        $kpiTotalRoomDays  = 0;
        $kpiBookedRoomDays = 0;

        foreach ($searchHotelIds as $hid) {
            $hotel = $availableHotels->firstWhere('id', $hid);
            if (!$hotel) continue;

            $hotelPerSlotRooms = $searchRooms->get($hid, collect())
                ->where('pricing_type', 'per_slot')->values();
            $hotelAllSlots = $searchSlots->get($hid, collect());
            $roomCount     = $hotelPerSlotRooms->count();

            $hotelSlotMap = [];     // slot_id => slot object
            foreach ($hotelAllSlots as $s) { $hotelSlotMap[$s->id] = $s; }

            // Whole-hotel bookings for this hotel (pre-indexed by date)
            $whForHotel = $rawWhBookings->get($hid, collect());
            $slotBksForHotel = $rawSlotBookings->get($hid, collect());

            // Per-slot room ID → room_number map
            $roomMap = []; // room_id => room_number
            foreach ($hotelPerSlotRooms as $r) { $roomMap[$r->id] = $r->room_number; }
            $hotelRoomIds = array_keys($roomMap);

            $hotelRow = [
                'hotel_id'    => $hid,
                'hotel_name'  => $hotel->name,
                'rooms_count' => $roomCount,
                'rooms'       => $hotelPerSlotRooms->map(fn($r) => [
                    'id'     => $r->id,
                    'number' => $r->room_number,
                    'type'   => $r->type,
                ])->values()->all(),
                'slots'       => [],
                'is_wh_any'   => false,
            ];

            foreach ($uniqueSlotTypes as $key => $slotGroup) {
                [$slotName, $slotStart, $slotEnd] = explode('||', $key);
                $isOvernight  = (bool) $slotGroup->first()->is_overnight;
                $groupSlotIds = $slotGroup->pluck('id')->all();

                // Does this hotel have this slot?
                $hotelHasSlot = $hotelAllSlots->pluck('id')->intersect($groupSlotIds)->isNotEmpty();

                $totalRoomDays  = $roomCount * $dateCount;
                $bookedRoomDays = 0;
                $worstFree      = $roomCount;   // minimum free rooms on any day
                $allDatesWH     = false;
                $dateBreakdown  = [];

                foreach ($period as $date) {
                    $ds     = $date->toDateString();
                    $prevDs = $date->copy()->subDay()->toDateString();

                    // Whole-hotel check for this date
                    $whForDay = $whForHotel->first(
                        fn($b) => $b->check_in_date <= $ds && $b->check_out_date > $ds
                    );

                    if ($whForDay) {
                        // All rooms blocked
                        $bookedRoomDays += $roomCount;
                        $worstFree = 0;
                        $dateBreakdown[$ds] = [
                            'available'   => 0,
                            'booked_count'=> $roomCount,
                            'booked_rooms'=> array_values(array_map(fn($rn) => [
                                'room_number' => $rn,
                                'guest_name'  => $whForDay->guest_name . ' (WH)',
                                'whole_hotel' => true,
                            ], $roomMap)),
                            'free_rooms'  => [],
                            'whole_hotel' => true,
                            'wh_guest'    => $whForDay->guest_name,
                        ];
                        $hotelRow['is_wh_any'] = true;
                        continue;
                    }

                    // Slot window
                    [$tStart, $tEnd] = $this->slotRange($slotStart, $slotEnd, $isOvernight, $ds);

                    // Find booked rooms for this slot + date
                    $bookedIds = [];
                    $bookedDetails = [];
                    $dayBks = $slotBksForHotel->filter(
                        fn($b) => $b->booking_date === $ds || $b->booking_date === $prevDs
                    );
                    foreach ($dayBks as $b) {
                        if (!in_array($b->time_slot_id, $groupSlotIds)) continue;
                        if ($b->pricing_type !== 'per_slot') continue;
                        if (!in_array($b->room_id, $hotelRoomIds)) continue;
                        [$bStart, $bEnd] = $this->slotRange($b->slot_start, $b->slot_end, (bool) $b->slot_overnight, $b->booking_date);
                        if ($tStart <= $bEnd && $bStart <= $tEnd && !isset($bookedIds[$b->room_id])) {
                            $bookedIds[$b->room_id] = true;
                            $bookedDetails[] = [
                                'room_number' => $b->room_number,
                                'guest_name'  => $b->guest_name,
                                'booking_id'  => $b->booking_id,
                                'whole_hotel' => false,
                            ];
                        }
                    }

                    $booked    = count($bookedIds);
                    $available = $roomCount - $booked;
                    $freeRooms = array_values(array_map(
                        fn($rid) => $roomMap[$rid],
                        array_filter($hotelRoomIds, fn($rid) => !isset($bookedIds[$rid]))
                    ));

                    $bookedRoomDays += $booked;
                    $worstFree = min($worstFree, $available);

                    $dateBreakdown[$ds] = [
                        'available'    => $available,
                        'booked_count' => $booked,
                        'booked_rooms' => $bookedDetails,
                        'free_rooms'   => $freeRooms,
                        'whole_hotel'  => false,
                    ];
                }

                $pct = $totalRoomDays > 0 ? round($bookedRoomDays / $totalRoomDays * 100) : 0;
                $color = $pct >= 100 ? 'red'
                       : ($pct > 0  ? 'amber'
                       : 'green');

                $rowStatus = $worstFree === $roomCount ? 'free'
                           : ($worstFree === 0 ? 'booked' : 'partial');

                // Apply status filter
                $passesFilter = match ($statusFilter) {
                    'free'    => $rowStatus === 'free',
                    'booked'  => $rowStatus === 'booked',
                    'partial' => $rowStatus === 'partial',
                    default   => true,
                };

                $kpiTotalRoomDays  += $totalRoomDays;
                $kpiBookedRoomDays += $bookedRoomDays;

                $hotelRow['slots'][$key] = [
                    'slot_name'    => $slotName,
                    'slot_time'    => $slotStart . '–' . $slotEnd,
                    'total_rooms'  => $roomCount,
                    'worst_free'   => $worstFree,
                    'worst_booked' => $roomCount - $worstFree,
                    'pct'          => $pct,
                    'color'        => $color,
                    'row_status'   => $rowStatus,
                    'passes_filter'=> $passesFilter,
                    'has_slot'     => $hotelHasSlot,
                    'dates'        => $dateBreakdown,
                ];
            }

            // Apply hotel-level filter: skip hotel if NO slot passes
            $anyPasses = count(array_filter($hotelRow['slots'], fn($s) => $s['passes_filter'])) > 0;
            if ($statusFilter !== 'all' && !$anyPasses) continue;

            $rows[] = $hotelRow;
        }

        $kpi = [
            'total_hotels'    => count($rows),
            'total_room_days' => $kpiTotalRoomDays,
            'booked_room_days'=> $kpiBookedRoomDays,
            'free_room_days'  => $kpiTotalRoomDays - $kpiBookedRoomDays,
            'pct_booked'      => $kpiTotalRoomDays > 0 ? round($kpiBookedRoomDays / $kpiTotalRoomDays * 100) : 0,
        ];

        return ['columns' => $slotColumns, 'rows' => $rows, 'kpi' => $kpi];
    }

    private function slotRange(string $startTime, string $endTime, bool $isOvernight, string $date): array
    {
        $start = Carbon::parse($date . ' ' . $startTime);
        $end   = Carbon::parse($date . ' ' . $endTime);
        if ($isOvernight || $end <= $start) $end->addDay();
        return [$start, $end];
    }
}
