@extends('layouts.platform')

@section('title', $user->name . ' — Platform Admin')
@section('page-title', $user->name)
@section('page-subtitle', $user->email . ' · ' . $assignments->count() . ' hotel assignment' . ($assignments->count() !== 1 ? 's' : ''))

@section('content')

{{-- Back link --}}
<a href="{{ route('platform.users.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;margin-bottom:20px;">
    <i class="fas fa-arrow-left"></i> Back to Users
</a>

{{-- User info card --}}
<div style="background:linear-gradient(135deg,#1e1b4b,#2d1b69);border-radius:20px;padding:28px;margin-bottom:24px;display:flex;align-items:center;gap:20px;">
    <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <span style="color:#fff;font-size:26px;font-weight:800;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
    </div>
    <div style="flex:1;">
        <h2 style="font-size:20px;font-weight:800;color:#fff;margin:0 0 4px;">{{ $user->name }}</h2>
        <p style="font-size:13px;color:#c4b5fd;margin:0 0 8px;">{{ $user->email }}</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <span style="font-size:11px;font-weight:700;background:rgba(255,255,255,.15);color:#e0e7ff;padding:3px 10px;border-radius:20px;">
                {{ $assignments->count() }} hotel{{ $assignments->count() !== 1 ? 's' : '' }}
            </span>
            @php $activeCount = $assignments->where('status','active')->count(); @endphp
            @if($activeCount > 0)
            <span style="font-size:11px;font-weight:700;background:rgba(16,185,129,.2);color:#6ee7b7;padding:3px 10px;border-radius:20px;">
                {{ $activeCount }} active
            </span>
            @endif
            @if($assignments->count() - $activeCount > 0)
            <span style="font-size:11px;font-weight:700;background:rgba(239,68,68,.2);color:#fca5a5;padding:3px 10px;border-radius:20px;">
                {{ $assignments->count() - $activeCount }} suspended
            </span>
            @endif
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:11px;color:#c4b5fd;margin-bottom:4px;">User ID</div>
        <div style="font-size:13px;font-weight:700;color:#fff;font-family:monospace;">#{{ $user->id }}</div>
    </div>
</div>

{{-- Hotel Assignments --}}
<div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:12px;">Hotel Assignments</div>

@if($assignments->isEmpty())
<div style="background:#fff;border-radius:20px;padding:60px 24px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
    <i class="fas fa-building" style="font-size:36px;color:#e2e8f0;display:block;margin-bottom:12px;"></i>
    <p style="color:#94a3b8;font-weight:600;">No hotel assignments found for this user.</p>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    @foreach($assignments as $a)
    @php
        $plans = config('plans', []);
        $plan  = $plans[$a->hotel_plan] ?? ['label' => ucfirst($a->hotel_plan), 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'];
        $isActive = $a->status === 'active';
        $roleColors = ['Admin' => ['#ede9fe','#6d28d9'], 'Manager' => ['#cffafe','#0e7490'], 'Receptionist' => ['#fef9c3','#854d0e']];
        $rc = $roleColors[$a->role] ?? ['#f1f5f9','#475569'];
    @endphp
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid {{ $isActive ? '#f1f5f9' : '#fee2e2' }};position:relative;overflow:hidden;">

        {{-- Hotel header --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:42px;height:42px;background:linear-gradient(135deg,{{ $isActive ? '#8b5cf6,#4c1d95' : '#94a3b8,#475569' }});border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:#fff;font-size:16px;font-weight:800;">{{ strtoupper(substr($a->hotel_name, 0, 1)) }}</span>
            </div>
            <div>
                <div style="font-size:15px;font-weight:800;color:#1e293b;">{{ $a->hotel_name }}</div>
                <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $a->hotel_slug }}</div>
            </div>
        </div>

        {{-- Badges row --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $rc[0] }};color:{{ $rc[1] }};">
                {{ $a->role }}
            </span>
            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $plan['badge_bg'] }};color:{{ $plan['badge_text'] }};">
                {{ $plan['label'] }}
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $isActive ? '#dcfce7' : '#fee2e2' }};color:{{ $isActive ? '#15803d' : '#b91c1c' }};">
                <span style="width:5px;height:5px;border-radius:50%;background:{{ $isActive ? '#15803d' : '#b91c1c' }};display:inline-block;"></span>
                {{ ucfirst($a->status) }}
            </span>
            @if($a->is_hotel_admin)
            <span style="display:inline-flex;align-items:center;gap:3px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#fef3c7;color:#b45309;">
                <i class="fas fa-star" style="font-size:9px;"></i> Hotel Admin
            </span>
            @endif
        </div>

        {{-- Joined date --}}
        <div style="font-size:12px;color:#94a3b8;margin-bottom:16px;">
            <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>
            Assigned {{ \Carbon\Carbon::parse($a->joined_at)->format('d M Y') }}
        </div>

        {{-- Suspend/Activate action --}}
        @if($isActive)
        <form method="POST" action="{{ route('platform.users.suspend', [$user->id, $a->hotel_id]) }}" style="margin:0;" onsubmit="return confirm('Suspend access to {{ addslashes($a->hotel_name) }}?')">
            @csrf
            <button type="submit" style="width:100%;padding:8px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-ban"></i> Suspend Access
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('platform.users.activate', [$user->id, $a->hotel_id]) }}" style="margin:0;">
            @csrf
            <button type="submit" style="width:100%;padding:8px;background:#dcfce7;color:#15803d;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-check-circle"></i> Reactivate Access
            </button>
        </form>
        @endif

    </div>
    @endforeach
</div>
@endif

@endsection
