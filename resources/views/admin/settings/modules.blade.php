@extends('layouts.admin')
@section('title', 'Modules')
@section('page-title', 'Feature Modules')
@section('page-subtitle', 'Enable or disable automation and integration modules')

@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

@php
$icons = [
    'whatsapp'            => ['fab fa-whatsapp',            'linear-gradient(135deg,#25d366,#128c7e)', '#25d366'],
    'payment_links'       => ['fas fa-link',                'linear-gradient(135deg,#f59e0b,#d97706)', '#f59e0b'],
    'pathik'              => ['fas fa-id-card',             'linear-gradient(135deg,#3b82f6,#1d4ed8)', '#3b82f6'],
    'channel_manager'     => ['fas fa-hotel',               'linear-gradient(135deg,#8b5cf6,#6d28d9)', '#8b5cf6'],
    'time-slot-pricing'   => ['fas fa-clock',               'linear-gradient(135deg,#7c3aed,#a855f7)', '#7c3aed'],
    'hourly-pricing'      => ['fas fa-hourglass-half',      'linear-gradient(135deg,#0891b2,#0e7490)', '#0891b2'],
    'booking-widget'      => ['fas fa-globe',               'linear-gradient(135deg,#6366f1,#4f46e5)', '#6366f1'],
    'restaurant'          => ['fas fa-concierge-bell',      'linear-gradient(135deg,#dc2626,#991b1b)', '#dc2626'],
    'whole-hotel-booking' => ['fas fa-building',            'linear-gradient(135deg,#0d9488,#0f766e)', '#0d9488'],
    'slot-search-engine'  => ['fas fa-search',              'linear-gradient(135deg,#0284c7,#0369a1)', '#0284c7'],
    'extra-billing'       => ['fas fa-receipt',             'linear-gradient(135deg,#a855f7,#7c3aed)', '#a855f7'],
    'inventory'           => ['fas fa-boxes',               'linear-gradient(135deg,#0ea5e9,#0369a1)', '#0ea5e9'],
    'food-menu'           => ['fas fa-utensils',            'linear-gradient(135deg,#f97316,#ea580c)', '#f97316'],
    'email-parser'        => ['fas fa-envelope-open-text',  'linear-gradient(135deg,#0d9488,#0f766e)', '#0d9488'],
];

$configRoutes = [
    'whatsapp'      => 'whatsapp.setup',
    'booking-widget'=> 'admin.booking-widget.settings',
    'email-parser'  => 'email-parser.config',
];
@endphp

@if($showAllHotels)

    {{-- Super Admin "All Hotels" view: group by hotel --}}
    @foreach($groupedByHotel as $hotelId => $hotelModules)
    @php $hotelName = $hotels[$hotelId]->name ?? ('Hotel #' . $hotelId); @endphp

    <div style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:28px;height:28px;background:linear-gradient(135deg,#0891b2,#0e7490);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-building" style="color:#fff;font-size:12px;"></i>
            </div>
            <span style="font-size:15px;font-weight:800;color:#1e293b;">{{ $hotelName }}</span>
            <span style="font-size:11px;color:#94a3b8;background:#f1f5f9;padding:2px 10px;border-radius:20px;">{{ $hotelModules->count() }} module{{ $hotelModules->count() !== 1 ? 's' : '' }}</span>
        </div>

        <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            @foreach($hotelModules as $i => $module)
            @php
                [$icon, $grad, $color] = $icons[$module->slug] ?? ['fas fa-puzzle-piece', 'linear-gradient(135deg,#64748b,#334155)', '#64748b'];
                $isLast = $i === $hotelModules->count() - 1;
            @endphp
            <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;{{ !$isLast ? 'border-bottom:1px solid #f1f5f9;' : '' }}background:{{ $module->is_enabled ? '#fafffe' : '#fff' }};">
                <div style="width:40px;height:40px;background:{{ $module->is_enabled ? $grad : '#f1f5f9' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="{{ $icon }}" style="font-size:17px;color:{{ $module->is_enabled ? '#fff' : '#94a3b8' }};"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $module->name }}</div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:1px;">{{ $module->description }}</div>
                </div>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $module->is_enabled ? '#dcfce7' : '#f1f5f9' }};color:{{ $module->is_enabled ? '#15803d' : '#94a3b8' }};white-space:nowrap;">
                    {{ $module->is_enabled ? 'Active' : 'Disabled' }}
                </span>
                <form action="{{ route('modules.toggle', $module) }}" method="POST">
                    @csrf
                    <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:{{ $module->is_enabled ? '#fee2e2' : $grad }};color:{{ $module->is_enabled ? '#b91c1c' : '#fff' }};border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;">
                        <i class="fas {{ $module->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        {{ $module->is_enabled ? 'Disable' : 'Enable' }}
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

@else

    {{-- Regular hotel-scoped list view --}}
    @php
        $enabled  = $modules->where('is_enabled', true);
        $disabled = $modules->where('is_enabled', false);
    @endphp

    @if($enabled->count())
    <div style="margin-bottom:24px;">
        <div style="font-size:12px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;padding-left:4px;">
            <i class="fas fa-circle" style="font-size:8px;margin-right:6px;"></i> Active ({{ $enabled->count() }})
        </div>
        <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            @foreach($enabled as $i => $module)
            @php
                [$icon, $grad, $color] = $icons[$module->slug] ?? ['fas fa-puzzle-piece', 'linear-gradient(135deg,#64748b,#334155)', '#64748b'];
                $isLast = $i === $enabled->count() - 1;
            @endphp
            <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;{{ !$isLast ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                <div style="width:42px;height:42px;background:{{ $grad }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="{{ $icon }}" style="font-size:18px;color:#fff;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $module->name }}</div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $module->description }}</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                    @if(isset($configRoutes[$module->slug]))
                    <a href="{{ route($configRoutes[$module->slug]) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:10px;"></i> Configure
                    </a>
                    @endif
                    <form action="{{ route('modules.toggle', $module) }}" method="POST">
                        @csrf
                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-toggle-on"></i> Disable
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($disabled->count())
    <div style="margin-bottom:24px;">
        <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;padding-left:4px;">
            <i class="fas fa-circle" style="font-size:8px;margin-right:6px;"></i> Disabled ({{ $disabled->count() }})
        </div>
        <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            @foreach($disabled as $i => $module)
            @php
                [$icon, $grad, $color] = $icons[$module->slug] ?? ['fas fa-puzzle-piece', 'linear-gradient(135deg,#64748b,#334155)', '#64748b'];
                $isLast = $i === $disabled->count() - 1;
            @endphp
            <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;{{ !$isLast ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                <div style="width:42px;height:42px;background:#f1f5f9;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="{{ $icon }}" style="font-size:18px;color:#94a3b8;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:#64748b;">{{ $module->name }}</div>
                    <div style="font-size:12px;color:#cbd5e1;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $module->description }}</div>
                </div>
                <div style="flex-shrink:0;">
                    <form action="{{ route('modules.toggle', $module) }}" method="POST">
                        @csrf
                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:{{ $grad }};color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-toggle-off"></i> Enable
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

@endif

<div style="background:#fff;border-radius:14px;padding:16px 20px;border:1px solid #f1f5f9;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
        <i class="fas fa-info-circle" style="color:#0891b2;"></i>
        <span style="font-size:13px;font-weight:700;color:#1e293b;">About Modules</span>
    </div>
    <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0;">
        Modules extend the CRM with optional features. Enabling a module makes its settings, navigation links, and automation hooks active. Disabling hides it completely without deleting any saved configuration.
    </p>
</div>

@endsection
