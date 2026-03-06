<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Resort CRM') — {{ $settings->resort_name ?? 'Resort CRM' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    </style>
    @stack('styles')
</head>
<body>

<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div style="display:flex;">

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

        <!-- User -->
        <div style="padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06);">
            <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">{{ session('crm_user_avatar','A') }}</div>
                <div style="min-width:0;">
                    <div style="color:#e2e8f0;font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ session('crm_user_name','Admin') }}</div>
                    <div style="color:#475569;font-size:11px;">{{ session('crm_user_role','Admin') }}</div>
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

            <div class="nav-section">Guest Management</div>

            <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-users"></i></span>
                Guests
            </a>

            <a href="{{ route('customers.index') }}" onclick="event.preventDefault(); window.location='{{ route('customers.index') }}'" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-file-alt"></i></span>
                Documents
            </a>

            <div class="nav-section">Operations</div>

            <a href="{{ route('rooms.index') }}" class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-door-open"></i></span>
                Rooms
            </a>

            <a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-calendar-check"></i></span>
                Bookings
            </a>

            <a href="{{ route('checkin.index') }}" class="nav-link {{ request()->routeIs('checkin.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-sign-in-alt"></i></span>
                Check-In
            </a>

            <a href="{{ route('checkout.index') }}" class="nav-link {{ request()->routeIs('checkout.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                Check-Out
            </a>

            <div class="nav-section">Finance</div>

            <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-credit-card"></i></span>
                Payments
            </a>

            <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                Invoices
            </a>

            <div class="nav-section">Analytics</div>

            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                Reports
            </a>

            <div class="nav-section">System</div>

            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-cog"></i></span>
                Settings
            </a>

        </nav>

        <!-- Logout -->
        <div style="padding:10px 10px 16px;border-top:1px solid rgba(255,255,255,.06);margin-top:10px;">
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
            <div style="display:flex;align-items:center;gap:16px;">
                <!-- Mobile hamburger -->
                <button onclick="openSidebar()" style="display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:#64748b;" id="hamburger">
                    <i class="fas fa-bars" style="font-size:18px;"></i>
                </button>
                <div>
                    <h1 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;line-height:1.2;">@yield('page-title','Dashboard')</h1>
                    <p style="font-size:12px;color:#94a3b8;margin:0;margin-top:1px;">@yield('page-subtitle','Azure Paradise Resort CRM')</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:7px 14px;">
                    <i class="fas fa-calendar-day" style="color:#06b6d4;font-size:13px;"></i>
                    <span style="font-size:13px;color:#475569;font-weight:500;">{{ now()->format('D, d M Y') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:7px 14px;">
                    <i class="fas fa-clock" style="color:#06b6d4;font-size:13px;"></i>
                    <span style="font-size:13px;color:#475569;font-weight:500;" id="liveClock"></span>
                </div>
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;box-shadow:0 4px 12px rgba(6,182,212,.3);cursor:pointer;">
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
</body>
</html>
