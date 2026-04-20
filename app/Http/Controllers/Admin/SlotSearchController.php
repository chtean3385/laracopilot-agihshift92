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

        // Determine which hotels to search across
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

        // ── QUERY 1: All rooms (per_slot) across available hotels ──
        $allRooms = DB::table('rooms')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('hotel_id')->orderBy('room_number')
            ->get(['id', 'hotel_id', 'room_number', 'pricing_type', 'type'])
            ->groupBy('hotel_id');

        // ── QUERY 2: All active slots across available hotels ──
        $allSlots = DB::table('hotel_time_slots')
            ->whereIn('hotel_id', $allHotelIds)
            ->where('is_active', true)
            ->orderBy('hotel_id')->orderBy('sort_order')->orderBy('start_time')
            ->get(['id', 'hotel_id', 'name', 'start_time', 'end_time', 'is_overnight'])
            ->groupBy('hotel_id');

        $flatRooms = $allRooms->flatten()->values();
        $flatSlots = $allSlots->flatten()->values();

        // Default initial view (no search yet)
        if (!$request->has('date_from')) {
            return view('admin.slot-search', compact(
                'flatRooms', 'flatSlots', 'availableHotels', 'isMultiHotel', 'allRooms', 'allSlots'
            ) + [
                'dateFrom'       => Carbon::today()->toDateString(),
                'dateTo'         => Carbon::today()->addDays(7)->toDateString(),
                'slotIds'        => [],
                'filterHotelIds' => [],
                'statusFilter'   => 'all',
                'matrix'         => null,
                'dates'          => [],
                'summary'        => null,
            ]);
        }

        // Filters
        $dateFrom       = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo         = $request->input('date_to',   Carbon::today()->addDays(7)->toDateString());
        $slotIds        = array_filter(array_map('intval', (array) $request->input('slot_ids', [])));
        $filterHotelIds = array_filter(array_map('intval', (array) $request->input('hotel_ids', [])));
        $statusFilter   = in_array($request->input('status'), ['free', 'booked']) ? $request->input('status') : 'all';

        try {
            $from = Carbon::parse($dateFrom);
            $to   = Carbon::parse($dateTo);
        } catch (\Exception $e) {
            $from = Carbon::today();
            $to   = Carbon::today()->addDays(7);
        }
        if ($to->lt($from))              $to = $from->copy()->addDays(7);
        if ($to->diffInDays($from) > 90) $to = $from->copy()->addDays(90);
        $dateFrom = $from->toDateString();
        $dateTo   = $to->toDateString();

        $searchHotelIds = $allHotelIds;
        if (!empty($filterHotelIds)) {
            $searchHotelIds = array_values(array_intersect($searchHotelIds, $filterHotelIds));
        }

        // Build date column headers
        $dates = [];
        foreach (CarbonPeriod::create($from, $to) as $d) {
            $dates[] = [
                'date'     => $d->toDateString(),
                'label'    => $d->format('D'),
                'sublabel' => $d->format('d M'),
                'isToday'  => $d->isToday(),
            ];
        }

        // Cache key versioned by booking changes
        $versionParts = array_map(fn($hid) => Cache::get(Module::cacheVersionKey($hid), 0), $searchHotelIds);
        $cacheKey = 'slot_search_' . md5(
            implode(',', $searchHotelIds) . '|' . $dateFrom . '|' . $dateTo . '|' .
            implode(',', $slotIds) . '|' . $statusFilter . '|' . implode(',', $versionParts)
        );

        $searchRooms = $allRooms->only($searchHotelIds);
        $searchSlots = $allSlots->only($searchHotelIds);

        $matrix = Cache::remember($cacheKey, 60, function () use (
            $from, $to, $slotIds, $searchHotelIds, $statusFilter,
            $searchRooms, $searchSlots
        ) {
            return $this->buildMatrix($from, $to, $slotIds, $searchHotelIds, $statusFilter, $searchRooms, $searchSlots);
        });

        $summary = $this->buildSummary($matrix);

        return view('admin.slot-search', compact(
            'flatRooms', 'flatSlots', 'availableHotels', 'isMultiHotel', 'allRooms', 'allSlots',
            'dateFrom', 'dateTo', 'slotIds', 'filterHotelIds', 'statusFilter',
            'matrix', 'dates', 'summary'
        ));
    }

    /**
     * Slot-centric merged matrix — slots as rows, dates as columns, all hotels combined.
     * Uses exactly 4 queries total (rooms + slots already loaded = Q1+Q2, then Q3+Q4 here).
     * Slots with the same name+time are merged across hotels (unique slot types).
     */
    private function buildMatrix(
        Carbon $from, Carbon $to,
        array  $slotIds,
        array  $searchHotelIds,
        string $statusFilter,
        $searchRooms,
        $searchSlots
    ): array {
        $lastDate = $to->toDateString();
        $prevDate = $from->copy()->subDay()->toDateString();

        // ── QUERY 3: Slot bookings with all needed data via JOINs (single query) ──
        $rawSlotBookings = DB::table('bookings as b')
            ->join('rooms as r',            'r.id',   '=', 'b.room_id')
            ->join('customers as c',         'c.id',   '=', 'b.customer_id')
            ->join('hotel_time_slots as ts', 'ts.id',  '=', 'b.time_slot_id')
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

        // ── QUERY 4: Whole-hotel bookings with guest name via JOIN (single query) ──
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

        // All per_slot rooms across all search hotels (flat)
        $allPerSlotRooms = $searchRooms->flatten()->values()->where('pricing_type', 'per_slot')->values();
        $totalPerSlot    = $allPerSlotRooms->count();
        $allPerSlotIds   = $allPerSlotRooms->pluck('id')->all();

        // Hotel → per_slot room IDs map (for whole-hotel blocking)
        $hotelRoomMap = [];
        foreach ($searchHotelIds as $hid) {
            $hotelRoomMap[$hid] = $searchRooms->get($hid, collect())
                ->where('pricing_type', 'per_slot')->pluck('id')->all();
        }

        // All flat slots; group into unique slot types by (name|start|end)
        $allFlatSlots  = $searchSlots->flatten()->values();
        $uniqueSlotTypes = $allFlatSlots->groupBy(fn($s) => $s->name . '||' . $s->start_time . '||' . $s->end_time);

        // Apply slot filter
        if (!empty($slotIds)) {
            $uniqueSlotTypes = $uniqueSlotTypes->filter(
                fn($group) => $group->pluck('id')->intersect($slotIds)->isNotEmpty()
            );
        }

        $period = CarbonPeriod::create($from, $to);
        $matrix = [];

        foreach ($uniqueSlotTypes as $key => $slotGroup) {
            [$slotName, $slotStart, $slotEnd] = explode('||', $key);
            $firstSlot    = $slotGroup->first();
            $isOvernight  = (bool) $firstSlot->is_overnight;
            $groupSlotIds = $slotGroup->pluck('id')->all();

            $slotRow = [
                'slot_name' => $slotName,
                'slot_time' => $slotStart . '–' . $slotEnd,
                'dates'     => [],
            ];

            foreach ($period as $date) {
                $ds     = $date->toDateString();
                $prevDs = $date->copy()->subDay()->toDateString();

                // Slot time window for this date
                [$tStart, $tEnd] = $this->slotRangeFromRaw($slotStart, $slotEnd, $isOvernight, $ds);

                // Whole-hotel bookings for this day across all hotels → block all their per_slot rooms
                $whBlockedRoomIds = [];
                $whInfoList       = [];
                foreach ($searchHotelIds as $hid) {
                    $whForHotel = $rawWhBookings->get($hid, collect());
                    $whForDay   = $whForHotel->first(
                        fn($b) => $b->check_in_date <= $ds && $b->check_out_date > $ds
                    );
                    if ($whForDay) {
                        foreach ($hotelRoomMap[$hid] ?? [] as $roomId) {
                            $whBlockedRoomIds[$roomId] = true;
                        }
                        $whInfoList[] = [
                            'booking_id'    => $whForDay->booking_id,
                            'booking_num'   => $whForDay->booking_number,
                            'guest_name'    => $whForDay->guest_name,
                            'check_in_date' => $whForDay->check_in_date,
                            'check_out_date'=> $whForDay->check_out_date,
                        ];
                    }
                }

                // Per-slot bookings overlapping this slot window
                $bookedRoomIds   = [];
                $bookedRoomDetails = [];
                foreach ($searchHotelIds as $hid) {
                    $dayBks = ($rawSlotBookings->get($hid, collect()))->filter(
                        fn($b) => $b->booking_date === $ds || $b->booking_date === $prevDs
                    );
                    foreach ($dayBks as $b) {
                        if (!in_array($b->time_slot_id, $groupSlotIds)) continue;
                        if ($b->pricing_type !== 'per_slot') continue;
                        [$bStart, $bEnd] = $this->slotRangeFromRaw($b->slot_start, $b->slot_end, (bool) $b->slot_overnight, $b->booking_date);
                        if ($tStart <= $bEnd && $bStart <= $tEnd && $b->room_id && !isset($bookedRoomIds[$b->room_id])) {
                            $bookedRoomIds[$b->room_id] = true;
                            $bookedRoomDetails[] = [
                                'room_number' => $b->room_number,
                                'guest_name'  => $b->guest_name,
                                'booking_id'  => $b->booking_id,
                            ];
                        }
                    }
                }

                // Add whole-hotel blocked rooms
                foreach ($whBlockedRoomIds as $roomId => $_) {
                    if (!isset($bookedRoomIds[$roomId])) {
                        $bookedRoomIds[$roomId] = true;
                        $room = $allPerSlotRooms->firstWhere('id', $roomId);
                        if ($room) {
                            $guestLabel = !empty($whInfoList) ? $whInfoList[0]['guest_name'] . ' (WH)' : 'Whole Hotel';
                            $bookedRoomDetails[] = [
                                'room_number' => $room->room_number,
                                'guest_name'  => $guestLabel,
                                'booking_id'  => $whInfoList[0]['booking_id'] ?? null,
                            ];
                        }
                    }
                }

                $bookedCount = count($bookedRoomIds);
                $freeRooms   = $allPerSlotRooms
                    ->filter(fn($r) => !isset($bookedRoomIds[$r->id]))
                    ->pluck('room_number')->values()->all();
                $rowStatus   = $bookedCount === 0 ? 'free' : 'booked';

                // Status filter
                if ($statusFilter === 'free'   && $rowStatus !== 'free')   continue;
                if ($statusFilter === 'booked' && $rowStatus !== 'booked') continue;

                $pct   = $totalPerSlot > 0 ? round($bookedCount / $totalPerSlot * 100) : 0;
                $color = $pct >= 100 ? 'red' : ($pct >= 60 ? 'amber' : 'green');

                $slotRow['dates'][$ds] = [
                    'total'           => $totalPerSlot,
                    'available'       => count($freeRooms),
                    'booked_count'    => $bookedCount,
                    'pct'             => $pct,
                    'color'           => $color,
                    'booked_rooms'    => $bookedRoomDetails,
                    'free_rooms'      => $freeRooms,
                    'whole_hotel_list'=> $whInfoList,
                    'row_status'      => $rowStatus,
                ];
            }

            if (!empty($slotRow['dates'])) {
                $matrix[] = $slotRow;
            }
        }

        return $matrix;
    }

    private function buildSummary(array $matrix): array
    {
        $total = $free = $booked = $wh = 0;
        foreach ($matrix as $slotRow) {
            foreach ($slotRow['dates'] as $dateData) {
                $total++;
                if (!empty($dateData['whole_hotel_list'])) {
                    $wh++;
                    $booked++;
                } elseif ($dateData['row_status'] === 'free') {
                    $free++;
                } else {
                    $booked++;
                }
            }
        }
        return compact('total', 'free', 'booked', 'wh');
    }

    private function slotRangeFromRaw(string $startTime, string $endTime, bool $isOvernight, string $date): array
    {
        $start = Carbon::parse($date . ' ' . $startTime);
        $end   = Carbon::parse($date . ' ' . $endTime);
        if ($isOvernight || $end <= $start) $end->addDay();
        return [$start, $end];
    }
}
