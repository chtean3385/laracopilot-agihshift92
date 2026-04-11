<?php

namespace App\Livewire\Platform;

use App\Http\Controllers\Platform\HotelController;
use App\Models\PlatformWhatsAppSetting;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    // ── Filters ──────────────────────────────────────────────────────────
    public string $filterPlan    = '';
    public string $filterStatus  = '';
    public string $filterInactive = '0';

    // ── Drill-down ───────────────────────────────────────────────────────
    public int    $selectedHotelId = 0;

    // ── Quick Action modal ───────────────────────────────────────────────
    public bool   $showQuickModal         = false;
    public int    $quickModalHotelId      = 0;
    public string $quickModalHotelName    = '';
    public string $quickModalChannel      = 'whatsapp'; // whatsapp | push
    public string $quickMessage           = '';
    public string $quickPushTitle         = '';
    public string $quickActionResult      = '';
    public string $selectedTemplateKey    = ''; // kept for compatibility
    public string $selectedTemplateName   = '';
    public string $selectedTemplateLang   = 'en';
    public array  $liveWaTemplates        = [];
    public bool   $waTemplatesLoading     = false;
    public bool   $quickModalConsented    = false;

    // ── Active Sessions tab ──────────────────────────────────────────────
    public string $activeTab = 'hotels'; // hotels | active

    public function clearSelected(): void  { $this->selectedHotelId = 0; }
    public function updatedFilterPlan():     void { $this->selectedHotelId = 0; }
    public function updatedFilterStatus():   void { $this->selectedHotelId = 0; }
    public function updatedFilterInactive(): void { $this->selectedHotelId = 0; }

    // ── Open quick-action modal ───────────────────────────────────────────
    public function openQuickModal(int $hotelId, string $channel = 'whatsapp'): void
    {
        $hotel = DB::table('hotels')->where('id', $hotelId)->first();
        $this->quickModalHotelId   = $hotelId;
        $this->quickModalHotelName = $hotel?->name ?? 'Hotel';
        $this->quickModalChannel   = $channel;
        $this->quickMessage        = '';
        $this->quickPushTitle      = '';
        $this->quickActionResult   = '';
        $this->selectedTemplateKey = '';
        $this->quickModalConsented = (bool) ($hotel?->owner_wa_consent ?? false);
        $this->showQuickModal      = true;
    }

    public function closeQuickModal(): void { $this->showQuickModal = false; $this->quickActionResult = ''; }

    public function selectTemplate(string $key): void
    {
        $this->selectedTemplateKey = $key;
        $this->quickActionResult   = '';
    }

    // ── Send quick WhatsApp template message ─────────────────────────────
    public function sendQuickWhatsApp(): void
    {
        if (!$this->selectedTemplateKey) {
            $this->quickActionResult = '⚠️ Please select a template first.';
            return;
        }

        $hotel    = DB::table('hotels')->where('id', $this->quickModalHotelId)->first();
        $platform = PlatformWhatsAppSetting::instance();

        if (!$hotel?->phone) {
            $this->quickActionResult = '❌ This hotel has no phone number. Add one in Edit Hotel.';
            return;
        }
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            $this->quickActionResult = '❌ Platform WhatsApp not configured.';
            return;
        }

        $templates = HotelController::platformWaTemplates();
        if (!isset($templates[$this->selectedTemplateKey])) {
            $this->quickActionResult = '❌ Unknown template.';
            return;
        }

        $tpl   = $templates[$this->selectedTemplateKey];
        $phone = preg_replace('/[^0-9]/', '', $hotel->phone);
        if (!str_starts_with($phone, '91') && strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        try {
            $response = Http::timeout(15)->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $tpl['meta_name'],
                        'language'   => ['code' => $tpl['language']],
                        'components' => [[
                            'type'       => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $hotel->name],
                                ['type' => 'text', 'text' => 'https://resort.dreamstechnology.in/'],
                            ],
                        ]],
                    ],
                ]);

            $body    = $response->json();
            $errCode = $body['error']['code'] ?? 0;

            if ($response->successful() && isset($body['messages'])) {
                $this->quickActionResult = '✅ WhatsApp sent to ' . $hotel->name;
            } elseif ($errCode === 132001) {
                $this->quickActionResult = "⚠️ Template \"{$tpl['meta_name']}\" not yet approved by Meta. Submit it in Meta Business Manager first.";
            } else {
                $errMsg = $body['error']['message'] ?? 'Unknown error';
                $this->quickActionResult = "❌ Meta error: {$errMsg}";
                Log::warning("Quick WA template failed for hotel {$this->quickModalHotelId}", ['body' => $body]);
            }
        } catch (\Throwable $e) {
            $this->quickActionResult = '❌ Failed: ' . $e->getMessage();
            Log::error("Quick WA exception for hotel {$this->quickModalHotelId}: " . $e->getMessage());
        }
    }

    // ── Send quick push notification ──────────────────────────────────────
    public function sendQuickPush(): void
    {
        if (!$this->quickPushTitle && !$this->quickMessage) return;

        $fcm    = app(FcmService::class);
        $tokens = $fcm->getTokensForHotel($this->quickModalHotelId);

        if (!$fcm->isEnabled()) {
            $this->quickActionResult = '❌ Firebase not enabled.';
            return;
        }
        if (empty($tokens)) {
            $this->quickActionResult = '⚠️ No devices registered for this hotel.';
            return;
        }

        $result = $fcm->sendToTokens($tokens, $this->quickPushTitle ?: 'Platform Alert', $this->quickMessage);
        $this->quickActionResult = "✅ Push sent to {$result['success']} device(s) (failed: {$result['failure']})";
    }

    // ── Linear regression prediction ──────────────────────────────────────
    private function revenuePrediction(): array
    {
        $revenueRows = DB::table('payments')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()
            ->keyBy('month');

        $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i)->format('Y-m'))->values()->toArray();
        $values = array_map(fn($k) => (float)($revenueRows[$k]?->total ?? 0), $months);

        $n    = count($values);
        $xArr = range(0, $n - 1);
        $sumX = array_sum($xArr);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        foreach ($xArr as $i => $xi) {
            $sumXY += $xi * $values[$i];
            $sumX2 += $xi * $xi;
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);
        $slope = $denom != 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $nextMonth = max(0, round($intercept + $slope * $n));
        $nextYear  = 0;
        for ($i = 0; $i < 12; $i++) {
            $nextYear += max(0, $intercept + $slope * ($n + $i));
        }
        $nextYear = round($nextYear);

        $avgRevenue = $n > 0 && $sumY > 0 ? ($sumY / $n) : 1;
        $trendPct   = round(($slope / max(1, $avgRevenue)) * 100, 1);

        // Confidence: how well does linear fit?
        $predicted = array_map(fn($i) => $intercept + $slope * $i, $xArr);
        $ssRes = 0; $ssTot = 0; $mean = $sumY / max(1, $n);
        foreach ($values as $i => $v) {
            $ssRes += ($v - $predicted[$i]) ** 2;
            $ssTot += ($v - $mean) ** 2;
        }
        $r2 = $ssTot > 0 ? round(1 - ($ssRes / $ssTot), 2) : 0;

        return [
            'nextMonth'    => $nextMonth,
            'nextYear'     => $nextYear,
            'trend'        => $slope > 100 ? 'up' : ($slope < -100 ? 'down' : 'flat'),
            'trendPct'     => $trendPct,
            'confidence'   => max(0, min(100, (int) ($r2 * 100))),
            'currentMonth' => (float)($revenueRows[Carbon::now()->format('Y-m')]?->total ?? 0),
        ];
    }

    // ── Active logins in last 30 minutes ─────────────────────────────────
    private function activeSessions(): array
    {
        $cutoff = Carbon::now()->subMinutes(30);

        $sessions = DB::table('activity_logs')
            ->where('created_at', '>=', $cutoff)
            ->select('hotel_id', 'user_name', 'user_role', 'action', 'module', DB::raw('MAX(created_at) as last_seen'))
            ->groupBy('hotel_id', 'user_name', 'user_role', 'action', 'module')
            ->orderByDesc('last_seen')
            ->limit(20)
            ->get();

        // Unique users
        $uniqueUsers = $sessions->unique(fn($s) => $s->hotel_id . '|' . $s->user_name)->count();

        // Hotels with active users
        $hotelIds = $sessions->pluck('hotel_id')->unique()->filter()->values();
        $hotelNames = DB::table('hotels')->whereIn('id', $hotelIds)->pluck('name', 'id');

        $grouped = $sessions->map(fn($s) => (object) [
            'hotel_name' => $hotelNames[$s->hotel_id] ?? 'Unknown',
            'hotel_id'   => $s->hotel_id,
            'user_name'  => $s->user_name,
            'user_role'  => $s->user_role,
            'action'     => $s->action,
            'module'     => $s->module,
            'last_seen'  => Carbon::parse($s->last_seen)->diffForHumans(),
        ]);

        return [
            'sessions'    => $grouped,
            'total_users' => $uniqueUsers,
            'total_hotels'=> $hotelIds->count(),
        ];
    }

    // ── Hotel engagement with performance score ───────────────────────────
    private function hotelEngagement(): \Illuminate\Support\Collection
    {
        $now       = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Last activity per hotel
        $lastActivity = DB::table('activity_logs')
            ->select('hotel_id', DB::raw('MAX(created_at) as last_at'), DB::raw('COUNT(*) as activity_count'))
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

        // Bookings this + last month
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

        $bookingsLastMonth = DB::table('bookings')
            ->select('hotel_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // Revenue this + last month
        $revenueThisMonth = DB::table('payments')
            ->select('hotel_id', DB::raw('SUM(amount) as total_revenue'))
            ->where('created_at', '>=', $thisMonth)
            ->where('status', 'completed')
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        $revenueLastMonth = DB::table('payments')
            ->select('hotel_id', DB::raw('SUM(amount) as total_revenue'))
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->where('status', 'completed')
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        // FCM device counts per hotel
        $deviceCounts = DB::table('fcm_tokens')
            ->select('hotel_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        $query = DB::table('hotels')->select(
            'id', 'name', 'slug', 'plan', 'status', 'email', 'phone',
            'trial_ends_at', 'plan_expires_at', 'created_at', 'owner_wa_consent'
        );

        if ($this->filterPlan !== '') {
            $query->where('plan', $this->filterPlan);
        }
        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        $hotels = $query->orderBy('name')->get();

        return $hotels->map(function ($hotel) use (
            $lastActivity, $roomStats, $bookingsThisMonth, $bookingsLastMonth,
            $revenueThisMonth, $revenueLastMonth, $deviceCounts, $now
        ) {
            $la = $lastActivity[$hotel->id] ?? null;
            $lastAt = $la ? Carbon::parse($la->last_at) : null;
            $daysSince = $lastAt ? (int) $lastAt->diffInDays($now) : 999;

            $rs  = $roomStats[$hotel->id] ?? null;
            $bm  = $bookingsThisMonth[$hotel->id] ?? null;
            $blm = $bookingsLastMonth[$hotel->id] ?? null;
            $rm  = $revenueThisMonth[$hotel->id] ?? null;
            $rlm = $revenueLastMonth[$hotel->id] ?? null;
            $dev = $deviceCounts[$hotel->id] ?? null;

            $revThis = (float)($rm?->total_revenue ?? 0);
            $revLast = (float)($rlm?->total_revenue ?? 0);
            $bkThis  = (int)($bm?->total ?? 0);
            $bkLast  = (int)($blm?->total ?? 0);

            $revGrowth = $revLast > 0 ? round((($revThis - $revLast) / $revLast) * 100, 1) : ($revThis > 0 ? 100 : 0);
            $bkGrowth  = $bkLast > 0  ? round((($bkThis - $bkLast) / $bkLast) * 100, 1) : ($bkThis > 0 ? 100 : 0);

            $totalRooms    = (int)($rs?->total_rooms ?? 0);
            $occupiedRooms = (int)($rs?->occupied_rooms ?? 0);
            $occupancyPct  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

            // Performance score (0–100)
            $score = 0;
            $score += min(40, $occupancyPct * 0.4);               // 0-40: occupancy
            $score += min(25, max(0, 12.5 + $revGrowth * 0.25));  // 0-25: revenue growth
            $score += match(true) {
                $daysSince <= 1  => 20,
                $daysSince <= 7  => 15,
                $daysSince <= 14 => 8,
                $daysSince <= 30 => 3,
                default          => 0,
            };                                                      // 0-20: activity
            $score += min(15, $bkThis * 1.5);                      // 0-15: bookings

            $score = (int) min(100, max(0, $score));
            $grade = match(true) {
                $score >= 80 => ['A', '#22c55e', '#dcfce7'],
                $score >= 60 => ['B', '#3b82f6', '#dbeafe'],
                $score >= 40 => ['C', '#f59e0b', '#fef3c7'],
                $score >= 20 => ['D', '#f97316', '#fff7ed'],
                default      => ['F', '#ef4444', '#fee2e2'],
            };

            $activityBadge = match(true) {
                $daysSince <= 0 => ['Live', '#15803d', '#dcfce7'],
                $daysSince <= 1 => ['Active', '#0891b2', '#e0f2fe'],
                $daysSince <= 7 => ['Idle', '#d97706', '#fef3c7'],
                default         => ['Dormant', '#b91c1c', '#fee2e2'],
            };

            $planExpired  = $hotel->plan_expires_at && Carbon::parse($hotel->plan_expires_at)->isPast();
            $trialExpired = $hotel->trial_ends_at  && Carbon::parse($hotel->trial_ends_at)->isPast();
            $inactive3d   = $daysSince >= 3;
            $needsAttention = ($planExpired || $trialExpired || $inactive3d);

            return (object) [
                'id'              => $hotel->id,
                'name'            => $hotel->name,
                'plan'            => $hotel->plan,
                'status'          => $hotel->status,
                'email'           => $hotel->email,
                'phone'           => $hotel->phone,
                'last_activity'   => $lastAt?->format('d M y, H:i') ?? 'Never',
                'days_since'      => $daysSince,
                'activity_badge'  => $activityBadge,
                'total_rooms'     => $totalRooms,
                'occupied_rooms'  => $occupiedRooms,
                'available_rooms' => (int)($rs?->available_rooms ?? 0),
                'occupancy_pct'   => $occupancyPct,
                'bookings_month'  => $bkThis,
                'bk_growth'       => $bkGrowth,
                'revenue_month'   => $revThis,
                'rev_last'        => $revLast,
                'rev_growth'      => $revGrowth,
                'score'           => $score,
                'grade'           => $grade,
                'devices'         => (int)($dev?->cnt ?? 0),
                'created_at'        => $hotel->created_at,
                'plan_expired'      => $planExpired || $trialExpired,
                'inactive_3d'       => $inactive3d,
                'needs_attention'   => $needsAttention,
                'owner_wa_consent'  => (bool)($hotel->owner_wa_consent ?? false),
            ];
        })->when($this->filterInactive !== '0', function ($col) {
            $days = (int) $this->filterInactive;
            return $col->filter(fn($h) => $h->days_since >= $days);
        })->sortByDesc('score');
    }

    private function getKpiData(): array
    {
        $now            = Carbon::now();
        $thisMonth      = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $totalHotels     = DB::table('hotels')->count();
        $activeHotels    = DB::table('hotels')->where('status', 'active')->count();
        $suspendedHotels = DB::table('hotels')->where('status', 'suspended')->count();
        $trialHotels     = DB::table('hotels')->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', $now)->count();
        $inactiveHotels  = $totalHotels - DB::table('activity_logs')
            ->select('hotel_id')
            ->distinct()
            ->where('created_at', '>=', $now->copy()->subDays(2))
            ->count();

        $totalRooms    = DB::table('rooms')->count();
        $occupiedRooms = DB::table('rooms')->where('status', 'occupied')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $revMonth  = (float) DB::table('payments')->where('status', 'completed')->where('created_at', '>=', $thisMonth)->sum('amount');
        $revLast   = (float) DB::table('payments')->where('status', 'completed')->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('amount');
        $revGrowth = $revLast > 0 ? round((($revMonth - $revLast) / $revLast) * 100, 1) : ($revMonth > 0 ? 100 : 0);

        $totalBookingsMonth = DB::table('bookings')->where('created_at', '>=', $thisMonth)->count();
        $totalDevices       = DB::table('fcm_tokens')->count();
        $waEnabled          = DB::table('whatsapp_configs')->where('setup_completed', true)->count();

        // Active 30 min
        $activeNow = DB::table('activity_logs')
            ->where('created_at', '>=', $now->copy()->subMinutes(30))
            ->distinct('user_name')
            ->count('user_name');

        // Platform health score (0-100)
        $healthScore = min(100, (int)(
            ($activeHotels / max(1, $totalHotels)) * 40 +
            $occupancyRate * 0.3 +
            ($revGrowth > 0 ? 20 : ($revGrowth > -10 ? 10 : 0)) +
            ($activeNow > 0 ? 10 : 0)
        ));

        return compact(
            'totalHotels', 'activeHotels', 'suspendedHotels', 'trialHotels', 'inactiveHotels',
            'totalRooms', 'occupiedRooms', 'occupancyRate',
            'revMonth', 'revLast', 'revGrowth',
            'totalBookingsMonth', 'totalDevices', 'waEnabled',
            'activeNow', 'healthScore'
        );
    }

    private function getChartData(): array
    {
        $now    = Carbon::now();
        $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i));

        // Monthly bookings + revenue (last 6 months)
        $bookingRows = DB::table('bookings')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN status='checked_in' THEN 1 ELSE 0 END) as checkins"),
                DB::raw("SUM(CASE WHEN status='checked_out' THEN 1 ELSE 0 END) as checkouts"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()->keyBy('month');

        $revenueRows = DB::table('payments')
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()->keyBy('month');

        // Daily bookings last 30 days (for sparkline)
        $dailyBookings = DB::table('bookings')
            ->select(DB::raw("DATE(created_at) as day"), DB::raw('COUNT(*) as cnt'))
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupByRaw("DATE(created_at)")
            ->get()->keyBy('day');

        $planDist   = DB::table('hotels')->select('plan', DB::raw('COUNT(*) as cnt'))->groupBy('plan')->get();
        $statusDist = DB::table('hotels')->select('status', DB::raw('COUNT(*) as cnt'))->groupBy('status')->get();

        $occupancy = DB::table('rooms')
            ->join('hotels', 'rooms.hotel_id', '=', 'hotels.id')
            ->select('hotels.name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN rooms.status='occupied' THEN 1 ELSE 0 END) as occupied")
            )
            ->groupBy('hotels.id', 'hotels.name')
            ->orderByDesc(DB::raw("SUM(CASE WHEN rooms.status='occupied' THEN 1 ELSE 0 END)"))
            ->limit(8)
            ->get();

        $monthLabels  = $months->map(fn($m) => $m->format('M Y'))->values()->toArray();
        $monthKeys    = $months->map(fn($m) => $m->format('Y-m'))->values()->toArray();
        $checkinData  = array_map(fn($k) => (int)($bookingRows[$k]?->checkins ?? 0), $monthKeys);
        $checkoutData = array_map(fn($k) => (int)($bookingRows[$k]?->checkouts ?? 0), $monthKeys);
        $revenueData  = array_map(fn($k) => (float)($revenueRows[$k]?->total ?? 0), $monthKeys);

        // 30-day sparkline
        $sparkDays = collect(range(29, 0))->map(fn($i) => $now->copy()->subDays($i)->toDateString())->values()->toArray();
        $sparkData = array_map(fn($d) => (int)($dailyBookings[$d]?->cnt ?? 0), $sparkDays);

        return [
            'monthLabels'  => $monthLabels,
            'checkinData'  => $checkinData,
            'checkoutData' => $checkoutData,
            'revenueData'  => $revenueData,
            'sparkData'    => $sparkData,
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
        if (!$this->selectedHotelId) return null;

        $hotel = DB::table('hotels')->where('id', $this->selectedHotelId)->first();
        if (!$hotel) return null;

        // 6-month revenue trend for this hotel
        $revTrend = DB::table('payments')
            ->where('hotel_id', $this->selectedHotelId)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->select(DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"), DB::raw('SUM(amount) as total'))
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()->keyBy('month');

        $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i)->format('Y-m'))->values()->toArray();
        $revTrendData = array_map(fn($m) => (float)($revTrend[$m]?->total ?? 0), $months);

        $recentActivity = DB::table('activity_logs')
            ->where('hotel_id', $this->selectedHotelId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $bookingBreakdown = DB::table('bookings')
            ->where('hotel_id', $this->selectedHotelId)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->get();

        $totalRevenue = DB::table('payments')
            ->where('hotel_id', $this->selectedHotelId)
            ->where('status', 'completed')
            ->sum('amount');

        $rooms = DB::table('rooms')
            ->where('hotel_id', $this->selectedHotelId)
            ->get(['room_number', 'type', 'status', 'price_per_night']);

        $devices = DB::table('fcm_tokens')->where('hotel_id', $this->selectedHotelId)->count();

        return (object) compact('hotel', 'recentActivity', 'bookingBreakdown', 'totalRevenue', 'rooms', 'devices', 'revTrendData');
    }

    public function render()
    {
        // ── Platform-wide metrics: cached 5 min ──────────────────────────
        $kpi        = Cache::remember('analytics_kpi',        300, fn() => $this->getKpiData());
        $charts     = Cache::remember('analytics_charts',     300, fn() => $this->getChartData());
        $prediction = Cache::remember('analytics_prediction', 300, fn() => $this->revenuePrediction());

        // ── Hotel engagement: filter-sensitive, cached per filter combo ──
        $engKey          = 'analytics_hotels_' . md5($this->filterPlan . '|' . $this->filterStatus . '|' . $this->filterInactive);
        $hotelEngagement = Cache::remember($engKey, 300, fn() => $this->hotelEngagement());

        // ── Active sessions: real-time, never cached ─────────────────────
        $activeSessions = $this->activeSessions();
        $selectedDetail = $this->getSelectedHotelDetail();
        $plans          = DB::table('hotels')->distinct()->pluck('plan')->sort()->values();

        return view('livewire.platform.analytics-dashboard', compact(
            'hotelEngagement', 'kpi', 'charts', 'prediction',
            'activeSessions', 'selectedDetail', 'plans'
        ));
    }
}
