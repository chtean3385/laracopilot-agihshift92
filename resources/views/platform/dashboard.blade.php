@extends('layouts.platform')

@section('title', 'Platform Dashboard')
@section('page-title', 'Platform Overview')
@section('page-subtitle', 'SaaS subscription metrics — all tenants at a glance')

@section('content')

@php
    $planCfg = fn($slug) => $plans[$slug] ?? ['label' => ucfirst($slug), 'color' => '#6d28d9', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569', 'monthly_price' => 0, 'yearly_price' => 0];

    // MRR breakdown per plan for the banner — ACTIVE tenants only (consistent with $mrr)
    $activePlanCounts = $hotelStats->where('status', 'active')->groupBy('plan');
    $planBreakdown = [];
    foreach ($activePlanCounts as $slug => $hotels) {
        $price = $plans[$slug]['monthly_price'] ?? 0;
        $count = $hotels->count();
        $planBreakdown[] = $count . ' × ' . ($plans[$slug]['label'] ?? ucfirst($slug)) . ' (Rs&nbsp;' . number_format($price) . ')';
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
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;">

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

    <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;">Tenant Directory</h2>
            <p style="font-size:12px;color:#94a3b8;margin:3px 0 0;">{{ $hotelStats->count() }} tenant{{ $hotelStats->count() !== 1 ? 's' : '' }} — subscription overview</p>
        </div>
        <a href="{{ route('platform.hotels.create') }}" class="btn-primary" style="font-size:12px;padding:8px 16px;">
            <i class="fas fa-plus"></i> New Hotel
        </a>
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
                    $monthlyPrice = $plan['monthly_price'] ?? 0;
                    $yearlyPrice  = $plan['yearly_price']  ?? 0;
                @endphp
                <tr style="border-bottom:1px solid #f8fafc;transition:background .15s;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background='transparent'">

                    {{-- Hotel name + slug --}}
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:linear-gradient(135deg,#8b5cf6,#4c1d95);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:13px;font-weight:800;">{{ strtoupper(substr($hotel->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $hotel->name }}</div>
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
                        @if($monthlyPrice > 0)
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">Rs {{ number_format($monthlyPrice) }}<span style="font-size:10px;font-weight:500;color:#94a3b8;">/mo</span></div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:1px;">Rs {{ number_format($yearlyPrice) }}/yr</div>
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
                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
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

@endsection
