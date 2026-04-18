<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $settings->resort_name ?? 'Hotel CRM' }} | Hotel & Resort Management System</title>

    {{-- ── SEO Meta Tags ── --}}
    <meta name="description" content="@yield('meta_description', 'Complete hotel and resort CRM — manage bookings, guest check-ins, room availability, time-slot pricing, housekeeping, payments, and business reports all in one platform.')">
    <meta name="keywords" content="hotel CRM, resort management software, hotel booking system, hotel property management system, resort CRM, hotel check-in software, hotel guest management, hotel PMS, resort booking management, hotel room management, time slot hotel booking, hotel revenue management">
    <meta name="author" content="{{ $settings->resort_name ?? 'Hotel CRM' }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f172a">

    {{-- ── Favicon ── --}}
    <link rel="icon" type="image/png" href="{{ asset('hotel-crm-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('hotel-crm-logo.png') }}">

    {{-- ── Open Graph (social sharing) ── --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->resort_name ?? 'Hotel CRM' }}">
    <meta property="og:title" content="@yield('title', 'Dashboard') — {{ $settings->resort_name ?? 'Hotel CRM' }}">
    <meta property="og:description" content="Complete hotel and resort management — bookings, check-ins, rooms, guests, payments, and reports.">
    <meta property="og:image" content="{{ asset('hotel-crm-logo.png') }}">

    {{-- ── Twitter Card ── --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $settings->resort_name ?? 'Hotel CRM' }} — Hotel & Resort Management System">
    <meta name="twitter:description" content="Complete hotel CRM — bookings, check-ins, rooms, guests, and reports.">
    <meta name="twitter:image" content="{{ asset('hotel-crm-logo.png') }}">

    {{-- ── Structured Data (JSON-LD) ── --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "{{ $settings->resort_name ?? 'Hotel CRM' }}",
        "description": "Complete hotel and resort property management system — manage bookings, guests, check-ins, room availability, time-slot pricing, housekeeping, and revenue reports.",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "offers": { "@@type": "Offer", "category": "Hotel & Resort Management" },
        "featureList": [
            "Hotel Booking Management",
            "Guest Check-in & Check-out",
            "Room Availability & Pricing",
            "Time-Slot & Hourly Room Booking",
            "Housekeeping Management",
            "Payment & Invoice Tracking",
            "Business Reports & Analytics",
            "Multi-user Staff Roles",
            "WhatsApp Notifications",
            "Guest CRM"
        ]
    }
    </script>

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
            overflow: hidden;
            transition: transform .3s ease;
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
            #main-wrap { margin-left: 0; overflow-x: hidden; }
            #sidebar-overlay.show { display: block; }
        }
        @media (max-width: 768px) {
            body { overflow-x: hidden; }
        }

        /* ── Mobile topbar fixes ── */
        @media (max-width: 768px) {
            .topbar-date, .topbar-time { display: none !important; }
            .topbar-wa-label { display: none !important; }
            .topbar-title h1 { font-size: 15px !important; }
            .topbar-title p  { display: none !important; }
            #main-wrap > header { padding: 0 12px !important; }
            #main-wrap > main  { padding: 14px 12px !important; }
            /* Push button — icon only on mobile */
            #push-enable-label { display: none !important; }
            #push-enable-btn   { padding: 7px 9px !important; }
        }
        @media (max-width: 400px) {
            .topbar-title h1 { font-size: 13px !important; }
        }

        /* ── Onboarding Tour ── */
        #crm-tour-highlight {
            position: fixed;
            border-radius: 12px;
            box-shadow: 0 0 0 4000px rgba(0,0,0,.65), 0 0 0 3px #06b6d4, 0 0 24px rgba(6,182,212,.7);
            transition: top .3s ease, left .3s ease, width .3s ease, height .3s ease;
            z-index: 200001;
            pointer-events: none;
        }
        #crm-tour-card {
            position: fixed;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(0,0,0,.28), 0 4px 20px rgba(0,0,0,.12);
            padding: 24px 26px 18px;
            width: 360px;
            z-index: 200002;
            transition: top .3s ease, left .3s ease;
        }
        #crm-tour-card .tour-step-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg,#06b6d4,#3b82f6);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        #crm-tour-card .tour-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        #crm-tour-card .tour-desc {
            font-size: 15px;
            color: #334155;
            line-height: 1.7;
            margin-bottom: 18px;
        }
        #crm-tour-card .tour-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        #crm-tour-card .tour-dots {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        #crm-tour-card .tour-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: background .2s, width .2s;
        }
        #crm-tour-card .tour-dot.active {
            background: #06b6d4;
            width: 20px;
            border-radius: 4px;
        }
        .tour-btn-skip {
            background: none;
            border: none;
            font-size: 14px;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: color .15s, background .15s;
        }
        .tour-btn-skip:hover { color: #475569; background: #f8fafc; }
        .tour-btn-next {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg,#06b6d4,#3b82f6);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .15s;
            box-shadow: 0 4px 14px rgba(6,182,212,.35);
        }
        .tour-btn-next:hover { opacity: .9; }
    </style>
    @stack('styles')
</head>
<body>

<!-- Full-Screen Lock Overlay (trial/plan expired — state set by CheckTrialStatus middleware in session) -->
@php
    $showLock  = session('crm_plan_locked', false) && session('crm_user_role') !== 'Super Admin';
    $lockReason = session('crm_lock_reason', '');
    if ($lockReason === 'trial_expired') {
        $lockHindi = 'आपका निःशुल्क ट्रायल समाप्त हो गया है';
        $lockEng   = 'Your free trial has expired. Please upgrade to continue using the CRM.';
    } else {
        $lockHindi = 'आपका प्लान समाप्त हो गया है';
        $lockEng   = 'Your plan has expired. Please renew to continue using the CRM.';
    }
@endphp
@if($showLock)
<div style="position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.96);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(8px);">
    <div style="background:linear-gradient(135deg,#1e1b4b,#0f172a);border:1px solid rgba(239,68,68,.3);border-radius:24px;padding:40px;max-width:480px;width:100%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.6);">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(239,68,68,.4);">
            <i class="fas fa-lock" style="color:#fff;font-size:24px;"></i>
        </div>
        <h2 style="font-size:20px;font-weight:900;color:#fca5a5;margin-bottom:8px;">{{ $lockHindi }}</h2>
        <p style="font-size:14px;color:rgba(252,165,165,.7);margin-bottom:28px;line-height:1.6;">{{ $lockEng }}</p>
        <a href="{{ route('upgrade') }}"
           style="display:inline-flex;align-items:center;gap:10px;padding:14px 32px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border-radius:14px;font-size:15px;font-weight:800;text-decoration:none;box-shadow:0 6px 20px rgba(124,58,237,.4);margin-bottom:16px;width:100%;justify-content:center;">
            <i class="fas fa-arrow-up"></i> अभी अपग्रेड करें — Upgrade Now
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,.4);font-size:13px;">
                <i class="fas fa-sign-out-alt" style="margin-right:5px;"></i> लॉग आउट करें
            </button>
        </form>
    </div>
</div>
@endif

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
        <nav style="flex:1;padding:10px 10px 10px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.08) transparent;min-height:0;">

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

            @if(\App\Models\Module::isEnabled('extra-billing'))
            <a href="{{ route('food-billing.index') }}" class="nav-link {{ request()->routeIs('food-billing.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-utensils"></i></span>
                Food Billing
            </a>
            @endif

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
            <a href="{{ route('whatsapp.setup') }}" class="nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
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

            @if(\App\Models\Module::isEnabled('booking-widget'))
            <a href="{{ route('admin.booking-widget.settings') }}" class="nav-link {{ request()->routeIs('admin.booking-widget.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-globe"></i></span>
                Booking Widget
            </a>
            @endif

            @if(\App\Models\Module::isEnabled('pathik'))
            <a href="{{ route('pathik.index') }}" class="nav-link {{ request()->routeIs('pathik.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                Pathik Portal
            </a>
            @endif

            @if(\App\Models\Module::isEnabled('time-slot-pricing') || \App\Models\Module::isEnabled('hourly-pricing'))
            <a href="{{ route('time-slots.index') }}" class="nav-link {{ request()->routeIs('time-slots.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-clock"></i></span>
                Time Slots & Add-Ons
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
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
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
        <div style="padding:6px 10px 16px;border-top:1px solid rgba(255,255,255,.06);margin-top:8px;">
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
                <!-- Push Enable Button (shows when not yet granted) -->
                <div id="push-enable-wrap" style="display:none;position:relative;">
                    <button id="push-enable-btn" onclick="window.requestPushPermission && window.requestPushPermission()"
                        title="Enable push notifications"
                        style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fdf4ff;border:1.5px solid #d8b4fe;border-radius:10px;color:#7c3aed;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#fdf4ff'">
                        <i class="fas fa-bell-slash" id="push-enable-icon" style="font-size:12px;"></i>
                        <span id="push-enable-label">Enable Notifications</span>
                    </button>
                    <div id="push-denied-tip" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:260px;background:#1e293b;color:#fff;border-radius:10px;padding:12px 14px;font-size:12px;line-height:1.6;z-index:300;box-shadow:0 8px 20px rgba(0,0,0,.3);">
                        <i class="fas fa-lock" style="color:#f59e0b;margin-right:6px;"></i><strong>Notifications are blocked.</strong><br>
                        Click the <strong>🔒 lock icon</strong> in your browser address bar → <strong>Notifications → Allow</strong>, then reload the page.
                        <div style="margin-top:8px;color:#94a3b8;font-size:11px;">⚠ Does not work in Incognito mode.</div>
                    </div>
                </div>

                <!-- Push Notification Bell -->
                <div style="position:relative;" id="notif-bell-wrap">
                    <button onclick="toggleNotifPanel()" title="Notifications"
                        style="position:relative;width:36px;height:36px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;transition:all .15s;flex-shrink:0;"
                        onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                        <i class="fas fa-bell" style="font-size:15px;"></i>
                        <span id="notif-badge" style="display:none;position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:#ef4444;color:#fff;border-radius:8px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"></span>
                    </button>
                    <div id="notif-panel" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border-radius:14px;box-shadow:0 8px 28px rgba(0,0,0,.13);border:1px solid #f1f5f9;z-index:200;overflow:hidden;">
                        <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;color:#1e293b;display:flex;justify-content:space-between;align-items:center;">
                            <span><i class="fas fa-bell" style="color:#7c3aed;margin-right:6px;"></i>Notifications</span>
                            <button onclick="markAllRead()" style="font-size:11px;color:#7c3aed;border:none;background:none;cursor:pointer;font-weight:700;">Mark all read</button>
                        </div>
                        <div id="notif-list" style="max-height:320px;overflow-y:auto;">
                            <div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px;">Loading...</div>
                        </div>
                    </div>
                </div>
                <!-- WhatsApp Support — header -->
                <a href="#" id="header-wa-btn" title="Get Support on WhatsApp"
                   style="display:flex;align-items:center;gap:7px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;padding:7px 14px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(37,211,102,.3);transition:all .18s;flex-shrink:0;"
                   onmouseover="this.style.boxShadow='0 5px 16px rgba(37,211,102,.45)';this.style.transform='translateY(-1px)'"
                   onmouseout="this.style.boxShadow='0 3px 10px rgba(37,211,102,.3)';this.style.transform=''">
                    <i class="fab fa-whatsapp" style="font-size:15px;"></i>
                    <span class="topbar-wa-label">Support</span>
                </a>
                <div style="position:relative;">
                    <button onclick="toggleUserMenu()" style="width:36px;height:36px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;box-shadow:0 4px 12px rgba(6,182,212,.3);cursor:pointer;border:none;flex-shrink:0;">
                        {{ session('crm_user_avatar','A') }}
                    </button>
                    <div id="user-menu" style="display:none;position:absolute;top:100%;right:0;margin-top:6px;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);border:1px solid #f1f5f9;min-width:200px;z-index:100;overflow:hidden;">
                        <div style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                            <div style="padding:0 14px;font-size:13px;font-weight:700;color:#0f172a;">{{ session('crm_user_name','Admin') }}</div>
                            <div style="padding:2px 14px;font-size:11px;color:#94a3b8;">{{ session('crm_user_role','Admin') }}</div>
                        </div>
                        <a href="{{ route('password.change.form') }}" style="display:block;padding:10px 14px;font-size:13px;color:#475569;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                            <i class="fas fa-lock" style="color:#64748b;font-size:12px;margin-right:8px;"></i>Change Password
                        </a>
                        <button onclick="crmTourStart();document.getElementById('user-menu').style.display='none';" style="display:block;width:100%;padding:10px 14px;font-size:13px;color:#0891b2;text-align:left;text-decoration:none;border:none;background:none;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background=''">
                            <i class="fas fa-graduation-cap" style="color:#06b6d4;font-size:12px;margin-right:8px;"></i>फिर से टूर शुरू करें
                        </button>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" style="display:block;width:100%;padding:10px 14px;font-size:13px;color:#dc2626;text-align:left;text-decoration:none;border:none;background:none;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                                <i class="fas fa-power-off" style="color:#dc2626;font-size:12px;margin-right:8px;"></i>Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Trial Warning Banner -->
        @if(session('trial_warning'))
        @php
            $twDays    = session('trial_days_left', 0);
            $twLevel   = session('trial_warning'); // 'urgent' (0-1 day) | 'soon' (2-7 days)
            $twUrgent  = $twLevel === 'urgent';
            $twBg      = $twUrgent ? '#fef2f2' : '#fffbeb';
            $twBorder  = $twUrgent ? '#fecaca' : '#fde68a';
            $twColor   = $twUrgent ? '#b91c1c' : '#92400e';
            $twIcon    = $twUrgent ? 'fa-exclamation-triangle' : 'fa-clock';
            $twIconClr = $twUrgent ? '#ef4444' : '#d97706';
            // Hindi + English warning text
            if ($twDays <= 0) {
                $twHindi = 'आपका ट्रायल/प्लान आज समाप्त हो रहा है!';
                $twEng   = 'Your subscription expires today! Upgrade now to avoid losing access.';
            } else {
                $twHindi = "आपका ट्रायल/प्लान {$twDays} दिन में समाप्त हो रहा है!";
                $twEng   = "Your subscription expires in {$twDays} day" . ($twDays === 1 ? '' : 's') . '. Upgrade to keep your data safe.';
            }
        @endphp
        <div style="padding:14px 24px 0;">
            <div style="background:{{ $twBg }};border:1px solid {{ $twBorder }};border-radius:12px;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fas {{ $twIcon }}" style="color:{{ $twIconClr }};font-size:16px;flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:{{ $twColor }};">{{ $twHindi }}</div>
                        <div style="font-size:12px;color:{{ $twColor }};opacity:.8;margin-top:2px;">{{ $twEng }}</div>
                    </div>
                </div>
                <a href="{{ route('upgrade') }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;background:{{ $twUrgent ? '#ef4444' : '#d97706' }};color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                    <i class="fas fa-arrow-up"></i> अभी अपग्रेड करें
                </a>
            </div>
        </div>
        @endif

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
        <footer style="background:#fff;border-top:1px solid #f1f5f9;padding:12px 24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <p style="font-size:12px;color:#94a3b8;margin:0;">
                    © {{ date('Y') }} {{ $settings->resort_name ?? 'Resort CRM' }}. All rights reserved.
                    <span style="margin:0 6px;">•</span>
                    Made with <span style="color:#ef4444;">♥</span> by
                    <a href="https://www.dreams-technology.com" target="_blank" style="color:#06b6d4;font-weight:600;text-decoration:none;">Dreams Technology</a>
                </p>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:12px;color:#94a3b8;">Need help?</span>
                    <a href="tel:+919725225519" style="font-size:12px;color:#06b6d4;font-weight:600;text-decoration:none;">+91 97252 25519</a>
                    <a href="#" id="footer-wa-btn"
                       style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:999px;text-decoration:none;box-shadow:0 3px 10px rgba(37,211,102,.35);transition:all .2s;"
                       onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 16px rgba(37,211,102,.45)'"
                       onmouseout="this.style.transform='';this.style.boxShadow='0 3px 10px rgba(37,211,102,.35)'"
                       target="_blank">
                        <i class="fab fa-whatsapp" style="font-size:14px;"></i> WhatsApp Support
                    </a>
                </div>
            </div>
        </footer>

        <script>
        (function() {
            var hotel = @json($settings->resort_name ?? session('crm_hotel_name', 'Hotel CRM'));
            var page  = document.title.split('—')[0].trim();
            var msg   = 'Hello! I need support with the *' + page + '* page.\nHotel: *' + hotel + '*\n\nPlease help me!';
            var url   = 'https://wa.me/919725225519?text=' + encodeURIComponent(msg);
            var fb = document.getElementById('footer-wa-btn');
            if (fb) { fb.href = url; }
            var hb = document.getElementById('header-wa-btn');
            if (hb) { hb.href = url; hb.target = '_blank'; }
        })();
        </script>

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

    // User menu dropdown
    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('user-menu');
        const button = event.target.closest('button[onclick="toggleUserMenu()"]');
        if (!button && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
    });

    // Auto-dismiss flash messages
    setTimeout(() => {
        document.querySelectorAll('[data-flash]').forEach(el => el.remove());
    }, 5000);
</script>

@stack('scripts')

{{-- ════════════════════════════════════════════════════
     ONBOARDING TOUR — Hindi guided walkthrough
     State stored in localStorage per user (no DB)
════════════════════════════════════════════════════ --}}
<script>
(function () {
    var CRM_TOUR_KEY      = 'crm_tour_done_{{ auth()->id() ?? 0 }}';
    var CRM_TOUR_STEP_KEY = 'crm_tour_step_{{ auth()->id() ?? 0 }}';

    var STEPS = [
        {
            sel: '#sidebar a[href*="dashboard"]',
            url: '{{ route("dashboard") }}',
            icon: 'fa-th-large',
            title: 'डैशबोर्ड (Dashboard)',
            desc: 'यहाँ होटल की पूरी जानकारी एक नज़र में देखें — आज की बुकिंग, कमाई, चेक-इन / चेक-आउट और हाल की गतिविधियाँ।'
        },
        {
            sel: '#sidebar a[href*="customers"]',
            url: '{{ route("customers.index") }}',
            icon: 'fa-users',
            title: 'मेहमान (Guests)',
            desc: 'होटल में आने वाले सभी मेहमानों की प्रोफ़ाइल यहाँ रखें — नाम, मोबाइल, पता, ID प्रूफ और दस्तावेज़।'
        },
        {
            sel: '#sidebar a[href*="rooms"]',
            url: '{{ route("rooms.index") }}',
            icon: 'fa-door-open',
            title: 'कमरे (Rooms)',
            desc: 'होटल के सभी कमरों का प्रबंधन करें — कमरा नंबर, प्रकार (AC/Non-AC), किराया और उपलब्धता।'
        },
        {
            sel: '#sidebar a[href*="bookings"]',
            url: '{{ route("bookings.index") }}',
            icon: 'fa-calendar-check',
            title: 'बुकिंग (Bookings)',
            desc: 'नई बुकिंग बनाएँ और सभी बुकिंग की सूची देखें। चेक-इन/आउट तारीख, कमरा और मेहमान यहीं से तय होते हैं।'
        },
        {
            sel: '#sidebar a[href*="checkin"]',
            url: '{{ route("checkin.index") }}',
            icon: 'fa-sign-in-alt',
            title: 'चेक-इन (Check-In)',
            desc: 'मेहमान के आने पर यहाँ से चेक-इन करें — ID वेरिफाई करें, कमरा आवंटित करें और स्वागत करें।'
        },
        {
            sel: '#sidebar a[href*="checkout"]',
            url: '{{ route("checkout.index") }}',
            icon: 'fa-sign-out-alt',
            title: 'चेक-आउट (Check-Out)',
            desc: 'मेहमान के जाने पर यहाँ से चेक-आउट करें — अंतिम बिल बनाएँ, भुगतान लें और कमरा खाली करें।'
        },
        {
            sel: '#sidebar a[href*="payments"]',
            url: '{{ route("payments.index") }}',
            icon: 'fa-credit-card',
            title: 'भुगतान (Payments)',
            desc: 'कैश, कार्ड, UPI — सभी प्रकार के भुगतान यहाँ दर्ज करें। बकाया और प्राप्त राशि का पूरा हिसाब।'
        },
        {
            sel: '#sidebar a[href*="invoices"]',
            url: '{{ route("invoices.index") }}',
            icon: 'fa-file-invoice-dollar',
            title: 'इनवॉइस (Invoices)',
            desc: 'मेहमानों के बिल यहाँ देखें और प्रिंट करें। हर बुकिंग के लिए GST इनवॉइस स्वचालित बनता है।'
        },
        {
            sel: '#sidebar a[href*="reports"]',
            url: '{{ route("reports.index") }}',
            icon: 'fa-chart-bar',
            title: 'रिपोर्ट (Reports)',
            desc: 'कमाई, कमरों की भराई दर, और पुलिस / सरकारी मेहमान रजिस्टर जैसी विस्तृत रिपोर्टें यहाँ देखें।'
        },
        {
            sel: '#sidebar a[href*="settings"]',
            url: '{{ route("settings.index") }}',
            icon: 'fa-cog',
            title: 'सेटिंग्स (Settings)',
            desc: 'होटल का नाम, लोगो, GST नंबर, ईमेल और अन्य जानकारी यहाँ से अपडेट करें।'
        },
        {
            sel: '#header-wa-btn',
            url: null,
            icon: 'fa-whatsapp',
            iconLib: 'fab',
            title: 'WhatsApp सपोर्ट',
            desc: 'कोई भी समस्या हो? हेडर में यह हरा बटन दबाएँ — सीधे हमारी सपोर्ट टीम से WhatsApp पर बात करें।'
        }
    ];

    var ALL_STEPS = STEPS;
    var currentStep = 0;
    var hlEl = null, cardEl = null;

    function buildUI() {
        if (document.getElementById('crm-tour-highlight')) return;

        hlEl = document.createElement('div');
        hlEl.id = 'crm-tour-highlight';
        document.body.appendChild(hlEl);

        cardEl = document.createElement('div');
        cardEl.id = 'crm-tour-card';
        cardEl.innerHTML =
            '<div class="tour-step-badge"><i class="fas fa-map-signs" style="font-size:11px;"></i> <span id="tBadge"></span></div>' +
            '<div class="tour-title" id="tTitle"></div>' +
            '<div class="tour-desc" id="tDesc"></div>' +
            '<div class="tour-actions">' +
              '<div class="tour-dots" id="tDots"></div>' +
              '<div style="display:flex;gap:8px;align-items:center;">' +
                '<button class="tour-btn-skip" id="tSkip">छोड़ें</button>' +
                '<button class="tour-btn-next" id="tNext"></button>' +
              '</div>' +
            '</div>';
        document.body.appendChild(cardEl);

        document.getElementById('tSkip').addEventListener('click', crmTourEnd);
        document.getElementById('tNext').addEventListener('click', function () {
            if (currentStep < ALL_STEPS.length - 1) {
                var nextIdx = currentStep + 1;
                var nextStep = ALL_STEPS[nextIdx];
                if (nextStep.url) {
                    localStorage.removeItem(CRM_TOUR_KEY);
                    localStorage.setItem(CRM_TOUR_STEP_KEY, nextIdx);
                    window.location.href = nextStep.url;
                } else {
                    currentStep = nextIdx;
                    renderStep();
                }
            } else {
                crmTourEnd();
            }
        });
    }

    function resolveTarget(step) {
        var candidates = document.querySelectorAll(step.sel);
        for (var i = 0; i < candidates.length; i++) {
            var rect = candidates[i].getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) return candidates[i];
        }
        return null;
    }

    function renderStep() {
        var step = ALL_STEPS[currentStep];
        var target = resolveTarget(step);

        document.getElementById('tBadge').textContent = 'चरण ' + (currentStep + 1) + ' / ' + ALL_STEPS.length;
        document.getElementById('tTitle').innerHTML =
            '<i class="' + (step.iconLib || 'fas') + ' ' + step.icon + '" style="color:#06b6d4;margin-right:8px;"></i>' + step.title;
        document.getElementById('tDesc').textContent = step.desc;
        document.getElementById('tNext').innerHTML =
            currentStep === ALL_STEPS.length - 1
                ? 'समाप्त <i class="fas fa-check" style="font-size:12px;"></i>'
                : 'आगे <i class="fas fa-arrow-right" style="font-size:12px;"></i>';

        var dots = document.getElementById('tDots');
        dots.innerHTML = '';
        for (var d = 0; d < ALL_STEPS.length; d++) {
            var dot = document.createElement('span');
            dot.className = 'tour-dot' + (d === currentStep ? ' active' : '');
            dots.appendChild(dot);
        }

        if (!target) {
            hlEl.style.display = 'none';
            positionCard(window.innerWidth / 2 - 180, window.innerHeight / 2 - 130);
            return;
        }

        if (window.innerWidth <= 1024) {
            var sb = document.getElementById('sidebar');
            if (sb && !sb.classList.contains('open')) {
                sb.classList.add('open');
                var ov = document.getElementById('sidebar-overlay');
                if (ov) ov.classList.add('show');
            }
        }

        target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

        hlEl.style.display = 'block';
        setTimeout(function () {
            var r = target.getBoundingClientRect();
            var pad = 6;
            hlEl.style.top    = (r.top  - pad) + 'px';
            hlEl.style.left   = (r.left - pad) + 'px';
            hlEl.style.width  = (r.width  + pad * 2) + 'px';
            hlEl.style.height = (r.height + pad * 2) + 'px';

            var cardW = 360, cardH = 260;
            var vw = window.innerWidth, vh = window.innerHeight;
            var cx, cy;
            if (r.right + 20 + cardW <= vw) {
                cx = r.right + 20;
                cy = Math.min(r.top, vh - cardH - 16);
            } else if (r.left - 20 - cardW >= 0) {
                cx = r.left - 20 - cardW;
                cy = Math.min(r.top, vh - cardH - 16);
            } else {
                cx = Math.max(8, (vw - cardW) / 2);
                cy = r.bottom + 16;
                if (cy + cardH > vh) cy = r.top - cardH - 16;
            }
            cy = Math.max(8, Math.min(cy, vh - cardH - 8));
            positionCard(cx, cy);
        }, 150);
    }

    function positionCard(x, y) {
        cardEl.style.left = x + 'px';
        cardEl.style.top  = y + 'px';
    }

    window.crmTourStart = function (startAt) {
        localStorage.removeItem(CRM_TOUR_KEY);
        currentStep = (typeof startAt === 'number') ? startAt : 0;
        buildUI();
        hlEl.style.display = 'none';
        cardEl.style.display = 'block';
        renderStep();
    };

    function crmTourEnd() {
        localStorage.setItem(CRM_TOUR_KEY, '1');
        localStorage.removeItem(CRM_TOUR_STEP_KEY);
        if (hlEl) hlEl.style.display = 'none';
        if (cardEl) cardEl.style.display = 'none';
        if (window.innerWidth <= 1024) {
            var sb = document.getElementById('sidebar');
            if (sb) sb.classList.remove('open');
            var ov = document.getElementById('sidebar-overlay');
            if (ov) ov.classList.remove('show');
        }
    }
    window.crmTourEnd = crmTourEnd;

    document.addEventListener('DOMContentLoaded', function () {
        if (localStorage.getItem(CRM_TOUR_KEY)) return;
        var savedStep = localStorage.getItem(CRM_TOUR_STEP_KEY);
        if (savedStep !== null) {
            localStorage.removeItem(CRM_TOUR_STEP_KEY);
            setTimeout(function () { window.crmTourStart(parseInt(savedStep, 10)); }, 600);
        } else {
            setTimeout(function () { window.crmTourStart(0); }, 800);
        }
    });
})();
</script>

@livewireScripts

{{-- ── Firebase Push Notifications ────────────────────────────────────── --}}
<script>
(function () {
    // ── In-app bell polling (no Firebase needed) ──────────────────────────
    let notifPanelOpen = false;
    let notifItems     = [];

    function toggleNotifPanel() {
        notifPanelOpen = !notifPanelOpen;
        const panel = document.getElementById('notif-panel');
        if (panel) panel.style.display = notifPanelOpen ? 'block' : 'none';
        if (notifPanelOpen) loadNotifications();
        document.addEventListener('click', onDocClick, { once: true });
    }

    function onDocClick(e) {
        const wrap = document.getElementById('notif-bell-wrap');
        if (wrap && !wrap.contains(e.target)) {
            notifPanelOpen = false;
            const panel = document.getElementById('notif-panel');
            if (panel) panel.style.display = 'none';
        }
    }

    async function loadNotifications() {
        try {
            const res  = await fetch('{{ route('fcm.notifications.unread') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            notifItems = await res.json();
            renderNotifications();
            updateBadge();
        } catch (e) {}
    }

    function renderNotifications() {
        const list = document.getElementById('notif-list');
        if (!list) return;

        if (!notifItems.length) {
            list.innerHTML = '<div style="padding:28px;text-align:center;color:#94a3b8;font-size:13px;"><i class="fas fa-bell-slash" style="font-size:20px;margin-bottom:8px;display:block;"></i>No new notifications</div>';
            return;
        }

        list.innerHTML = notifItems.map(n => `
            <div onclick="openNotif(${n.delivery_id},'${n.action_url || ''}',${n.id})"
                style="padding:12px 16px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;">${escHtml(n.title)}</div>
                <div style="font-size:12px;color:#64748b;line-height:1.4;">${escHtml(n.body)}</div>
                <div style="font-size:10px;color:#94a3b8;margin-top:4px;">${timeAgo(n.sent_at)}</div>
            </div>
        `).join('');
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function timeAgo(ts) {
        if (!ts) return '';
        const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function updateBadge() {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (notifItems.length > 0) {
            badge.textContent = notifItems.length > 9 ? '9+' : notifItems.length;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    async function openNotif(deliveryId, url, notifId) {
        try {
            await fetch(`{{ url('/notifications') }}/${deliveryId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            notifItems = notifItems.filter(n => n.delivery_id !== deliveryId);
            updateBadge();
            renderNotifications();
        } catch (e) {}

        if (url && url !== 'null' && url !== 'undefined') {
            window.open(url, '_blank');
        }
    }

    async function markAllRead() {
        for (const n of notifItems) {
            try {
                await fetch(`{{ url('/notifications') }}/${n.delivery_id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
            } catch(e) {}
        }
        notifItems = [];
        updateBadge();
        renderNotifications();
    }

    // Poll every 60 seconds
    loadNotifications();
    setInterval(loadNotifications, 60000);

    // Expose to window for inline onclick
    window.toggleNotifPanel = toggleNotifPanel;
    window.markAllRead      = markAllRead;
    window.openNotif        = openNotif;

    // ── Firebase Push Subscription (browser only, graceful fallback) ──────
    async function initFirebase() {
        try {
            const cfgRes = await fetch('{{ route('fcm.config') }}', { headers: { 'Accept': 'application/json' } });
            const cfg    = await cfgRes.json();
            if (!cfg.enabled) return;

            // Dynamically load Firebase SDK
            const [{ initializeApp }, { getMessaging, getToken, onMessage }] = await Promise.all([
                import('https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js'),
                import('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging.js'),
            ]);

            if (!cfg.vapidKey) {
                console.error('[CRM Push] VAPID key is missing — save it in Platform Admin → Push Settings.');
                return;
            }

            // Step 1: Request browser permission first
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.warn('[CRM Push] Notification permission denied by user.');
                showPushEnableBtn('denied');
                return;
            }
            hidePushEnableBtn();

            // Step 2: Register service worker BEFORE calling getToken()
            if (!('serviceWorker' in navigator)) {
                console.error('[CRM Push] Service workers not supported in this browser.');
                return;
            }
            const swReg = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            await navigator.serviceWorker.ready;
            console.info('[CRM Push] Service worker registered.');

            // Step 3: Init Firebase with the SW registration
            const fbConfig = { apiKey: cfg.apiKey, projectId: cfg.projectId, messagingSenderId: cfg.messagingSenderId, appId: cfg.appId };
            const fbApp    = initializeApp(fbConfig);
            const messaging = getMessaging(fbApp);

            // Send Firebase config to the SW so it can handle background messages
            swReg.active?.postMessage({ type: 'FIREBASE_CONFIG', config: fbConfig });

            // Step 4: Get FCM token (with SW registration passed explicitly)
            const token = await getToken(messaging, {
                vapidKey: cfg.vapidKey,
                serviceWorkerRegistration: swReg,
            });

            if (!token) {
                console.error('[CRM Push] getToken() returned null. Check VAPID key and Firebase project configuration.');
                return;
            }
            console.info('[CRM Push] FCM token obtained:', token.slice(0, 30) + '...');

            // Step 5: Save token to server
            const saveRes = await fetch('{{ route('fcm.token.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token, platform: 'web', device_id: navigator.userAgent.slice(0, 200) })
            });
            const saveJson = await saveRes.json().catch(() => ({}));
            if (saveJson.ok) {
                console.info('[CRM Push] Device registered successfully for push notifications.');
                hidePushEnableBtn();
            } else {
                console.warn('[CRM Push] Token save response:', saveJson);
            }

            // Foreground message handler
            onMessage(messaging, (payload) => {
                const n = payload.notification || {};
                const d = payload.data || {};

                // Call native JS bridge if Flutter WebView
                if (window.onCrmNotification) {
                    window.onCrmNotification(JSON.stringify(payload));
                }

                // Play notification sound
                playNotificationSound();

                // Show in-app toast
                showPushToast(n.title || d.title || 'Notification', n.body || d.body || '', d.click_url || '/');

                // Reload notification list
                loadNotifications();
            });

            // Background push → service worker tells us to play sound
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', event => {
                    if (event.data && event.data.type === 'PLAY_NOTIFICATION_SOUND') {
                        playNotificationSound();
                    }
                });
            }

        } catch (e) {
            console.warn('[CRM Push] Firebase init skipped:', e.message);
            showPushEnableBtn('error');
        }
    }

    // ── Notification sound (Web Audio API — no file needed) ──────────────
    function playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            // Two-note "ding": C5 → E5
            const notes = [523.25, 659.25];
            let startTime = ctx.currentTime;
            notes.forEach(freq => {
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, startTime);
                gain.gain.setValueAtTime(0, startTime);
                gain.gain.linearRampToValueAtTime(0.35, startTime + 0.01);  // quick attack
                gain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.45); // natural decay
                osc.start(startTime);
                osc.stop(startTime + 0.45);
                startTime += 0.2; // gap between notes
            });
            setTimeout(() => ctx.close(), 1200);
        } catch (e) {
            // Audio not supported or blocked — silently skip
        }
    }

    // ── Flutter / native WebView detection ───────────────────────────────
    // In Flutter WebView browser push doesn't work; Flutter handles FCM natively.
    // Hide the permission UI but still allow FCM token registration to proceed.
    function isNativeWebView() {
        if (window.flutter_inappwebview) return true;               // flutter_inappwebview package
        if (window.ReactNativeWebView)   return true;               // React Native WebView
        const ua = navigator.userAgent || '';
        if (/Android/.test(ua) && /wv\)/.test(ua))  return true;   // Android WebView "wv" flag
        if (/Android/.test(ua) && !/Chrome\//.test(ua)) return true; // Android without Chrome = WebView
        return false;
    }

    // ── Push permission UI helpers ────────────────────────────────────────
    function showPushEnableBtn(state) {
        if (isNativeWebView()) return; // Flutter manages push natively — hide browser UI
        const wrap  = document.getElementById('push-enable-wrap');
        const icon  = document.getElementById('push-enable-icon');
        const label = document.getElementById('push-enable-label');
        const tip   = document.getElementById('push-denied-tip');
        if (!wrap) return;
        wrap.style.display = 'block';

        if (state === 'denied') {
            icon.className  = 'fas fa-bell-slash';
            label.textContent = 'Notifications Blocked';
            wrap.querySelector('button').style.borderColor = '#fca5a5';
            wrap.querySelector('button').style.color       = '#ef4444';
            wrap.querySelector('button').style.background  = '#fef2f2';
            wrap.querySelector('button').onclick = () => {
                tip.style.display = tip.style.display === 'none' ? 'block' : 'none';
            };
        } else {
            icon.className  = 'fas fa-bell-slash';
            label.textContent = 'Enable Notifications';
        }
    }

    function hidePushEnableBtn() {
        const wrap = document.getElementById('push-enable-wrap');
        if (wrap) wrap.style.display = 'none';
    }

    // Expose manual trigger (called by button or external code)
    window.requestPushPermission = async function () {
        const tip = document.getElementById('push-denied-tip');
        if (tip) tip.style.display = 'none';
        await initFirebase();
    };

    function showPushToast(title, body, url) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;border-radius:14px;padding:14px 18px;max-width:320px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.25);cursor:pointer;animation:slideInUp .3s ease;';
        toast.innerHTML = `<div style="font-size:13px;font-weight:700;margin-bottom:4px;">${escHtml(title)}</div><div style="font-size:12px;opacity:.8;line-height:1.4;">${escHtml(body)}</div>`;
        toast.onclick = () => { window.open(url,'_blank'); toast.remove(); };
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity .4s'; setTimeout(() => toast.remove(), 400); }, 5000);
    }

    // Show permission button state immediately on load (before initFirebase runs)
    function checkPermissionState() {
        if (isNativeWebView()) return;           // Flutter handles push — no browser UI needed
        if (!('Notification' in window)) return; // Browser doesn't support push
        if (Notification.permission === 'denied')  showPushEnableBtn('denied');
        if (Notification.permission === 'default') showPushEnableBtn('default');
        // if granted: button stays hidden, initFirebase will register/refresh token
    }

    // Init Firebase after page is ready
    if (document.readyState === 'complete') { checkPermissionState(); initFirebase(); }
    else { window.addEventListener('load', () => { checkPermissionState(); initFirebase(); }); }
})();
</script>
<style>
@keyframes slideInUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
</style>
</body>
</html>
