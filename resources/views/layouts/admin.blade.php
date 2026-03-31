<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Resort CRM') — {{ $settings->resort_name ?? 'Resort CRM' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @livewireStyles
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 260px;
            min-width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 60%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 50;
            overflow-y: auto;
            transition: transform .3s ease;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }

        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        /* Main content offset */
        #main-wrap {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Nav link ── */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all .18s ease;
            white-space: nowrap;
        }
        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.07);
        }
        .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(6,182,212,.25), rgba(59,130,246,.2));
            border: 1px solid rgba(6,182,212,.3);
        }
        .nav-link .icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .nav-link.active .icon {
            background: linear-gradient(135deg,#06b6d4,#3b82f6);
            color: #fff;
            box-shadow: 0 4px 12px rgba(6,182,212,.4);
        }
        .nav-link:not(.active) .icon {
            background: rgba(255,255,255,.05);
            color: #64748b;
        }
        .nav-link:hover .icon {
            background: rgba(255,255,255,.1);
            color: #cbd5e1;
        }

        /* ── Section label ── */
        .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #475569;
            padding: 18px 14px 6px;
        }

        /* ── Logout btn ── */
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
        }
        .logout-btn:hover {
            color: #fca5a5;
            background: rgba(239,68,68,.1);
        }
        .logout-btn .icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
            background: rgba(255,255,255,.05);
            color: #64748b;
        }
        .logout-btn:hover .icon {
            background: rgba(239,68,68,.2);
            color: #fca5a5;
        }

        /* ── Utility classes ── */
        .card-hover { transition: all .25s ease; }
        .card-hover:hover { box-shadow: 0 10px 40px rgba(0,0,0,.1); transform: translateY(-2px); }
        .stat-card { background:#fff; border-radius:16px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #f1f5f9; }
        .btn-primary { display:inline-flex; align-items:center; background:linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; padding:10px 20px; border-radius:12px; font-weight:600; font-size:14px; border:none; cursor:pointer; transition:all .2s; box-shadow:0 4px 12px rgba(6,182,212,.3); text-decoration:none; }
        .btn-primary:hover { background:linear-gradient(135deg,#0891b2,#2563eb); box-shadow:0 6px 20px rgba(6,182,212,.4); transform:translateY(-1px); }
        .btn-secondary { display:inline-flex; align-items:center; background:#fff; color:#374151; padding:10px 20px; border-radius:12px; font-weight:600; font-size:14px; border:1px solid #e5e7eb; cursor:pointer; transition:all .2s; text-decoration:none; }
        .btn-secondary:hover { background:#f9fafb; border-color:#d1d5db; }
        .btn-danger { display:inline-flex; align-items:center; background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; padding:8px 16px; border-radius:10px; font-weight:600; font-size:13px; border:none; cursor:pointer; transition:all .2s; text-decoration:none; }
        .form-input { width:100%; padding:10px 16px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px; color:#374151; outline:none; transition:all .2s; background:#fff; }
        .form-input:focus { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,.1); }
        .form-label { display:block; font-size:13px; font-weight:600; color:#4b5563; margin-bottom:6px; }
        .badge-green  { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#d1fae5; color:#065f46; }
        .badge-blue   { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#dbeafe; color:#1e40af; }
        .badge-yellow { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#fef3c7; color:#92400e; }
        .badge-red    { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#fee2e2; color:#991b1b; }
        .badge-gray   { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#f3f4f6; color:#374151; }
        .badge-purple { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#ede9fe; color:#5b21b6; }

        /* ══════════════════════════════════════
           List-view shared component classes
           ══════════════════════════════════════ */

        /* Filter bar card */
        .lv-filter-bar {
            background: #fff;
            border-radius: 18px;
            padding: 18px 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            border: 1px solid #f1f5f9;
            margin-bottom: 18px;
        }
        .lv-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .lv-filter-group { display: flex; flex-direction: column; }
        .lv-filter-group-grow { flex: 1; min-width: 200px; }
        .lv-filter-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 6px;
        }
        .lv-filter-input, .lv-filter-select {
            padding: 9px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #374151;
            outline: none;
            background: #fff;
            transition: border-color .15s;
            width: 100%;
            box-sizing: border-box;
        }
        .lv-filter-input:focus, .lv-filter-select:focus { border-color: #06b6d4; }
        .lv-filter-input-icon { padding-left: 38px; }
        .lv-filter-icon-wrap {
            position: relative;
        }
        .lv-filter-icon-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }
        .lv-filter-spinner {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
        }
        .lv-filter-result {
            margin-top: 10px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .lv-clear-btn {
            padding: 9px 16px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all .15s;
        }
        .lv-clear-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #374151; }

        /* Main table card */
        .lv-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }
        .lv-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lv-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .lv-card-icon i { color: #fff; font-size: 14px; }
        .lv-card-title {
            font-weight: 800;
            color: #1e293b;
            font-size: 15px;
        }
        .lv-card-title span {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
        }
        .lv-card-subtitle { font-size: 12px; color: #94a3b8; margin-top: 1px; }

        /* Table */
        .lv-table-wrap { overflow-x: auto; }
        .lv-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        .lv-th {
            text-align: left;
            padding: 11px 18px;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            background: #f8fafc;
        }
        .lv-th-right { text-align: right; }
        .lv-td {
            padding: 14px 18px;
            font-size: 14px;
            color: #374151;
            border-top: 1px solid #f8fafc;
        }
        .lv-td-right { text-align: right; }
        .lv-td-center { text-align: center; }
        .lv-row { transition: background .12s; }
        .lv-row:hover { background: #f8fafc; }
        .lv-pagination { padding: 16px 22px; border-top: 1px solid #f8fafc; }

        /* Avatar */
        .lv-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,.12);
        }

        /* Name link */
        .lv-name-link {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
            text-decoration: none;
            transition: color .12s;
        }
        .lv-name-link:hover { color: #0891b2; }

        /* Secondary / muted text */
        .lv-secondary { font-size: 12px; color: #94a3b8; margin-top: 2px; }

        /* Monospace identifiers */
        .lv-mono {
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
        }

        /* Room number pill */
        .lv-room-pill {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 40px;
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            border-radius: 10px;
        }
        .lv-room-pill-label {
            font-size: 8px;
            color: rgba(255,255,255,.45);
            font-weight: 600;
            letter-spacing: .04em;
            line-height: 1;
        }
        .lv-room-pill-num {
            font-size: 13px;
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
        }

        /* Action icon buttons */
        .lv-action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .12s;
            flex-shrink: 0;
        }
        .lv-action-btn i { font-size: 11px; }
        .lv-action-btn-blue   { background: #eff6ff; color: #2563eb; }
        .lv-action-btn-blue:hover   { background: #dbeafe; color: #1d4ed8; }
        .lv-action-btn-amber  { background: #fffbeb; color: #d97706; }
        .lv-action-btn-amber:hover  { background: #fef3c7; color: #b45309; }
        .lv-action-btn-purple { background: #faf5ff; color: #7c3aed; }
        .lv-action-btn-purple:hover { background: #ede9fe; color: #6d28d9; }
        .lv-action-btn-red    { background: #fff1f2; color: #e11d48; }
        .lv-action-btn-red:hover    { background: #ffe4e6; color: #be123c; }
        .lv-action-btn-green  { background: #f0fdf4; color: #16a34a; }
        .lv-action-btn-green:hover  { background: #dcfce7; color: #15803d; }
        .lv-action-btn-gray   { background: #f8fafc; color: #475569; }
        .lv-action-btn-gray:hover   { background: #f1f5f9; color: #334155; }
        .lv-action-btn-cyan   { background: #ecfeff; color: #0891b2; }
        .lv-action-btn-cyan:hover   { background: #cffafe; color: #0e7490; }

        /* Inline badge variants (pill shape) */
        .lv-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .lv-badge-green  { background: #dcfce7; color: #15803d; }
        .lv-badge-amber  { background: #fef3c7; color: #92400e; }
        .lv-badge-red    { background: #fee2e2; color: #b91c1c; }
        .lv-badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .lv-badge-purple { background: #ede9fe; color: #6d28d9; }
        .lv-badge-cyan   { background: #ecfeff; color: #0e7490; }
        .lv-badge-gray   { background: #f1f5f9; color: #475569; }
        .lv-badge-cash   { background: #dcfce7; color: #15803d; }
        .lv-badge-card   { background: #dbeafe; color: #1d4ed8; }
        .lv-badge-upi    { background: #ede9fe; color: #6d28d9; }
        .lv-badge-bank   { background: #ecfeff; color: #0e7490; }
        .lv-badge-cheque { background: #fef3c7; color: #92400e; }

        /* Empty state */
        .lv-empty {
            padding: 56px 24px;
            text-align: center;
        }
        .lv-empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }
        .lv-empty-title {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
        }
        .lv-empty-sub { font-size: 13px; color: #94a3b8; margin-bottom: 16px; }

        /* Stats grid (payments page) */
        .lv-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 16px;
            margin-bottom: 18px;
            align-items: stretch;
        }
        @media (max-width: 768px) {
            .lv-stats-grid { grid-template-columns: 1fr 1fr; }
            .lv-stats-grid > *:last-child { grid-column: span 2; }
        }
        .lv-stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            border: 1px solid #f1f5f9;
        }
        .lv-stat-card-accent {
            border-radius: 18px;
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
        }
        .lv-stat-card-accent::after {
            content: '';
            position: absolute;
            top: -24px; right: -24px;
            width: 90px; height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
        }
        .lv-stat-label { font-size: 12px; font-weight: 600; letter-spacing: .04em; margin-bottom: 6px; }
        .lv-stat-value { font-size: 26px; font-weight: 900; line-height: 1; }
        .lv-stat-sub   { font-size: 12px; margin-top: 4px; }

        /* Mobile overlay */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 40;
        }

        @media (max-width: 1024px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #main-wrap { margin-left: 0; }
            #sidebar-overlay.show { display: block; }
        }

        /* ── Mobile topbar fixes ── */
        @media (max-width: 768px) {
            .topbar-date, .topbar-time { display: none !important; }
            .topbar-title h1 { font-size: 15px !important; }
            .topbar-title p  { display: none !important; }
            #main-wrap > header { padding: 0 12px !important; }
            #main-wrap > main  { padding: 14px 12px !important; }
        }
        @media (max-width: 400px) {
            .topbar-title h1 { font-size: 13px !important; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div>

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside id="sidebar">

        <!-- Logo -->
        <div style="padding: 20px 16px 16px; border-bottom: 1px solid rgba(255,255,255,.06);">
            <div style="display:flex; align-items:center; gap:12px;">
                @if($settings && $settings->logo && file_exists(public_path('storage/' . $settings->logo)))
                <div style="width:42px;height:42px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#fff;">
                    <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" style="width:42px;height:42px;object-fit:contain;padding:4px;">
                </div>
                @else
                <div style="width:42px;height:42px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,182,212,.4);flex-shrink:0;">
                    <i class="fas fa-umbrella-beach" style="color:#fff;font-size:18px;"></i>
                </div>
                @endif
                <div style="min-width:0;">
                    <div style="color:#fff;font-weight:800;font-size:14px;line-height:1.2;">{{ $settings->resort_name ?? 'Resort CRM' }}</div>
                    <div style="color:#475569;font-size:11px;margin-top:1px;">{{ $settings->tagline ?? 'Resort & Spa CRM' }}</div>
                </div>
            </div>
        </div>

        <!-- Hotel Badge / SA Hotel Switcher -->
        @if(session('crm_user_role') === 'Super Admin')
        @php
            $saHotels = \Illuminate\Support\Facades\DB::table('hotels')->orderBy('name')->get();
            $saFilterId = session('crm_sa_hotel_filter');
            $saSelected = $saHotels->firstWhere('id', $saFilterId);
        @endphp
        <div style="padding:8px 16px 4px;">
            <form method="POST" action="{{ route('sa.hotel.filter') }}" id="sa-hotel-form">
                @csrf
                <div style="background:rgba(124,58,237,.10);border:1px solid rgba(124,58,237,.25);border-radius:10px;padding:8px 12px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(196,181,253,.5);margin-bottom:5px;">Hotel Filter</div>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:24px;height:24px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-building" style="color:#fff;font-size:10px;"></i>
                        </div>
                        <select name="hotel_id"
                                onchange="document.getElementById('sa-hotel-form').submit()"
                                style="background:transparent;border:none;color:#c4b5fd;font-size:12px;font-weight:700;flex:1;min-width:0;outline:none;cursor:pointer;appearance:none;-webkit-appearance:none;">
                            <option value="" {{ !$saFilterId ? 'selected' : '' }} style="background:#1e1b4b;color:#c4b5fd;">All Hotels</option>
                            @foreach($saHotels as $h)
                            <option value="{{ $h->id }}" {{ $saFilterId == $h->id ? 'selected' : '' }} style="background:#1e1b4b;color:#c4b5fd;">{{ $h->name }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down" style="color:#7c3aed;font-size:9px;flex-shrink:0;pointer-events:none;"></i>
                    </div>
                </div>
            </form>
        </div>
        @elseif(session('crm_hotel_name'))
        @php
            $hotelInitial = strtoupper(substr(session('crm_hotel_name', 'H'), 0, 1));
        @endphp
        <div style="padding:8px 16px 4px;">
            <div style="background:rgba(6,182,212,.08);border:1px solid rgba(6,182,212,.2);border-radius:10px;padding:8px 12px;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(103,232,249,.5);margin-bottom:5px;">Current Hotel</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;background:linear-gradient(135deg,#06b6d4,#0284c7);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;flex-shrink:0;">{{ $hotelInitial }}</div>
                    <span style="color:#67e8f9;font-size:12px;font-weight:700;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ session('crm_hotel_name') }}</span>
                    @if(session('crm_hotel_count', 1) > 1)
                    <a href="{{ route('select.hotel') }}" style="width:24px;height:24px;background:rgba(6,182,212,.15);border:1px solid rgba(6,182,212,.3);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#67e8f9;font-size:10px;text-decoration:none;flex-shrink:0;transition:background .15s;" title="Switch Hotel" onmouseover="this.style.background='rgba(6,182,212,.3)'" onmouseout="this.style.background='rgba(6,182,212,.15)'">
                        <i class="fas fa-exchange-alt"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- User -->
        <div style="padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.06);">
            <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
                @php
                    $roleColors = ['Super Admin'=>'#7c3aed','Admin'=>'#dc2626','Manager'=>'#2563eb','Receptionist'=>'#16a34a'];
                    $roleBg = $roleColors[session('crm_user_role','Admin')] ?? '#475569';
                @endphp
                <div style="width:36px;height:36px;background:{{ $roleBg }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">{{ session('crm_user_avatar','A') }}</div>
                <div style="min-width:0;">
                    <div style="color:#e2e8f0;font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ session('crm_user_name','Admin') }}</div>
                    <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                        <span style="display:inline-block;background:{{ $roleBg }};color:#fff;font-size:9px;font-weight:700;padding:1px 7px;border-radius:999px;letter-spacing:.05em;text-transform:uppercase;">{{ session('crm_user_role','Admin') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav style="flex:1;padding:10px 10px 0;">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-th-large"></i></span>
                Dashboard
            </a>

            @canDo('guests.view')
            <div class="nav-section">Guest Management</div>
            <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-users"></i></span>
                Guests
            </a>
            @endCanDo

            @php
                $showOps = \App\Services\PermissionService::check('rooms.view')
                    || \App\Services\PermissionService::check('bookings.view')
                    || \App\Services\PermissionService::check('checkin.process')
                    || \App\Services\PermissionService::check('checkout.process');
            @endphp
            @if($showOps)
            <div class="nav-section">Operations</div>
            @endif

            @canDo('rooms.view')
            <a href="{{ route('rooms.index') }}" class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-door-open"></i></span>
                Rooms
            </a>
            @endCanDo

            @canDo('bookings.view')
            <a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-calendar-check"></i></span>
                Bookings
            </a>
            @endCanDo

            @canDo('checkin.process')
            <a href="{{ route('checkin.index') }}" class="nav-link {{ request()->routeIs('checkin.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-sign-in-alt"></i></span>
                Check-In
            </a>
            @endCanDo

            @canDo('checkout.process')
            <a href="{{ route('checkout.index') }}" class="nav-link {{ request()->routeIs('checkout.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                Check-Out
            </a>
            @endCanDo

            @php
                $showFinance = \App\Services\PermissionService::check('payments.view')
                    || \App\Services\PermissionService::check('invoices.view');
            @endphp
            @if($showFinance)
            <div class="nav-section">Finance</div>
            @endif

            @canDo('payments.view')
            <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-credit-card"></i></span>
                Payments
            </a>
            @endCanDo

            @canDo('invoices.view')
            <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                Invoices
            </a>
            @endCanDo

            @canDo('reports.view')
            <div class="nav-section">Analytics</div>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                Reports
            </a>
            @endCanDo

            <div class="nav-section">Automation</div>

            @if(\App\Models\Module::isEnabled('whatsapp'))
            <a href="{{ route('whatsapp.config') }}" class="nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <span class="icon"><i class="fab fa-whatsapp"></i></span>
                WhatsApp
            </a>
            @endif

            @if(\App\Models\Module::isEnabled('payment_links'))
            <a href="{{ route('payment_links.config') }}" class="nav-link {{ request()->routeIs('payment_links.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-qrcode"></i></span>
                Payment Links
            </a>
            @endif

            @if(\App\Models\Module::isEnabled('channel_manager'))
            <a href="{{ route('channel_manager.index') }}" class="nav-link {{ request()->routeIs('channel_manager.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-sitemap"></i></span>
                Channel Manager
            </a>
            @endif

            @if(\App\Models\Module::isEnabled('pathik'))
            <a href="{{ route('pathik.index') }}" class="nav-link {{ request()->routeIs('pathik.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                Pathik Portal
            </a>
            @endif

            <div class="nav-section">System</div>

            @if(session('crm_user_role') === 'Super Admin')
            <a href="{{ route('platform.dashboard') }}" class="nav-link {{ request()->routeIs('platform.*') ? 'active' : '' }}" style="{{ request()->routeIs('platform.*') ? '' : 'border:1px dashed rgba(139,92,246,.3);' }}">
                <span class="icon" style="{{ request()->routeIs('platform.*') ? '' : 'background:rgba(139,92,246,.12);color:#7c3aed;' }}"><i class="fas fa-layer-group"></i></span>
                Platform Admin
            </a>
            @endif

            @if(session('crm_user_role') === 'Super Admin')
            <a href="{{ route('modules.index') }}" class="nav-link {{ request()->routeIs('modules.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-puzzle-piece"></i></span>
                Modules
            </a>
            @endif

            @canDo('settings.view')
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-cog"></i></span>
                Settings
            </a>
            @endCanDo

            @canDo('activity_log.view')
            <a href="{{ route('activity_log.index') }}" class="nav-link {{ request()->routeIs('activity_log.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-history"></i></span>
                Activity Log
            </a>
            @endCanDo

            @canDo('roles.view')
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-shield-halved"></i></span>
                Roles & Permissions
            </a>
            @endCanDo

            @canDo('users.view')
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-user-cog"></i></span>
                User Management
            </a>
            @endCanDo

        </nav>

        <!-- Logout -->
        <div style="padding:10px 10px 16px;border-top:1px solid rgba(255,255,255,.06);margin-top:10px;">
            <a href="{{ route('password.change.form') }}" class="logout-btn" style="text-decoration:none;margin-bottom:4px;">
                <span class="icon"><i class="fas fa-lock"></i></span>
                Change Password
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">
                    <span class="icon"><i class="fas fa-power-off"></i></span>
                    Sign Out
                </button>
            </form>
        </div>

    </aside>
    <!-- ═══════════════ END SIDEBAR ═══════════════ -->

    <!-- Main Wrapper -->
    <div id="main-wrap">

        <!-- Top Bar -->
        <header style="background:#fff;border-bottom:1px solid #f1f5f9;padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:30;box-shadow:0 1px 3px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                <!-- Mobile hamburger -->
                <button onclick="openSidebar()" style="display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#64748b;flex-shrink:0;" id="hamburger">
                    <i class="fas fa-bars" style="font-size:20px;"></i>
                </button>
                <div class="topbar-title" style="min-width:0;">
                    <h1 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">@yield('page-title','Dashboard')</h1>
                    <p style="font-size:12px;color:#94a3b8;margin:0;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">@yield('page-subtitle','Azure Paradise Resort CRM')</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <div class="topbar-date" style="display:flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:7px 12px;">
                    <i class="fas fa-calendar-day" style="color:#06b6d4;font-size:12px;"></i>
                    <span style="font-size:12px;color:#475569;font-weight:500;">{{ now()->format('D, d M Y') }}</span>
                </div>
                <div class="topbar-time" style="display:flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:7px 12px;">
                    <i class="fas fa-clock" style="color:#06b6d4;font-size:12px;"></i>
                    <span style="font-size:12px;color:#475569;font-weight:500;" id="liveClock"></span>
                </div>
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;box-shadow:0 4px 12px rgba(6,182,212,.3);cursor:pointer;flex-shrink:0;">
                    {{ session('crm_user_avatar','A') }}
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success') || session('error'))
        <div style="padding:16px 24px 0;">
            @if(session('success'))
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 18px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
                <i class="fas fa-check-circle" style="color:#22c55e;font-size:16px;flex-shrink:0;"></i>
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#86efac;font-size:16px;">×</button>
            </div>
            @endif
            @if(session('error'))
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 18px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
                <i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:16px;flex-shrink:0;"></i>
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#fca5a5;font-size:16px;">×</button>
            </div>
            @endif
        </div>
        @endif

        <!-- Page Content -->
        <main style="flex:1;padding:24px;">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer style="background:#fff;border-top:1px solid #f1f5f9;padding:14px 24px;text-align:center;">
            <p style="font-size:12px;color:#94a3b8;margin:0;">
                © {{ date('Y') }} {{ $settings->resort_name ?? 'Resort CRM' }}. All rights reserved.
                <span style="margin:0 8px;">•</span>
                Support: <a href="tel:+918460765785" style="color:#06b6d4;font-weight:600;text-decoration:none;">+91 84607 65785</a>
                <span style="margin:0 8px;">•</span>
                Made with <span style="color:#ef4444;">♥</span> by
                <a href="https://www.dreams-technology.com" target="_blank" style="color:#06b6d4;font-weight:600;text-decoration:none;">Dreams Technology</a>
            </p>
        </footer>

    </div>
    <!-- END Main Wrapper -->

</div>

<script>
    // Live clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Mobile sidebar
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebar-overlay').classList.add('show');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebar-overlay').classList.remove('show');
    }

    // Show hamburger on mobile
    function checkMobile() {
        const ham = document.getElementById('hamburger');
        if (window.innerWidth <= 1024) {
            ham.style.display = 'block';
        } else {
            ham.style.display = 'none';
            closeSidebar();
        }
    }
    checkMobile();
    window.addEventListener('resize', checkMobile);

    // Auto-dismiss flash messages
    setTimeout(() => {
        document.querySelectorAll('[data-flash]').forEach(el => el.remove());
    }, 5000);
</script>

@stack('scripts')
@livewireScripts
</body>
</html>
