@extends('layouts.platform')

@section('title', 'Platform Dashboard')
@section('page-title', 'Platform Overview')
@section('page-subtitle', 'SaaS subscription metrics — all tenants at a glance')

@section('content')

@php
    $planCfg = fn($slug) => $plans[$slug] ?? ['label' => ucfirst($slug), 'color' => '#6d28d9', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569', 'monthly_price' => 0, 'yearly_price' => 0];
@endphp

@if($expiryAlerts->isNotEmpty())
@php
    $expiredCount  = $expiryAlerts->where('urgency','expired')->count();
    $todayCount    = $expiryAlerts->where('urgency','today')->count();
    $criticalCount = $expiryAlerts->where('urgency','critical')->count();
    $urgentTotal   = $expiredCount + $todayCount + $criticalCount;
@endphp

{{-- ════════════════════════════════════════════════════════════
     PREMIUM EXPIRY ALERT POPUP
     ════════════════════════════════════════════════════════════ --}}
<style>
@keyframes epOverlayIn  { from { opacity:0; } to { opacity:1; } }
@keyframes epCardIn     { from { opacity:0; transform:scale(.9) translateY(24px); } to { opacity:1; transform:scale(1) translateY(0); } }
@keyframes epBellRing   { 0%,100%{transform:rotate(0)} 10%{transform:rotate(18deg)} 20%{transform:rotate(-16deg)} 30%{transform:rotate(14deg)} 40%{transform:rotate(-10deg)} 50%{transform:rotate(6deg)} 60%{transform:rotate(0)} }
@keyframes epPulseRed   { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.5)} 70%{box-shadow:0 0 0 10px rgba(239,68,68,0)} }
@keyframes epStripPulse { 0%,100%{opacity:1} 50%{opacity:.7} }
#epOverlay { animation: epOverlayIn .25s ease; }
#epCard    { animation: epCardIn .35s cubic-bezier(.22,1,.36,1); }
#epBell    { animation: epBellRing 1.4s ease .4s 2; }
.ep-row { transition: background .15s, transform .12s; }
.ep-row:hover { background: rgba(255,255,255,.08) !important; transform: translateX(3px); }
</style>

<div id="epOverlay" style="position:fixed;inset:0;background:rgba(10,5,25,.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:9990;display:{{ $showExpiryPopup ? 'flex' : 'none' }};align-items:center;justify-content:center;padding:20px;">
    <div id="epCard" style="background:#0f0a1e;border:1px solid rgba(255,255,255,.1);border-radius:26px;max-width:600px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 40px 100px rgba(0,0,0,.7),0 0 0 1px rgba(255,255,255,.05);">

        {{-- ── Gradient header ── --}}
        <div style="background:linear-gradient(135deg,#1a0533 0%,#3b0764 40%,#7c1d1d 100%);padding:28px 28px 22px;flex-shrink:0;position:relative;overflow:hidden;">
            {{-- Decorative orbs --}}
            <div style="position:absolute;left:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(239,68,68,.25),transparent 70%);pointer-events:none;"></div>
            <div style="position:absolute;right:-30px;bottom:-50px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(124,58,237,.3),transparent 70%);pointer-events:none;"></div>
            {{-- Close btn --}}
            <button onclick="dismissEP()" style="position:absolute;right:20px;top:20px;width:34px;height:34px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;" onmouseenter="this.style.background='rgba(255,255,255,.2)'" onmouseleave="this.style.background='rgba(255,255,255,.1)'">
                <i class="fas fa-times" style="color:rgba(255,255,255,.8);font-size:14px;"></i>
            </button>
            {{-- Icon + title --}}
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="width:60px;height:60px;background:linear-gradient(135deg,#ef4444,#dc2626);border-radius:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 4px rgba(239,68,68,.25);animation:epPulseRed 2s infinite;">
                    <i id="epBell" class="fas fa-bell" style="color:#fff;font-size:26px;"></i>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:900;color:#fff;letter-spacing:-.4px;line-height:1.2;">Subscription Alert</div>
                    <div style="font-size:13px;color:rgba(255,255,255,.6);margin-top:5px;display:flex;align-items:center;gap:8px;">
                        <span>{{ $expiryAlerts->count() }} hotel{{ $expiryAlerts->count()!==1?'s':'' }} require attention today</span>
                        @if($urgentTotal)
                        <span style="background:rgba(239,68,68,.25);color:#fca5a5;font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;border:1px solid rgba(239,68,68,.3);">{{ $urgentTotal }} URGENT</span>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Stat chips --}}
            <div style="display:flex;gap:8px;margin-top:20px;flex-wrap:wrap;">
                @if($expiredCount)
                <div style="background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:6px 14px;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:#f87171;">{{ $expiredCount }}</div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Expired</div>
                </div>
                @endif
                @if($todayCount)
                <div style="background:rgba(234,88,12,.2);border:1px solid rgba(234,88,12,.3);border-radius:10px;padding:6px 14px;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:#fb923c;">{{ $todayCount }}</div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Today</div>
                </div>
                @endif
                @if($criticalCount)
                <div style="background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:6px 14px;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:#fbbf24;">{{ $criticalCount }}</div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Critical</div>
                </div>
                @endif
                @php $soonCount = $expiryAlerts->where('urgency','soon')->count(); @endphp
                @if($soonCount)
                <div style="background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:6px 14px;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:#34d399;">{{ $soonCount }}</div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Soon</div>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Hotel rows ── --}}
        <div style="overflow-y:auto;padding:16px 20px;flex:1;background:#0f0a1e;">
            <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($expiryAlerts as $alert)
            @php
                $epR = match($alert->urgency) {
                    'expired'  => ['bar'=>'#ef4444','badge_bg'=>'rgba(239,68,68,.2)','badge_border'=>'rgba(239,68,68,.35)','badge_text'=>'#f87171','label'=>'EXPIRED','icon'=>'fa-times-circle'],
                    'today'    => ['bar'=>'#ea580c','badge_bg'=>'rgba(234,88,12,.2)','badge_border'=>'rgba(234,88,12,.35)','badge_text'=>'#fb923c','label'=>'TODAY','icon'=>'fa-exclamation-circle'],
                    'critical' => ['bar'=>'#f59e0b','badge_bg'=>'rgba(245,158,11,.2)','badge_border'=>'rgba(245,158,11,.35)','badge_text'=>'#fbbf24','label'=>$alert->days_left.'d LEFT','icon'=>'fa-clock'],
                    default    => ['bar'=>'#10b981','badge_bg'=>'rgba(16,185,129,.15)','badge_border'=>'rgba(16,185,129,.25)','badge_text'=>'#34d399','label'=>$alert->days_left.'d LEFT','icon'=>'fa-info-circle'],
                };
                $typeBadge = $alert->type === 'trial' ? 'Trial' : 'Plan';
            @endphp
            <div class="ep-row" style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:12px 14px;overflow:hidden;position:relative;">
                {{-- Urgency bar --}}
                <div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:{{ $epR['bar'] }};border-radius:4px 0 0 4px;"></div>
                <div style="padding-left:6px;flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-size:14px;font-weight:800;color:#f1f5f9;letter-spacing:-.2px;">{{ $alert->name }}</span>
                        <span style="font-size:10px;font-weight:700;background:rgba(255,255,255,.1);color:rgba(255,255,255,.5);padding:1px 8px;border-radius:8px;text-transform:uppercase;letter-spacing:.4px;">{{ $typeBadge }}</span>
                    </div>
                    <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:3px;">
                        {{ $alert->type==='trial'?'Trial':'Plan' }} {{ $alert->days_left<0?'expired':'expires' }} · <span style="color:rgba(255,255,255,.6);font-weight:600;">{{ $alert->expiry_date }}</span>
                    </div>
                </div>
                <span style="font-size:11px;font-weight:800;background:{{ $epR['badge_bg'] }};color:{{ $epR['badge_text'] }};padding:4px 10px;border-radius:20px;border:1px solid {{ $epR['badge_border'] }};white-space:nowrap;flex-shrink:0;letter-spacing:.3px;">{{ $epR['label'] }}</span>
                <a href="{{ route('platform.hotels.edit', $alert->id) }}"
                   style="flex-shrink:0;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:rgba(255,255,255,.8);font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;transition:background .15s;"
                   onmouseenter="this.style.background='rgba(255,255,255,.16)'" onmouseleave="this.style.background='rgba(255,255,255,.08)'">
                    Manage <i class="fas fa-external-link-alt" style="font-size:10px;margin-left:3px;"></i>
                </a>
            </div>
            @endforeach
            </div>
        </div>

        {{-- ── Footer ── --}}
        <div style="padding:16px 20px;background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.07);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-shrink:0;">
            <span style="font-size:12px;color:rgba(255,255,255,.35);">Scroll down to manage plans in Tenant Directory</span>
            <button onclick="dismissEP()" style="padding:10px 24px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(239,68,68,.4);letter-spacing:.2px;transition:opacity .15s;" onmouseenter="this.style.opacity='.85'" onmouseleave="this.style.opacity='1'">
                Acknowledged &nbsp;✓
            </button>
        </div>

    </div>
</div>

<script>
function dismissEP() {
    var o = document.getElementById('epOverlay');
    if (!o) return;
    o.style.transition = 'opacity .22s';
    o.style.opacity = '0';
    setTimeout(function(){ o.style.display = 'none'; o.style.opacity = ''; }, 230);
}
function showEP() {
    var o = document.getElementById('epOverlay');
    if (!o) return;
    o.style.opacity = '0';
    o.style.display = 'flex';
    setTimeout(function(){ o.style.transition='opacity .22s'; o.style.opacity='1'; }, 10);
}
document.getElementById('epOverlay').addEventListener('click', function(e) {
    if (e.target === this) dismissEP();
});
</script>

{{-- ── Inline alert strip (persistent, always visible) ── --}}
<div style="background:linear-gradient(135deg,{{ $urgentTotal ? 'rgba(127,20,20,.08),rgba(127,60,20,.05)' : 'rgba(109,40,217,.06),rgba(59,130,246,.04)' }});border:1px solid {{ $urgentTotal ? 'rgba(239,68,68,.25)' : 'rgba(109,40,217,.2)' }};border-radius:16px;padding:14px 20px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;animation:epStripPulse {{ $urgentTotal ? '2.5s ease infinite' : 'none' }};">
    <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;background:{{ $urgentTotal ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,#8b5cf6,#6d28d9)' }};border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px {{ $urgentTotal ? 'rgba(239,68,68,.3)' : 'rgba(109,40,217,.3)' }};">
            <i class="fas {{ $urgentTotal ? 'fa-exclamation-triangle' : 'fa-clock' }}" style="color:#fff;font-size:14px;"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:800;color:{{ $urgentTotal ? '#991b1b' : '#4c1d95' }};">
                {{ $expiryAlerts->count() }} hotel{{ $expiryAlerts->count()!==1?'s':'' }} with expiring subscriptions
                @if($urgentTotal)<span style="background:{{ $urgentTotal ? '#fee2e2' : '#ede9fe' }};color:{{ $urgentTotal ? '#b91c1c' : '#6d28d9' }};font-size:11px;padding:1px 8px;border-radius:10px;margin-left:6px;">{{ $urgentTotal }} urgent</span>@endif
            </div>
            <div style="font-size:11px;color:{{ $urgentTotal ? '#b91c1c' : '#6d28d9' }};opacity:.7;margin-top:2px;">Click View Details to see full breakdown</div>
        </div>
    </div>
    <button onclick="showEP()" style="padding:9px 20px;background:{{ $urgentTotal ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,#8b5cf6,#6d28d9)' }};color:#fff;border:none;border-radius:11px;font-size:12px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px {{ $urgentTotal ? 'rgba(239,68,68,.3)' : 'rgba(109,40,217,.3)' }};display:flex;align-items:center;gap:6px;">
        <i class="fas fa-bell"></i> View Details
    </button>
</div>

@endif

@php

    // MRR breakdown per plan for the banner — ACTIVE tenants only, using effective prices
    $activePlanCounts = $hotelStats->where('status', 'active')->groupBy('plan');
    $planBreakdown = [];
    $hasCustomPricing = false;
    foreach ($activePlanCounts as $slug => $hotels) {
        $count = $hotels->count();
        $planMrr = 0;
        foreach ($hotels as $h) {
            $defMonthly = (int)($plans[$slug]['monthly_price'] ?? 0);
            $defYearly  = (int)($plans[$slug]['yearly_price']  ?? 0);
            $effMonthly = ($h->custom_monthly_price > 0) ? (int)$h->custom_monthly_price : $defMonthly;
            $effYearly  = ($h->custom_yearly_price  > 0) ? (int)$h->custom_yearly_price  : $defYearly;
            if ($h->custom_monthly_price > 0 || $h->custom_yearly_price > 0) $hasCustomPricing = true;
            $planMrr += ($h->billing_cycle === 'yearly') ? round($effYearly / 12) : $effMonthly;
        }
        $planBreakdown[] = $count . ' × ' . ($plans[$slug]['label'] ?? ucfirst($slug)) . ' (Rs&nbsp;' . number_format($planMrr) . '/mo)';
    }
@endphp

{{-- ── MRR Banner ──────────────────────────────────────────────────────────── --}}
@if(!session('platform_reminder_dismissed') && $totalHotels > 0)
<div style="background:linear-gradient(135deg,#1e1b4b,#2d1b69);border-radius:18px;padding:18px 22px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;background:rgba(255,255,255,.1);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-chart-line" style="color:#facc15;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:14px;font-weight:800;color:#fff;margin-bottom:3px;">
                MRR — Rs {{ number_format($mrr) }}/month &nbsp;·&nbsp; ARR Rs {{ number_format($arr) }}/year
            </div>
            <div style="font-size:12px;color:#a78bfa;">
                {!! implode(' &nbsp;·&nbsp; ', $planBreakdown) !!}
                &nbsp;·&nbsp; {{ $activeHotels }} active tenant{{ $activeHotels !== 1 ? 's' : '' }}
                @if($hasCustomPricing)
                &nbsp;<span style="font-size:10px;font-weight:700;background:rgba(250,204,21,.2);color:#fbbf24;padding:1px 7px;border-radius:4px;vertical-align:middle;">custom pricing applied</span>
                @endif
            </div>
        </div>
    </div>
    <form method="POST" action="{{ route('platform.dismiss-reminder') }}" style="margin:0;flex-shrink:0;">
        @csrf
        <button type="submit" style="padding:7px 16px;background:rgba(255,255,255,.1);color:#c4b5fd;border:1px solid rgba(255,255,255,.15);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
            <i class="fas fa-times" style="margin-right:5px;"></i> Dismiss
        </button>
    </form>
</div>
@endif

{{-- ── SaaS KPI Cards (4 cards: MRR, Active Subscriptions, Suspended/Inactive, Next Month) ──────── --}}
<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        margin-bottom: 28px;
    }
    @media (min-width: 600px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 960px) {
        .kpi-grid { grid-template-columns: repeat(4, 1fr); }
    }
</style>
<div class="kpi-grid">

    {{-- Monthly Recurring Revenue --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-indian-rupee-sign" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">Rs {{ number_format($mrr) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Monthly Recurring Revenue</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">ARR: Rs {{ number_format($arr) }}/yr</span>
        </div>
    </div>

    {{-- Active Subscriptions --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(139,92,246,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-crown" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ $activeSubscriptions }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Active Subscriptions</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">{{ $totalHotels }} tenant{{ $totalHotels !== 1 ? 's' : '' }} total</span>
        </div>
    </div>

    {{-- Suspended / Inactive --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,.06);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-ban" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ $suspendedHotels }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Suspended Tenants</div>
        <div style="margin-top:8px;">
            @if($trialHotels > 0)
            <span style="font-size:10px;font-weight:700;background:#ffedd5;color:#c2410c;padding:2px 8px;border-radius:20px;">{{ $trialHotels }} on trial</span>
            @else
            <span style="font-size:10px;color:#94a3b8;">{{ $suspendedHotels === 0 ? 'All tenants healthy' : 'Needs attention' }}</span>
            @endif
        </div>
    </div>

    {{-- Next Month Expected --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(6,182,212,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-calendar-check" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">Rs {{ number_format($nextMonthRevenue) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Next Month Expected</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">Based on active subscriptions</span>
        </div>
    </div>

</div>

{{-- ── Tenant Directory ─────────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
            <div>
                <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;">Tenant Directory</h2>
                <p id="tenantDirCount" style="font-size:12px;color:#94a3b8;margin:3px 0 0;">{{ $hotelStats->count() }} tenant{{ $hotelStats->count() !== 1 ? 's' : '' }} — subscription overview</p>
            </div>
            <a href="{{ route('platform.hotels.create') }}" class="btn-primary" style="font-size:12px;padding:8px 16px;">
                <i class="fas fa-plus"></i> New Hotel
            </a>
        </div>
        {{-- Live search + filter --}}
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <div style="position:relative;flex:1;min-width:180px;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                <input type="text" id="tenantSearch" placeholder="Search hotel name or slug…"
                    oninput="filterTenants()"
                    style="width:100%;padding:8px 12px 8px 32px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;outline:none;box-sizing:border-box;background:#fff;"
                    autocomplete="off">
            </div>
            <select id="tenantStatusFilter" onchange="filterTenants()"
                style="padding:8px 30px 8px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;background:#fff;outline:none;cursor:pointer;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 9px center;">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
    </div>

    @if($hotelStats->isEmpty())
    <div style="padding:60px 24px;text-align:center;">
        <i class="fas fa-building" style="font-size:36px;color:#e2e8f0;margin-bottom:12px;display:block;"></i>
        <p style="color:#94a3b8;font-weight:600;">No tenants registered yet.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Hotel / Tenant</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Plan</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Status</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Pricing</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Users</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Joined</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hotelStats as $hotel)
                @php
                    $plan       = $planCfg($hotel->plan);
                    $isActive   = $hotel->status === 'active';
                    $statusBg   = $isActive ? '#dcfce7' : '#fee2e2';
                    $statusText = $isActive ? '#15803d' : '#b91c1c';
                    $statusLabel = ucfirst($hotel->status);
                    $monthlyPrice = $hotel->custom_monthly_price > 0 ? (float)$hotel->custom_monthly_price : ($plan['monthly_price'] ?? 0);
                    $yearlyPrice  = $hotel->custom_yearly_price  > 0 ? (float)$hotel->custom_yearly_price  : ($plan['yearly_price']  ?? 0);
                    $isCustom     = $hotel->custom_monthly_price > 0 || $hotel->custom_yearly_price > 0;
                    $cycle        = $hotel->billing_cycle ?? 'monthly';
                    // Attention flags
                    $planExpired   = ($hotel->plan_expires_at && \Carbon\Carbon::parse($hotel->plan_expires_at)->isPast())
                                  || ($hotel->trial_ends_at  && \Carbon\Carbon::parse($hotel->trial_ends_at)->isPast());
                    $lastActAt     = $hotel->last_activity ? \Carbon\Carbon::parse($hotel->last_activity) : null;
                    $daysSince     = $lastActAt ? (int) $lastActAt->diffInDays(now()) : 999;
                    $inactive3d    = $daysSince >= 3;
                    $needsAttention = $planExpired || $inactive3d;
                    // WhatsApp link
                    $rawPhone = preg_replace('/\D/', '', $hotel->phone ?? '');
                    $waPhone  = strlen($rawPhone) === 10 ? '91'.$rawPhone : $rawPhone;
                    $waMsg    = urlencode("Hello, this is a message from ResortSaaS regarding your hotel *{$hotel->name}*.");
                    $waUrl    = $waPhone ? "https://wa.me/{$waPhone}?text={$waMsg}" : null;
                @endphp
                <tr class="tenant-row"
                    data-name="{{ strtolower($hotel->name . ' ' . $hotel->slug) }}"
                    data-status="{{ $hotel->status }}"
                    style="border-bottom:1px solid #f8fafc;transition:background .15s;{{ $needsAttention ? 'background:#fffbeb;border-left:3px solid #f59e0b;' : 'border-left:3px solid transparent;' }}"
                    onmouseover="this.style.background='{{ $needsAttention ? '#fef9c3' : '#fafbff' }}'"
                    onmouseout="this.style.background='{{ $needsAttention ? '#fffbeb' : 'transparent' }}'"
                    >

                    {{-- Hotel name + slug --}}
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:{{ $needsAttention ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#8b5cf6,#4c1d95)' }};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:13px;font-weight:800;">{{ strtoupper(substr($hotel->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                                    <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ $hotel->name }}</span>
                                    @if($planExpired)
                                    <span style="font-size:9px;font-weight:800;background:#fee2e2;color:#b91c1c;padding:1px 5px;border-radius:4px;white-space:nowrap;">EXPIRED</span>
                                    @elseif($inactive3d)
                                    <span style="font-size:9px;font-weight:800;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;white-space:nowrap;">INACTIVE {{ $daysSince === 999 ? '(never)' : $daysSince.'d' }}</span>
                                    @endif
                                </div>
                                <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $hotel->slug }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Plan badge --}}
                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $plan['badge_bg'] ?? '#f1f5f9' }};color:{{ $plan['badge_text'] ?? '#475569' }};">
                            {{ $plan['label'] }}
                        </span>
                    </td>

                    {{-- Status badge --}}
                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $statusBg }};color:{{ $statusText }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $statusText }};display:inline-block;"></span>
                            {{ $statusLabel }}
                        </span>
                    </td>

                    {{-- Pricing --}}
                    <td style="padding:14px;">
                        @if($monthlyPrice > 0 || $yearlyPrice > 0)
                        <div style="display:flex;align-items:center;gap:5px;">
                            @if($cycle === 'yearly')
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Rs {{ number_format($yearlyPrice) }}<span style="font-size:10px;font-weight:500;color:#94a3b8;">/yr</span></div>
                            @else
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Rs {{ number_format($monthlyPrice) }}<span style="font-size:10px;font-weight:500;color:#94a3b8;">/mo</span></div>
                            @endif
                            @if($isCustom)
                            <span style="font-size:9px;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;">CUSTOM</span>
                            @endif
                        </div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">
                            {{ $cycle === 'yearly' ? 'Yearly billing · Rs '.number_format($monthlyPrice).'/mo equiv' : 'Monthly billing' }}
                        </div>
                        @else
                        <span style="font-size:12px;color:#94a3b8;">—</span>
                        @endif
                    </td>

                    {{-- Users --}}
                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->user_count) }}</span>
                        @if($hotel->max_users && $hotel->max_users < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_users }}</span>
                        @endif
                    </td>

                    {{-- Joined date --}}
                    <td style="padding:14px;">
                        <span style="font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($hotel->created_at)->format('d M Y') }}</span>
                    </td>

                    {{-- Actions --}}
                    <td style="padding:14px 20px;text-align:center;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;">
                            @if($hotel->phone)
                            <button onclick="openWaModal({{ $hotel->id }}, '{{ addslashes($hotel->name) }}')"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:{{ $needsAttention ? '#dcfce7' : '#f0fdf4' }};color:#15803d;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s;white-space:nowrap;{{ $needsAttention ? 'box-shadow:0 0 0 2px #22c55e;' : '' }}"
                               onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='{{ $needsAttention ? '#dcfce7' : '#f0fdf4' }}'"
                               title="Quick WhatsApp to {{ $hotel->phone }}">
                                <i class="fab fa-whatsapp" style="font-size:13px;"></i> WA
                            </button>
                            @endif
                            <a href="{{ route('platform.hotels.view-in-crm', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#ede9fe;color:#6d28d9;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:background .15s;white-space:nowrap;"
                               onmouseover="this.style.background='#ddd6fe'" onmouseout="this.style.background='#ede9fe'"
                               title="View this hotel in the CRM">
                                <i class="fas fa-eye"></i> View CRM
                            </a>
                            <a href="{{ route('platform.hotels.edit', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#f1f5f9;color:#475569;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:background .15s;white-space:nowrap;"
                               onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"
                               title="Edit hotel settings">
                                <i class="fas fa-cog"></i> Edit
                            </a>
                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>

            {{-- Platform SaaS totals row --}}
            <tfoot>
                <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                    <td colspan="2" style="padding:12px 20px;">
                        <span style="font-size:12px;font-weight:700;color:#475569;">Platform Totals</span>
                    </td>
                    <td style="padding:12px 14px;">
                        <span style="font-size:10px;font-weight:700;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;">{{ $activeHotels }} active</span>
                    </td>
                    <td style="padding:12px 14px;">
                        <div style="font-size:13px;font-weight:800;color:#059669;">Rs {{ number_format($mrr) }}/mo</div>
                        <div style="font-size:10px;color:#94a3b8;">Rs {{ number_format($arr) }}/yr</div>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:14px;font-weight:800;color:#1e293b;">{{ number_format($totalUsers) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>

        </table>
    </div>
    @endif

</div>

{{-- ── Quick WhatsApp Modal ─────────────────────────────────────────────── --}}
<div id="waModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        {{-- Header --}}
        <div style="padding:20px 24px;background:linear-gradient(135deg,#128c43,#25d366);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;color:#fff;font-size:17px;font-weight:800;">
                    <i class="fab fa-whatsapp" style="font-size:20px;"></i> Quick WhatsApp
                </div>
                <div id="waModalTo" style="color:rgba(255,255,255,.8);font-size:12px;margin-top:2px;"></div>
            </div>
            <button onclick="closeWaModal()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>
        {{-- Body --}}
        <div style="padding:20px 24px;">
            <div id="waModalResult" style="display:none;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600;"></div>
            <p style="font-size:12px;font-weight:700;color:#475569;margin:0 0 12px;">Choose a template:</p>
            <div id="waTemplates" style="display:flex;flex-direction:column;gap:10px;">
                <label style="display:flex;gap:12px;padding:14px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:border .15s;" onclick="selectWaTpl(this)">
                    <input type="radio" name="wa_tpl" data-name="{{ $ownerWaTemplates['crm_update']['meta_name'] }}" data-lang="en_US" style="margin-top:3px;accent-color:#25d366;">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px;">🔔 CRM Dashboard Update</div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">Hello [Hotel Name], Your hotel CRM dashboard has recent updates that can help you manage bookings and customer communic...</div>
                    </div>
                </label>
                <label style="display:flex;gap:12px;padding:14px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:border .15s;" onclick="selectWaTpl(this)">
                    <input type="radio" name="wa_tpl" data-name="{{ $ownerWaTemplates['login_reminder']['meta_name'] }}" data-lang="en_US" style="margin-top:3px;accent-color:#25d366;">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px;">🔔 Login Reminder</div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">Hello [Hotel Name], We noticed you haven't logged into your Hotel CRM in a while. Your bookings and guests need attenti...</div>
                    </div>
                </label>
            </div>
        </div>
        {{-- Footer --}}
        <div style="padding:0 24px 20px;">
            <button id="waSendBtn" onclick="sendWaMessage()"
                style="width:100%;padding:14px;background:linear-gradient(135deg,#128c43,#25d366);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;">
                <i class="fab fa-whatsapp"></i> Send WhatsApp Now
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterTenants() {
    var term   = (document.getElementById('tenantSearch').value || '').toLowerCase().trim();
    var status = (document.getElementById('tenantStatusFilter').value || '').toLowerCase();
    var rows   = document.querySelectorAll('.tenant-row');
    var shown  = 0;
    var total  = rows.length;

    rows.forEach(function(row) {
        var name      = (row.getAttribute('data-name') || '').toLowerCase();
        var rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
        var matchTerm = term === '' || name.indexOf(term) !== -1;
        var matchStat = status === '' || rowStatus === status;
        if (matchTerm && matchStat) {
            row.style.display = '';
            shown++;
        } else {
            row.style.display = 'none';
        }
    });

    var countEl = document.getElementById('tenantDirCount');
    if (countEl) {
        if (shown === total) {
            countEl.textContent = total + ' tenant' + (total !== 1 ? 's' : '') + ' — subscription overview';
        } else {
            countEl.textContent = shown + ' of ' + total + ' tenant' + (total !== 1 ? 's' : '') + ' shown';
        }
    }
}

// ── Quick WA Modal ────────────────────────────────────────────────────
var _waHotelId = null;

function openWaModal(hotelId, hotelName) {
    _waHotelId = hotelId;
    document.getElementById('waModalTo').textContent = 'To: ' + hotelName;
    document.getElementById('waModalResult').style.display = 'none';
    document.getElementById('waModalResult').textContent = '';
    document.getElementById('waSendBtn').disabled = false;
    document.getElementById('waSendBtn').style.opacity = '1';
    document.getElementById('waSendBtn').innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
    document.querySelectorAll('input[name="wa_tpl"]').forEach(function(r) { r.checked = false; });
    document.querySelectorAll('#waTemplates label').forEach(function(l) { l.style.border = '2px solid #e2e8f0'; });
    document.getElementById('waModal').style.display = 'flex';
}

function closeWaModal() {
    document.getElementById('waModal').style.display = 'none';
}

function selectWaTpl(label) {
    document.querySelectorAll('#waTemplates label').forEach(function(l) {
        l.style.border = '2px solid #e2e8f0';
    });
    label.style.border = '2px solid #25d366';
    label.querySelector('input').checked = true;
}

function sendWaMessage() {
    var selected = document.querySelector('input[name="wa_tpl"]:checked');
    if (!selected) { alert('Please choose a template first.'); return; }

    var btn = document.getElementById('waSendBtn');
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';

    fetch('/platform/hotels/' + _waHotelId + '/send-quick-wa', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            template_name:     selected.getAttribute('data-name'),
            template_language: selected.getAttribute('data-lang'),
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var el = document.getElementById('waModalResult');
        el.style.display = 'block';
        el.style.background = data.success ? '#dcfce7' : '#fee2e2';
        el.style.color       = data.success ? '#15803d' : '#b91c1c';
        el.textContent       = data.message || (data.success ? '✅ Sent!' : '❌ Error');
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
        if (!data.success) { btn.disabled = false; btn.style.opacity = '1'; }
    })
    .catch(function() {
        var el = document.getElementById('waModalResult');
        el.style.display = 'block';
        el.style.background = '#fee2e2';
        el.style.color = '#b91c1c';
        el.textContent = '❌ Network error. Please try again.';
        btn.disabled = false; btn.style.opacity = '1';
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Send WhatsApp Now';
    });
}

// Close modal on backdrop click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('waModal').addEventListener('click', function(e) {
        if (e.target === this) closeWaModal();
    });
});
</script>
@endpush

@endsection
