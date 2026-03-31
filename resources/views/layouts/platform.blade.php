<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform Admin') — SaaS Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
        }

        /* ── Sidebar ── */
        #platform-sidebar {
            width: 260px;
            min-width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1e1b4b 0%, #2d1b69 50%, #1e1b4b 100%);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 50;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }

        #platform-sidebar::-webkit-scrollbar { width: 4px; }
        #platform-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

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
            color: #c4b5fd;
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
            background: linear-gradient(90deg, rgba(139,92,246,.35), rgba(124,58,237,.25));
            border: 1px solid rgba(139,92,246,.4);
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
            background: linear-gradient(135deg,#8b5cf6,#7c3aed);
            color: #fff;
            box-shadow: 0 4px 12px rgba(124,58,237,.5);
        }
        .nav-link:not(.active) .icon {
            background: rgba(255,255,255,.06);
            color: #7c3aed;
        }
        .nav-link:hover .icon {
            background: rgba(255,255,255,.12);
            color: #c4b5fd;
        }

        /* ── Section label ── */
        .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(196,181,253,.4);
            padding: 14px 14px 4px;
        }

        /* ── Top bar ── */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* ── Page content ── */
        .page-content {
            padding: 28px;
            flex: 1;
        }

        /* ── Badges ── */
        .badge-purple { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#ede9fe;color:#6d28d9; }
        .badge-cyan   { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#cffafe;color:#0e7490; }
        .badge-green  { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#15803d; }
        .badge-red    { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#fee2e2;color:#b91c1c; }
        .badge-gray   { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#f1f5f9;color:#475569; }
        .badge-orange { display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#ffedd5;color:#c2410c; }

        /* ── Form elements ── */
        .form-label { display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;letter-spacing:.025em;text-transform:uppercase; }
        .form-input { width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 13px;font-size:14px;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s;background:#fff; }
        .form-input:focus { border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.15); }
        select.form-input { cursor:pointer; }
        textarea.form-input { resize:vertical;min-height:90px; }

        /* ── Buttons ── */
        .btn-primary { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
        .btn-primary:hover { opacity:.88; }
        .btn-secondary { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s; }
        .btn-secondary:hover { background:#e2e8f0; }
        .btn-danger { display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fecaca;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .15s; }
        .btn-danger:hover { background:#fecaca; }
        .btn-success { display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#dcfce7;color:#15803d;border:1.5px solid #bbf7d0;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .15s; }
        .btn-success:hover { background:#bbf7d0; }

        /* ── Alert ── */
        .alert-success { background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600; }
        .alert-error   { background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600; }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
<aside id="platform-sidebar">

    <!-- Branding -->
    <div style="padding:18px 16px 12px;border-bottom:1px solid rgba(255,255,255,.08);">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#8b5cf6,#4c1d95);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-layer-group" style="color:#fff;font-size:16px;"></i>
            </div>
            <div>
                <div style="color:#fff;font-size:13px;font-weight:800;line-height:1.2;">Platform Admin</div>
                <div style="color:#a78bfa;font-size:10px;font-weight:600;letter-spacing:.05em;">SaaS Management Console</div>
            </div>
        </div>
    </div>

    <!-- Back to CRM link -->
    <div style="padding:8px 16px 4px;">
        <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:7px 10px;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='rgba(255,255,255,.06)'">
            <i class="fas fa-arrow-left" style="color:#a78bfa;font-size:10px;flex-shrink:0;"></i>
            <span style="color:#c4b5fd;font-size:11px;font-weight:600;">Back to Hotel CRM</span>
        </a>
    </div>

    <!-- SA User card -->
    <div style="padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.06);">
        <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#8b5cf6,#4c1d95);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">S</div>
            <div style="min-width:0;">
                <div style="color:#e2e8f0;font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ session('crm_user_name','Super Admin') }}</div>
                <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                    <span style="display:inline-block;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;font-size:9px;font-weight:700;padding:1px 8px;border-radius:999px;letter-spacing:.05em;text-transform:uppercase;">Super Admin</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav style="flex:1;padding:10px 10px 0;">

        <div class="nav-section">Platform</div>

        <a href="{{ route('platform.dashboard') }}" class="nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-chart-pie"></i></span>
            Dashboard
        </a>

        <div class="nav-section">Tenant Management</div>

        <a href="{{ route('platform.hotels.index') }}" class="nav-link {{ request()->routeIs('platform.hotels.*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-building"></i></span>
            Hotels
        </a>

        <a href="{{ route('platform.users.index') }}" class="nav-link {{ request()->routeIs('platform.users.*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-users"></i></span>
            Users
        </a>

        <div class="nav-section">System</div>

        <a href="{{ route('activity_log.index') }}" class="nav-link">
            <span class="icon"><i class="fas fa-history"></i></span>
            Activity Log
        </a>

    </nav>

    <!-- Footer -->
    <div style="padding:12px 16px;border-top:1px solid rgba(255,255,255,.06);">
        <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('platform-logout-form').submit();" style="display:flex;align-items:center;gap:8px;color:#6b7280;font-size:12px;text-decoration:none;padding:8px 10px;border-radius:8px;transition:all .15s;" onmouseover="this.style.background='rgba(255,255,255,.05)';this.style.color='#c4b5fd'" onmouseout="this.style.background='transparent';this.style.color='#6b7280'">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
        <form id="platform-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
    </div>

</aside>

<!-- ── Main content ────────────────────────────────────────────────────── -->
<div id="main-wrap">

    <!-- Top bar -->
    <header id="topbar">
        <div>
            <h1 style="font-size:17px;font-weight:800;color:#1e293b;margin:0;">@yield('page-title', 'Platform Admin')</h1>
            <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;">@yield('page-subtitle', '')</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:11px;color:#94a3b8;background:#f1f5f9;padding:4px 12px;border-radius:20px;border:1px solid #e2e8f0;">
                <i class="fas fa-shield-halved" style="color:#8b5cf6;margin-right:4px;"></i>
                Platform Admin Console
            </span>
        </div>
    </header>

    <!-- Flash messages -->
    <div style="padding:16px 28px 0;">
        @if(session('success'))
        <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Page content -->
    <main class="page-content">
        @yield('content')
    </main>

</div>

@stack('scripts')
</body>
</html>
