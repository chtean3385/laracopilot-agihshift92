<div wire:poll.60000ms>

{{-- ─── Filter Bar ─────────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <span style="font-size:13px;font-weight:700;color:#374151;"><i class="fas fa-filter" style="color:#7c3aed;margin-right:6px;"></i>Filter</span>
    <select wire:model.live="filterPlan" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;background:#f8fafc;">
        <option value="">All Plans</option>
        @foreach($plans as $p)
        <option value="{{ $p }}">{{ ucfirst($p) }}</option>
        @endforeach
    </select>
    <select wire:model.live="filterStatus" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;background:#f8fafc;">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
        <option value="inactive">Inactive</option>
    </select>
    <select wire:model.live="filterInactive" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;background:#f8fafc;">
        <option value="0">Any Activity</option>
        <option value="2">Idle ≥ 2 days</option>
        <option value="7">Idle ≥ 7 days</option>
        <option value="30">Idle ≥ 30 days</option>
    </select>
    <span style="font-size:12px;color:#94a3b8;margin-left:auto;">
        <i class="fas fa-sync-alt" style="margin-right:4px;"></i>Auto-refreshes every 60s
    </span>
</div>

{{-- ─── KPI Cards ──────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:22px;">

    @php
    $cards = [
        ['label'=>'Total Hotels',   'val'=>$kpi['totalHotels'],     'icon'=>'fas fa-building',     'color'=>'#7c3aed','bg'=>'#f5f3ff'],
        ['label'=>'Active',         'val'=>$kpi['activeHotels'],    'icon'=>'fas fa-check-circle', 'color'=>'#15803d','bg'=>'#dcfce7'],
        ['label'=>'Dormant (2d+)',  'val'=>$kpi['inactiveHotels'],  'icon'=>'fas fa-moon',         'color'=>'#92400e','bg'=>'#fef3c7'],
        ['label'=>'Trials',         'val'=>$kpi['trialHotels'],     'icon'=>'fas fa-hourglass',    'color'=>'#1d4ed8','bg'=>'#dbeafe'],
        ['label'=>'Suspended',      'val'=>$kpi['suspendedHotels'], 'icon'=>'fas fa-ban',          'color'=>'#b91c1c','bg'=>'#fee2e2'],
        ['label'=>'Total Rooms',    'val'=>$kpi['totalRooms'],      'icon'=>'fas fa-bed',          'color'=>'#0891b2','bg'=>'#e0f2fe'],
        ['label'=>'Occupied',       'val'=>$kpi['occupiedRooms'],   'icon'=>'fas fa-door-closed',  'color'=>'#7c3aed','bg'=>'#f5f3ff'],
        ['label'=>'Available',      'val'=>$kpi['availRooms'],      'icon'=>'fas fa-door-open',    'color'=>'#15803d','bg'=>'#dcfce7'],
        ['label'=>'WA Enabled',     'val'=>$kpi['waEnabled'],       'icon'=>'fab fa-whatsapp',     'color'=>'#15803d','bg'=>'#dcfce7'],
    ];
    @endphp

    @foreach($cards as $c)
    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;background:{{ $c['bg'] }};border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $c['icon'] }}" style="color:{{ $c['color'] }};font-size:14px;"></i>
            </div>
            <span style="font-size:12px;color:#94a3b8;font-weight:600;">{{ $c['label'] }}</span>
        </div>
        <div style="font-size:26px;font-weight:900;color:#1e293b;">{{ number_format($c['val']) }}</div>
    </div>
    @endforeach

    {{-- Revenue & bookings with growth --}}
    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;background:#f0fdf4;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-rupee-sign" style="color:#15803d;font-size:14px;"></i>
            </div>
            <span style="font-size:12px;color:#94a3b8;font-weight:600;">Revenue (Month)</span>
        </div>
        <div style="font-size:22px;font-weight:900;color:#1e293b;">₹{{ number_format($kpi['revMonth']) }}</div>
        @if($kpi['revGrowth'] != 0)
        <div style="font-size:11px;margin-top:2px;color:{{ $kpi['revGrowth'] >= 0 ? '#15803d' : '#b91c1c' }};">
            <i class="fas fa-arrow-{{ $kpi['revGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($kpi['revGrowth']) }}% vs last month
        </div>
        @endif
    </div>

    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;background:#e0f2fe;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-sign-in-alt" style="color:#0891b2;font-size:14px;"></i>
            </div>
            <span style="font-size:12px;color:#94a3b8;font-weight:600;">Check-ins (Month)</span>
        </div>
        <div style="font-size:26px;font-weight:900;color:#1e293b;">{{ $kpi['checkinsMonth'] }}</div>
        @if($kpi['ciGrowth'] != 0)
        <div style="font-size:11px;margin-top:2px;color:{{ $kpi['ciGrowth'] >= 0 ? '#15803d' : '#b91c1c' }};">
            <i class="fas fa-arrow-{{ $kpi['ciGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($kpi['ciGrowth']) }}% vs last month
        </div>
        @endif
    </div>

    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;background:#fef3c7;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-sign-out-alt" style="color:#d97706;font-size:14px;"></i>
            </div>
            <span style="font-size:12px;color:#94a3b8;font-weight:600;">Check-outs (Month)</span>
        </div>
        <div style="font-size:26px;font-weight:900;color:#1e293b;">{{ $kpi['checkoutsMonth'] }}</div>
        @if($kpi['coGrowth'] != 0)
        <div style="font-size:11px;margin-top:2px;color:{{ $kpi['coGrowth'] >= 0 ? '#15803d' : '#b91c1c' }};">
            <i class="fas fa-arrow-{{ $kpi['coGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($kpi['coGrowth']) }}% vs last month
        </div>
        @endif
    </div>
</div>

{{-- ─── Charts Row ─────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:22px;">
    <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:14px;"><i class="fas fa-chart-bar" style="color:#7c3aed;margin-right:6px;"></i>Bookings — Last 6 Months</div>
        <div id="chart-bookings"></div>
    </div>
    <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:14px;"><i class="fas fa-chart-pie" style="color:#06b6d4;margin-right:6px;"></i>Hotel Plans</div>
        <div id="chart-plans"></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;">
    <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:14px;"><i class="fas fa-chart-area" style="color:#10b981;margin-right:6px;"></i>Revenue Trend — Last 6 Months</div>
        <div id="chart-revenue"></div>
    </div>
    <div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:14px;"><i class="fas fa-bed" style="color:#f59e0b;margin-right:6px;"></i>Room Occupancy by Hotel</div>
        <div id="chart-occupancy"></div>
    </div>
</div>

{{-- ─── Hotel Engagement Table ─────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;margin-bottom:22px;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:15px;font-weight:800;color:#1e293b;"><i class="fas fa-hotel" style="color:#7c3aed;margin-right:8px;"></i>Hotel Engagement — {{ $hotelEngagement->count() }} hotels</div>
        <a href="{{ route('platform.analytics.campaigns') }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;">
            <i class="fas fa-bullhorn"></i> Send Campaign
        </a>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748b;">Hotel</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;">Plan</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;">Status</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;">Activity</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;">Rooms</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;">Bookings/Mo</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:700;color:#64748b;">Revenue/Mo</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:700;color:#64748b;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($hotelEngagement as $h)
                @php
                    $actColor = match($h->activity_badge) {
                        'active'  => ['#dcfce7','#15803d','Active'],
                        'idle'    => ['#fef3c7','#92400e','Idle'],
                        default   => ['#fee2e2','#b91c1c','Dormant'],
                    };
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;"
                    onclick="@this.set('selectedHotelId', {{ $h->id }})"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding:11px 16px;">
                        <div style="font-weight:700;color:#1e293b;">{{ $h->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $h->email }}</div>
                    </td>
                    <td style="padding:11px 12px;text-align:center;">
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#f1f5f9;color:#374151;">{{ strtoupper($h->plan) }}</span>
                    </td>
                    <td style="padding:11px 12px;text-align:center;">
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $h->status === 'active' ? '#dcfce7' : '#fee2e2' }};color:{{ $h->status === 'active' ? '#15803d' : '#b91c1c' }};">{{ ucfirst($h->status) }}</span>
                    </td>
                    <td style="padding:11px 12px;text-align:center;">
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $actColor[0] }};color:{{ $actColor[1] }};">{{ $actColor[2] }}</span>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $h->last_activity }}</div>
                    </td>
                    <td style="padding:11px 12px;text-align:center;">
                        <span style="font-weight:700;color:#1e293b;">{{ $h->total_rooms }}</span>
                        <span style="font-size:10px;color:#94a3b8;"> ({{ $h->occupied_rooms }} occ.)</span>
                    </td>
                    <td style="padding:11px 12px;text-align:center;font-weight:700;color:#1e293b;">{{ $h->bookings_month }}</td>
                    <td style="padding:11px 12px;text-align:right;font-weight:700;color:#15803d;">₹{{ number_format($h->revenue_month) }}</td>
                    <td style="padding:11px 12px;text-align:center;">
                        <i class="fas fa-chevron-right" style="color:#94a3b8;font-size:11px;"></i>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="padding:30px;text-align:center;color:#94a3b8;font-style:italic;">No hotels match the current filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ─── Hotel Drill-Down Panel ─────────────────────────────────────────── --}}
@if($selectedDetail)
@php $d = $selectedDetail; @endphp
<div style="background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 4px 20px rgba(0,0,0,.1);border:2px solid #7c3aed;margin-bottom:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:18px;font-weight:800;color:#1e293b;">{{ $d->hotel->name }} — Detail View</div>
            <div style="font-size:13px;color:#94a3b8;">{{ $d->hotel->email }} · Plan: {{ strtoupper($d->hotel->plan) }}</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('platform.hotels.edit', $d->hotel->id) }}" style="padding:7px 14px;background:#f1f5f9;color:#374151;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-edit"></i> Edit Hotel</a>
            <button wire:click="clearSelected" style="padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-times"></i> Close</button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">
        @foreach([['Rooms',$d->rooms->count(),'#7c3aed'],['Bookings (all)',DB::table('bookings')->where('hotel_id',$d->hotel->id)->count(),'#0891b2'],['Revenue Total','₹'.number_format($d->totalRevenue),'#15803d'],['Recent Activity',$d->recentActivity->count().' logs','#d97706']] as [$label,$val,$color])
        <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;">
            <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:4px;">{{ $label }}</div>
            <div style="font-size:20px;font-weight:800;color:{{ $color }};">{{ $val }}</div>
        </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:10px;"><i class="fas fa-history" style="color:#7c3aed;margin-right:6px;"></i>Recent Activity</div>
            @forelse($d->recentActivity as $log)
            <div style="padding:8px 12px;background:#f8fafc;border-radius:9px;margin-bottom:6px;">
                <div style="font-size:12px;font-weight:600;color:#374151;">{{ $log->action }} — {{ $log->module }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $log->user_name }} · {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
            </div>
            @empty
            <div style="font-size:13px;color:#94a3b8;font-style:italic;">No activity logged yet.</div>
            @endforelse
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:10px;"><i class="fas fa-chart-bar" style="color:#0891b2;margin-right:6px;"></i>Booking Breakdown</div>
            @foreach($d->bookingBreakdown as $b)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:9px;margin-bottom:6px;">
                <span style="font-size:12px;font-weight:600;color:#374151;">{{ ucfirst($b->status) }}</span>
                <span style="font-size:13px;font-weight:800;color:#1e293b;">{{ $b->cnt }}</span>
            </div>
            @endforeach
            <div style="font-size:13px;font-weight:700;color:#1e293b;margin-top:14px;margin-bottom:8px;"><i class="fas fa-rupee-sign" style="color:#15803d;margin-right:6px;"></i>Recent Payments</div>
            @forelse($d->payments as $pay)
            <div style="display:flex;justify-content:space-between;padding:7px 12px;background:#f8fafc;border-radius:9px;margin-bottom:5px;">
                <span style="font-size:12px;color:#374151;">{{ ucfirst($pay->payment_method ?? 'N/A') }}</span>
                <span style="font-size:12px;font-weight:700;color:#15803d;">₹{{ number_format($pay->amount) }}</span>
            </div>
            @empty
            <div style="font-size:13px;color:#94a3b8;font-style:italic;">No payments yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endif

{{-- ─── Chart Data JSON (hidden, for JS) ──────────────────────────────── --}}
<script id="chart-data" type="application/json">@json($charts)</script>

</div>

@script
<script>
function initCharts() {
    const raw = document.getElementById('chart-data');
    if (!raw || typeof ApexCharts === 'undefined') return;

    const data = JSON.parse(raw.textContent);

    // Bookings stacked bar
    const el1 = document.getElementById('chart-bookings');
    if (el1 && !el1._chart) {
        el1._chart = new ApexCharts(el1, {
            chart: { type: 'bar', height: 200, stacked: true, toolbar: { show: false } },
            series: [
                { name: 'Check-ins',  data: data.checkinData },
                { name: 'Check-outs', data: data.checkoutData }
            ],
            xaxis: { categories: data.monthLabels, labels: { style: { fontSize: '11px' } } },
            colors: ['#7c3aed', '#06b6d4'],
            legend: { position: 'top', fontSize: '12px' },
            grid: { borderColor: '#f1f5f9' },
            plotOptions: { bar: { borderRadius: 4 } },
        });
        el1._chart.render();
    }

    // Plan donut
    const el2 = document.getElementById('chart-plans');
    if (el2 && !el2._chart) {
        el2._chart = new ApexCharts(el2, {
            chart: { type: 'donut', height: 200 },
            series: data.planCounts,
            labels: data.planLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            colors: ['#7c3aed','#06b6d4','#10b981','#f59e0b','#ef4444','#64748b'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { style: { fontSize: '11px' } },
        });
        el2._chart.render();
    }

    // Revenue area
    const el3 = document.getElementById('chart-revenue');
    if (el3 && !el3._chart) {
        el3._chart = new ApexCharts(el3, {
            chart: { type: 'area', height: 200, toolbar: { show: false } },
            series: [{ name: 'Revenue (₹)', data: data.revenueData }],
            xaxis: { categories: data.monthLabels, labels: { style: { fontSize: '11px' } } },
            colors: ['#10b981'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f1f5f9' },
            stroke: { curve: 'smooth', width: 2 },
            yaxis: { labels: { formatter: v => '₹' + Math.round(v).toLocaleString('en-IN') } },
        });
        el3._chart.render();
    }

    // Occupancy bar
    const el4 = document.getElementById('chart-occupancy');
    if (el4 && !el4._chart) {
        el4._chart = new ApexCharts(el4, {
            chart: { type: 'bar', height: 200, toolbar: { show: false } },
            series: [
                { name: 'Occupied', data: data.occOccupied },
                { name: 'Total',    data: data.occTotal }
            ],
            xaxis: { categories: data.occHotels, labels: { style: { fontSize: '10px' } } },
            colors: ['#f59e0b','#e2e8f0'],
            plotOptions: { bar: { horizontal: false, borderRadius: 4 } },
            legend: { position: 'top', fontSize: '11px' },
            grid: { borderColor: '#f1f5f9' },
            dataLabels: { enabled: false },
        });
        el4._chart.render();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof ApexCharts !== 'undefined') initCharts();
});

document.addEventListener('livewire:update', () => {
    ['chart-bookings','chart-plans','chart-revenue','chart-occupancy'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el._chart) { el._chart.destroy(); delete el._chart; }
    });
    setTimeout(initCharts, 100);
});
</script>
@endscript
