<?php

namespace App\Livewire\Platform;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public string $filterPlan    = '';
    public string $filterStatus  = '';
    public string $filterInactive = '0';
    public int    $selectedHotelId = 0;

    protected $listeners = ['closePanel' => 'clearSelected'];

    public function clearSelected(): void
    {
        $this->selectedHotelId = 0;
    }

    private function hotelEngagement(): \Illuminate\Support\Collection
    {
        $now = Carbon::now();

        // Last activity per hotel from activity_logs
        $lastActivity = DB::table('activity_logs')
            ->select('hotel_id', DB::raw('MAX(created_at) as last_at'))
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // Room stats per hotel
        $roomStats = DB::table('rooms')
            ->select('hotel_id',
                DB::raw('COUNT(*) as total_rooms'),
                DB::raw("SUM(CASE WHEN status='occupied' THEN 1 ELSE 0 END) as occupied_rooms"),
                DB::raw("SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) as available_rooms")
            )
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // Bookings this month per hotel
        $thisMonth   = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $bookingsThisMonth = DB::table('bookings')
            ->select('hotel_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status='checked_in' THEN 1 ELSE 0 END) as checkins"),
                DB::raw("SUM(CASE WHEN status='checked_out' THEN 1 ELSE 0 END) as checkouts")
            )
            ->where('created_at', '>=', $thisMonth)
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // Revenue this month per hotel
        $revenueThisMonth = DB::table('payments')
            ->select('hotel_id', DB::raw('SUM(amount) as total_revenue'))
            ->where('created_at', '>=', $thisMonth)
            ->where('status', 'completed')
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // Hotels query with optional filters
        $query = DB::table('hotels')->select(
            'id', 'name', 'slug', 'plan', 'status', 'email', 'phone',
            'trial_ends_at', 'plan_expires_at', 'created_at'
        );

        if ($this->filterPlan !== '') {
            $query->where('plan', $this->filterPlan);
        }
        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        $hotels = $query->orderBy('name')->get();

        return $hotels->map(function ($hotel) use ($lastActivity, $roomStats, $bookingsThisMonth, $revenueThisMonth, $now) {
            $la = $lastActivity[$hotel->id] ?? null;
            $lastAt = $la ? Carbon::parse($la->last_at) : null;
            $daysSince = $lastAt ? (int) $lastAt->diffInDays($now) : 999;

            $activityBadge = match(true) {
                $daysSince <= 1   => 'active',
                $daysSince <= 7   => 'idle',
                default           => 'dormant',
            };

            $rs = $roomStats[$hotel->id]     ?? null;
            $bm = $bookingsThisMonth[$hotel->id] ?? null;
            $rm = $revenueThisMonth[$hotel->id]  ?? null;

            return (object) [
                'id'             => $hotel->id,
                'name'           => $hotel->name,
                'plan'           => $hotel->plan,
                'status'         => $hotel->status,
                'email'          => $hotel->email,
                'phone'          => $hotel->phone,
                'last_activity'  => $lastAt?->format('d M Y H:i') ?? 'Never',
                'days_since'     => $daysSince,
                'activity_badge' => $activityBadge,
                'total_rooms'    => $rs?->total_rooms ?? 0,
                'occupied_rooms' => $rs?->occupied_rooms ?? 0,
                'available_rooms'=> $rs?->available_rooms ?? 0,
                'bookings_month' => $bm?->total ?? 0,
                'checkins_month' => $bm?->checkins ?? 0,
                'checkouts_month'=> $bm?->checkouts ?? 0,
                'revenue_month'  => (float)($rm?->total_revenue ?? 0),
                'created_at'     => $hotel->created_at,
                'trial_ends_at'  => $hotel->trial_ends_at,
            ];
        })->when($this->filterInactive !== '0', function ($col) {
            $days = (int) $this->filterInactive;
            return $col->filter(fn($h) => $h->days_since >= $days);
        });
    }

    public function getKpiData(iterable $hotels): array
    {
        $all = collect($hotels);
        $allHotels = DB::table('hotels');

        $now       = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $totalHotels     = $allHotels->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', $now)->count();
        $inactiveHotels  = DB::table('activity_logs')
            ->select('hotel_id', DB::raw('MAX(created_at) as last_at'))
            ->groupBy('hotel_id')
            ->havingRaw("MAX(created_at) < ?", [$now->copy()->subDays(2)])
            ->count();

        $totalRooms    = DB::table('rooms')->count();
        $occupiedRooms = DB::table('rooms')->where('status', 'occupied')->count();
        $availRooms    = DB::table('rooms')->where('status', 'available')->count();

        $checkinsMonth  = DB::table('bookings')->where('status', 'checked_in')->where('created_at', '>=', $thisMonth)->count();
        $checkoutsMonth = DB::table('bookings')->where('status', 'checked_out')->where('created_at', '>=', $thisMonth)->count();
        $checkinsLast   = DB::table('bookings')->where('status', 'checked_in')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
        $checkoutsLast  = DB::table('bookings')->where('status', 'checked_out')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $revMonth = (float) DB::table('payments')->where('status', 'completed')->where('created_at', '>=', $thisMonth)->sum('amount');
        $revLast  = (float) DB::table('payments')->where('status', 'completed')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->sum('amount');

        $waEnabled = DB::table('whatsapp_configs')->where('setup_completed', true)->count();

        $revGrowth = $revLast > 0 ? round((($revMonth - $revLast) / $revLast) * 100, 1) : 0;
        $ciGrowth  = $checkinsLast > 0 ? round((($checkinsMonth - $checkinsLast) / $checkinsLast) * 100, 1) : 0;
        $coGrowth  = $checkoutsLast > 0 ? round((($checkoutsMonth - $checkoutsLast) / $checkoutsLast) * 100, 1) : 0;

        return compact(
            'totalHotels', 'activeHotels', 'suspendedHotels', 'trialHotels', 'inactiveHotels',
            'totalRooms', 'occupiedRooms', 'availRooms',
            'checkinsMonth', 'checkoutsMonth', 'ciGrowth', 'coGrowth',
            'revMonth', 'revLast', 'revGrowth', 'waEnabled'
        );
    }

    public function getChartData(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i));

        // Monthly bookings (last 6 months)
        $bookingRows = DB::table('bookings')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN status='checked_in' THEN 1 ELSE 0 END) as checkins"),
                DB::raw("SUM(CASE WHEN status='checked_out' THEN 1 ELSE 0 END) as checkouts"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()
            ->keyBy('month');

        // Monthly revenue (last 6 months)
        $revenueRows = DB::table('payments')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()
            ->keyBy('month');

        $monthLabels  = $months->map(fn($m) => $m->format('M Y'))->values()->toArray();
        $monthKeys    = $months->map(fn($m) => $m->format('Y-m'))->values()->toArray();
        $checkinData  = array_map(fn($k) => (int)($bookingRows[$k]?->checkins ?? 0), $monthKeys);
        $checkoutData = array_map(fn($k) => (int)($bookingRows[$k]?->checkouts ?? 0), $monthKeys);
        $revenueData  = array_map(fn($k) => (float)($revenueRows[$k]?->total ?? 0), $monthKeys);

        // Plan distribution
        $planDist = DB::table('hotels')
            ->select('plan', DB::raw('COUNT(*) as cnt'))
            ->groupBy('plan')
            ->get();

        // Hotel count by status
        $statusDist = DB::table('hotels')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->get();

        // Room occupancy per hotel (top 10)
        $occupancy = DB::table('rooms')
            ->join('hotels', 'rooms.hotel_id', '=', 'hotels.id')
            ->select('hotels.name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN rooms.status='occupied' THEN 1 ELSE 0 END) as occupied")
            )
            ->groupBy('hotels.id', 'hotels.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'monthLabels'  => $monthLabels,
            'checkinData'  => $checkinData,
            'checkoutData' => $checkoutData,
            'revenueData'  => $revenueData,
            'planLabels'   => $planDist->pluck('plan')->toArray(),
            'planCounts'   => $planDist->pluck('cnt')->map(fn($v) => (int)$v)->toArray(),
            'statusLabels' => $statusDist->pluck('status')->toArray(),
            'statusCounts' => $statusDist->pluck('cnt')->map(fn($v) => (int)$v)->toArray(),
            'occHotels'    => $occupancy->pluck('name')->toArray(),
            'occTotal'     => $occupancy->pluck('total')->map(fn($v) => (int)$v)->toArray(),
            'occOccupied'  => $occupancy->pluck('occupied')->map(fn($v) => (int)$v)->toArray(),
        ];
    }

    public function getSelectedHotelDetail(): ?object
    {
        if (!$this->selectedHotelId) {
            return null;
        }

        $hotel = DB::table('hotels')->where('id', $this->selectedHotelId)->first();
        if (!$hotel) {
            return null;
        }

        $recentActivity = DB::table('activity_logs')
            ->where('hotel_id', $this->selectedHotelId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $bookingBreakdown = DB::table('bookings')
            ->where('hotel_id', $this->selectedHotelId)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->get();

        $payments = DB::table('payments')
            ->where('hotel_id', $this->selectedHotelId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $rooms = DB::table('rooms')
            ->where('hotel_id', $this->selectedHotelId)
            ->get(['room_number', 'type', 'status', 'price_per_night']);

        $totalRevenue = DB::table('payments')
            ->where('hotel_id', $this->selectedHotelId)
            ->where('status', 'completed')
            ->sum('amount');

        return (object) compact('hotel', 'recentActivity', 'bookingBreakdown', 'payments', 'rooms', 'totalRevenue');
    }

    public function render()
    {
        $hotelEngagement = $this->hotelEngagement();
        $kpi             = $this->getKpiData($hotelEngagement);
        $charts          = $this->getChartData();
        $selectedDetail  = $this->getSelectedHotelDetail();
        $plans           = DB::table('hotels')->distinct()->pluck('plan')->sort()->values();

        return view('livewire.platform.analytics-dashboard', compact(
            'hotelEngagement', 'kpi', 'charts', 'selectedDetail', 'plans'
        ));
    }
}
