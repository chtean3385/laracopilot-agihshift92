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
    'whatsapp'          => ['fab fa-whatsapp',       'linear-gradient(135deg,#25d366,#128c7e)', '#25d366'],
    'payment_links'     => ['fas fa-link',            'linear-gradient(135deg,#f59e0b,#d97706)', '#f59e0b'],
    'pathik'            => ['fas fa-id-card',         'linear-gradient(135deg,#3b82f6,#1d4ed8)', '#3b82f6'],
    'channel_manager'   => ['fas fa-hotel',           'linear-gradient(135deg,#8b5cf6,#6d28d9)', '#8b5cf6'],
    'time-slot-pricing' => ['fas fa-clock',           'linear-gradient(135deg,#7c3aed,#a855f7)', '#7c3aed'],
    'hourly-pricing'    => ['fas fa-hourglass-half',  'linear-gradient(135deg,#0891b2,#0e7490)', '#0891b2'],
    'booking-widget'    => ['fas fa-globe',           'linear-gradient(135deg,#6366f1,#4f46e5)', '#6366f1'],
    'restaurant'        => ['fas fa-concierge-bell',  'linear-gradient(135deg,#dc2626,#991b1b)', '#dc2626'],
    'whole-hotel-booking' => ['fas fa-building',      'linear-gradient(135deg,#0d9488,#0f766e)', '#0d9488'],
    'slot-search-engine'  => ['fas fa-search',        'linear-gradient(135deg,#0284c7,#0369a1)', '#0284c7'],
    'extra-billing'     => ['fas fa-receipt',         'linear-gradient(135deg,#a855f7,#7c3aed)', '#a855f7'],
    'inventory'         => ['fas fa-boxes',           'linear-gradient(135deg,#0ea5e9,#0369a1)', '#0ea5e9'],
    'food-menu'         => ['fas fa-utensils',        'linear-gradient(135deg,#f97316,#ea580c)', '#f97316'],
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

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
            @foreach($hotelModules as $module)
            @php [$icon, $grad, $color] = $icons[$module->slug] ?? ['fas fa-puzzle-piece', 'linear-gradient(135deg,#64748b,#334155)', '#64748b']; @endphp
            <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:2px solid {{ $module->is_enabled ? $color.'33' : '#f1f5f9' }};transition:border-color .2s;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:{{ $module->is_enabled ? $color.'15' : '#f8fafc' }};"></div>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
                    <div style="width:52px;height:52px;background:{{ $module->is_enabled ? $grad : '#f1f5f9' }};border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="{{ $icon }}" style="font-size:22px;color:{{ $module->is_enabled ? '#fff' : '#94a3b8' }};"></i>
                    </div>
                    <span style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $module->is_enabled ? '#dcfce7' : '#f1f5f9' }};color:{{ $module->is_enabled ? '#15803d' : '#94a3b8' }};">
                        {{ $module->is_enabled ? 'Active' : 'Disabled' }}
                    </span>
                </div>
                <div style="font-size:17px;font-weight:800;color:#1e293b;margin-bottom:6px;">{{ $module->name }}</div>
                <div style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:20px;">{{ $module->description }}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <form action="{{ route('modules.toggle', $module) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:{{ $module->is_enabled ? '#fee2e2' : $grad }};color:{{ $module->is_enabled ? '#b91c1c' : '#fff' }};border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
                            <i class="fas {{ $module->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                            {{ $module->is_enabled ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                    @if($module->is_enabled && $module->slug === 'whatsapp')
                    <a href="{{ route('whatsapp.setup') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:11px;"></i> Configure
                    </a>
                    @elseif($module->is_enabled && $module->slug === 'food-menu')
                    <a href="{{ route('food-menu.dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:11px;"></i> Configure
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

@else

    {{-- Regular hotel-scoped view --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
        @foreach($modules as $module)
        @php [$icon, $grad, $color] = $icons[$module->slug] ?? ['fas fa-puzzle-piece', 'linear-gradient(135deg,#64748b,#334155)', '#64748b']; @endphp
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:2px solid {{ $module->is_enabled ? $color.'33' : '#f1f5f9' }};transition:border-color .2s;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:{{ $module->is_enabled ? $color.'15' : '#f8fafc' }};"></div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
                <div style="width:52px;height:52px;background:{{ $module->is_enabled ? $grad : '#f1f5f9' }};border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s;">
                    <i class="{{ $icon }}" style="font-size:22px;color:{{ $module->is_enabled ? '#fff' : '#94a3b8' }};transition:color .2s;"></i>
                </div>
                <span style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $module->is_enabled ? '#dcfce7' : '#f1f5f9' }};color:{{ $module->is_enabled ? '#15803d' : '#94a3b8' }};">
                    {{ $module->is_enabled ? 'Active' : 'Disabled' }}
                </span>
            </div>
            <div style="font-size:17px;font-weight:800;color:#1e293b;margin-bottom:6px;">{{ $module->name }}</div>
            <div style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:20px;">{{ $module->description }}</div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <form action="{{ route('modules.toggle', $module) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:{{ $module->is_enabled ? '#fee2e2' : $grad }};color:{{ $module->is_enabled ? '#b91c1c' : '#fff' }};border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;">
                        <i class="fas {{ $module->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        {{ $module->is_enabled ? 'Disable' : 'Enable' }}
                    </button>
                </form>
                @if($module->is_enabled)
                    @if($module->slug === 'whatsapp')
                    <a href="{{ route('whatsapp.setup') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:11px;"></i> Configure
                    </a>
                    @elseif($module->slug === 'booking-widget')
                    <a href="{{ route('admin.booking-widget.settings') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:11px;"></i> Configure
                    </a>
                    @elseif($module->slug === 'food-menu')
                    <a href="{{ route('food-menu.dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;text-decoration:none;">
                        <i class="fas fa-cog" style="font-size:11px;"></i> Configure
                    </a>
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>

@endif

<div style="margin-top:24px;background:#fff;border-radius:16px;padding:18px 22px;border:1px solid #f1f5f9;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <i class="fas fa-info-circle" style="color:#0891b2;"></i>
        <span style="font-size:14px;font-weight:700;color:#1e293b;">About Modules</span>
    </div>
    <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0;">
        Modules extend the CRM with optional features. Enabling a module makes its settings, navigation links, and automation hooks active across the system. Disabling a module hides it completely without deleting any saved configuration.
    </p>
</div>

@endsection
