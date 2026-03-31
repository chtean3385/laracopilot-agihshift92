@extends('layouts.platform')

@section('title', 'Platform Dashboard')
@section('page-title', 'Platform Overview')
@section('page-subtitle', 'Live SaaS metrics — all hotels at a glance')

@section('content')

@php
    $plans   = config('plans', []);
    $planCfg = fn($slug) => $plans[$slug] ?? ['label' => ucfirst($slug), 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'];
    $fmt     = fn($n) => $currencySymbol . ' ' . number_format($n, 0);
@endphp

{{-- ── KPI Cards ──────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin-bottom:28px;">

    {{-- Total Hotels --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(139,92,246,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-building" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ $totalHotels }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Total Hotels</div>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
            <span style="font-size:10px;font-weight:700;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:20px;">{{ $activeHotels }} active</span>
            @if($suspendedHotels > 0)
            <span style="font-size:10px;font-weight:700;background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:20px;">{{ $suspendedHotels }} suspended</span>
            @endif
            @if($trialHotels > 0)
            <span style="font-size:10px;font-weight:700;background:#ffedd5;color:#c2410c;padding:2px 8px;border-radius:20px;">{{ $trialHotels }} trial</span>
            @endif
        </div>
    </div>

    {{-- Total Revenue --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-indian-rupee-sign" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:24px;font-weight:800;color:#1e293b;line-height:1;">{{ $fmt($totalRevenue) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Total Revenue</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">All completed payments</span>
        </div>
    </div>

    {{-- Total Bookings --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(6,182,212,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-calendar-check" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ number_format($totalBookings) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Total Bookings</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;font-weight:700;background:#cffafe;color:#0e7490;padding:2px 8px;border-radius:20px;">{{ $activeBookings }} active</span>
        </div>
    </div>

    {{-- Total Guests --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(245,158,11,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-users" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ number_format($totalGuests) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Total Guests</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">Across all hotels</span>
        </div>
    </div>

    {{-- Active Staff Users --}}
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(99,102,241,.08);"></div>
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#6366f1,#4338ca);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i class="fas fa-user-tie" style="color:#fff;font-size:17px;"></i>
        </div>
        <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1;">{{ number_format($totalUsers) }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;font-weight:600;">Active Staff Users</div>
        <div style="margin-top:8px;">
            <span style="font-size:10px;color:#94a3b8;">{{ $totalRooms }} rooms configured</span>
        </div>
    </div>

</div>

{{-- ── Per-Hotel Summary Table ─────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;">Hotel Directory</h2>
            <p style="font-size:12px;color:#94a3b8;margin:3px 0 0;">{{ $hotelStats->count() }} tenant{{ $hotelStats->count() !== 1 ? 's' : '' }} — live stats</p>
        </div>
        <a href="{{ route('platform.hotels.create') }}" class="btn-primary" style="font-size:12px;padding:8px 16px;">
            <i class="fas fa-plus"></i> New Hotel
        </a>
    </div>

    @if($hotelStats->isEmpty())
    <div style="padding:60px 24px;text-align:center;">
        <i class="fas fa-building" style="font-size:36px;color:#e2e8f0;margin-bottom:12px;display:block;"></i>
        <p style="color:#94a3b8;font-weight:600;">No hotels registered yet.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Hotel</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Plan</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Status</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Rooms</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Bookings</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Revenue</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Users</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 14px;">Joined</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hotelStats as $hotel)
                @php
                    $plan     = $planCfg($hotel->plan);
                    $isActive = $hotel->status === 'active';
                    $statusBg   = $isActive ? '#dcfce7' : '#fee2e2';
                    $statusText = $isActive ? '#15803d' : '#b91c1c';
                    $statusLabel = ucfirst($hotel->status);
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
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $plan['badge_bg'] }};color:{{ $plan['badge_text'] }};">
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

                    {{-- Rooms --}}
                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->room_count) }}</span>
                        @if($hotel->max_rooms && $hotel->max_rooms < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_rooms }}</span>
                        @endif
                    </td>

                    {{-- Bookings --}}
                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->booking_count) }}</span>
                        @if($hotel->active_booking_count > 0)
                        <span style="font-size:10px;color:#0891b2;display:block;">{{ $hotel->active_booking_count }} active</span>
                        @endif
                    </td>

                    {{-- Revenue --}}
                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#059669;">{{ $fmt($hotel->revenue) }}</span>
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

            {{-- Platform totals row --}}
            <tfoot>
                <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                    <td colspan="3" style="padding:12px 20px;">
                        <span style="font-size:12px;font-weight:700;color:#475569;">Platform Totals</span>
                    </td>
                    <td style="padding:12px 14px;text-align:right;font-size:14px;font-weight:800;color:#1e293b;">{{ number_format($totalRooms) }}</td>
                    <td style="padding:12px 14px;text-align:right;font-size:14px;font-weight:800;color:#1e293b;">{{ number_format($totalBookings) }}</td>
                    <td style="padding:12px 14px;text-align:right;font-size:14px;font-weight:800;color:#059669;">{{ $fmt($totalRevenue) }}</td>
                    <td style="padding:12px 14px;text-align:right;font-size:14px;font-weight:800;color:#1e293b;">{{ number_format($totalUsers) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>

        </table>
    </div>
    @endif

</div>

@endsection
