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

        // Module guard: use isEnabledForHotel with parent fallback
        if (!Module::isEnabledForHotel('slot-search-engine', $currentHotelId)) {
            return redirect()->route('dashboard')->with('warning', 'Slot Search Engine is not enabled for your hotel.');
        }

        // Determine available hotels for this session
        $hotelOptions = session('crm_hotel_options', []);
        $availableHotels = collect();
        if (!empty($hotelOptions)) {
            $hotelIds = array_column($hotelOptions, 'hotel_id');
            $availableHotels = Hotel::whereIn('id', $hotelIds)
                ->where('status', 'active')
                ->get(['id', 'name']);
        }
        if ($availableHotels->isEmpty()) {
            $availableHotels = Hotel::where('id', $currentHotelId)->get(['id', 'name']);
        }

        $isMultiHotel = $availableHotels->count() > 1;

        // Default filter values
        $dateFrom    = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo      = $request->input('date_to',   Carbon::today()->addDays(6)->toDateString());
        $slotIds     = array_filter(array_map('intval', (array) $request->input('slot_ids', [])));
        $filterHotelIds = array_filter(array_map('intval', (array) $request->input('hotel_ids', [])));
        $statusFilter = $request->input('status', 'all');

        // Clamp date range to 90 days
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

        // Which hotels to actually search (filtered against available hotels)
        $searchHotelIds = $availableHotels->pluck('id')->all();
        if (!empty($filterHotelIds)) {
            $searchHotelIds = array_values(array_intersect($searchHotelIds, $filterHotelIds));
        }

        // All per-slot rooms across search hotels (for filter UI)
        $allRooms = Room::whereIn('hotel_id', $availableHotels->pluck('id'))
            ->where('pricing_type', 'per_slot')
            ->where('status', '!=', 'maintenance')
            ->orderBy('hotel_id')->orderBy('room_number')
            ->get();

        // All active slots across search hotels (for filter UI)
        $allSlots = HotelTimeSlot::whereIn('hotel_id', $availableHotels->pluck('id'))
            ->where('is_active', true)
            ->ordered()
            ->get();

        if (!$request->has('date_from')) {
            return view('admin.slot-search', compact(
                'allSlots', 'allRooms', 'availableHotels', 'isMultiHotel',
                'dateFrom', 'dateTo', 'slotIds', 'filterHotelIds', 'statusFilter'
            ));
        }

        // Build cache key
        $versionParts = array_map(
            fn($hid) => Cache::get(Module::cacheVersionKey($hid), 0),
            $searchHotelIds
        );
        $cacheKey = 'slot_search_' . md5(
            implode(',', $searchHotelIds) . '|' . $dateFrom . '|' . $dateTo . '|' .
            implode(',', $slotIds) . '|' . $statusFilter . '|' . implode(',', $versionParts)
        );

        $matrix = Cache::remember($cacheKey, 60, function () use (
            $from, $to, $slotIds, $searchHotelIds, $statusFilter, $availableHotels, $allRooms, $allSlots
        ) {
            return $this->buildMatrix($from, $to, $slotIds, $searchHotelIds, $statusFilter, $availableHotels, $allRooms, $allSlots);
        });

        $summary = $this->buildSummary($matrix);

        return view('admin.slot-search', compact(
            'allSlots', 'allRooms', 'availableHotels', 'isMultiHotel',
            'dateFrom', 'dateTo', 'slotIds', 'filterHotelIds', 'statusFilter',
            'matrix', 'summary'
        ));
    }

    private function buildMatrix(
        Carbon $from, Carbon $to,
        array  $slotIds,
        array  $searchHotelIds,
        string $statusFilter,
        $availableHotels,
        $allRooms,
        $allSlots
    ): array {
        $period   = CarbonPeriod::create($from, $to);
        $prevDate = $from->copy()->subDay()->toDateString();
        $lastDate = $to->toDateString();

        // QUERY 1: All rooms for search hotels (all pricing types — per_night/per_hour show as N/A)
        $hotelRooms = Room::whereIn('hotel_id', $searchHotelIds)
            ->where('status', '!=', 'maintenance')
            ->orderBy('room_number')
            ->get()
            ->groupBy('hotel_id');

        // QUERY 2: All active slots for search hotels
        $hotelSlots = HotelTimeSlot::whereIn('hotel_id', $searchHotelIds)
            ->where('is_active', true)
            ->ordered()
            ->get()
            ->groupBy('hotel_id');

        // QUERY 3: All slot bookings in date range (incl. prev day for overnight overlap)
        $allBookings = Booking::with(['room:id,room_number,hotel_id,pricing_type', 'customer:id,name', 'timeSlot'])
            ->whereNotNull('time_slot_id')
            ->whereIn('hotel_id', $searchHotelIds)
            ->whereIn('booking_date', $this->dateRange($from->copy()->subDay(), $to))
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->get()
            ->groupBy('hotel_id');

        // QUERY 4: Whole-hotel bookings overlapping date range
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
            $hotel    = $availableHotels->firstWhere('id', $hotelId);
            $rooms    = $hotelRooms->get($hotelId, collect());
            $slots    = $hotelSlots->get($hotelId, collect());
            $bookings = $allBookings->get($hotelId, collect());
            $whForHotel = $whBookings->get($hotelId, collect());

            if (!$slots->isNotEmpty() || !$rooms->isNotEmpty()) {
                continue;
            }

            // Filter slots by requested slot_ids (if any filter applied)
            $targetSlots = !empty($slotIds) ? $slots->whereIn('id', $slotIds) : $slots;
            if ($targetSlots->isEmpty()) continue;

            $perSlotRooms = $rooms->where('pricing_type', 'per_slot');
            $perSlotRoomIds = $perSlotRooms->pluck('id')->all();

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

                foreach ($period as $date) {
                    $ds      = $date->toDateString();
                    $prevDs  = $date->copy()->subDay()->toDateString();

                    // Check whole-hotel booking for this date
                    $whForDay = $whForHotel->first(
                        fn($b) => $b->check_in_date->toDateString() <= $ds && $b->check_out_date->toDateString() > $ds
                    );

                    if ($whForDay) {
                        $slotData['dates'][$ds] = [
                            'whole_hotel' => [
                                'booking_id'  => $whForDay->id,
                                'booking_num' => $whForDay->booking_number,
                                'guest_name'  => $whForDay->customer->name ?? 'Guest',
                            ],
                            'rooms' => [],
                        ];
                        continue;
                    }

                    // Collect bookings for this date (today + prev day for overnight overlap)
                    $dayBookings = $bookings->filter(
                        fn($b) => $b->booking_date->toDateString() === $ds || $b->booking_date->toDateString() === $prevDs
                    );

                    [$tStart, $tEnd] = $this->slotRange($slot, $ds);

                    $bookedRoomMap = [];
                    foreach ($dayBookings as $booking) {
                        $bSlot = $booking->timeSlot;
                        if (!$bSlot) continue;
                        [$bStart, $bEnd] = $this->slotRange($bSlot, $booking->booking_date->toDateString());
                        if ($tStart <= $bEnd && $bStart <= $tEnd) {
                            $rid = $booking->room_id;
                            if ($rid && !isset($bookedRoomMap[$rid])) {
                                $bookedRoomMap[$rid] = [
                                    'booking_id'  => $booking->id,
                                    'guest_name'  => $booking->customer?->name ?? 'Guest',
                                    'room_number' => $booking->room?->room_number ?? '—',
                                ];
                            }
                        }
                    }

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

                    // Apply status filter
                    $bookedCount   = count($bookedRoomMap);
                    $perSlotTotal  = count($perSlotRoomIds);
                    $rowStatus     = $bookedCount === 0 ? 'available'
                        : ($bookedCount >= $perSlotTotal && $perSlotTotal > 0 ? 'full' : 'partial');

                    if ($statusFilter !== 'all' && $this->mapStatus($rowStatus) !== $statusFilter) {
                        continue;
                    }

                    $slotData['dates'][$ds] = [
                        'whole_hotel'  => null,
                        'rooms'        => $roomCells,
                        'row_status'   => $rowStatus,
                        'booked_count' => $bookedCount,
                        'free_count'   => $perSlotTotal - $bookedCount,
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

    private function mapStatus(string $rowStatus): string
    {
        return match($rowStatus) {
            'available' => 'available',
            'full'      => 'full',
            'partial'   => 'partial',
            default     => 'all',
        };
    }

    private function buildSummary(array $matrix): array
    {
        $total = $available = $partial = $full = $wh = 0;
        foreach ($matrix as $hotelData) {
            foreach ($hotelData['slots'] as $slotData) {
                foreach ($slotData['dates'] as $dateData) {
                    $total++;
                    if ($dateData['whole_hotel']) {
                        $wh++;
                    } else {
                        match($dateData['row_status'] ?? 'available') {
                            'available' => $available++,
                            'partial'   => $partial++,
                            'full'      => $full++,
                            default     => $available++,
                        };
                    }
                }
            }
        }
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
