@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . session('crm_user_name') . '! Here\'s what\'s happening today.')

@section('content')
<style>
.kpi-card {
    border-radius: 20px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    transition: transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.12); }
.kpi-card .kpi-shine {
    position: absolute; top: -40px; right: -40px;
    width: 130px; height: 130px; border-radius: 50%;
    background: rgba(255,255,255,.12);
}
.kpi-card .kpi-shine2 {
    position: absolute; bottom: -30px; left: -20px;
    width: 90px; height: 90px; border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.kpi-num { font-size: 2.4rem; font-weight: 900; line-height: 1; color: #fff; }
.kpi-label { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.82); margin-top: 4px; }
.kpi-sub { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: 6px; }
.kpi-icon { font-size: 1.8rem; opacity: .35; position: absolute; top: 18px; right: 20px; color: #fff; }

/* Occupancy circle animation */
@keyframes dashDraw {
    from { stroke-dasharray: 0, 100; }
}
.occ-ring { animation: dashDraw 1.2s ease-out forwards; }
@keyframes pulseGlow {
    0%,100% { filter: drop-shadow(0 0 4px rgba(6,182,212,.4)); }
    50%      { filter: drop-shadow(0 0 12px rgba(6,182,212,.8)); }
}
.occ-svg { animation: pulseGlow 3s ease-in-out infinite; }

/* Bar chart bar */
.rev-bar { transition: height .6s cubic-bezier(.34,1.56,.64,1); }
.rev-bar:hover { filter: brightness(1.12); }

/* Calendar cell */
.cal-cell {
    border-radius: 14px; padding: 8px 6px;
    min-height: 90px; display: flex; flex-direction: column;
    transition: all .18s;
    text-decoration: none;
}
.cal-cell:hover { z-index: 2; box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.cal-cell.today { background: linear-gradient(135deg,#ecfeff,#e0f2fe); border: 2px solid #22d3ee; }
.cal-cell.in-month { background: #f8fafc; border: 1px solid #f1f5f9; }
.cal-cell.in-month:hover { background: #f1f5f9; }
.cal-cell.out-month { background: #fff; border: 1px solid #f8fafc; opacity: .4; }
.cal-day-num { font-size: 1rem; font-weight: 800; line-height: 1; }
/* Calendar tooltip */
#calTooltip {
    position: fixed; z-index: 9999; pointer-events: none;
    background: #1e293b; color: #fff; border-radius: 12px;
    padding: 10px 13px; min-width: 180px; max-width: 260px;
    box-shadow: 0 8px 28px rgba(0,0,0,.28);
    font-size: 12px; line-height: 1.5;
    opacity: 0; transition: opacity .15s ease;
}
#calTooltip.visible { opacity: 1; }
#calTooltip .tt-date { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
#calTooltip .tt-section { font-size: 11px; font-weight: 700; margin: 6px 0 3px; padding-top: 5px; border-top: 1px solid rgba(255,255,255,.1); }
#calTooltip .tt-section:first-of-type { border-top: none; margin-top: 2px; }
#calTooltip .tt-row { display: flex; align-items: center; gap: 7px; padding: 2px 0; }
#calTooltip .tt-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
#calTooltip .tt-name { font-weight: 600; color: #f1f5f9; }
#calTooltip .tt-room { color: #94a3b8; font-size: 11px; }

/* Quick actions */
.qa-btn { border-radius: 14px; padding: 12px 14px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all .18s; }
.qa-btn:hover { transform: translateX(4px); }
</style>

<div style="display:flex;flex-direction:column;gap:24px;">

    {{-- KPI Row 1 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;" class="kpi-grid">
        {{-- Check-Ins --}}
        <div class="kpi-card" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-sign-in-alt kpi-icon"></i>
            <div class="kpi-label">Today's Check-Ins</div>
            <div class="kpi-num" data-count="{{ $todayCheckins->count() }}">{{ $todayCheckins->count() }}</div>
            <div class="kpi-sub">Pending arrival</div>
        </div>
        {{-- Check-Outs --}}
        <div class="kpi-card" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-sign-out-alt kpi-icon"></i>
            <div class="kpi-label">Today's Check-Outs</div>
            <div class="kpi-num" data-count="{{ $todayCheckouts->count() }}">{{ $todayCheckouts->count() }}</div>
            <div class="kpi-sub">Pending departure</div>
        </div>
        {{-- Available Rooms --}}
        <div class="kpi-card" style="background:linear-gradient(135deg,#10b981,#059669);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-door-open kpi-icon"></i>
            <div class="kpi-label">Available Rooms</div>
            <div class="kpi-num" data-count="{{ $availableRooms }}">{{ $availableRooms }}</div>
            <div class="kpi-sub">of {{ $totalRooms }} total</div>
        </div>
        {{-- Occupied Rooms --}}
        <div class="kpi-card" style="background:linear-gradient(135deg,#f43f5e,#be185d);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-bed kpi-icon"></i>
            <div class="kpi-label">Occupied Rooms</div>
            <div class="kpi-num" data-count="{{ $occupiedRooms }}">{{ $occupiedRooms }}</div>
            <div class="kpi-sub">{{ $occupancyRate }}% occupancy</div>
        </div>
    </div>

    {{-- KPI Row 2: Financial + Operational --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;" class="kpi-grid">
        @canDo('reports.view')
        <div class="kpi-card" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-rupee-sign kpi-icon"></i>
            <div class="kpi-label">Today's Revenue</div>
            <div class="kpi-num" data-count="{{ $todayRevenue }}" data-prefix="₹" data-format="currency">₹{{ number_format($todayRevenue) }}</div>
            <div class="kpi-sub">Collected today</div>
        </div>
        <div class="kpi-card" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-chart-line kpi-icon"></i>
            <div class="kpi-label">Month Revenue</div>
            <div class="kpi-num" data-count="{{ $monthRevenue }}" data-prefix="₹" data-format="currency">₹{{ number_format($monthRevenue) }}</div>
            <div class="kpi-sub">{{ now()->format('F Y') }}</div>
        </div>
        @endCanDo
        <div class="kpi-card" style="background:linear-gradient(135deg,#d97706,#b45309);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-exclamation-triangle kpi-icon"></i>
            <div class="kpi-label">Pending Payments</div>
            <div class="kpi-num" data-count="{{ $pendingPayments }}">{{ $pendingPayments }}</div>
            <div class="kpi-sub">Needs attention</div>
        </div>
        <div class="kpi-card" style="background:linear-gradient(135deg,#0891b2,#0e7490);">
            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
            <i class="fas fa-users kpi-icon"></i>
            <div class="kpi-label">Total Guests</div>
            <div class="kpi-num" data-count="{{ $totalCustomers }}">{{ $totalCustomers }}</div>
            <div class="kpi-sub">+{{ $newCustomersMonth }} this month</div>
        </div>
    </div>

    {{-- Occupancy + Revenue --}}
    @canDo('reports.view')
    <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:stretch;" class="occ-rev-grid">

        {{-- Occupancy Circle --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;display:flex;flex-direction:column;align-items:center;">
            <div style="font-weight:800;color:#1e293b;font-size:15px;margin-bottom:20px;align-self:flex-start;width:100%;">Room Occupancy</div>
            <div style="position:relative;width:200px;height:200px;flex-shrink:0;">
                <svg viewBox="0 0 36 36" class="occ-svg" style="width:200px;height:200px;transform:rotate(-90deg);">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3.2"/>
                    @php
                        $occ = (int)$occupancyRate;
                        $color1 = $occ >= 80 ? '#ef4444' : ($occ >= 50 ? '#f59e0b' : '#06b6d4');
                        $color2 = $occ >= 80 ? '#be185d' : ($occ >= 50 ? '#d97706' : '#3b82f6');
                    @endphp
                    <defs>
                        <linearGradient id="occGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:{{ $color1 }}"/>
                            <stop offset="100%" style="stop-color:{{ $color2 }}"/>
                        </linearGradient>
                    </defs>
                    <circle class="occ-ring" cx="18" cy="18" r="15.9" fill="none"
                        stroke="url(#occGrad)" stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $occ }}, 100"/>
                </svg>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                    <div style="font-size:2.4rem;font-weight:900;color:#1e293b;line-height:1;">{{ $occupancyRate }}%</div>
                    <div style="font-size:.72rem;color:#94a3b8;font-weight:600;margin-top:2px;">Occupied</div>
                </div>
            </div>
            <div style="width:100%;margin-top:22px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:12px;height:12px;border-radius:50%;background:#10b981;"></div>
                        <span style="font-size:13px;color:#475569;">Available</span>
                    </div>
                    <span style="font-weight:800;color:#1e293b;font-size:15px;">{{ $availableRooms }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:12px;height:12px;border-radius:50%;background:#f43f5e;"></div>
                        <span style="font-size:13px;color:#475569;">Occupied</span>
                    </div>
                    <span style="font-weight:800;color:#1e293b;font-size:15px;">{{ $occupiedRooms }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></div>
                        <span style="font-size:13px;color:#475569;">Maintenance</span>
                    </div>
                    <span style="font-weight:800;color:#1e293b;font-size:15px;">{{ $maintenanceRooms }}</span>
                </div>
                <div style="height:1px;background:#f1f5f9;margin:4px 0;"></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#94a3b8;">Total Rooms</span>
                    <span style="font-weight:800;color:#1e293b;font-size:15px;">{{ $totalRooms }}</span>
                </div>
            </div>
        </div>

        {{-- Weekly Revenue Chart --}}
        <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            @php $weekTotal = array_sum(array_column($weeklyRevenue, 'amount')); @endphp
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">7-Day Revenue Overview</div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Payments collected over the last 7 days</div>
                </div>
                <div style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);padding:6px 14px;border-radius:20px;">
                    <span style="font-weight:800;color:#059669;font-size:14px;">₹{{ number_format($weekTotal) }} total</span>
                </div>
            </div>
            @php $maxRevenue = max(array_column($weeklyRevenue, 'amount')) ?: 1; @endphp
            <div style="display:flex;align-items:flex-end;gap:8px;height:160px;margin-top:24px;">
                @foreach($weeklyRevenue as $day)
                    @php $pct = $day['amount'] > 0 ? max(8, round(($day['amount']/$maxRevenue)*100)) : 3; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;" class="bar-col">
                        <div style="font-size:11px;font-weight:700;color:{{ $day['amount']>0 ? '#475569' : '#cbd5e1' }};white-space:nowrap;opacity:0;" class="bar-label">
                            {{ $day['amount']>0 ? '₹'.number_format($day['amount']) : '—' }}
                        </div>
                        <div class="rev-bar" style="width:100%;border-radius:10px 10px 4px 4px;
                            background:{{ $day['isToday'] ? 'linear-gradient(to top,#0891b2,#22d3ee)' : 'linear-gradient(to top,#3b82f6,#93c5fd)' }};
                            height:{{ $pct }}%;box-shadow:{{ $day['isToday'] ? '0 4px 14px rgba(6,182,212,.35)' : 'none' }};"
                            onmouseenter="this.previousElementSibling.style.opacity=1"
                            onmouseleave="this.previousElementSibling.style.opacity=0">
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:12px;font-weight:700;color:{{ $day['isToday'] ? '#0891b2' : '#64748b' }};">{{ $day['day'] }}</div>
                            <div style="font-size:11px;color:{{ $day['isToday'] ? '#22d3ee' : '#cbd5e1' }};">{{ $day['date'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="margin-top:16px;border-top:1px solid #f8fafc;padding-top:12px;display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
                @foreach($weeklyRevenue as $day)
                <div style="text-align:center;font-size:11px;font-weight:{{ $day['amount']>0 ? '700' : '400' }};color:{{ $day['amount']>0 ? '#475569' : '#cbd5e1' }};">
                    {{ $day['amount']>0 ? '₹'.number_format($day['amount']/1000,1).'k' : '—' }}
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endCanDo

    {{-- Booking Calendar --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(6,182,212,.3);">
                    <i class="fas fa-calendar-alt" style="color:#fff;font-size:16px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:16px;">Booking Calendar</div>
                    <div style="font-size:12px;color:#64748b;">{{ $calStart->format('F Y') }} — arrivals &amp; departures</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <a href="{{ route('dashboard', ['cal_year'=>$prevMonth->year,'cal_month'=>$prevMonth->month]) }}"
                   style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                    <i class="fas fa-chevron-left" style="font-size:12px;"></i>
                </a>
                <a href="{{ route('dashboard') }}"
                   style="padding:0 14px;height:36px;display:flex;align-items:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">Today</a>
                <a href="{{ route('dashboard', ['cal_year'=>$nextMonth->year,'cal_month'=>$nextMonth->month]) }}"
                   style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                    <i class="fas fa-chevron-right" style="font-size:12px;"></i>
                </a>
            </div>
        </div>
        <div style="padding:20px;">
            {{-- Day headers --}}
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:6px;">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                <div style="text-align:center;font-size:12px;font-weight:700;color:#94a3b8;padding:6px 0;letter-spacing:.04em;">{{ $dow }}</div>
                @endforeach
            </div>
            {{-- Weeks --}}
            @if(count($calWeeks) > 0)
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($calWeeks as $week)
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;">
                    @foreach($week as $cell)
                    @php
                        $hasGuests = ($cell['checkins'] + $cell['checkouts'] + $cell['staying']) > 0;
                        $ttData = $hasGuests ? htmlspecialchars(json_encode([
                            'date'     => $cell['date']->format('D, d M Y'),
                            'checkins' => $cell['checkin_guests'],
                            'checkouts'=> $cell['checkout_guests'],
                            'staying'  => $cell['staying_guests'],
                        ]), ENT_QUOTES, 'UTF-8') : '';
                    @endphp
                    <a href="{{ route('bookings.index', ['check_in_date'=>$cell['ds']]) }}"
                       class="cal-cell {{ $cell['isToday'] ? 'today' : ($cell['inMonth'] ? 'in-month' : 'out-month') }}"
                       @if($hasGuests) data-cal-guests="{!! $ttData !!}" @endif>
                        <span class="cal-day-num" style="color:{{ $cell['isToday'] ? '#0891b2' : ($cell['inMonth'] ? '#1e293b' : '#cbd5e1') }};">{{ $cell['day'] }}</span>
                        <div style="display:flex;flex-direction:column;gap:3px;margin-top:auto;">
                            @if($cell['checkins'] > 0)
                            <div style="display:flex;align-items:center;gap:4px;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#06b6d4;flex-shrink:0;"></span>
                                <span style="font-size:11px;color:#0891b2;font-weight:700;line-height:1;">{{ $cell['checkins'] }} in</span>
                            </div>
                            @endif
                            @if($cell['checkouts'] > 0)
                            <div style="display:flex;align-items:center;gap:4px;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></span>
                                <span style="font-size:11px;color:#b45309;font-weight:700;line-height:1;">{{ $cell['checkouts'] }} out</span>
                            </div>
                            @endif
                            @if($cell['staying'] > 0)
                            <div style="display:flex;align-items:center;gap:4px;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0;"></span>
                                <span style="font-size:11px;color:#047857;font-weight:700;line-height:1;">{{ $cell['staying'] }} stay</span>
                            </div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center;padding:32px;color:#94a3b8;font-size:14px;">Calendar unavailable</div>
            @endif
            <div style="display:flex;align-items:center;gap:20px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Check-in</span></div>
                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Check-out</span></div>
                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">In-house</span></div>
                <div style="display:flex;align-items:center;gap:6px;margin-left:auto;"><span style="width:10px;height:10px;border-radius:50%;border:2px solid #22d3ee;background:#ecfeff;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Today</span></div>
            </div>
        </div>
    </div>

    {{-- Quick Actions + Recent Bookings --}}
    <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;" class="qa-recent-grid">

        {{-- Quick Actions --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="font-weight:800;color:#1e293b;font-size:15px;margin-bottom:18px;">Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @canDo('bookings.create')
                <a href="{{ route('bookings.create') }}" class="qa-btn" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);" onmouseenter="this.style.background='linear-gradient(135deg,#dbeafe,#bfdbfe)'" onmouseleave="this.style.background='linear-gradient(135deg,#eff6ff,#dbeafe)'">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(59,130,246,.3);flex-shrink:0;">
                        <i class="fas fa-plus" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#1e40af;font-size:14px;">New Booking</div>
                        <div style="font-size:12px;color:#93c5fd;">Create reservation</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#93c5fd;font-size:11px;margin-left:auto;"></i>
                </a>
                @endCanDo
                @canDo('checkin.process')
                <a href="{{ route('checkin.index') }}" class="qa-btn" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);" onmouseenter="this.style.background='linear-gradient(135deg,#dcfce7,#bbf7d0)'" onmouseleave="this.style.background='linear-gradient(135deg,#f0fdf4,#dcfce7)'">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(16,185,129,.3);flex-shrink:0;">
                        <i class="fas fa-sign-in-alt" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#065f46;font-size:14px;">Process Check-In</div>
                        <div style="font-size:12px;color:#6ee7b7;">{{ $todayCheckins->count() }} pending</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#6ee7b7;font-size:11px;margin-left:auto;"></i>
                </a>
                @endCanDo
                @canDo('checkout.process')
                <a href="{{ route('checkout.index') }}" class="qa-btn" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);" onmouseenter="this.style.background='linear-gradient(135deg,#fef3c7,#fde68a)'" onmouseleave="this.style.background='linear-gradient(135deg,#fffbeb,#fef3c7)'">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(245,158,11,.3);flex-shrink:0;">
                        <i class="fas fa-sign-out-alt" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#92400e;font-size:14px;">Process Check-Out</div>
                        <div style="font-size:12px;color:#fcd34d;">{{ $todayCheckouts->count() }} pending</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#fcd34d;font-size:11px;margin-left:auto;"></i>
                </a>
                @endCanDo
                @canDo('guests.create')
                <a href="{{ route('customers.create') }}" class="qa-btn" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);" onmouseenter="this.style.background='linear-gradient(135deg,#ede9fe,#ddd6fe)'" onmouseleave="this.style.background='linear-gradient(135deg,#faf5ff,#ede9fe)'">
                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(139,92,246,.3);flex-shrink:0;">
                        <i class="fas fa-user-plus" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#4c1d95;font-size:14px;">Add Guest</div>
                        <div style="font-size:12px;color:#c4b5fd;">New guest profile</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#c4b5fd;font-size:11px;margin-left:auto;"></i>
                </a>
                @endCanDo
            </div>
        </div>

        {{-- Recent Bookings --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                <div style="font-weight:800;color:#1e293b;font-size:15px;">Recent Bookings</div>
                <a href="{{ route('bookings.index') }}" style="color:#0891b2;font-size:13px;font-weight:600;text-decoration:none;">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @forelse($recentBookings as $booking)
                <div style="display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:14px;background:#f8fafc;transition:background .15s;" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#f8fafc'">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#e2e8f0,#cbd5e1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#475569;font-weight:800;font-size:15px;flex-shrink:0;">
                        {{ substr($booking->customer?->name ?? 'G', 0, 1) }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:#1e293b;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                        <div style="font-size:12px;color:#94a3b8;">Room {{ $booking->room->room_number }} &bull; {{ $booking->check_in_date->format('d M') }} &ndash; {{ $booking->check_out_date->format('d M') }} &bull; {{ $booking->nights }} nights</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        @canDo('reports.view')
                        <div style="font-weight:800;color:#1e293b;font-size:14px;">₹{{ number_format($booking->total_amount) }}</div>
                        @endCanDo
                        <span class="badge-{{ $booking->status_color }}" style="font-size:11px;">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:32px;color:#94a3b8;">
                    <i class="fas fa-calendar-times" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
                    <p style="font-size:14px;">No recent bookings</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Today's Arrivals & Departures --}}
    @if($todayCheckins->count() > 0 || $todayCheckouts->count() > 0)
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
        @if($todayCheckins->count() > 0)
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-sign-in-alt" style="color:#fff;font-size:14px;"></i>
                </div>
                <div style="font-weight:800;color:#1e293b;font-size:15px;">Today's Arrivals ({{ $todayCheckins->count() }})</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($todayCheckins as $booking)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#ecfeff,#e0f2fe);border-radius:12px;">
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                        <div style="font-size:12px;color:#64748b;">Room {{ $booking->room->room_number }} &bull; {{ $booking->nights }} night(s)</div>
                    </div>
                    @canDo('checkin.process')
                    <a href="{{ route('checkin.show', $booking->id) }}" style="background:linear-gradient(135deg,#06b6d4,#0891b2);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(6,182,212,.3);">Check In</a>
                    @endCanDo
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($todayCheckouts->count() > 0)
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-sign-out-alt" style="color:#fff;font-size:14px;"></i>
                </div>
                <div style="font-weight:800;color:#1e293b;font-size:15px;">Today's Departures ({{ $todayCheckouts->count() }})</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($todayCheckouts as $booking)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-radius:12px;">
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                        <div style="font-size:12px;color:#64748b;">Room {{ $booking->room->room_number }}
                            @canDo('reports.view') &bull; Due: ₹{{ number_format($booking->balance_due) }} @endCanDo
                        </div>
                    </div>
                    @canDo('checkout.process')
                    <a href="{{ route('checkout.show', $booking->id) }}" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(245,158,11,.3);">Check Out</a>
                    @endCanDo
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

</div>

{{-- Mobile responsiveness --}}
<style>
@media (max-width: 1024px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .occ-rev-grid { grid-template-columns: 1fr !important; }
    .qa-recent-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 600px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi-num { font-size: 1.7rem !important; }
    .cal-cell { min-height: 60px !important; }
    .cal-day-num { font-size: .85rem !important; }
}
</style>

{{-- Calendar tooltip --}}
<div id="calTooltip"></div>

<script>
(function() {
    var tooltip = document.getElementById('calTooltip');
    var hideTimer = null;

    function buildHtml(data) {
        var html = '<div class="tt-date">' + data.date + '</div>';
        var sections = [
            { key: 'checkins',  label: 'Check-In',  color: '#06b6d4' },
            { key: 'checkouts', label: 'Check-Out', color: '#f59e0b' },
            { key: 'staying',   label: 'In-House',  color: '#10b981' },
        ];
        sections.forEach(function(s) {
            var guests = data[s.key];
            if (!guests || guests.length === 0) return;
            html += '<div class="tt-section" style="color:' + s.color + ';">' + s.label + ' (' + guests.length + ')</div>';
            guests.forEach(function(g) {
                html += '<div class="tt-row">' +
                    '<span class="tt-dot" style="background:' + s.color + ';"></span>' +
                    '<span class="tt-name">' + g.name + '</span>' +
                    '<span class="tt-room">Rm ' + g.room + '</span>' +
                '</div>';
            });
        });
        return html;
    }

    function positionTooltip(e) {
        var vw = window.innerWidth, vh = window.innerHeight;
        var tw = tooltip.offsetWidth || 220, th = tooltip.offsetHeight || 120;
        var x = e.clientX + 14, y = e.clientY + 14;
        if (x + tw > vw - 10) x = e.clientX - tw - 10;
        if (y + th > vh - 10) y = e.clientY - th - 10;
        tooltip.style.left = x + 'px';
        tooltip.style.top  = y + 'px';
    }

    document.querySelectorAll('[data-cal-guests]').forEach(function(cell) {
        var data = null;
        try { data = JSON.parse(cell.getAttribute('data-cal-guests')); } catch(e) {}
        if (!data) return;

        cell.addEventListener('mouseenter', function(e) {
            clearTimeout(hideTimer);
            tooltip.innerHTML = buildHtml(data);
            positionTooltip(e);
            tooltip.classList.add('visible');
        });
        cell.addEventListener('mousemove', positionTooltip);
        cell.addEventListener('mouseleave', function() {
            hideTimer = setTimeout(function() { tooltip.classList.remove('visible'); }, 80);
        });
    });
})();
</script>

{{-- Count-up animation --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-count]').forEach(function(el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var fmt = el.getAttribute('data-format');
        var prefix = el.getAttribute('data-prefix') || '';
        var duration = 900;
        var start = performance.now();
        function update(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            var ease = 1 - Math.pow(1 - progress, 3);
            var val = Math.round(target * ease);
            if (fmt === 'currency') {
                el.textContent = prefix + val.toLocaleString('en-IN');
            } else {
                el.textContent = prefix + val.toLocaleString('en-IN');
            }
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
});
</script>

@endsection
