@extends('layouts.admin')
@section('title','OTA Channel Manager')
@section('page-title','OTA Channel Manager')
@section('page-subtitle','Sync availability and manage OTA bookings')

@section('content')
<div style="display:grid;gap:20px;">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Status Bar --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;background:{{ $config->is_active ? 'linear-gradient(135deg,#0891b2,#0e7490)' : 'linear-gradient(135deg,#94a3b8,#64748b)' }};border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-sitemap" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <div style="font-weight:800;font-size:15px;color:#1e293b;">
                    {{ $config->exists ? $config->providerLabel() : 'Not Configured' }}
                </div>
                <div style="font-size:12px;color:{{ $config->is_active ? '#16a34a' : '#94a3b8' }};font-weight:600;">
                    @if($config->is_active)
                        <i class="fas fa-circle" style="font-size:8px;"></i> Active
                        @if($config->last_synced_at)
                            &nbsp;· Last sync: {{ $config->last_synced_at->diffForHumans() }}
                        @else
                            &nbsp;· Never synced
                        @endif
                    @else
                        <i class="fas fa-circle" style="font-size:8px;"></i> Inactive
                    @endif
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('channel_manager.config') }}" style="padding:8px 16px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;"><i class="fas fa-cog" style="margin-right:5px;"></i>Config</a>
            <a href="{{ route('channel_manager.rooms') }}" style="padding:8px 16px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;"><i class="fas fa-bed" style="margin-right:5px;"></i>Map Rooms</a>
            <a href="{{ route('channel_manager.availability') }}" style="padding:8px 16px;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;"><i class="fas fa-sync-alt" style="margin-right:5px;"></i>Sync Availability</a>
        </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
        @foreach([
            ['OTA Bookings (This Month)', $totalBookings, 'fas fa-calendar-check', '#0891b2', '#e0f7fa'],
            ['Pending Imports', $pending, 'fas fa-inbox', '#f59e0b', '#fef9c3'],
            ['OTA Net Revenue', 'Rs'.number_format($otaRevenue), 'fas fa-rupee-sign', '#16a34a', '#f0fdf4'],
            ['Avg Commission', number_format($avgCommission,1).'%', 'fas fa-percent', '#7c3aed', '#f5f3ff'],
        ] as [$label,$value,$icon,$color,$bg])
        <div style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:36px;height:36px;background:{{ $bg }};border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="{{ $icon }}" style="color:{{ $color }};font-size:15px;"></i>
                </div>
                <span style="font-size:11px;color:#64748b;font-weight:600;">{{ $label }}</span>
            </div>
            <div style="font-size:24px;font-weight:900;color:#1e293b;">{{ $value }}</div>
        </div>
        @endforeach
    </div>

    {{-- Recent OTA Bookings --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-weight:800;font-size:15px;color:#1e293b;"><i class="fas fa-list" style="color:#0891b2;margin-right:8px;"></i>Recent OTA Bookings</h3>
            <a href="{{ route('channel_manager.bookings') }}" style="font-size:12px;color:#0891b2;font-weight:700;text-decoration:none;">View All →</a>
        </div>
        @if($recent->isEmpty())
        <div style="text-align:center;padding:32px;color:#94a3b8;">
            <i class="fas fa-inbox" style="font-size:32px;margin-bottom:10px;"></i>
            <p style="font-size:13px;">No OTA bookings yet. <a href="{{ route('channel_manager.bookings') }}" style="color:#0891b2;">Add one manually</a> or connect your channel manager.</p>
        </div>
        @else
        <div class="lv-table-wrap">
            <table class="lv-table">
                <thead><tr>
                    <th>OTA Ref</th><th>Channel</th><th>Guest</th><th>Room</th><th>Dates</th><th>Amount</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @foreach($recent as $b)
                <tr>
                    <td style="font-family:monospace;font-size:12px;font-weight:700;">{{ $b->ota_booking_id }}</td>
                    <td><span style="background:{{ $b->channelColor() }};color:#fff;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;">{{ $b->channelLabel() }}</span></td>
                    <td style="font-weight:600;">{{ $b->guest_name }}</td>
                    <td>{{ $b->room?->room_number ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $b->check_in_date->format('d M') }} – {{ $b->check_out_date->format('d M Y') }}</td>
                    <td style="font-weight:700;">Rs{{ number_format($b->net_amount) }}</td>
                    <td><span style="background:{{ $b->statusColor() }}22;color:{{ $b->statusColor() }};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;">{{ ucfirst($b->status) }}</span></td>
                    <td><a href="{{ route('channel_manager.bookings') }}" style="font-size:12px;color:#0891b2;">View</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        @foreach([
            ['Configure Provider', 'Connect eZee, STAAH, SiteMinder or RateGain', 'fas fa-plug', '#0891b2', 'channel_manager.config'],
            ['Map Rooms', 'Link CRM rooms to OTA room type codes', 'fas fa-bed', '#7c3aed', 'channel_manager.rooms'],
            ['Availability Calendar', 'View and push 30-day availability to OTA', 'fas fa-calendar-alt', '#f59e0b', 'channel_manager.availability'],
            ['OTA Bookings', 'Import and manage bookings from all channels', 'fas fa-list-alt', '#16a34a', 'channel_manager.bookings'],
        ] as [$title,$desc,$icon,$color,$route])
        <a href="{{ route($route) }}" style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;padding:18px;text-decoration:none;display:block;transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='none'">
            <div style="width:40px;height:40px;background:{{ $color }}1a;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <i class="{{ $icon }}" style="color:{{ $color }};font-size:16px;"></i>
            </div>
            <div style="font-weight:800;font-size:13px;color:#1e293b;margin-bottom:4px;">{{ $title }}</div>
            <div style="font-size:11px;color:#94a3b8;">{{ $desc }}</div>
        </a>
        @endforeach
    </div>

</div>
@endsection
