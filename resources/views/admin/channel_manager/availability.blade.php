@extends('layouts.admin')
@section('title','Availability Calendar')
@section('page-title','Availability Calendar')
@section('page-subtitle','View and sync 30-day room availability to your OTA')

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

    {{-- Toolbar --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:2px;"><i class="fas fa-calendar-alt" style="color:#0891b2;margin-right:8px;"></i>Next 30 Days</h3>
            <p style="font-size:12px;color:#94a3b8;">
                {{ now()->format('d M Y') }} – {{ now()->addDays(29)->format('d M Y') }}
                @if($config->last_synced_at)
                    &nbsp;· Last synced: {{ $config->last_synced_at->diffForHumans() }}
                @endif
            </p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <div style="display:flex;gap:8px;font-size:11px;align-items:center;">
                <span style="width:14px;height:14px;background:#dcfce7;border:1px solid #86efac;border-radius:3px;display:inline-block;"></span><span style="color:#64748b;">Available</span>
                <span style="width:14px;height:14px;background:#fee2e2;border:1px solid #fca5a5;border-radius:3px;display:inline-block;"></span><span style="color:#64748b;">Blocked</span>
                <span style="width:14px;height:14px;background:#e0f2fe;border:1px solid #7dd3fc;border-radius:3px;display:inline-block;"></span><span style="color:#64748b;">Unmapped</span>
            </div>
            @if($config->is_active)
            <form action="{{ route('channel_manager.availability.sync') }}" method="POST">
                @csrf
                <button type="submit" style="padding:9px 18px;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                    <i class="fas fa-sync-alt" style="margin-right:6px;"></i>Sync to {{ $config->providerLabel() }}
                </button>
            </form>
            @else
            <a href="{{ route('channel_manager.config') }}" style="padding:9px 18px;background:#f1f5f9;color:#64748b;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">Configure Provider First</a>
            @endif
        </div>
    </div>

    {{-- Calendar Grid --}}
    @if($rooms->isEmpty())
    <div style="background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:48px;text-align:center;color:#94a3b8;">
        <i class="fas fa-bed" style="font-size:36px;margin-bottom:12px;display:block;"></i>
        No rooms found. Please add rooms first.
    </div>
    @else
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:11px;min-width:900px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 14px;text-align:left;font-weight:800;color:#1e293b;border-bottom:1px solid #e2e8f0;position:sticky;left:0;background:#f8fafc;z-index:2;min-width:100px;">Room</th>
                        @foreach($dates as $d)
                        @php $dayObj = \Carbon\Carbon::parse($d); @endphp
                        <th style="padding:6px 4px;text-align:center;font-weight:600;color:{{ $dayObj->isWeekend() ? '#dc2626' : '#64748b' }};border-bottom:1px solid #e2e8f0;min-width:36px;white-space:nowrap;">
                            <div>{{ $dayObj->format('D') }}</div>
                            <div style="font-weight:800;color:#1e293b;">{{ $dayObj->format('d') }}</div>
                            <div style="font-size:10px;">{{ $dayObj->format('M') }}</div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($rooms as $room)
                <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding:8px 14px;font-weight:700;color:#1e293b;position:sticky;left:0;background:#fff;z-index:1;border-right:1px solid #e2e8f0;">
                        <div>{{ $room->room_number }}</div>
                        @if($room->channelMapping)
                            <div style="font-size:10px;color:#64748b;font-family:monospace;">{{ $room->channelMapping->channel_room_code }}</div>
                        @else
                            <div style="font-size:10px;color:#f59e0b;font-weight:600;">⚠ Not mapped</div>
                        @endif
                    </td>
                    @foreach($dates as $d)
                    @php $isBlocked = isset($blocked[$room->id][$d]); $isMapped = (bool)$room->channelMapping; @endphp
                    <td style="padding:3px;text-align:center;">
                        <div style="width:28px;height:28px;border-radius:6px;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;
                            background:{{ !$isMapped ? '#e0f2fe' : ($isBlocked ? '#fee2e2' : '#dcfce7') }};
                            color:{{ !$isMapped ? '#0369a1' : ($isBlocked ? '#dc2626' : '#16a34a') }};
                            border:1px solid {{ !$isMapped ? '#7dd3fc' : ($isBlocked ? '#fca5a5' : '#86efac') }};">
                            {{ !$isMapped ? '—' : ($isBlocked ? '✕' : '✓') }}
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(!$config->is_active)
    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:12px;padding:14px 18px;">
        <p style="font-size:12px;color:#854d0e;font-weight:600;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Channel manager is not configured. <a href="{{ route('channel_manager.config') }}" style="color:#0891b2;">Go to Config</a> to connect your provider and enable syncing.</p>
    </div>
    @endif

</div>
@endsection
