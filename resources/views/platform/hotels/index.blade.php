@extends('layouts.platform')

@section('title', 'Hotels — Platform Admin')
@section('page-title', 'Hotel Management')
@section('page-subtitle', 'All tenants — create, configure, suspend')

@section('content')

@php
    $planCfg = fn($slug) => $plans[$slug] ?? ['label' => ucfirst($slug), 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'];
    $fmt     = fn($n) => $currencySymbol . ' ' . number_format($n, 0);
@endphp

{{-- Header action --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <span style="font-size:13px;color:#64748b;">{{ $hotels->count() }} hotel{{ $hotels->count() !== 1 ? 's' : '' }} registered on platform</span>
    </div>
    <a href="{{ route('platform.hotels.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Hotel
    </a>
</div>

{{-- Table card --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    @if($hotels->isEmpty())
    <div style="padding:80px 24px;text-align:center;">
        <i class="fas fa-building" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
        <p style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 8px;">No hotels yet</p>
        <p style="color:#94a3b8;margin:0 0 20px;">Create the first hotel tenant to get started.</p>
        <a href="{{ route('platform.hotels.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> Create First Hotel
        </a>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Hotel</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Plan</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Status</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Rooms</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Bookings</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Revenue</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Users</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Created</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hotels as $hotel)
                @php
                    $plan     = $planCfg($hotel->plan);
                    $isActive = $hotel->status === 'active';
                    $sBg      = $isActive ? '#dcfce7' : '#fee2e2';
                    $sTx      = $isActive ? '#15803d' : '#b91c1c';
                @endphp
                <tr style="border-bottom:1px solid #f8fafc;cursor:pointer;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background='transparent'" onclick="window.location='{{ route('platform.hotels.edit', $hotel->id) }}'" title="Click to edit {{ addslashes($hotel->name) }}">

                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:linear-gradient(135deg,{{ $isActive ? '#8b5cf6,#4c1d95' : '#94a3b8,#475569' }});border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:13px;font-weight:800;">{{ strtoupper(substr($hotel->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $hotel->name }}</div>
                                <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $hotel->slug }}</div>
                            </div>
                        </div>
                    </td>

                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $plan['badge_bg'] }};color:{{ $plan['badge_text'] }};">
                            {{ $plan['label'] }}
                        </span>
                    </td>

                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $sBg }};color:{{ $sTx }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $sTx }};display:inline-block;"></span>
                            {{ ucfirst($hotel->status) }}
                        </span>
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->room_count) }}</span>
                        @if($hotel->max_rooms && $hotel->max_rooms < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_rooms }}</span>
                        @endif
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->booking_count) }}</span>
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#059669;">{{ $fmt($hotel->revenue) }}</span>
                    </td>

                    <td style="padding:14px;text-align:right;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">{{ number_format($hotel->user_count) }}</span>
                        @if($hotel->max_users && $hotel->max_users < PHP_INT_MAX)
                        <span style="font-size:10px;color:#94a3b8;display:block;">/ {{ $hotel->max_users }}</span>
                        @endif
                    </td>

                    <td style="padding:14px;">
                        <span style="font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($hotel->created_at)->format('d M Y') }}</span>
                    </td>

                    <td style="padding:14px 20px;" onclick="event.stopPropagation()">
                        <div style="display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap;">

                            {{-- View CRM --}}
                            <a href="{{ route('platform.hotels.view-in-crm', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#ede9fe;color:#6d28d9;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;"
                               title="Open this hotel in CRM">
                                <i class="fas fa-eye"></i> CRM
                            </a>

                            {{-- Edit --}}
                            <a href="{{ route('platform.hotels.edit', $hotel->id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#f1f5f9;color:#475569;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;"
                               title="Edit hotel settings">
                                <i class="fas fa-cog"></i> Edit
                            </a>

                            {{-- Suspend / Activate toggle --}}
                            @if($isActive)
                            <form method="POST" action="{{ route('platform.hotels.suspend', $hotel->id) }}" style="margin:0;" onsubmit="return confirm('Suspend {{ addslashes($hotel->name) }}? All staff logins will be blocked.')">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#fee2e2;color:#b91c1c;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-ban"></i> Suspend
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('platform.hotels.activate', $hotel->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#dcfce7;color:#15803d;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-check-circle"></i> Activate
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

@endsection
