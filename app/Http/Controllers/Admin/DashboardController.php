<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HotelFullAlertMail;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\DashboardPreference;
use App\Models\Hotel;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Models\Payment;
use App\Models\RestaurantBill;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Services\PermissionService;
use App\Services\SlotConflictService;
use App\Support\AnalyticsCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $today = Carbon::today();
        $hotelId = (int) session('crm_hotel_id');

        // ── Per-role data scope ────────────────────────────────────────────────
        $isRestaurantOnly = PermissionService::isRestaurantOnly();

        // ── Hotel-wide data (skipped for restaurant-only users) ──────────────
        $todayCheckins    = collect();
        $todayCheckouts   = collect();
        $availableRooms   = 0;
        $occupiedRooms    = 0;
        $maintenanceRooms = 0;
        $totalRooms       = 0;
        $dirtyRooms       = 0;
        $monthRevenue     = 0;
        $todayRevenue     = 0;
        $pendingPayments  = 0;
        $websitePendingCount = 0;
        $otaPendingCount  = 0;
        $lowStockCount    = 0;
        $totalCustomers   = 0;
        $newCustomersMonth = 0;
        $recentBookings   = collect();
        $occupancyRate    = 0;
        $weeklyRevenue    = [];
        $hotelFull        = false;
        $dirtyRoomsList   = [];
        $showAgenda       = false;

        $hasSlotModule = false;
        $dashboardSlots = collect();
        $dashboardSlotAvailability = [];
        $slotWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY);

        $calWeeks  = [];
        $calStart  = $today->copy()->startOfMonth();
        $prevMonth = $calStart->copy()->subMonth();
        $nextMonth = $calStart->copy()->addMonth();

        if (!$isRestaurantOnly) {
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
                // Single query for all room status counts (was 4 separate COUNT queries)
                $roomCounts = Room::selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'dirty'       THEN 1 ELSE 0 END) as dirty,
                    SUM(CASE WHEN status = 'available'   THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'occupied'    THEN 1 ELSE 0 END) as occupied
                ")->first();
                $totalRooms       = (int)($roomCounts->total ?? 0);
                $maintenanceRooms = (int)($roomCounts->maintenance ?? 0);
                $dirtyRooms       = (int)($roomCounts->dirty ?? 0);
                $nonMaintenanceRooms = $totalRooms - $maintenanceRooms;
                $wholeHotelActiveToday = Booking::where('is_whole_hotel', true)
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->where('check_in_date', '<=', $today->toDateString())
                    ->where('check_out_date', '>', $today->toDateString())
                    ->exists();
                if ($wholeHotelActiveToday) {
                    $occupiedRooms  = $nonMaintenanceRooms;
                    $availableRooms = 0;
                } else {
                    $availableRooms = (int)($roomCounts->available ?? 0);
                    $occupiedRooms  = (int)($roomCounts->occupied ?? 0);
                }
            } catch (\Exception $e) {
                $availableRooms = $occupiedRooms = $maintenanceRooms = $totalRooms = $dirtyRooms = 0;
            }

            try {
                // Single query for both month and today revenue (was 2 separate SUM queries)
                $revenueStats = Payment::where('status', 'completed')
                    ->whereYear('created_at', $today->year)
                    ->whereMonth('created_at', $today->month)
                    ->selectRaw(
                        "SUM(amount) as month_total,
                         SUM(CASE WHEN DATE(created_at) = ? THEN amount ELSE 0 END) as today_total",
                        [$today->toDateString()]
                    )
                    ->first();
                $monthRevenue = (float)($revenueStats->month_total ?? 0);
                $todayRevenue = (float)($revenueStats->today_total ?? 0);
            } catch (\Exception $e) {
                $monthRevenue = $todayRevenue = 0;
            }

            try {
                $pendingPayments = Booking::whereIn('payment_status', ['pending', 'partial'])->count();
            } catch (\Exception $e) {
                $pendingPayments = 0;
            }

            try {
                $websitePendingCount = Module::isEnabled('booking-widget')
                    ? Booking::where('status', 'website_pending')->count()
                    : 0;
            } catch (\Exception $e) {
                $websitePendingCount = 0;
            }

            try {
                $otaPendingCount = Module::isEnabled('ota_whatsapp_sync')
                    ? \App\Models\OtaImportedBooking::where('hotel_id', $hotelId)
                        ->where('status', 'pending')
                        ->count()
                    : 0;
            } catch (\Exception $e) {
                $otaPendingCount = 0;
            }

            try {
                $lowStockCount = Module::isEnabled('inventory')
                    ? \App\Models\InventoryItem::where('is_active', true)
                        ->whereRaw('current_stock <= reorder_level AND reorder_level > 0')
                        ->count()
                    : 0;
            } catch (\Exception $e) {
                $lowStockCount = 0;
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
                    ->take(5)
                    ->get();
            } catch (\Exception $e) {
                $recentBookings = collect();
            }

            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

            // ── Hotel Full Alert ──────────────────────────────────────────────
            $hotelFull = $totalRooms > 0 && $availableRooms === 0;
            if ($hotelFull) {
                $cacheKey = "hotel_full_alert_{$hotelId}_" . now()->toDateString();
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->endOfDay()->diffInSeconds(now()));
                    try {
                        $hotel = Hotel::find($hotelId);
                        if ($hotel && $hotel->email) {
                            Mail::to($hotel->email)->send(new HotelFullAlertMail(
                                hotelName:     $hotel->name,
                                totalRooms:    $totalRooms,
                                occupiedRooms: $occupiedRooms,
                                date:          now()->format('d M Y'),
                            ));
                        }
                    } catch (\Exception $e) {
                        // silently skip — never crash the dashboard
                    }
                }
            }

            // Weekly revenue chart
            try {
                $revenueByDay = Payment::where('status', 'completed')
                    ->where('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
                    ->selectRaw("DATE(created_at) as day, SUM(amount) as total")
                    ->groupBy('day')
                    ->pluck('total', 'day');
            } catch (\Exception $e) {
                $revenueByDay = collect();
            }
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $weeklyRevenue[] = [
                    'day'     => $date->format('D'),
                    'date'    => $date->format('d'),
                    'label'   => $date->format('D, d M'),
                    'amount'  => (float) ($revenueByDay[$date->toDateString()] ?? 0),
                    'isToday' => $date->isToday(),
                ];
            }

            // ── Slot Availability Widget ──────────────────────────────────────
            if (Module::isEnabled('time-slot-pricing') || Module::isEnabled('hourly-pricing')) {
                try {
                    if (request('slot_week')) {
                        $slotWeekStart = Carbon::parse(request('slot_week'))->startOfWeek(Carbon::MONDAY);
                    }
                    $slotWeekEnd = $slotWeekStart->copy()->addDays(6);
                    $dashboardSlots = HotelTimeSlot::where('is_active', true)->ordered()->get();
                    $slotRooms      = Room::where('pricing_type', 'per_slot')->orderBy('room_number')->get();
                    $slotRoomCount  = $slotRooms->count();
                    $slotRoomIds    = $slotRooms->pluck('id')->toArray();

                    $whBookingsWeek = Booking::where('is_whole_hotel', true)
                        ->whereNotIn('status', ['cancelled', 'checked_out'])
                        ->where('check_in_date', '<=', $slotWeekEnd->toDateString())
                        ->where('check_out_date', '>', $slotWeekStart->toDateString())
                        ->with('customer:id,name')
                        ->get();

                    if ($dashboardSlots->isNotEmpty() && $slotRoomCount > 0) {
                        $hasSlotModule = true;
                        $conflictSvc   = new SlotConflictService();
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
                            $whForDay = $whBookingsWeek->first(
                                fn($b) => $b->check_in_date->toDateString() <= $ds && $b->check_out_date->toDateString() > $ds
                            );
                            foreach ($dashboardSlots as $slot) {
                                if ($whForDay) {
                                    $dayData['slots'][] = [
                                        'slot_name'      => $slot->name,
                                        'time'           => $slot->start_time . '–' . $slot->end_time,
                                        'available'      => 0,
                                        'booked'         => $slotRoomCount,
                                        'total'          => $slotRoomCount,
                                        'pct'            => 100,
                                        'color'          => 'red',
                                        'booked_rooms'   => $slotRooms->map(fn($r) => ['room_id' => $r->id, 'room_number' => $r->room_number, 'guest' => $whForDay->customer->name ?? '—'])->values()->toArray(),
                                        'free_rooms'     => [],
                                        'whole_hotel_bk' => $whForDay->booking_number,
                                    ];
                                    continue;
                                }
                                $bookedDetails = $conflictSvc->getConflictingRoomDetails($slot, $ds);
                                $bookedIds     = array_column($bookedDetails, 'room_id');
                                $bookedDetails = array_values(array_filter($bookedDetails, fn($d) => in_array($d['room_id'], $slotRoomIds)));
                                $bookedIds     = array_column($bookedDetails, 'room_id');
                                $booked        = count($bookedIds);
                                $total         = $slotRoomCount;
                                $available     = $total - $booked;
                                $pct           = $total > 0 ? round($booked / $total * 100) : 0;
                                $color         = $pct >= 100 ? 'red' : ($pct >= 60 ? 'amber' : 'green');
                                $freeRooms     = $slotRooms->filter(fn($r) => !in_array($r->id, $bookedIds))->pluck('room_number')->values()->all();
                                $dayData['slots'][] = [
                                    'slot_name'    => $slot->name,
                                    'time'         => $slot->start_time . '–' . $slot->end_time,
                                    'available'    => $available,
                                    'booked'       => $booked,
                                    'total'        => $total,
                                    'pct'          => $pct,
                                    'color'        => $color,
                                    'booked_rooms' => $bookedDetails,
                                    'free_rooms'   => $freeRooms,
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

            // ── Booking Calendar ──────────────────────────────────────────────
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

                $calWhBookings = Booking::where('is_whole_hotel', true)
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->where('check_in_date', '<=', $calGridEnd->toDateString())
                    ->where('check_out_date', '>', $calGridStart->toDateString())
                    ->with('customer:id,name')
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
                    $whForCalDay = $calWhBookings->first(
                        fn($b) => $b->check_in_date->toDateString() <= $ds && $b->check_out_date->toDateString() > $ds
                    );
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
                        'whole_hotel'      => $whForCalDay ? $whForCalDay->booking_number : null,
                        'wh_guest'         => $whForCalDay ? ($whForCalDay->customer->name ?? 'Guest') : null,
                    ];
                    $cur->addDay();
                }
                $calWeeks = array_chunk($calDays, 7);
            } catch (\Exception $e) {
                $calWeeks = [];
            }

            // ── Dirty rooms list for Today's Agenda modal ─────────────────────
            $dirtyRoomsList = Room::where('status', 'dirty')
                ->orderBy('room_number')
                ->get(['id', 'room_number', 'type'])
                ->toArray();

            // ── Today's Agenda ────────────────────────────────────────────────
            $agendaKey  = 'agenda_shown_' . now()->toDateString();
            $showAgenda = !session($agendaKey, false);
            if ($showAgenda) {
                session([$agendaKey => true]);
            }
        }

        // ── Restaurant KPIs (for restaurant-only users, OR all users when module enabled) ─
        $restActiveOrders    = 0;
        $restPendingQr       = 0;
        $restTablesOccupied  = 0;
        $restTablesTotal     = 0;
        $restTodayRevenue    = 0;
        $restMenuItems       = 0;
        $restRecentOrders    = collect();
        $restWeeklyRevenue   = [];

        $restAllTables = collect();

        if ($isRestaurantOnly && Module::isEnabled('restaurant')) {
            try {
                $restActiveOrders = RestaurantOrder::whereIn('status', ['open', 'kotted', 'served'])->count();
                $restPendingQr    = RestaurantOrder::where('source', 'guest_qr')
                    ->where('approval_status', 'pending')
                    ->count();
                $restTablesOccupied = RestaurantTable::where('status', 'occupied')->count();
                $restTablesTotal    = RestaurantTable::where('is_active', true)->count();
                $restTodayRevenue   = RestaurantBill::whereDate('created_at', $today)
                    ->where('payment_status', 'paid')
                    ->sum('total');
                $restMenuItems      = RestaurantMenuItem::where('is_active', true)->count();
                $restAllTables      = RestaurantTable::with('activeOrder')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
                $restRecentOrders   = RestaurantOrder::with(['table', 'items'])
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();

                // Weekly food revenue for mini chart
                $foodRevenueByDay = RestaurantBill::whereDate('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
                    ->where('payment_status', 'paid')
                    ->selectRaw("DATE(created_at) as day, SUM(total) as total")
                    ->groupBy('day')
                    ->pluck('total', 'day');
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $restWeeklyRevenue[] = [
                        'day'    => $date->format('D'),
                        'date'   => $date->format('d'),
                        'label'  => $date->format('D, d M'),
                        'amount' => (float) ($foodRevenueByDay[$date->toDateString()] ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                // silently skip — never crash the dashboard
            }
        }

        // ── Dashboard preferences (per-user, fallback to hotel default) ─────────
        $userId  = (int) session('crm_user_id');

        // Single query for both user preference and hotel default (was 2 queries)
        $allDashPrefs = DashboardPreference::where('hotel_id', $hotelId)
            ->where(fn($q) => $q
                ->where('user_id', $userId)
                ->orWhere(fn($q2) => $q2->whereNull('user_id')->where('is_hotel_default', true))
            )
            ->get();
        $dashPref = $allDashPrefs->firstWhere('user_id', $userId)
            ?? $allDashPrefs->first(fn($p) => is_null($p->user_id));

        $allWidgetKeys = [
            'kpi-row-1', 'shortcuts-actions-pair',
            'revenue-trend',
            'slot-availability', 'booking-calendar',
            'arrivals-departures', 'recent-room-pair', 'live-activity',
        ];

        $dashWidgetOrder   = $dashPref?->preferences['widget_order']   ?? $allWidgetKeys;
        $dashHiddenWidgets = $dashPref?->preferences['hidden_widgets']  ?? [];
        $dashIsPersonal    = $dashPref && $dashPref->user_id !== null;
        // Reuse the already-loaded collection — no extra query needed
        $dashHotelDefault  = $allDashPrefs->first(fn($p) => is_null($p->user_id) && $p->is_hotel_default);

        return view('admin.dashboard', compact(
            'todayCheckins', 'todayCheckouts', 'availableRooms', 'occupiedRooms',
            'dirtyRooms', 'maintenanceRooms', 'totalRooms', 'monthRevenue', 'todayRevenue',
            'pendingPayments', 'totalCustomers', 'newCustomersMonth',
            'recentBookings', 'occupancyRate', 'weeklyRevenue',
            'calWeeks', 'calStart', 'prevMonth', 'nextMonth',
            'hasSlotModule', 'dashboardSlots', 'dashboardSlotAvailability', 'slotWeekStart',
            'websitePendingCount',
            'dashWidgetOrder', 'dashHiddenWidgets', 'dashIsPersonal', 'dashHotelDefault', 'allWidgetKeys',
            'dirtyRoomsList', 'showAgenda', 'hotelFull', 'otaPendingCount', 'lowStockCount',
            'isRestaurantOnly',
            'restActiveOrders', 'restPendingQr', 'restTablesOccupied', 'restTablesTotal',
            'restTodayRevenue', 'restMenuItems', 'restRecentOrders', 'restWeeklyRevenue',
            'restAllTables'
        ));
    }

    // ── Restaurant Tables JSON (for dashboard quick-order live polling) ──────
    public function restaurantTables()
    {
        if (!session('crm_logged_in')) return response()->json([], 401);

        $tables = RestaurantTable::with('activeOrder')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id'           => $t->id,
                'name'         => $t->name,
                'capacity'     => $t->capacity,
                'status'       => $t->status,
                'status_label' => $t->statusLabel(),
                'order_id'     => $t->activeOrder?->id,
                'order_number' => $t->activeOrder?->order_number,
                'order_total'  => $t->activeOrder ? number_format((float) $t->activeOrder->total, 2) : null,
            ]);

        $pendingQr = RestaurantOrder::where('source', 'guest_qr')
            ->where('approval_status', 'pending')
            ->count();

        return response()->json([
            'tables'      => $tables,
            'pending_qr'  => $pendingQr,
            'updated_at'  => now()->format('H:i:s'),
        ]);
    }

    // ── Live Activity Feed ────────────────────────────────────────────────────
    public function liveFeed()
    {
        if (!session('crm_logged_in')) return response()->json([], 401);

        // Resolve hotel via HotelContext so Super Admin's hotel-filter view
        // (crm_sa_hotel_filter) also returns activity, not only crm_hotel_id.
        $hotelId = app(\App\Services\HotelContext::class)->getHotel()
            ?? (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        if (!$hotelId) return response()->json([]);

        $actionColors = [
            'created'   => ['bg' => '#dcfce7', 'color' => '#16a34a', 'label' => 'Created'],
            'updated'   => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'label' => 'Updated'],
            'deleted'   => ['bg' => '#fee2e2', 'color' => '#dc2626', 'label' => 'Deleted'],
            'checked_in'  => ['bg' => '#dbeafe', 'color' => '#2563eb', 'label' => 'Check-In'],
            'checked_out' => ['bg' => '#f3e8ff', 'color' => '#9333ea', 'label' => 'Check-Out'],
            'login'     => ['bg' => '#e0f2fe', 'color' => '#0891b2', 'label' => 'Login'],
            'payment'   => ['bg' => '#d1fae5', 'color' => '#059669', 'label' => 'Payment'],
            'cancelled' => ['bg' => '#fef2f2', 'color' => '#ef4444', 'label' => 'Cancelled'],
        ];

        $entries = ActivityLog::where('hotel_id', $hotelId)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($e) use ($actionColors) {
                $action  = strtolower($e->action ?? '');
                $style   = $actionColors[$action] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => ucfirst($action)];
                return [
                    'id'          => $e->id,
                    'user_name'   => $e->user_name ?? 'System',
                    'user_role'   => $e->user_role ?? '',
                    'action'      => $e->action,
                    'action_label'=> $style['label'],
                    'action_bg'   => $style['bg'],
                    'action_color'=> $style['color'],
                    'module'      => $e->module ?? '',
                    'description' => $e->description ?? '',
                    'time'        => $e->created_at->diffForHumans(),
                    'timestamp'   => $e->created_at->toISOString(),
                    'avatar'      => strtoupper(substr($e->user_name ?? 'S', 0, 1)),
                ];
            });

        return response()->json($entries);
    }

    // ── Revenue Trend JSON (for dashboard chart widget) ───────────────────────
    public function revenueTrend(Request $request)
    {
        if (!session('crm_logged_in')) return response()->json([], 401);

        $range = $request->input('range', '7d');
        $today = Carbon::today();

        $hotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
        if ($hotelId) {
            $payload = AnalyticsCache::remember(
                $hotelId,
                'revenue_trend',
                ['range' => $range, 'today' => $today->toDateString()],
                fn() => $this->buildRevenueTrend($range, $today)
            );
            return response()->json($payload);
        }

        return response()->json($this->buildRevenueTrend($range, $today));
    }

    private function buildRevenueTrend(string $range, Carbon $today): array
    {

        switch ($range) {
            case '30d':
                $from = $today->copy()->subDays(29); $to = $today->copy(); $step = 'day'; break;
            case '90d':
                $from = $today->copy()->subDays(89); $to = $today->copy(); $step = 'day'; break;
            case '12m':
                $from = $today->copy()->startOfMonth()->subMonths(11);
                $to   = $today->copy()->endOfMonth();
                $step = 'month';
                break;
            case '7d':
            default:
                $from = $today->copy()->subDays(6); $to = $today->copy(); $step = 'day';
        }

        // Previous comparable period (same length immediately preceding $from)
        if ($step === 'month') {
            $prevTo   = $from->copy()->subDay()->endOfMonth();
            $prevFrom = $from->copy()->subMonths(12)->startOfMonth();
        } else {
            $periodDays = $from->diffInDays($to) + 1; // inclusive
            $prevTo     = $from->copy()->subDay();
            $prevFrom   = $prevTo->copy()->subDays($periodDays - 1);
        }

        try {
            $payments = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->get(['amount', 'created_at']);
        } catch (\Exception $e) {
            $payments = collect();
        }

        try {
            $prevPayments = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$prevFrom->copy()->startOfDay(), $prevTo->copy()->endOfDay()])
                ->get(['amount', 'created_at']);
        } catch (\Exception $e) {
            $prevPayments = collect();
        }

        $totalRooms = max(1, (int) Room::count());

        // Single query: all bookings overlapping the period (any of its nights)
        try {
            $bookings = Booking::whereIn('status', ['confirmed','checked_in','checked_out'])
                ->where('check_in_date',  '<=', $to->toDateString())
                ->where('check_out_date', '>',  $from->toDateString())
                ->get(['check_in_date','check_out_date','is_whole_hotel']);
        } catch (\Exception $e) { $bookings = collect(); }

        // Build per-day occupied-room counts (whole-hotel = all rooms occupied)
        $occByDay = [];
        foreach ($bookings as $b) {
            try {
                $ci = Carbon::parse($b->check_in_date)->startOfDay();
                $co = Carbon::parse($b->check_out_date)->startOfDay();
            } catch (\Exception $e) { continue; }
            $start = $ci->greaterThan($from) ? $ci->copy() : $from->copy()->startOfDay();
            $end   = $co->lessThan($to->copy()->addDay()) ? $co->copy() : $to->copy()->addDay();
            $cur = $start->copy();
            $add = $b->is_whole_hotel ? $totalRooms : 1;
            while ($cur->lt($end)) {
                $k = $cur->toDateString();
                $occByDay[$k] = ($occByDay[$k] ?? 0) + $add;
                $cur->addDay();
            }
        }

        $labels = [];
        $revenue = [];
        $prevRevenue = [];
        $occupancy = [];

        if ($step === 'month') {
            $g  = $payments->groupBy(fn($p) => Carbon::parse($p->created_at)->format('Y-m'));
            $pg = $prevPayments->groupBy(fn($p) => Carbon::parse($p->created_at)->format('Y-m'));
            $cur = $from->copy();
            $pcur = $prevFrom->copy();
            while ($cur <= $to) {
                $k = $cur->format('Y-m');
                $pk = $pcur->format('Y-m');
                $labels[] = $cur->format('M Y');
                $revenue[] = (float) ($g->get($k)?->sum('amount') ?? 0);
                $prevRevenue[] = (float) ($pg->get($pk)?->sum('amount') ?? 0);
                $pcur->addMonth();
                // Sum room-nights across days in this month, divide by (rooms × days_in_month)
                $monthStart = $cur->copy()->startOfMonth();
                $monthEnd   = $cur->copy()->endOfMonth();
                $roomNights = 0; $d = $monthStart->copy();
                while ($d->lte($monthEnd)) {
                    $roomNights += min($totalRooms, $occByDay[$d->toDateString()] ?? 0);
                    $d->addDay();
                }
                $denom = $totalRooms * $monthStart->daysInMonth;
                $occupancy[] = round(min(100, ($roomNights / max(1,$denom)) * 100), 1);
                $cur->addMonth();
            }
        } else {
            $g  = $payments->groupBy(fn($p) => Carbon::parse($p->created_at)->toDateString());
            $pg = $prevPayments->groupBy(fn($p) => Carbon::parse($p->created_at)->toDateString());
            $cur  = $from->copy();
            $pcur = $prevFrom->copy();
            while ($cur <= $to) {
                $k  = $cur->toDateString();
                $pk = $pcur->toDateString();
                $labels[] = $cur->format('d M');
                $revenue[] = (float) ($g->get($k)?->sum('amount') ?? 0);
                $prevRevenue[] = (float) ($pg->get($pk)?->sum('amount') ?? 0);
                $occRooms = min($totalRooms, $occByDay[$k] ?? 0);
                $occupancy[] = round(min(100, ($occRooms / $totalRooms) * 100), 1);
                $cur->addDay();
                $pcur->addDay();
            }
        }

        $total = array_sum($revenue);
        $avg   = count($revenue) ? $total / count($revenue) : 0;
        $peak  = count($revenue) ? max($revenue) : 0;

        $prevTotal = array_sum($prevRevenue);
        $prevAvg   = count($prevRevenue) ? $prevTotal / count($prevRevenue) : 0;
        $prevPeak  = count($prevRevenue) ? max($prevRevenue) : 0;

        $pctDelta = function ($cur, $prev) {
            if ($prev <= 0) return $cur > 0 ? null : 0.0; // null = N/A (no baseline)
            return round((($cur - $prev) / $prev) * 100, 1);
        };

        return [
            'range'        => $range,
            'labels'       => $labels,
            'revenue'      => $revenue,
            'prev_revenue' => $prevRevenue,
            'occupancy'    => $occupancy,
            'total'        => $total,
            'avg'          => $avg,
            'peak'         => $peak,
            'prev_total'   => $prevTotal,
            'prev_avg'     => $prevAvg,
            'prev_peak'    => $prevPeak,
            'delta_total'  => $pctDelta($total, $prevTotal),
            'delta_avg'    => $pctDelta($avg,   $prevAvg),
            'delta_peak'   => $pctDelta($peak,  $prevPeak),
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'prev_from'    => $prevFrom->toDateString(),
            'prev_to'      => $prevTo->toDateString(),
        ];
    }

    // ── Live KPI Counts ───────────────────────────────────────────────────────
    public function kpiLive()
    {
        if (!session('crm_logged_in')) return response()->json([], 401);

        $today = Carbon::today();

        try {
            $checkins     = Booking::whereDate('check_in_date', $today)->where('status', 'confirmed')->count();
            $checkouts    = Booking::whereDate('check_out_date', $today)->where('status', 'checked_in')->count();
            $available    = Room::where('status', 'available')->count();
            $occupied     = Room::where('status', 'occupied')->count();
            $dirty        = Room::where('status', 'dirty')->count();
            $total        = Room::count();
            $occupancy    = $total > 0 ? round(($occupied / $total) * 100) : 0;
            $pending      = Booking::where('payment_status', 'pending')
                                   ->whereNotIn('status', ['cancelled', 'checked_out'])->count();
            $todayRevenue = Payment::whereDate('created_at', $today)->where('status', 'completed')->sum('amount');
            $monthRevenue = Payment::whereYear('created_at', $today->year)->whereMonth('created_at', $today->month)->where('status', 'completed')->sum('amount');
        } catch (\Exception $e) {
            return response()->json(['error' => 'failed'], 500);
        }

        return response()->json(compact(
            'checkins', 'checkouts', 'available', 'occupied',
            'dirty', 'total', 'occupancy', 'pending', 'todayRevenue', 'monthRevenue'
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

        // Check if a whole-hotel booking covers this date
        $wholeHotelBooking = Booking::with('customer')
            ->where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<=', $d)
            ->where('check_out_date', '>', $d)
            ->first();

        if ($wholeHotelBooking) {
            $rooms = Room::where('status', '!=', 'maintenance')->orderBy('room_number')->get();
            $occupied = $rooms->map(fn($room) => [
                'id'           => $room->id,
                'room_number'  => $room->room_number,
                'type'         => ucfirst($room->type),
                'pricing_type' => $room->pricing_type,
                'guest'        => $wholeHotelBooking->customer->name ?? '—',
                'status'       => $wholeHotelBooking->status,
                'booking_url'  => route('bookings.show', $wholeHotelBooking->id),
                'whole_hotel'  => true,
            ])->values()->toArray();
            return response()->json([
                'date'           => Carbon::parse($d)->format('D, d M Y'),
                'available'      => [],
                'occupied'       => $occupied,
                'whole_hotel_bk' => $wholeHotelBooking->booking_number,
            ]);
        }

        // Dirty rooms are always unavailable regardless of bookings — return separately
        $dirtyRoomsList = Room::where('status', 'dirty')->orderBy('room_number')->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'room_number' => $r->room_number,
                'type'        => ucfirst($r->type),
            ])->values()->toArray();

        $rooms = Room::with(['bookings' => function ($q) use ($d) {
            $q->with('customer')
              ->whereIn('status', ['confirmed', 'checked_in'])
              ->where(function ($q2) use ($d) {
                  $q2->where(function ($q3) use ($d) {
                      $q3->whereDate('check_in_date', '<=', $d)
                         ->whereDate('check_out_date', '>', $d);
                  })
                  ->orWhere(function ($q3) use ($d) {
                      $q3->whereDate('check_in_date', $d)
                         ->whereDate('check_out_date', $d);
                  })
                  ->orWhere(function ($q3) use ($d) {
                      $q3->whereDate('booking_date', $d)
                         ->whereNotNull('booking_date');
                  });
              });
        }])->whereNotIn('status', ['maintenance', 'dirty', 'inactive'])->orderBy('room_number')->get();

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
            'dirty'     => $dirtyRoomsList,
        ]);
    }
}
