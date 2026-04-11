<div wire:poll.60000ms>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- HERO BANNER                                                           --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 40%,#312e81 70%,#4c1d95 100%);border-radius:20px;padding:28px 30px;margin-bottom:24px;position:relative;overflow:hidden;">

    {{-- Decorative blobs --}}
    <div style="position:absolute;top:-40px;right:-40px;width:250px;height:250px;background:radial-gradient(circle,rgba(124,58,237,.4),transparent 70%);border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-50px;left:100px;width:200px;height:200px;background:radial-gradient(circle,rgba(6,182,212,.3),transparent 70%);border-radius:50%;pointer-events:none;"></div>

    <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:20px;align-items:start;flex-wrap:wrap;position:relative;z-index:1;">

        {{-- Platform health --}}
        <div>
            <div style="font-size:11px;font-weight:700;color:#a5b4fc;letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">Platform Health</div>
            <div style="display:flex;align-items:center;gap:14px;">
                {{-- Score ring --}}
                <div style="position:relative;width:72px;height:72px;flex-shrink:0;">
                    <svg width="72" height="72" viewBox="0 0 72 72" style="transform:rotate(-90deg);">
                        <circle cx="36" cy="36" r="30" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="7"/>
                        <circle cx="36" cy="36" r="30" fill="none" stroke="url(#scoreGrad)" stroke-width="7"
                            stroke-dasharray="{{ round(188.5 * $kpi['healthScore'] / 100) }} 188.5"
                            stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="scoreGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#a78bfa"/>
                                <stop offset="100%" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                        <span style="font-size:19px;font-weight:900;color:#fff;line-height:1;">{{ $kpi['healthScore'] }}</span>
                        <span style="font-size:8px;color:#a5b4fc;letter-spacing:1px;">SCORE</span>
                    </div>
                </div>
                <div>
                    <div style="font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1;">
                        @if($kpi['healthScore'] >= 80) 🟢 Excellent
                        @elseif($kpi['healthScore'] >= 60) 🟡 Good
                        @elseif($kpi['healthScore'] >= 40) 🟠 Fair
                        @else 🔴 Needs Attention
                        @endif
                    </div>
                    <div style="font-size:13px;color:#c4b5fd;margin-top:4px;">
                        {{ $kpi['activeHotels'] }}/{{ $kpi['totalHotels'] }} hotels active · {{ $kpi['occupancyRate'] }}% occupancy
                    </div>
                    @if($kpi['activeNow'] > 0)
                    <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);border-radius:20px;padding:3px 10px;margin-top:6px;">
                        <span style="width:7px;height:7px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse-dot 1.5s infinite;"></span>
                        <span style="font-size:12px;font-weight:700;color:#6ee7b7;">{{ $kpi['activeNow'] }} user{{ $kpi['activeNow'] > 1 ? 's' : '' }} online now</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Next Month Prediction --}}
        <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(8px);border-radius:16px;padding:16px 20px;min-width:160px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#a5b4fc;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px;">Next Month</div>
            <div style="font-size:22px;font-weight:900;color:#fff;">₹{{ number_format($prediction['nextMonth']) }}</div>
            <div style="font-size:11px;color:{{ $prediction['trend'] === 'up' ? '#6ee7b7' : ($prediction['trend'] === 'down' ? '#fca5a5' : '#a5b4fc') }};margin-top:4px;">
                @if($prediction['trend'] === 'up') ↑ +{{ abs($prediction['trendPct']) }}% trend
                @elseif($prediction['trend'] === 'down') ↓ {{ $prediction['trendPct'] }}% trend
                @else → Stable
                @endif
            </div>
            <div style="font-size:10px;color:#6b7280;margin-top:2px;">{{ $prediction['confidence'] }}% confidence</div>
        </div>

        {{-- Next Year Prediction --}}
        <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(8px);border-radius:16px;padding:16px 20px;min-width:160px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#a5b4fc;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px;">12-Month Forecast</div>
            <div style="font-size:22px;font-weight:900;color:#fff;">₹{{ number_format($prediction['nextYear']) }}</div>
            <div style="font-size:11px;color:#c4b5fd;margin-top:4px;">Based on 6-month trend</div>
            <div style="margin-top:8px;background:rgba(255,255,255,.1);border-radius:6px;height:4px;overflow:hidden;">
                <div style="height:100%;width:{{ $prediction['confidence'] }}%;background:linear-gradient(90deg,#a78bfa,#06b6d4);border-radius:6px;"></div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach([
                ['₹'.number_format($kpi['revMonth']), 'This Month', $kpi['revGrowth'] >= 0 ? '↑' : '↓', $kpi['revGrowth'] >= 0 ? '#6ee7b7' : '#fca5a5', abs($kpi['revGrowth']).'%'],
                [$kpi['totalBookingsMonth'].' bookings', 'This Month', '📅', '#c4b5fd', ''],
                [$kpi['totalDevices'].' devices', 'Push Registered', '📱', '#7dd3fc', ''],
            ] as [$val, $lbl, $icon, $color, $badge])
            <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.06);border-radius:10px;padding:8px 12px;">
                <div style="font-size:14px;font-weight:900;color:{{ $color }};">{{ $val }}</div>
                <div>
                    <div style="font-size:10px;color:#6b7280;">{{ $lbl }}</div>
                    @if($badge)<div style="font-size:10px;color:{{ $color }};">{{ $icon }} {{ $badge }}</div>@endif
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FILTER BAR + TABS                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;padding:14px 18px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <div style="display:flex;gap:0;border:1px solid #e2e8f0;border-radius:9px;overflow:hidden;margin-right:6px;">
        <button wire:click="$set('activeTab','hotels')" style="padding:6px 14px;font-size:12px;font-weight:700;border:none;cursor:pointer;background:{{ $activeTab==='hotels' ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : '#fff' }};color:{{ $activeTab==='hotels' ? '#fff' : '#374151' }};">
            <i class="fas fa-hotel" style="margin-right:5px;"></i>Hotels
        </button>
        <button wire:click="$set('activeTab','active')" style="padding:6px 14px;font-size:12px;font-weight:700;border:none;cursor:pointer;background:{{ $activeTab==='active' ? 'linear-gradient(135deg,#10b981,#059669)' : '#fff' }};color:{{ $activeTab==='active' ? '#fff' : '#374151' }};position:relative;">
            <i class="fas fa-circle" style="margin-right:5px;font-size:8px;{{ $activeSessions['total_users'] > 0 ? 'color:#10b981;' : '' }}"></i>Live Sessions
            @if($activeSessions['total_users'] > 0)
            <span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:8px;font-size:9px;font-weight:700;padding:1px 5px;">{{ $activeSessions['total_users'] }}</span>
            @endif
        </button>
    </div>

    <select wire:model.live="filterPlan" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:12px;color:#374151;background:#f8fafc;">
        <option value="">All Plans</option>
        @foreach($plans as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach
    </select>
    <select wire:model.live="filterStatus" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:12px;color:#374151;background:#f8fafc;">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
    </select>
    <select wire:model.live="filterInactive" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:12px;color:#374151;background:#f8fafc;">
        <option value="0">Any Activity</option>
        <option value="2">Idle ≥ 2 days</option>
        <option value="7">Idle ≥ 7 days</option>
        <option value="30">Dormant ≥ 30 days</option>
    </select>

    <span style="font-size:11px;color:#94a3b8;margin-left:auto;display:flex;align-items:center;gap:5px;">
        <span style="width:7px;height:7px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse-dot 2s infinite;"></span>
        Auto-refresh every 60s
    </span>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- KPI CARDS ROW                                                         --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:22px;">

    @php
    $kpiCards = [
        ['Total Hotels',   $kpi['totalHotels'],      'fas fa-building',    'linear-gradient(135deg,#7c3aed,#5b21b6)', '#f5f3ff', null, null],
        ['Active Hotels',  $kpi['activeHotels'],     'fas fa-check-circle','linear-gradient(135deg,#10b981,#059669)', '#dcfce7', null, null],
        ['Dormant (2d+)',  $kpi['inactiveHotels'],   'fas fa-moon',        'linear-gradient(135deg,#f59e0b,#d97706)', '#fef3c7', null, null],
        ['On Trial',       $kpi['trialHotels'],      'fas fa-hourglass-half','linear-gradient(135deg,#3b82f6,#2563eb)','#dbeafe', null, null],
        ['Suspended',      $kpi['suspendedHotels'],  'fas fa-ban',         'linear-gradient(135deg,#ef4444,#dc2626)', '#fee2e2', null, null],
        ['Total Rooms',    $kpi['totalRooms'],        'fas fa-bed',         'linear-gradient(135deg,#06b6d4,#0891b2)', '#e0f2fe', null, null],
        ['Occupied',       $kpi['occupiedRooms'],     'fas fa-door-closed', 'linear-gradient(135deg,#8b5cf6,#6d28d9)', '#f5f3ff', null, null],
        ['Occupancy Rate', $kpi['occupancyRate'].'%', 'fas fa-percent',     'linear-gradient(135deg,#ec4899,#db2777)', '#fdf2f8', null, null],
        ['WA Enabled',     $kpi['waEnabled'],         'fab fa-whatsapp',    'linear-gradient(135deg,#25d366,#128c43)', '#dcfce7', null, null],
    ];
    @endphp

    @foreach($kpiCards as [$label, $value, $icon, $grad, $bg, $trend, $trendPct])
    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,.1)'"
         onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(0,0,0,.05)'">
        <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:{{ $grad }};opacity:.08;border-radius:0 14px 0 60px;"></div>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:8px;">
            <div style="width:32px;height:32px;background:{{ $grad }};border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 8px rgba(0,0,0,.15);">
                <i class="{{ $icon }}" style="color:#fff;font-size:13px;"></i>
            </div>
            <span style="font-size:11px;color:#94a3b8;font-weight:700;line-height:1.2;">{{ $label }}</span>
        </div>
        <div style="font-size:28px;font-weight:900;color:#0f172a;letter-spacing:-1px;">{{ $value }}</div>
    </div>
    @endforeach

    {{-- Revenue card with growth --}}
    <div style="background:#fff;border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,.1)'"
         onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(0,0,0,.05)'">
        <div style="position:absolute;top:0;right:0;width:60px;height:60px;background:linear-gradient(135deg,#10b981,#059669);opacity:.08;border-radius:0 14px 0 60px;"></div>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:8px;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#10b981,#059669);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 8px rgba(0,0,0,.15);">
                <i class="fas fa-rupee-sign" style="color:#fff;font-size:13px;"></i>
            </div>
            <span style="font-size:11px;color:#94a3b8;font-weight:700;">Revenue / Month</span>
        </div>
        <div style="font-size:20px;font-weight:900;color:#0f172a;letter-spacing:-0.5px;">₹{{ number_format($kpi['revMonth']) }}</div>
        @if($kpi['revGrowth'] != 0)
        <div style="font-size:11px;margin-top:3px;font-weight:700;color:{{ $kpi['revGrowth'] >= 0 ? '#10b981' : '#ef4444' }};">
            <i class="fas fa-arrow-{{ $kpi['revGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($kpi['revGrowth']) }}% vs last month
        </div>
        @endif
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- CHARTS ROW                                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px;">

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <div style="font-size:14px;font-weight:800;color:#0f172a;">Bookings — Last 6 Months</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Check-ins vs check-outs</div>
            </div>
            <div id="sparkline-wrap"></div>
        </div>
        <div id="chart-bookings"></div>
    </div>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:4px;">Plan Distribution</div>
        <div style="font-size:11px;color:#94a3b8;margin-bottom:14px;">Hotels by subscription</div>
        <div id="chart-plans"></div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;">

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:4px;">Revenue Trend</div>
        <div style="font-size:11px;color:#94a3b8;margin-bottom:14px;">Last 6 months + projection</div>
        <div id="chart-revenue"></div>
    </div>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
        <div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:4px;">Room Occupancy</div>
        <div style="font-size:11px;color:#94a3b8;margin-bottom:14px;">Per hotel breakdown</div>
        <div id="chart-occupancy"></div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- HOTEL SCOREBOARD / LIVE SESSIONS (TABBED)                            --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

@if($activeTab === 'hotels')

{{-- HOTEL PERFORMANCE SCOREBOARD --}}
<div style="background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;margin-bottom:22px;">
    <div style="padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:16px;font-weight:800;color:#0f172a;">🏆 Hotel Performance Scoreboard</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $hotelEngagement->count() }} hotels · sorted by performance score</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('platform.analytics.campaigns') }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(124,58,237,.3);">
                <i class="fas fa-bullhorn"></i> Broadcast Campaign
            </a>
        </div>
    </div>

    {{-- Grade legend --}}
    <div style="padding:10px 22px;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
        <span style="font-size:11px;font-weight:700;color:#64748b;">Performance Grades:</span>
        @foreach([['A','80+','#22c55e','#dcfce7'],['B','60-80','#3b82f6','#dbeafe'],['C','40-60','#f59e0b','#fef3c7'],['D','20-40','#f97316','#fff7ed'],['F','<20','#ef4444','#fee2e2']] as [$g,$range,$color,$bg])
        <span style="padding:2px 9px;background:{{ $bg }};color:{{ $color }};border-radius:6px;font-size:11px;font-weight:800;">{{ $g }} — {{ $range }}</span>
        @endforeach
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:11px 16px;text-align:left;font-weight:700;color:#475569;font-size:11px;letter-spacing:.5px;">HOTEL</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">GRADE</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">PLAN</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">ACTIVITY</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">OCCUPANCY</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">BOOKINGS/MO</th>
                    <th style="padding:11px 10px;text-align:right;font-weight:700;color:#475569;font-size:11px;">REVENUE/MO</th>
                    <th style="padding:11px 10px;text-align:center;font-weight:700;color:#475569;font-size:11px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hotelEngagement as $h)
                @php
                    $rowBg     = $h->needs_attention ? 'background:#fffbeb;' : '';
                    $rowBorder = $h->needs_attention ? 'border-left:3px solid #f59e0b;' : 'border-left:3px solid transparent;';
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;{{ $rowBg }}{{ $rowBorder }}"
                    onclick="@this.set('selectedHotelId', {{ $h->id === $selectedHotelId ? 0 : $h->id }})"
                    onmouseover="this.style.background='{{ $h->needs_attention ? '#fef9c3' : '#f8fafc' }}'" onmouseout="this.style.background='{{ $h->needs_attention ? '#fffbeb' : 'transparent' }}'">

                    <td style="padding:13px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:{{ $h->grade[1] }};border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;color:#fff;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.15);">
                                {{ mb_strtoupper(mb_substr($h->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="display:flex;align-items:center;gap:5px;">
                                    <span style="font-weight:800;color:#0f172a;font-size:13px;">{{ $h->name }}</span>
                                    @if($h->plan_expired)
                                    <span style="font-size:9px;font-weight:800;background:#fee2e2;color:#b91c1c;padding:1px 5px;border-radius:4px;white-space:nowrap;">EXPIRED</span>
                                    @endif
                                    @if($h->inactive_3d && !$h->plan_expired)
                                    <span style="font-size:9px;font-weight:800;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;white-space:nowrap;">INACTIVE</span>
                                    @endif
                                </div>
                                <div style="font-size:10px;color:#94a3b8;margin-top:1px;">{{ $h->email }}</div>
                            </div>
                        </div>
                    </td>

                    <td style="padding:13px 10px;text-align:center;">
                        <div style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:{{ $h->grade[2] }};border-radius:50%;font-size:16px;font-weight:900;color:{{ $h->grade[1] }};box-shadow:0 2px 6px rgba(0,0,0,.08);">{{ $h->grade[0] }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $h->score }}/100</div>
                    </td>

                    <td style="padding:13px 10px;text-align:center;">
                        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;background:#f1f5f9;color:#374151;letter-spacing:.5px;">{{ strtoupper($h->plan) }}</span>
                    </td>

                    <td style="padding:13px 10px;text-align:center;">
                        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $h->activity_badge[2] }};color:{{ $h->activity_badge[1] }};">{{ $h->activity_badge[0] }}</span>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $h->last_activity }}</div>
                    </td>

                    <td style="padding:13px 10px;">
                        <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                            <div style="flex:1;max-width:80px;background:#e2e8f0;border-radius:4px;height:6px;overflow:hidden;">
                                <div style="height:100%;width:{{ $h->occupancy_pct }}%;background:{{ $h->occupancy_pct >= 70 ? 'linear-gradient(90deg,#10b981,#059669)' : ($h->occupancy_pct >= 40 ? 'linear-gradient(90deg,#f59e0b,#d97706)' : 'linear-gradient(90deg,#ef4444,#dc2626)') }};border-radius:4px;transition:width .3s;"></div>
                            </div>
                            <span style="font-size:12px;font-weight:800;color:{{ $h->occupancy_pct >= 70 ? '#10b981' : ($h->occupancy_pct >= 40 ? '#f59e0b' : '#ef4444') }};min-width:34px;">{{ $h->occupancy_pct }}%</span>
                        </div>
                        <div style="font-size:10px;color:#94a3b8;text-align:center;margin-top:2px;">{{ $h->occupied_rooms }}/{{ $h->total_rooms }} rooms</div>
                    </td>

                    <td style="padding:13px 10px;text-align:center;">
                        <div style="font-size:16px;font-weight:900;color:#0f172a;">{{ $h->bookings_month }}</div>
                        @if($h->bk_growth != 0)
                        <div style="font-size:10px;font-weight:700;color:{{ $h->bk_growth >= 0 ? '#10b981' : '#ef4444' }};">
                            {{ $h->bk_growth >= 0 ? '↑' : '↓' }} {{ abs($h->bk_growth) }}%
                        </div>
                        @endif
                    </td>

                    <td style="padding:13px 10px;text-align:right;">
                        <div style="font-size:15px;font-weight:900;color:#0f172a;">₹{{ number_format($h->revenue_month) }}</div>
                        @if($h->rev_growth != 0)
                        <div style="font-size:10px;font-weight:700;color:{{ $h->rev_growth >= 0 ? '#10b981' : '#ef4444' }};">
                            {{ $h->rev_growth >= 0 ? '↑' : '↓' }} {{ abs($h->rev_growth) }}%
                        </div>
                        @endif
                    </td>

                    <td style="padding:13px 10px;text-align:center;" onclick="event.stopPropagation()">
                        <div style="display:flex;gap:5px;justify-content:center;align-items:center;">
                            <button
                                data-wa-hotel-id="{{ $h->id }}"
                                onclick="analyticsOpenModal({{ $h->id }}, '{{ addslashes($h->name) }}', '{{ addslashes($h->phone ?? '') }}', 'whatsapp', {{ $h->owner_wa_consent ? 'true' : 'false' }})"
                                title="Quick WhatsApp"
                                style="width:30px;height:30px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                                onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform=''">
                                <i class="fab fa-whatsapp" style="font-size:13px;"></i>
                            </button>
                            <button
                                onclick="analyticsOpenModal({{ $h->id }}, '{{ addslashes($h->name) }}', '', 'push', false)"
                                title="Quick Push"
                                style="width:30px;height:30px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                                onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform=''">
                                <i class="fas fa-bell" style="font-size:12px;"></i>
                            </button>
                            <a href="{{ route('platform.hotels.edit', $h->id) }}" title="Edit Hotel"
                               style="width:30px;height:30px;background:#f1f5f9;color:#374151;border-radius:8px;display:flex;align-items:center;justify-content:center;text-decoration:none;"
                               onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                <i class="fas fa-edit" style="font-size:11px;"></i>
                            </a>
                        </div>
                    </td>
                </tr>

                {{-- Drill-down panel --}}
                @if($selectedHotelId === $h->id && $selectedDetail)
                <tr style="background:#f8fafc;">
                    <td colspan="8" style="padding:0;border-bottom:2px solid #7c3aed;">
                        @php $d = $selectedDetail; @endphp
                        <div style="padding:20px 22px;">
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;">
                                @foreach([
                                    ['All-Time Revenue','₹'.number_format($d->totalRevenue),'#10b981'],
                                    ['Total Rooms',$d->rooms->count().' rooms','#7c3aed'],
                                    ['Push Devices',$d->devices.' registered','#06b6d4'],
                                    ['Booking Types',$d->bookingBreakdown->count().' statuses','#f59e0b'],
                                ] as [$lbl,$val,$color])
                                <div style="background:#fff;border-radius:12px;padding:13px 15px;border:1px solid #e2e8f0;">
                                    <div style="font-size:10px;color:#94a3b8;font-weight:700;margin-bottom:4px;">{{ $lbl }}</div>
                                    <div style="font-size:18px;font-weight:900;color:{{ $color }};">{{ $val }}</div>
                                </div>
                                @endforeach
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                                <div>
                                    <div style="font-size:12px;font-weight:800;color:#374151;margin-bottom:8px;"><i class="fas fa-chart-line" style="color:#7c3aed;margin-right:5px;"></i>6-Month Revenue</div>
                                    <div id="hotel-rev-sparkline-{{ $h->id }}"></div>
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:800;color:#374151;margin-bottom:8px;"><i class="fas fa-history" style="color:#06b6d4;margin-right:5px;"></i>Recent Activity</div>
                                    @forelse($d->recentActivity as $log)
                                    <div style="padding:6px 10px;background:#fff;border-radius:8px;margin-bottom:5px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <span style="font-size:11px;font-weight:700;color:#374151;">{{ $log->action }}</span>
                                            <span style="font-size:10px;color:#94a3b8;margin-left:4px;">{{ $log->module }}</span>
                                        </div>
                                        <span style="font-size:10px;color:#94a3b8;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                                    </div>
                                    @empty
                                    <div style="font-size:12px;color:#94a3b8;font-style:italic;">No activity yet.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <script>
                        (function() {
                            var el = document.getElementById('hotel-rev-sparkline-{{ $h->id }}');
                            if (!el || el._chart) return;
                            el._chart = new ApexCharts(el, {
                                chart: { type: 'area', height: 100, toolbar: { show:false }, sparkline: { enabled: false } },
                                series: [{ name: 'Revenue', data: @json($d->revTrendData) }],
                                xaxis: { categories: @json($charts['monthLabels']), labels: { style: { fontSize: '10px' } } },
                                colors: ['#7c3aed'],
                                fill: { type:'gradient', gradient: { opacityFrom:.4, opacityTo:.02 } },
                                stroke: { curve:'smooth', width:2 },
                                dataLabels: { enabled:false },
                                grid: { borderColor:'#f1f5f9', padding:{ left:0,right:0 } },
                                yaxis: { labels: { formatter: v => '₹'+Math.round(v/1000)+'k', style:{ fontSize:'10px' } } },
                            });
                            el._chart.render();
                        })();
                        </script>
                    </td>
                </tr>
                @endif

                @empty
                <tr><td colspan="8" style="padding:40px;text-align:center;color:#94a3b8;font-style:italic;">No hotels match the current filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
{{-- LIVE SESSIONS VIEW --}}
<div style="background:#fff;border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;margin-bottom:22px;">
    <div style="padding:18px 22px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#0f172a,#1e293b);display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:15px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;">
                <span style="width:9px;height:9px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse-dot 1.2s infinite;"></span>
                Live Sessions — Last 30 Minutes
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $activeSessions['total_users'] }} users across {{ $activeSessions['total_hotels'] }} hotels</div>
        </div>
    </div>
    @forelse($activeSessions['sessions'] as $sess)
    <div style="padding:14px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:16px;"
         onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#fff;flex-shrink:0;">
                {{ mb_strtoupper(mb_substr($sess->user_name, 0, 1)) }}
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $sess->user_name }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $sess->hotel_name }} · {{ $sess->user_role }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:12px;font-weight:700;color:#374151;">{{ $sess->action }} → {{ $sess->module }}</div>
            <div style="font-size:11px;color:#94a3b8;">{{ $sess->last_seen }}</div>
        </div>
    </div>
    @empty
    <div style="padding:60px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">😴</div>
        <div style="font-size:14px;font-weight:700;color:#374151;margin-bottom:4px;">No active sessions in the last 30 minutes</div>
        <div style="font-size:12px;color:#94a3b8;">Sessions appear here as hotel users take actions in the CRM.</div>
    </div>
    @endforelse
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- QUICK ACTION MODAL — Pure JS, no Livewire re-render on open/close ──  --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div id="analyticsQuickModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)analyticsCloseModal()">
    <div style="background:#fff;border-radius:20px;width:460px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;" onclick="event.stopPropagation()">

        <div id="aqmHeader" style="padding:20px 24px;background:linear-gradient(135deg,#128c43,#25d366);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div id="aqmTitle" style="font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;">
                    <i id="aqmIcon" class="fab fa-whatsapp"></i> Quick WhatsApp
                </div>
                <div id="aqmSubtitle" style="font-size:12px;color:rgba(255,255,255,.75);margin-top:2px;"></div>
            </div>
            <button onclick="analyticsCloseModal()" style="width:32px;height:32px;background:rgba(255,255,255,.2);border:none;border-radius:8px;color:#fff;cursor:pointer;font-size:16px;">✕</button>
        </div>

        <div style="padding:22px 24px;">
            <div id="aqmResult" style="display:none;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600;"></div>

            {{-- WA section --}}
            <div id="aqmWaSection">
                <div id="aqmConsentWarn" style="display:none;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:9px 12px;margin-bottom:12px;font-size:12px;font-weight:600;color:#92400e;">
                    ⚠️ This owner hasn't consented to WhatsApp messages yet. Proceed with caution.
                </div>
                <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Choose a template:</div>
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                    @foreach(\App\Http\Controllers\Platform\HotelController::platformWaTemplates() as $tplKey => $tpl)
                    <div id="aqm-tpl-{{ $tplKey }}"
                        onclick="analyticsSelectTpl('{{ $tplKey }}','{{ $tpl['meta_name'] }}','{{ $tpl['language'] }}')"
                        style="border:2px solid #e2e8f0;border-radius:11px;padding:11px 13px;cursor:pointer;background:#fff;transition:border-color .15s;">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:4px;">
                            {{ $tplKey === 'crm_update' ? '📣' : '🔔' }} {{ $tpl['label'] }}
                        </div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">
                            {{ Str::limit(str_replace(['{name}', '{url}'], ['[Hotel Name]', '[CRM URL]'], $tpl['preview']), 120) }}
                        </div>
                    </div>
                    @endforeach
                </div>
                <button id="aqmWaSendBtn" onclick="analyticsSendWA()"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;opacity:.5;"
                    disabled>
                    <i class="fab fa-whatsapp"></i> Send WhatsApp Now
                </button>
            </div>

            {{-- Push section --}}
            <div id="aqmPushSection" style="display:none;">
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Notification Title</label>
                    <input id="aqmPushTitle" type="text" placeholder="e.g. Important Update"
                        style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#374151;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Message Body</label>
                    <textarea id="aqmPushBody" rows="4" placeholder="Push notification body..."
                        style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#374151;resize:vertical;box-sizing:border-box;"></textarea>
                </div>
                <button onclick="analyticsSendPush()"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fas fa-bell"></i> Send Push Notification
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Chart data --}}
<script id="chart-data" type="application/json">@json($charts)</script>
<script id="prediction-data" type="application/json">@json($prediction)</script>

</div>

<style>
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .6; transform: scale(1.3); }
}
</style>

@script
<script>
function destroyCharts() {
    ['chart-bookings','chart-plans','chart-revenue','chart-occupancy','sparkline-wrap'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el._chart) { el._chart.destroy(); delete el._chart; }
    });
}

function initCharts() {
    const rawData  = document.getElementById('chart-data');
    const rawPred  = document.getElementById('prediction-data');
    if (!rawData || typeof ApexCharts === 'undefined') return;

    const data = JSON.parse(rawData.textContent);
    const pred = JSON.parse(rawPred.textContent);

    // ── Bookings stacked bar ──────────────────────────────────────────────
    const el1 = document.getElementById('chart-bookings');
    if (el1 && !el1._chart) {
        el1._chart = new ApexCharts(el1, {
            chart: { type:'bar', height:230, stacked:false, toolbar:{ show:false }, animations:{ enabled:true, speed:600 } },
            series: [
                { name:'Check-ins',  data: data.checkinData },
                { name:'Check-outs', data: data.checkoutData }
            ],
            xaxis: { categories: data.monthLabels, labels:{ style:{ fontSize:'11px', colors:'#94a3b8' } }, axisBorder:{ show:false }, axisTicks:{ show:false } },
            yaxis: { labels:{ style:{ colors:'#94a3b8', fontSize:'11px' } } },
            colors: ['#7c3aed','#06b6d4'],
            plotOptions: { bar:{ borderRadius:6, columnWidth:'50%' } },
            dataLabels: { enabled:false },
            legend: { position:'top', labels:{ colors:'#374151' }, fontSize:'12px' },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            fill: {
                type:['gradient','gradient'],
                gradient: { shade:'light', type:'vertical', shadeIntensity:.4, opacityFrom:.9, opacityTo:.6 }
            },
            tooltip: { theme:'light' },
        });
        el1._chart.render();
    }

    // ── Sparkline (30-day) ─────────────────────────────────────────────────
    const sp = document.getElementById('sparkline-wrap');
    if (sp && !sp._chart) {
        sp._chart = new ApexCharts(sp, {
            chart: { type:'line', height:40, width:120, sparkline:{ enabled:true } },
            series: [{ data: data.sparkData }],
            colors: ['#7c3aed'],
            stroke: { curve:'smooth', width:2 },
            tooltip: { enabled:false },
        });
        sp._chart.render();
    }

    // ── Plan donut ───────────────────────────────────────────────────────
    const el2 = document.getElementById('chart-plans');
    if (el2 && !el2._chart) {
        el2._chart = new ApexCharts(el2, {
            chart: { type:'donut', height:230 },
            series: data.planCounts,
            labels: data.planLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            colors: ['#7c3aed','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#64748b'],
            legend: { position:'bottom', fontSize:'11px', labels:{ colors:'#374151' } },
            dataLabels: { style:{ fontSize:'11px' } },
            plotOptions: { pie: { donut: { size:'65%', labels:{ show:true, total:{ show:true, label:'Hotels', color:'#94a3b8', fontSize:'12px', fontWeight:700 } } } } },
            stroke: { width:2 },
        });
        el2._chart.render();
    }

    // ── Revenue area with prediction annotation ───────────────────────────
    const el3 = document.getElementById('chart-revenue');
    if (el3 && !el3._chart) {
        const nextMonthVal = pred.nextMonth || 0;
        const revenueWithPred = [...data.revenueData, nextMonthVal];
        const labelsWithPred  = [...data.monthLabels, 'Next Month ▸'];

        el3._chart = new ApexCharts(el3, {
            chart: { type:'area', height:230, toolbar:{ show:false }, animations:{ enabled:true, speed:700 } },
            series: [{ name:'Revenue (₹)', data: revenueWithPred }],
            xaxis: { categories: labelsWithPred, labels:{ style:{ fontSize:'11px', colors:'#94a3b8' } }, axisBorder:{ show:false }, axisTicks:{ show:false } },
            yaxis: { labels:{ formatter: v => '₹'+Math.round(v/1000)+'k', style:{ colors:'#94a3b8', fontSize:'11px' } } },
            colors: ['#10b981'],
            fill: { type:'gradient', gradient:{ shade:'light', type:'vertical', shadeIntensity:.3, opacityFrom:.6, opacityTo:.02 } },
            dataLabels: { enabled:false },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            stroke: { curve:'smooth', width:2.5 },
            annotations: { points:[{ x:'Next Month ▸', y:nextMonthVal, marker:{ size:6, fillColor:'#a78bfa', strokeColor:'#fff', strokeWidth:2 }, label:{ text:'Predicted', style:{ background:'#7c3aed', color:'#fff', fontSize:'10px', fontWeight:700, borderRadius:4, padding:{ top:3, bottom:3, left:6, right:6 } } } }] },
            markers: { size:[4,4,4,4,4,4,6], colors:['#10b981'], strokeColors:'#fff', strokeWidth:2 },
            tooltip: { theme:'light' },
        });
        el3._chart.render();
    }

    // ── Occupancy radial/bar ──────────────────────────────────────────────
    const el4 = document.getElementById('chart-occupancy');
    if (el4 && !el4._chart) {
        const pcts = data.occTotal.map((t,i) => t > 0 ? Math.round(data.occOccupied[i]/t*100) : 0);
        el4._chart = new ApexCharts(el4, {
            chart: { type:'bar', height:230, toolbar:{ show:false }, animations:{ enabled:true, speed:500 } },
            series: [{ name:'Occupancy %', data: pcts }],
            xaxis: { categories: data.occHotels, labels:{ style:{ fontSize:'10px', colors:'#94a3b8' }, rotate:-15 }, axisBorder:{ show:false }, axisTicks:{ show:false } },
            yaxis: { max:100, labels:{ formatter:v=>v+'%', style:{ colors:'#94a3b8', fontSize:'10px' } } },
            colors: ['#f59e0b'],
            plotOptions: { bar: { borderRadius:8, columnWidth:'55%' } },
            dataLabels: { enabled:true, formatter: v => v+'%', style:{ fontSize:'10px', fontWeight:700, colors:['#374151'] }, offsetY:-4 },
            fill: { type:'gradient', gradient:{ shade:'light', type:'vertical', shadeIntensity:.3, opacityFrom:.95, opacityTo:.65 } },
            grid: { borderColor:'#f1f5f9', strokeDashArray:4 },
            tooltip: { theme:'light' },
        });
        el4._chart.render();
    }
}

// Init charts with retry until ApexCharts is loaded
function tryInitCharts(attempts) {
    if (typeof ApexCharts !== 'undefined') {
        initCharts();
    } else if (attempts > 0) {
        setTimeout(() => tryInitCharts(attempts - 1), 200);
    }
}

document.addEventListener('DOMContentLoaded', () => tryInitCharts(20));

// Also try immediately in case DOMContentLoaded already fired
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    tryInitCharts(20);
}

document.addEventListener('livewire:update', () => {
    destroyCharts();
    setTimeout(() => tryInitCharts(10), 150);
    if (typeof waInitAllCooldowns === 'function') waInitAllCooldowns();
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof waInitAllCooldowns === 'function') waInitAllCooldowns();
});

// ── Analytics Quick Modal (pure JS, no Livewire re-render) ─────────────
window._aqmHotelId = 0; window._aqmChannel = 'whatsapp'; window._aqmTplName = ''; window._aqmTplLang = '';

window.analyticsOpenModal = function(hotelId, hotelName, phone, channel, consented) {
    window._aqmHotelId = hotelId;
    window._aqmChannel = channel;
    window._aqmTplName = ''; window._aqmTplLang = '';

    document.getElementById('aqmSubtitle').textContent = 'To: ' + hotelName + (phone ? ' (' + phone + ')' : '');
    document.getElementById('aqmResult').style.display = 'none';

    var isWa = (channel === 'whatsapp');
    document.getElementById('aqmHeader').style.background = isWa
        ? 'linear-gradient(135deg,#128c43,#25d366)'
        : 'linear-gradient(135deg,#5b21b6,#7c3aed)';
    document.getElementById('aqmIcon').className = isWa ? 'fab fa-whatsapp' : 'fas fa-bell';
    document.getElementById('aqmTitle').innerHTML = '<i id="aqmIcon" class="' + (isWa ? 'fab fa-whatsapp' : 'fas fa-bell') + '"></i> Quick ' + (isWa ? 'WhatsApp' : 'Push');

    document.getElementById('aqmWaSection').style.display  = isWa ? '' : 'none';
    document.getElementById('aqmPushSection').style.display = isWa ? 'none' : '';

    if (isWa) {
        document.getElementById('aqmConsentWarn').style.display = consented ? 'none' : 'block';
        var sendBtn = document.getElementById('aqmWaSendBtn');
        sendBtn.disabled = true; sendBtn.style.opacity = '0.5';
        document.querySelectorAll('[id^="aqm-tpl-"]').forEach(function(el) {
            el.style.border = '2px solid #e2e8f0';
            el.style.background = '#fff';
        });
    } else {
        document.getElementById('aqmPushTitle').value = '';
        document.getElementById('aqmPushBody').value = '';
    }

    document.getElementById('analyticsQuickModal').style.display = 'flex';
};

window.analyticsCloseModal = function() {
    document.getElementById('analyticsQuickModal').style.display = 'none';
};

window.analyticsSelectTpl = function(key, name, lang) {
    document.querySelectorAll('[id^="aqm-tpl-"]').forEach(function(el) {
        el.style.border = '2px solid #e2e8f0'; el.style.background = '#fff';
    });
    var el = document.getElementById('aqm-tpl-' + key);
    if (el) { el.style.border = '2px solid #25d366'; el.style.background = '#f0fdf4'; }
    window._aqmTplName = name; window._aqmTplLang = lang;
    var btn = document.getElementById('aqmWaSendBtn');
    btn.disabled = false; btn.style.opacity = '1';
};

function _aqmShowResult(success, msg) {
    var res = document.getElementById('aqmResult');
    res.style.display = 'block';
    res.style.background = success ? '#dcfce7' : '#fee2e2';
    res.style.color      = success ? '#15803d' : '#b91c1c';
    res.textContent      = msg;
}

window.analyticsSendWA = function() {
    if (!window._aqmTplName || !window._aqmHotelId) return;
    var btn = document.getElementById('aqmWaSendBtn');
    btn.disabled = true; btn.style.opacity = '0.6';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    fetch('/platform/hotels/' + window._aqmHotelId + '/send-quick-wa', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ template_name: window._aqmTplName, template_language: window._aqmTplLang }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        _aqmShowResult(data.success, data.message || (data.success ? '✅ Sent!' : '❌ Error'));
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
        btn.disabled = false; btn.style.opacity = '1';
        if (data.success && typeof waSetCooldown === 'function') waSetCooldown(window._aqmHotelId);
    }).catch(function() {
        _aqmShowResult(false, '❌ Network error. Try again.');
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
        btn.disabled = false; btn.style.opacity = '1';
    });
};

window.analyticsSendPush = function() {
    if (!window._aqmHotelId) return;
    var title = document.getElementById('aqmPushTitle').value.trim();
    var body  = document.getElementById('aqmPushBody').value.trim();
    if (!title || !body) { alert('Please fill in both title and message.'); return; }
    fetch('/platform/hotels/' + window._aqmHotelId + '/send-quick-push', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ title: title, body: body }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        _aqmShowResult(data.success, data.message || (data.success ? '✅ Sent!' : '❌ Error'));
    }).catch(function() {
        _aqmShowResult(false, '❌ Network error. Try again.');
    });
};
</script>
@endscript
