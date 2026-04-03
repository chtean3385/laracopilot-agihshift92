<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Services\SlotConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $today = Carbon::today();

        try {
            $todayCheckins = Booking::with(['customer', 'room'])
                ->whereDate('check_in_date', $today)
                ->where('status', 'confirmed')
                ->get();
        } catch (\Exception $e) {
            $todayCheckins = collect();
        }

        try {
            $todayCheckouts = Booking::with(['customer', 'room'])
                ->whereDate('check_out_date', $today)
                ->where('status', 'checked_in')
                ->get();
        } catch (\Exception $e) {
            $todayCheckouts = collect();
        }

        try {
            $availableRooms   = Room::where('status', 'available')->count();
            $occupiedRooms    = Room::where('status', 'occupied')->count();
            $maintenanceRooms = Room::where('status', 'maintenance')->count();
            $totalRooms       = Room::count();
        } catch (\Exception $e) {
            $availableRooms = $occupiedRooms = $maintenanceRooms = $totalRooms = 0;
        }

        try {
            $monthRevenue = Payment::whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
                ->where('status', 'completed')
                ->sum('amount');
            $todayRevenue = Payment::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('amount');
        } catch (\Exception $e) {
            $monthRevenue = $todayRevenue = 0;
        }

        try {
            $pendingPayments = Booking::whereIn('payment_status', ['pending', 'partial'])->count();
        } catch (\Exception $e) {
            $pendingPayments = 0;
        }

        try {
            $totalCustomers    = Customer::count();
            $newCustomersMonth = Customer::whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
                ->count();
        } catch (\Exception $e) {
            $totalCustomers = $newCustomersMonth = 0;
        }

        try {
            $recentBookings = Booking::with(['customer' => fn($q) => $q->withTrashed(), 'room'])
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        } catch (\Exception $e) {
            $recentBookings = collect();
        }

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = Carbon::today()->subDays($i);
            $amount = 0;
            try {
                $amount = Payment::whereDate('created_at', $date)->where('status', 'completed')->sum('amount');
            } catch (\Exception $e) {}
            $weeklyRevenue[] = [
                'day'     => $date->format('D'),
                'date'    => $date->format('d'),
                'label'   => $date->format('D, d M'),
                'amount'  => $amount,
                'isToday' => $date->isToday(),
            ];
        }

        // --- Slot Availability Widget ---
        $hasSlotModule = false;
        $dashboardSlots = collect();
        $dashboardSlotAvailability = [];
        $slotWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY);

        if (Module::isEnabled('time-slot-pricing') || Module::isEnabled('hourly-pricing')) {
            try {
                if (request('slot_week')) {
                    $slotWeekStart = Carbon::parse(request('slot_week'))->startOfWeek(Carbon::MONDAY);
                }
                $slotWeekEnd = $slotWeekStart->copy()->addDays(6);
                $dashboardSlots = HotelTimeSlot::where('is_active', true)->ordered()->get();
                $slotRoomCount  = Room::where('pricing_type', 'per_slot')->count();

                if ($dashboardSlots->isNotEmpty() && $slotRoomCount > 0) {
                    $hasSlotModule = true;
                    $conflictSvc   = new SlotConflictService();
                    $slotRoomIds   = Room::where('pricing_type', 'per_slot')->pluck('id')->toArray();
                    $cur = $slotWeekStart->copy();
                    while ($cur <= $slotWeekEnd) {
                        $ds      = $cur->toDateString();
                        $dayData = [
                            'date'     => $ds,
                            'label'    => $cur->format('D'),
                            'sublabel' => $cur->format('d M'),
                            'isToday'  => $cur->isToday(),
                            'slots'    => [],
                        ];
                        foreach ($dashboardSlots as $slot) {
                            $conflicting = $conflictSvc->getConflictingRoomIds($slot, $ds);
                            $booked      = count(array_intersect($conflicting, $slotRoomIds));
                            $total       = $slotRoomCount;
                            $available   = $total - $booked;
                            $pct         = $total > 0 ? round($booked / $total * 100) : 0;
                            $color       = $pct >= 100 ? 'red' : ($pct >= 60 ? 'amber' : 'green');
                            $dayData['slots'][] = [
                                'slot_name' => $slot->name,
                                'time'      => $slot->start_time . '–' . $slot->end_time,
                                'available' => $available,
                                'booked'    => $booked,
                                'total'     => $total,
                                'pct'       => $pct,
                                'color'     => $color,
                            ];
                        }
                        $dashboardSlotAvailability[] = $dayData;
                        $cur->addDay();
                    }
                }
            } catch (\Exception $e) {
                $hasSlotModule = false;
            }
        }

        // --- Booking Calendar ---
        $calWeeks  = [];
        $calStart  = $today->copy()->startOfMonth();
        $prevMonth = $calStart->copy()->subMonth();
        $nextMonth = $calStart->copy()->addMonth();

        try {
            $calYear  = (int) request('cal_year',  $today->year);
            $calMonth = (int) request('cal_month', $today->month);

            if ($calMonth < 1)  { $calMonth = 12; $calYear--; }
            if ($calMonth > 12) { $calMonth = 1;  $calYear++; }

            $calStart     = Carbon::create($calYear, $calMonth, 1)->startOfDay();
            $calEnd       = $calStart->copy()->endOfMonth();
            $calGridStart = $calStart->copy()->startOfWeek(Carbon::SUNDAY);
            $calGridEnd   = $calEnd->copy()->endOfWeek(Carbon::SATURDAY);
            $prevMonth    = $calStart->copy()->subMonth();
            $nextMonth    = $calStart->copy()->addMonth();

            $calBookings = Booking::whereIn('status', ['confirmed', 'checked_in'])
                ->with(['customer', 'room'])
                ->where(function ($q) use ($calGridStart, $calGridEnd) {
                    $q->whereBetween('check_in_date', [$calGridStart->toDateString(), $calGridEnd->toDateString()])
                      ->orWhereBetween('check_out_date', [$calGridStart->toDateString(), $calGridEnd->toDateString()])
                      ->orWhere(function ($q2) use ($calGridStart, $calGridEnd) {
                          $q2->where('check_in_date', '<=', $calGridStart->toDateString())
                             ->where('check_out_date', '>=', $calGridEnd->toDateString());
                      });
                })
                ->get();

            $calDays = [];
            $cur = $calGridStart->copy();
            while ($cur <= $calGridEnd) {
                $ds = $cur->toDateString();

                $checkinBookings  = $calBookings->filter(fn($b) => $b->check_in_date->toDateString() === $ds);
                $checkoutBookings = $calBookings->filter(fn($b) => $b->check_out_date->toDateString() === $ds);
                $stayingBookings  = $calBookings->filter(
                    fn($b) => $b->check_in_date->toDateString() < $ds
                           && $b->check_out_date->toDateString() > $ds
                           && $b->status === 'checked_in'
                );

                $buildTooltip = function ($collection) {
                    return $collection->map(fn($b) => [
                        'name'   => $b->customer->name ?? '—',
                        'room'   => $b->room->room_number ?? '—',
                        'type'   => $b->room->type ?? '',
                        'status' => $b->status,
                    ])->values()->toArray();
                };

                $calDays[] = [
                    'date'             => $cur->copy(),
                    'ds'               => $ds,
                    'day'              => $cur->day,
                    'inMonth'          => $cur->month === $calMonth,
                    'isToday'          => $cur->isToday(),
                    'checkins'         => $checkinBookings->count(),
                    'checkouts'        => $checkoutBookings->count(),
                    'staying'          => $stayingBookings->count(),
                    'checkin_guests'   => $buildTooltip($checkinBookings),
                    'checkout_guests'  => $buildTooltip($checkoutBookings),
                    'staying_guests'   => $buildTooltip($stayingBookings),
                ];
                $cur->addDay();
            }
            $calWeeks = array_chunk($calDays, 7);
        } catch (\Exception $e) {
            $calWeeks = [];
        }

        return view('admin.dashboard', compact(
            'todayCheckins', 'todayCheckouts', 'availableRooms', 'occupiedRooms',
            'maintenanceRooms', 'totalRooms', 'monthRevenue', 'todayRevenue',
            'pendingPayments', 'totalCustomers', 'newCustomersMonth',
            'recentBookings', 'occupancyRate', 'weeklyRevenue',
            'calWeeks', 'calStart', 'prevMonth', 'nextMonth',
            'hasSlotModule', 'dashboardSlots', 'dashboardSlotAvailability', 'slotWeekStart'
        ));
    }

    public function daySummary()
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $date = request('date');
        if (!$date) return response()->json(['error' => 'Date required'], 422);

        try {
            $d = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date'], 422);
        }

        $bookings = Booking::with(['customer', 'room', 'timeSlot'])
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where(function ($q) use ($d) {
                $q->whereDate('check_in_date', $d)
                  ->orWhereDate('check_out_date', $d)
                  ->orWhere(function ($q2) use ($d) {
                      $q2->where('check_in_date', '<', $d)->where('check_out_date', '>', $d)->where('status', 'checked_in');
                  });
            })
            ->get();

        $checkins  = $bookings->filter(fn($b) => optional($b->check_in_date)->toDateString()  === $d && $b->status === 'confirmed');
        $checkouts = $bookings->filter(fn($b) => optional($b->check_out_date)->toDateString() === $d && $b->status === 'checked_in');
        $staying   = $bookings->filter(fn($b) => optional($b->check_in_date)->toDateString() < $d && optional($b->check_out_date)->toDateString() > $d && $b->status === 'checked_in');

        $fmt = fn($col) => $col->map(fn($b) => [
            'id'           => $b->id,
            'guest'        => $b->customer->name ?? '—',
            'room'         => $b->room->room_number ?? '—',
            'type'         => $b->room ? ucfirst($b->room->type) : '',
            'time_slot'    => $b->timeSlot?->name,
            'slot_time'    => $b->timeSlot ? ($b->timeSlot->start_time . '–' . $b->timeSlot->end_time) : null,
            'pricing_type' => $b->room?->pricing_type ?? 'per_night',
            'status'       => $b->status,
            'url'          => route('bookings.show', $b->id),
        ])->values();

        return response()->json([
            'date'      => Carbon::parse($d)->format('D, d M Y'),
            'checkins'  => $fmt($checkins),
            'checkouts' => $fmt($checkouts),
            'staying'   => $fmt($staying),
        ]);
    }

    public function checkAvailability()
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $date = request('date');
        if (!$date) return response()->json(['error' => 'Date required'], 422);

        try {
            $d = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date'], 422);
        }

        $rooms = Room::with(['bookings' => function ($q) use ($d) {
            $q->with('customer')
              ->whereIn('status', ['confirmed', 'checked_in'])
              ->where(function ($q2) use ($d) {
                  // Per-night: occupies if check_in_date <= date AND check_out_date > date (or >= for same-day)
                  $q2->where(function ($q3) use ($d) {
                      $q3->whereDate('check_in_date', '<=', $d)
                         ->whereDate('check_out_date', '>', $d);
                  })
                  // Per-night same-day: check_in_date = date AND check_out_date = date
                  ->orWhere(function ($q3) use ($d) {
                      $q3->whereDate('check_in_date', $d)
                         ->whereDate('check_out_date', $d);
                  })
                  // Per-slot / per-hour: occupies if booking_date = date
                  ->orWhere(function ($q3) use ($d) {
                      $q3->whereDate('booking_date', $d)
                         ->whereNotNull('booking_date');
                  });
              });
        }])->where('status', '!=', 'maintenance')->orderBy('room_number')->get();

        $available = [];
        $occupied  = [];

        foreach ($rooms as $room) {
            $activeBookings = $room->bookings;
            if ($activeBookings->isEmpty()) {
                $available[] = [
                    'id'           => $room->id,
                    'room_number'  => $room->room_number,
                    'type'         => ucfirst($room->type),
                    'pricing_type' => $room->pricing_type,
                    'booking_url'  => route('bookings.create', ['room_id' => $room->id, 'date' => $d]),
                ];
            } else {
                $booking = $activeBookings->first();
                $occupied[] = [
                    'id'           => $room->id,
                    'room_number'  => $room->room_number,
                    'type'         => ucfirst($room->type),
                    'pricing_type' => $room->pricing_type,
                    'guest'        => $booking->customer->name ?? '—',
                    'status'       => $booking->status,
                    'booking_url'  => route('bookings.show', $booking->id),
                ];
            }
        }

        return response()->json([
            'date'      => Carbon::parse($d)->format('D, d M Y'),
            'available' => $available,
            'occupied'  => $occupied,
        ]);
    }
}
