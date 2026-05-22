                @extends('layouts.admin')
                @section('title', 'Dashboard')
                @section('page-title', 'Dashboard')
                @section('page-subtitle', 'Welcome back, ' . session('crm_user_name') . '! Here\'s what\'s happening today.')

                @section('content')
                <style>
                .kpi-grid { display: grid !important; }

                /* ── Widget wrapper ── */
                .db-widget-wrap { transition: opacity .2s; }
                .db-widget-wrap.db-widget-hidden { display: none !important; }
                .db-widget-wrap.db-widget-dragging { opacity: .55; }

                /* ── Customize panel ── */
                #dbCustomizePanel {
                    background: #fff;
                    border-radius: 20px;
                    box-shadow: 0 8px 40px rgba(0,0,0,.1);
                    border: 1px solid #e2e8f0;
                    overflow: hidden;
                    margin-bottom: 8px;
                    display: none;
                }
                #dbCustomizePanel.open { display: block; }
                .db-cp-header {
                    padding: 16px 22px;
                    border-bottom: 1px solid #f1f5f9;
                    background: linear-gradient(135deg,#f8fafc,#f1f5f9);
                    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
                }
                .db-cp-title { font-weight: 800; color: #1e293b; font-size: 14px; display: flex; align-items: center; gap: 8px; }
                .db-cp-body { padding: 16px 22px; display: flex; flex-direction: column; gap: 6px; }
                .db-widget-item {
                    display: flex; align-items: center; gap: 12px;
                    padding: 10px 14px; border-radius: 12px;
                    background: #f8fafc; border: 1.5px solid #f1f5f9;
                    cursor: default; user-select: none;
                    transition: background .15s, border-color .15s, box-shadow .15s;
                }
                .db-widget-item.sortable-chosen { background: #f0f9ff; border-color: #7dd3fc; box-shadow: 0 4px 16px rgba(6,182,212,.12); }
                .db-widget-item.sortable-ghost { opacity: .4; }
                .db-drag-handle {
                    color: #94a3b8; cursor: grab; font-size: 15px; flex-shrink: 0;
                    padding: 2px 4px; border-radius: 6px;
                    transition: color .15s;
                }
                .db-drag-handle:hover { color: #475569; }
                .db-widget-item-icon {
                    width: 32px; height: 32px; border-radius: 10px;
                    display: flex; align-items: center; justify-content: center;
                    flex-shrink: 0; font-size: 13px; color: #fff;
                }
                .db-widget-item-label { flex: 1; font-size: 13px; font-weight: 700; color: #1e293b; }
                .db-widget-item-sub { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 1px; }
                .db-toggle {
                    position: relative; width: 36px; height: 20px;
                    background: #e2e8f0; border-radius: 999px;
                    cursor: pointer; border: none; outline: none;
                    transition: background .2s; flex-shrink: 0;
                }
                .db-toggle.on { background: #10b981; }
                .db-toggle::after {
                    content: ''; position: absolute; top: 2px; left: 2px;
                    width: 16px; height: 16px; border-radius: 50%;
                    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.2);
                    transition: transform .2s;
                }
                .db-toggle.on::after { transform: translateX(16px); }
                .db-cp-save-badge {
                    display: none; align-items: center; gap: 5px;
                    font-size: 11px; color: #10b981; font-weight: 700;
                    background: #f0fdf4; border-radius: 8px; padding: 4px 10px;
                    border: 1px solid #bbf7d0;
                }
                .db-cp-save-badge.show { display: flex; }

                .kpi-card {
                    border-radius: 20px;
                    padding: 24px;
                    position: relative;
                    overflow: hidden;
                    box-shadow: 0 10px 30px -10px rgba(0,0,0,.25), 0 2px 6px rgba(0,0,0,.06), inset 0 1px 0 rgba(255,255,255,.18);
                    transition: transform .35s cubic-bezier(.2,.9,.3,1.4), box-shadow .35s ease, filter .35s ease;
                    text-decoration: none;
                    display: block;
                    cursor: pointer;
                    isolation: isolate;
                    backdrop-filter: blur(10px);
                }
                /* Subtle aurora sweep across the card */
                .kpi-card::before {
                    content: ""; position: absolute; inset: 0;
                    background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,.18) 50%, transparent 70%);
                    background-size: 220% 100%;
                    background-position: 200% 0;
                    transition: background-position .9s ease;
                    pointer-events: none; z-index: 1;
                }
                /* Inner glass border */
                .kpi-card::after {
                    content: ""; position: absolute; inset: 1px;
                    border-radius: inherit;
                    border: 1px solid rgba(255,255,255,.18);
                    pointer-events: none; z-index: 1;
                }
                .kpi-card:hover {
                    transform: translateY(-6px) scale(1.025);
                    box-shadow: 0 22px 45px -12px rgba(0,0,0,.35), 0 6px 14px rgba(0,0,0,.12), inset 0 1px 0 rgba(255,255,255,.25);
                    filter: brightness(1.06) saturate(1.08);
                }
                .kpi-card:hover::before { background-position: -100% 0; }
                .kpi-card:hover .kpi-icon { transform: scale(1.18) rotate(-6deg); opacity: .85; filter: drop-shadow(0 0 8px rgba(255,255,255,.6)); }
                .kpi-card:hover .kpi-num  { letter-spacing: .5px; text-shadow: 0 4px 18px rgba(255,255,255,.35); }

                /* Animated floating orbs */
                .kpi-card .kpi-shine {
                    position: absolute; top: -45px; right: -45px;
                    width: 140px; height: 140px; border-radius: 50%;
                    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.55), rgba(255,255,255,.05) 60%, transparent 70%);
                    filter: blur(2px);
                    animation: kpi-orb-1 9s ease-in-out infinite;
                    z-index: 0;
                }
                .kpi-card .kpi-shine2 {
                    position: absolute; bottom: -35px; left: -25px;
                    width: 100px; height: 100px; border-radius: 50%;
                    background: radial-gradient(circle at 70% 70%, rgba(255,255,255,.35), rgba(255,255,255,.04) 65%, transparent 75%);
                    filter: blur(3px);
                    animation: kpi-orb-2 11s ease-in-out infinite;
                    z-index: 0;
                }

                .kpi-num   { font-size: 2.4rem; font-weight: 900; line-height: 1; color: #fff; position: relative; z-index: 2; transition: text-shadow .3s ease, letter-spacing .3s ease; text-shadow: 0 2px 10px rgba(0,0,0,.18); }
                .kpi-label { font-size: .78rem; font-weight: 700; color: rgba(255,255,255,.92); margin-top: 6px; position: relative; z-index: 2; text-transform: uppercase; letter-spacing: .04em; }
                .kpi-sub   { font-size: .72rem; color: rgba(255,255,255,.78); margin-top: 6px; position: relative; z-index: 2; font-weight: 600; }
                .kpi-icon  {
                    font-size: 1.8rem; opacity: .55; position: absolute; top: 16px; right: 18px; color: #fff;
                    z-index: 2;
                    transition: transform .35s cubic-bezier(.2,.9,.3,1.4), opacity .35s ease, filter .35s ease;
                    filter: drop-shadow(0 2px 6px rgba(0,0,0,.25));
                }
                /* Glowing icon halo */
                .kpi-card .kpi-icon::after {
                    content: ""; position: absolute; inset: -8px; border-radius: 50%;
                    background: radial-gradient(circle, rgba(255,255,255,.35), transparent 70%);
                    opacity: 0; transition: opacity .35s ease; z-index: -1;
                }
                .kpi-card:hover .kpi-icon::after { opacity: 1; }

                /* Compact 8-card row */
                .kpi-card-sm { border-radius: 16px !important; padding: 14px 14px 14px !important; }
                .kpi-card-sm .kpi-num  { font-size: 1.7rem !important; font-weight: 900 !important; }
                .kpi-card-sm .kpi-label{ font-size: .66rem !important; margin-top: 4px !important; letter-spacing: .06em !important; }
                .kpi-card-sm .kpi-sub  { font-size: .62rem !important; margin-top: 4px !important; opacity: .85; }
                .kpi-card-sm .kpi-icon { font-size: 1.35rem !important; top: 10px !important; right: 12px !important; }
                .kpi-card-sm .kpi-shine  { width: 90px !important; height: 90px !important; top: -28px !important; right: -28px !important; }
                .kpi-card-sm .kpi-shine2 { width: 70px !important; height: 70px !important; bottom: -22px !important; left: -16px !important; }
                /* Bottom accent bar that grows on hover */
                .kpi-card-sm::after {
                    border-bottom: 3px solid rgba(255,255,255,.35);
                    border-left: 0; border-right: 0; border-top: 0;
                    inset: auto 14px 6px 14px;
                    border-radius: 2px;
                    transform: scaleX(.25); transform-origin: left;
                    transition: transform .5s cubic-bezier(.2,.9,.3,1.4), border-color .3s ease;
                }
                .kpi-card-sm:hover::after { transform: scaleX(1); border-bottom-color: rgba(255,255,255,.7); }

                @keyframes kpi-orb-1  { 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(-10px,8px) scale(1.08);} }
                @keyframes kpi-orb-2  { 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(8px,-6px) scale(1.12);} }
                @keyframes pulse-dirty        { 0%,100%{box-shadow:0 0 0 0 rgba(249,115,22,.55),0 4px 18px rgba(249,115,22,.3);} 50%{box-shadow:0 0 0 10px rgba(249,115,22,0),0 4px 24px rgba(249,115,22,.5);} }
                @keyframes dirty-bg-flash     { 0%,100%{background:linear-gradient(135deg,#f97316,#ea580c);} 45%,55%{background:linear-gradient(135deg,#dc2626,#b91c1c);} }
                @keyframes dirty-icon-shake   { 0%,100%{transform:rotate(0deg);} 15%{transform:rotate(-14deg);} 35%{transform:rotate(12deg);} 55%{transform:rotate(-8deg);} 75%{transform:rotate(6deg);} }
                @keyframes dirty-badge-pop    { 0%,100%{transform:scale(1);} 50%{transform:scale(1.25);} }
                @keyframes pulse-live  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4);} 50%{box-shadow:0 0 0 6px rgba(16,185,129,0);} }
                @keyframes agendaSlideIn { from{opacity:0;transform:scale(.93) translateY(16px);} to{opacity:1;transform:scale(1) translateY(0);} }
                @media(max-width:900px){ .kpi-grid-8 { grid-template-columns: repeat(4,1fr) !important; } }
                @media(max-width:540px){ .kpi-grid-8 { grid-template-columns: repeat(2,1fr) !important; } }

                /* Occupancy circle animation */
                @keyframes dashDraw {
                    from { stroke-dasharray: 0, 100; }
                }
                .occ-ring { animation: dashDraw 1.2s ease-out forwards; }
                @keyframes pulseGlow {
                    0%,100% { filter: drop-shadow(0 0 4px rgba(6,182,212,.4)); }
                    50%      { filter: drop-shadow(0 0 12px rgba(6,182,212,.8)); }
                }
                .occ-svg { animation: pulseGlow 3s ease-in-out infinite; }

                /* Bar chart bar */
                .rev-bar { transition: height .6s cubic-bezier(.34,1.56,.64,1); }
                .rev-bar:hover { filter: brightness(1.12); }

                /* Calendar cell */
                .cal-cell {
                    border-radius: 14px; padding: 8px 6px;
                    min-height: 90px; display: flex; flex-direction: column;
                    transition: all .18s;
                    text-decoration: none;
                }
                .cal-cell:hover { z-index: 2; box-shadow: 0 6px 20px rgba(0,0,0,.1); }
                .cal-cell.today { background: linear-gradient(135deg,#ecfeff,#e0f2fe); border: 2px solid #22d3ee; }
                .cal-cell.in-month { background: #f8fafc; border: 1px solid #f1f5f9; }
                .cal-cell.in-month:hover { background: #f1f5f9; }
                .cal-cell.out-month { background: #fff; border: 1px solid #f8fafc; opacity: .4; }
                .cal-cell.whole-hotel { background: linear-gradient(135deg,#fff1f2,#ffe4e6) !important; border: 1px solid #fca5a5 !important; }
                .cal-cell.whole-hotel:hover { background: linear-gradient(135deg,#ffe4e6,#fecdd3) !important; }
                .cal-day-num { font-size: 1rem; font-weight: 800; line-height: 1; }
                /* Calendar tooltip */
                #calTooltip {
                    position: fixed; z-index: 9999; pointer-events: none;
                    background: #1e293b; color: #fff; border-radius: 12px;
                    padding: 10px 13px; min-width: 180px; max-width: 260px;
                    box-shadow: 0 8px 28px rgba(0,0,0,.28);
                    font-size: 12px; line-height: 1.5;
                    opacity: 0; transition: opacity .15s ease;
                }
                #calTooltip.visible { opacity: 1; }
                #calTooltip .tt-date { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
                #calTooltip .tt-section { font-size: 11px; font-weight: 700; margin: 6px 0 3px; padding-top: 5px; border-top: 1px solid rgba(255,255,255,.1); }
                #calTooltip .tt-section:first-of-type { border-top: none; margin-top: 2px; }
                #calTooltip .tt-row { display: flex; align-items: center; gap: 7px; padding: 2px 0; }
                #calTooltip .tt-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
                #calTooltip .tt-name { font-weight: 600; color: #f1f5f9; }
                #calTooltip .tt-room { color: #94a3b8; font-size: 11px; }

                /* Quick actions */
                .qa-btn { border-radius: 14px; padding: 12px 14px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all .18s; }
                .qa-btn:hover { transform: translateX(4px); }
                .shortcut-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
                .shortcut-card {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px 14px;
                    border-radius: 16px;
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    box-shadow: 0 2px 10px rgba(0,0,0,.05);
                    text-decoration: none;
                    transition: transform .15s, box-shadow .15s, border-color .15s;
                    min-height: 62px;
                }
                .shortcut-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); border-color: #cbd5e1; }
                .shortcut-icon {
                    width: 38px;
                    height: 38px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    color: #fff;
                    font-size: 16px;
                }
                .shortcut-title { font-size: 13px; font-weight: 800; color: #0f172a; line-height: 1.15; }
                .shortcut-sub { font-size: 11px; color: #64748b; margin-top: 2px; }

                /* ── Dashboard card ── */
                .db-card {
                    background: #fff;
                    border-radius: 20px;
                    padding: 24px;
                    box-shadow: 0 2px 12px rgba(0,0,0,.06);
                    border: 1px solid #f1f5f9;
                }
                .db-card-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 14px;
                    gap: 8px;
                    flex-wrap: wrap;
                }
                .db-card-title { font-weight: 800; color: #1e293b; font-size: 15px; }
                .db-card-link  { color: #0891b2; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; }

                /* ── Booking list item ── */
                .booking-row {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 10px 12px;
                    border-radius: 14px;
                    background: #f8fafc;
                    text-decoration: none;
                    transition: background .15s;
                }
                .booking-row:hover { background: #f1f5f9; }
                .booking-avatar {
                    width: 38px; height: 38px;
                    background: linear-gradient(135deg,#e2e8f0,#cbd5e1);
                    border-radius: 50%;
                    display: flex; align-items: center; justify-content: center;
                    color: #475569; font-weight: 800; font-size: 14px;
                    flex-shrink: 0;
                }
                .booking-info { flex: 1; min-width: 0; }
                .booking-name {
                    font-weight: 700; color: #1e293b; font-size: 13px;
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                }
                .booking-sub  { font-size: 11px; color: #94a3b8; }
                .booking-meta { text-align: right; flex-shrink: 0; }
                .booking-amount { font-weight: 800; color: #1e293b; font-size: 13px; }

                /* ── Arrivals / departures 2-col grid ── */
                .arrivals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

                /* ── Responsive: narrow screens ── */
                @media (max-width: 480px) {
                    .db-card { padding: 14px; border-radius: 16px; }
                    .booking-row { gap: 10px; padding: 8px 10px; }
                    .arrivals-grid { grid-template-columns: 1fr; gap: 14px; }
                }
                @media (max-width: 360px) {
                    .db-card { padding: 12px; border-radius: 14px; }
                    .booking-row { flex-wrap: wrap; }
                    .booking-meta {
                        order: 3; width: 100%;
                        display: flex; justify-content: space-between;
                        align-items: center; flex-direction: row-reverse;
                    }
                }
                </style>

                {{-- Pending guest QR orders (Restaurant module) --}}
                @if(\App\Models\Module::isEnabled('restaurant') && \App\Services\PermissionService::check('restaurant.orders'))
                @php
                    $pendingGuestOrders = \App\Models\RestaurantOrder::with('items')
                        ->where('source', 'guest_qr')
                        ->where('approval_status', 'pending')
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();
                @endphp
                @if($pendingGuestOrders->isNotEmpty())
                <div style="background:linear-gradient(135deg,#fff7ed,#fed7aa);border:2px solid #f97316;border-radius:16px;padding:18px 22px;margin-bottom:20px;box-shadow:0 4px 18px rgba(249,115,22,.18);">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:12px;display:flex;align-items:center;justify-content:center;animation:pulse 1.6s infinite;">
                                <i class="fas fa-bell" style="color:#fff;font-size:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:16px;font-weight:900;color:#7c2d12;">{{ $pendingGuestOrders->count() }} guest order{{ $pendingGuestOrders->count() === 1 ? '' : 's' }} waiting for approval</div>
                                <div style="font-size:12px;color:#9a3412;">Scan-to-order from QR — review and approve to send to kitchen.</div>
                            </div>
                        </div>
                        @if($pendingGuestOrders->count() === 1)
                        <a href="{{ route('restaurant.orders.show', $pendingGuestOrders->first()->id) }}" style="padding:9px 16px;background:#ea580c;color:#fff;border:1.5px solid #c2410c;border-radius:10px;text-decoration:none;font-weight:800;font-size:13px;">Open order →</a>
                        @else
                        <a href="{{ route('restaurant.orders.index') }}" style="padding:9px 16px;background:#fff;color:#ea580c;border:1.5px solid #f97316;border-radius:10px;text-decoration:none;font-weight:800;font-size:13px;">View all →</a>
                        @endif
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;">
                        @foreach($pendingGuestOrders as $po)
                        <a href="{{ route('restaurant.orders.show', $po->id) }}" style="display:block;background:#fff;padding:11px 14px;border-radius:10px;text-decoration:none;color:#1e293b;border:1px solid #fed7aa;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <span style="font-size:13px;font-weight:800;">{{ $po->order_number }}</span>
                                @if($po->room_number)
                                <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">Room {{ $po->room_number }}</span>
                                @elseif($po->table_id)
                                <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">Table</span>
                                @else
                                <span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">Walk-in</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#64748b;display:flex;justify-content:space-between;">
                                <span>{{ $po->items->sum('quantity') }} item(s) · {{ $po->guest_name ?: 'Guest' }}</span>
                                <span style="font-weight:700;color:#f97316;">₹ {{ number_format((float)$po->total, 2) }}</span>
                            </div>
                            <div style="font-size:10px;color:#94a3b8;margin-top:3px;">{{ $po->created_at->diffForHumans() }}</div>
                        </a>
                        @endforeach
                    </div>
                </div>
                <style>@keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.08);} }</style>
                @endif
                @endif

                {{-- ══ SUSPENSION BANNER ═══════════════════════════════════════════ --}}
                @if(session('crm_hotel_suspended'))
                <div style="background:linear-gradient(135deg,#1e293b,#0f172a);border-left:5px solid #f43f5e;border-radius:16px;padding:22px 28px;margin-bottom:20px;display:flex;align-items:flex-start;gap:18px;box-shadow:0 8px 32px rgba(244,63,94,.18);">
                    <div style="width:52px;height:52px;background:rgba(244,63,94,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid rgba(244,63,94,.3);">
                        <i class="fas fa-ban" style="color:#f43f5e;font-size:22px;"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:16px;font-weight:900;color:#fff;margin-bottom:6px;">Your hotel account has been suspended</div>
                        <div style="font-size:13px;color:#94a3b8;line-height:1.6;margin-bottom:16px;">Access to this account has been restricted by the platform administrator. Your data is safe. Please contact us to restore your access.</div>
                        <a href="https://wa.me/919725225519?text={{ urlencode('Hello, my hotel account (' . session('crm_hotel_name', '') . ') has been suspended. Please help restore my access.') }}"
                           target="_blank"
                           style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 4px 14px rgba(37,211,102,.3);">
                            <i class="fab fa-whatsapp" style="font-size:16px;"></i>Contact +91 97252 25519
                        </a>
                    </div>
                </div>
                @endif

                {{-- ══ TRIAL EXPIRED BANNERS ═══════════════════════════════════════ --}}
                @if(session('crm_trial_expired'))
                    @if(!session('crm_trial_extended_once'))
                    {{-- ── First expiry: offer one-time 3-day extension ── --}}
                    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:2px solid #f59e0b;border-radius:16px;padding:22px 28px;margin-bottom:20px;display:flex;align-items:flex-start;gap:18px;box-shadow:0 8px 32px rgba(245,158,11,.15);">
                        <div style="width:52px;height:52px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(245,158,11,.35);">
                            <i class="fas fa-hourglass-end" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:900;color:#92400e;margin-bottom:6px;">Your free trial has ended</div>
                            <div style="font-size:13px;color:#78350f;line-height:1.6;margin-bottom:16px;">You can extend your trial by <strong>3 more days</strong> — one time only. After that you'll need to upgrade to keep using the system.</div>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                <form method="POST" action="{{ route('upgrade.extend-trial') }}" style="display:inline;">
                                    @csrf
                                    <button type="submit"
                                            style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px rgba(245,158,11,.4);"
                                            onclick="this.disabled=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Extending…';this.form.submit();">
                                        <i class="fas fa-clock"></i> Extend 3 Days (Free — One Time)
                                    </button>
                                </form>
                                <a href="{{ route('upgrade') }}"
                                   style="display:inline-flex;align-items:center;gap:7px;padding:11px 20px;background:#fff;border:1.5px solid #d97706;color:#92400e;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;">
                                    <i class="fas fa-arrow-up"></i> Upgrade Plan
                                </a>
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- ── Extended trial also expired: strict no-more-extension warning ── --}}
                    <div style="background:linear-gradient(135deg,#1e293b,#0f172a);border-left:5px solid #ef4444;border-radius:16px;padding:22px 28px;margin-bottom:20px;display:flex;align-items:flex-start;gap:18px;box-shadow:0 8px 32px rgba(239,68,68,.2);">
                        <div style="width:52px;height:52px;background:rgba(239,68,68,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid rgba(239,68,68,.35);">
                            <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:900;color:#fff;margin-bottom:6px;">Trial period fully ended — no further extensions</div>
                            <div style="font-size:13px;color:#94a3b8;line-height:1.6;margin-bottom:16px;">
                                You've already used your one-time 3-day extension. The system is now in read-only mode.
                                To continue using all features, please upgrade your plan or contact us directly.
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                <a href="{{ route('upgrade') }}"
                                   style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 4px 14px rgba(124,58,237,.4);">
                                    <i class="fas fa-arrow-up"></i> Upgrade Plan
                                </a>
                                <a href="https://wa.me/919725225519?text={{ urlencode('Hello, I need to upgrade my hotel CRM plan. Hotel: ' . session('crm_hotel_name', '')) }}"
                                   target="_blank"
                                   style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#25d366,#128c43);color:#fff;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 4px 14px rgba(37,211,102,.3);">
                                    <i class="fab fa-whatsapp" style="font-size:16px;"></i> Contact +91 97252 25519
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif

                {{-- ══════════════════════════════════════════════════════════════════
                     TODAY'S AGENDA MODAL — shown once per login-day
                ══════════════════════════════════════════════════════════════════ --}}
                @if($showAgenda)
                <div id="agendaOverlay" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);">
                    <div id="agendaModal" style="background:#fff;border-radius:24px;box-shadow:0 24px 60px rgba(0,0,0,.25);width:100%;max-width:740px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;animation:agendaSlideIn .35s cubic-bezier(.34,1.56,.64,1) both;">

                        {{-- Header --}}
                        <div style="padding:22px 28px 18px;background:linear-gradient(135deg,#0ea5e9,#7c3aed);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <div style="width:46px;height:46px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
                                    <i class="fas fa-calendar-day" style="color:#fff;font-size:20px;"></i>
                                </div>
                                <div>
                                    <div style="font-size:18px;font-weight:900;color:#fff;letter-spacing:-.02em;">Today's Agenda</div>
                                    <div style="font-size:12px;color:rgba(255,255,255,.8);margin-top:2px;">{{ now()->format('l, d F Y') }}</div>
                                </div>
                            </div>
                            <button onclick="closeAgenda()" style="width:34px;height:34px;background:rgba(255,255,255,.15);border:none;border-radius:10px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        {{-- Summary Strip --}}
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
                            <div style="padding:14px 20px;text-align:center;border-right:1px solid #f1f5f9;">
                                <div style="font-size:28px;font-weight:900;color:#0891b2;line-height:1;">{{ $todayCheckins->count() }}</div>
                                <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;"><i class="fas fa-sign-in-alt" style="color:#06b6d4;margin-right:4px;"></i>Arrivals</div>
                            </div>
                            <div style="padding:14px 20px;text-align:center;border-right:1px solid #f1f5f9;">
                                <div style="font-size:28px;font-weight:900;color:#d97706;line-height:1;">{{ $todayCheckouts->count() }}</div>
                                <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;"><i class="fas fa-sign-out-alt" style="color:#f59e0b;margin-right:4px;"></i>Departures</div>
                            </div>
                            <div style="padding:14px 20px;text-align:center;">
                                <div style="font-size:28px;font-weight:900;color:{{ ($dirtyRooms ?? 0) > 0 ? '#ea580c' : '#10b981' }};line-height:1;">{{ $dirtyRooms ?? 0 }}</div>
                                <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;"><i class="fas fa-broom" style="color:{{ ($dirtyRooms ?? 0) > 0 ? '#f97316' : '#10b981' }};margin-right:4px;"></i>Need Cleaning</div>
                            </div>
                        </div>

                        {{-- Scrollable Body --}}
                        <div style="overflow-y:auto;flex:1;padding:20px 28px;display:flex;flex-direction:column;gap:20px;">

                            {{-- Arrivals --}}
                            @if($todayCheckins->count() > 0)
                            <div>
                                <div style="font-size:12px;font-weight:800;color:#0891b2;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-sign-in-alt"></i> Today's Arrivals
                                    <span style="background:#e0f2fe;color:#0891b2;border-radius:20px;padding:1px 9px;font-size:11px;">{{ $todayCheckins->count() }}</span>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    @foreach($todayCheckins as $bk)
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#ecfeff,#e0f2fe);border-radius:12px;border-left:3px solid #06b6d4;">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:34px;height:34px;background:linear-gradient(135deg,#06b6d4,#0284c7);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;flex-shrink:0;">{{ strtoupper(substr($bk->customer?->name ?? 'G', 0, 1)) }}</div>
                                            <div>
                                                <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $bk->customer?->name ?? '(Deleted Guest)' }}</div>
                                                <div style="font-size:12px;color:#64748b;">{{ $bk->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($bk->room?->room_number ?? '—')) }} &bull; {{ $bk->nights }} night(s)</div>
                                            </div>
                                        </div>
                                        @canDo('checkin.process')
                                        <a href="{{ route('checkin.show', $bk->id) }}" onclick="closeAgenda()" style="background:linear-gradient(135deg,#06b6d4,#0284c7);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(6,182,212,.3);white-space:nowrap;">Check In</a>
                                        @endCanDo
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Departures --}}
                            @if($todayCheckouts->count() > 0)
                            <div>
                                <div style="font-size:12px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-sign-out-alt"></i> Today's Departures
                                    <span style="background:#fef3c7;color:#b45309;border-radius:20px;padding:1px 9px;font-size:11px;">{{ $todayCheckouts->count() }}</span>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    @foreach($todayCheckouts as $bk)
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-radius:12px;border-left:3px solid #f59e0b;">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:34px;height:34px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;flex-shrink:0;">{{ strtoupper(substr($bk->customer?->name ?? 'G', 0, 1)) }}</div>
                                            <div>
                                                <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $bk->customer?->name ?? '(Deleted Guest)' }}</div>
                                                <div style="font-size:12px;color:#64748b;">{{ $bk->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($bk->room?->room_number ?? '—')) }}
                                                    @canDo('reports.view') &bull; Balance: ₹{{ number_format($bk->balance_due) }} @endCanDo
                                                </div>
                                            </div>
                                        </div>
                                        @canDo('checkout.process')
                                        <a href="{{ route('checkout.show', $bk->id) }}" onclick="closeAgenda()" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(245,158,11,.3);white-space:nowrap;">Check Out</a>
                                        @endCanDo
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Dirty Rooms --}}
                            @if(count($dirtyRoomsList) > 0)
                            <div>
                                <div style="font-size:12px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-broom"></i> Needs Cleaning
                                    <span style="background:#ffedd5;color:#c2410c;border-radius:20px;padding:1px 9px;font-size:11px;">{{ count($dirtyRoomsList) }}</span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                    @foreach($dirtyRoomsList as $dr)
                                    <a href="{{ route('rooms.index') }}?status=dirty" onclick="closeAgenda()" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1.5px solid #fed7aa;border-radius:10px;padding:8px 14px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                                        <i class="fas fa-broom" style="color:#f97316;font-size:11px;"></i>
                                        <span style="font-weight:700;color:#c2410c;font-size:13px;">{{ $dr['room_number'] }}</span>
                                        <span style="font-size:11px;color:#9a3412;">{{ ucfirst($dr['type']) }}</span>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- All-clear state --}}
                            @if($todayCheckins->count() === 0 && $todayCheckouts->count() === 0 && count($dirtyRoomsList) === 0)
                            <div style="text-align:center;padding:32px 0;">
                                <div style="font-size:40px;margin-bottom:12px;">🎉</div>
                                <div style="font-weight:800;color:#1e293b;font-size:16px;">All clear for today!</div>
                                <div style="font-size:13px;color:#64748b;margin-top:4px;">No arrivals, departures, or rooms needing attention.</div>
                            </div>
                            @endif

                        </div>

                        {{-- Footer --}}
                        <div style="padding:16px 28px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;flex-shrink:0;background:#fafafa;">
                            <button onclick="closeAgenda()" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:12px;padding:11px 28px;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px rgba(124,58,237,.3);transition:all .15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
                                <i class="fas fa-check" style="margin-right:7px;"></i>Got it, let's go!
                            </button>
                        </div>

                    </div>
                </div>
                @endif

                @php
                    $dashRole = session('crm_user_role');
                    $canSetDefault = in_array($dashRole, ['Super Admin', 'Admin']);
                    $widgetMeta = [
                        'kpi-row-1'          => ['label' => 'KPI Stats — All 8 cards',           'icon' => 'fa-th-large',      'bg' => 'linear-gradient(135deg,#06b6d4,#3b82f6)'],
                        'shortcuts-actions-pair' => ['label' => 'Shortcuts + Quick Actions', 'icon' => 'fa-th-large',      'bg' => 'linear-gradient(135deg,#f59e0b,#f97316)'],
                        'revenue-trend'      => ['label' => 'Revenue Trend & Occupancy chart', 'icon' => 'fa-chart-area', 'bg' => 'linear-gradient(135deg,#10b981,#0ea5e9)'],
                        'slot-availability'  => ['label' => 'Slot Availability',               'icon' => 'fa-clock',         'bg' => 'linear-gradient(135deg,#7c3aed,#6d28d9)'],
                        'booking-calendar'   => ['label' => 'Booking Calendar',               'icon' => 'fa-calendar-alt',  'bg' => 'linear-gradient(135deg,#06b6d4,#0891b2)'],
                        'arrivals-departures'=> ['label' => 'Today\'s Arrivals & Departures', 'icon' => 'fa-exchange-alt',  'bg' => 'linear-gradient(135deg,#f43f5e,#be185d)'],
                        'recent-room-pair'   => ['label' => 'Recent Bookings + Room Availability', 'icon' => 'fa-th-large', 'bg' => 'linear-gradient(135deg,#10b981,#0284c7)'],
                    ];
                    $orderedWidgets = collect($dashWidgetOrder)->filter(fn($k) => array_key_exists($k, $widgetMeta))->values()->all();
                    foreach (array_keys($widgetMeta) as $k) {
                        if (!in_array($k, $orderedWidgets)) $orderedWidgets[] = $k;
                    }
                @endphp

                {{-- ⚙ Customize bar ──────────────────────────────────────────────── --}}
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:4px;flex-wrap:wrap;">
                    @if($dashIsPersonal)
                    <span style="font-size:11px;color:#94a3b8;font-weight:600;background:#f8fafc;border-radius:8px;padding:4px 10px;border:1px solid #f1f5f9;">
                        <i class="fas fa-user-cog" style="margin-right:4px;color:#7c3aed;"></i>Your custom layout
                    </span>
                    @elseif($dashHotelDefault)
                    <span style="font-size:11px;color:#94a3b8;font-weight:600;background:#f8fafc;border-radius:8px;padding:4px 10px;border:1px solid #f1f5f9;">
                        <i class="fas fa-hotel" style="margin-right:4px;color:#06b6d4;"></i>Hotel default layout
                    </span>
                    @endif
                    <div id="dbSaveBadge" class="db-cp-save-badge"><i class="fas fa-check-circle"></i> Saved</div>
                    <button onclick="dbToggleCustomize()" id="dbCustomizeBtn"
                        style="display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(0,0,0,.05);"
                        onmouseenter="this.style.background='#f8fafc';this.style.borderColor='#cbd5e1'"
                        onmouseleave="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                        <i class="fas fa-sliders-h"></i> Customize
                    </button>
                </div>

                {{-- ⚙ Customize panel ─────────────────────────────────────────────── --}}
                <div id="dbCustomizePanel">
                    <div class="db-cp-header">
                        <div class="db-cp-title">
                            <i class="fas fa-sliders-h" style="color:#7c3aed;"></i>
                            Dashboard Customisation
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-size:12px;color:#94a3b8;">Drag to reorder &bull; Toggle to show/hide</span>
                            @if($canSetDefault)
                            <button onclick="dbSaveDefault()" id="dbSetDefaultBtn"
                                style="padding:6px 14px;border-radius:10px;border:1.5px solid #ddd6fe;background:#faf5ff;color:#7c3aed;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;"
                                onmouseenter="this.style.background='#ede9fe'" onmouseleave="this.style.background='#faf5ff'">
                                <i class="fas fa-hotel" style="margin-right:5px;"></i>Set as Hotel Default
                            </button>
                            @endif
                            @if($dashIsPersonal)
                            <button onclick="dbResetPrefs()" id="dbResetBtn"
                                style="padding:6px 14px;border-radius:10px;border:1.5px solid #fca5a5;background:#fff1f2;color:#dc2626;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;"
                                onmouseenter="this.style.background='#fee2e2'" onmouseleave="this.style.background='#fff1f2'">
                                <i class="fas fa-undo" style="margin-right:5px;"></i>Reset to Default
                            </button>
                            @endif
                            <button onclick="dbToggleCustomize()"
                                style="padding:6px 12px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="db-cp-body" id="dbWidgetList">
                        @foreach($orderedWidgets as $wKey)
                        @php
                            $wm = $widgetMeta[$wKey] ?? null;
                            if (!$wm) continue;
                            if ($wKey === 'slot-availability' && !$hasSlotModule) continue;
                        @endphp
                        <div class="db-widget-item" data-widget-key="{{ $wKey }}">
                            <span class="db-drag-handle" title="Drag to reorder"><i class="fas fa-grip-lines"></i></span>
                            <div class="db-widget-item-icon" style="background:{{ $wm['bg'] }};"><i class="fas {{ $wm['icon'] }}"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="db-widget-item-label">{{ $wm['label'] }}</div>
                            </div>
                            <button class="db-toggle {{ !in_array($wKey, $dashHiddenWidgets) ? 'on' : '' }}"
                                onclick="dbToggleWidget('{{ $wKey }}', this)"
                                title="{{ !in_array($wKey, $dashHiddenWidgets) ? 'Click to hide' : 'Click to show' }}">
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── OTA Email Alerts — OUTSIDE dbMain so JS reorder can't push them down ── --}}
                @php
                    $epEnabled  = \App\Models\Module::isEnabled('email-parser');
                    $epHotelId  = (int) session('crm_hotel_id');
                    $epConflicts = $epEnabled ? \App\Models\OtaBookingConflict::unresolvedCountForHotel($epHotelId) : 0;

                    // Only show "New OTA Bookings" banner for imports the user hasn't seen yet.
                    // Seen = user visited the Bookings page after the import arrived.
                    // We store the seen timestamp in the session as ota_email_seen_{hotelId}.
                    $epSeenAt   = session("ota_email_seen_{$epHotelId}");
                    $epNewQuery = $epEnabled
                        ? \App\Models\Booking::where('hotel_id', $epHotelId)
                            ->whereNotNull('external_booking_id')
                            ->whereDate('created_at', today())
                        : null;
                    if ($epNewQuery && $epSeenAt) {
                        $epNewQuery->where('created_at', '>', $epSeenAt);
                    }
                    $epNewToday = $epNewQuery ? $epNewQuery->count() : 0;
                @endphp
                @if($epEnabled && $epConflicts > 0)
                <div style="position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#7f1d1d,#b91c1c,#dc2626);box-shadow:0 8px 32px rgba(220,38,38,.45);animation:pulse-dirty 2s infinite;margin-bottom:4px;">
                    <div style="position:absolute;right:-40px;top:-40px;width:180px;height:180px;background:rgba(255,255,255,.06);border-radius:50%;pointer-events:none;"></div>
                    <div style="position:absolute;left:60px;bottom:-50px;width:130px;height:130px;background:rgba(255,255,255,.04);border-radius:50%;pointer-events:none;"></div>
                    <a href="{{ route('email-parser.conflicts') }}" style="display:flex;align-items:center;gap:20px;padding:22px 28px;text-decoration:none;flex-wrap:wrap;">
                        <div style="position:relative;flex-shrink:0;">
                            <div style="position:absolute;inset:0;background:rgba(255,255,255,.25);border-radius:18px;animation:pulse-dirty 1.5s infinite;"></div>
                            <div style="position:relative;width:62px;height:62px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.4);border-radius:18px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-triangle-exclamation" style="color:#fff;font-size:26px;"></i>
                            </div>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                                <span style="font-size:19px;font-weight:900;color:#fff;letter-spacing:-.3px;">{{ $epConflicts }} OTA Booking Conflict{{ $epConflicts === 1 ? '' : 's' }} Need Attention</span>
                                <span style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);color:#fff;font-size:11px;font-weight:800;padding:3px 12px;border-radius:20px;letter-spacing:.06em;text-transform:uppercase;">Action Required</span>
                            </div>
                            <div style="font-size:14px;color:rgba(255,255,255,.85);">Bookings imported from email are missing a room assignment — click to resolve now.</div>
                        </div>
                        <div style="flex-shrink:0;background:#fff;color:#b91c1c;font-size:14px;font-weight:800;padding:12px 24px;border-radius:12px;display:flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(0,0,0,.2);">Resolve <i class="fas fa-arrow-right"></i></div>
                    </a>
                </div>
                @endif
                @if($epEnabled && $epNewToday > 0)
                <div style="position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#064e3b,#065f46,#059669);box-shadow:0 8px 28px rgba(5,150,105,.4);margin-bottom:4px;">
                    <div style="position:absolute;right:-40px;top:-40px;width:160px;height:160px;background:rgba(255,255,255,.06);border-radius:50%;pointer-events:none;"></div>
                    <div style="position:absolute;left:80px;bottom:-50px;width:120px;height:120px;background:rgba(255,255,255,.04);border-radius:50%;pointer-events:none;"></div>
                    <a href="{{ route('bookings.index') }}" style="display:flex;align-items:center;gap:20px;padding:22px 28px;text-decoration:none;flex-wrap:wrap;">
                        <div style="width:62px;height:62px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.35);border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-envelope-open-text" style="color:#fff;font-size:26px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                                <span style="font-size:19px;font-weight:900;color:#fff;letter-spacing:-.3px;">{{ $epNewToday }} New OTA Booking{{ $epNewToday === 1 ? '' : 's' }} Imported Today</span>
                                <span style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);color:#fff;font-size:11px;font-weight:800;padding:3px 12px;border-radius:20px;letter-spacing:.06em;text-transform:uppercase;">New</span>
                            </div>
                            <div style="font-size:14px;color:rgba(255,255,255,.85);">Auto-created from your inbox — click to view &amp; this banner will clear.</div>
                        </div>
                        <div style="flex-shrink:0;background:#fff;color:#065f46;font-size:14px;font-weight:800;padding:12px 24px;border-radius:12px;display:flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(0,0,0,.2);">View Bookings <i class="fas fa-arrow-right"></i></div>
                    </a>
                </div>
                @endif

                <div class="dashboard-main" id="dbMain" style="display:flex;flex-direction:column;gap:24px;">

                    {{-- ── Hotel Full Alert Banner ─────────────────────────────────────────── --}}
                    @if($hotelFull)
                    <div style="position:relative;overflow:hidden;border-radius:20px;padding:22px 28px;background:linear-gradient(135deg,#dc2626,#991b1b);box-shadow:0 8px 32px rgba(220,38,38,.4);animation:pulse-dirty 2s infinite;display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                        {{-- Orb decorations --}}
                        <div style="position:absolute;right:-30px;top:-30px;width:140px;height:140px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;"></div>
                        <div style="position:absolute;right:80px;bottom:-40px;width:100px;height:100px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none;"></div>
                        {{-- Icon --}}
                        <div style="width:56px;height:56px;background:rgba(255,255,255,.18);border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(255,255,255,.3);">
                            <i class="fas fa-hotel" style="color:#fff;font-size:24px;"></i>
                        </div>
                        {{-- Text --}}
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:18px;font-weight:900;color:#fff;letter-spacing:-.3px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                🚨 Hotel Fully Booked — {{ now()->format('d M Y') }}
                                <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:3px 12px;font-size:12px;font-weight:700;letter-spacing:.04em;">100% OCCUPIED</span>
                            </div>
                            <div style="font-size:14px;color:rgba(255,255,255,.85);margin-top:5px;">
                                All <strong>{{ $totalRooms }}</strong> rooms are occupied today. An alert email has been sent to the hotel admin.
                            </div>
                        </div>
                        {{-- Actions --}}
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            <a href="{{ route('rooms.index') }}" style="background:rgba(255,255,255,.2);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.35);color:#fff;font-size:13px;font-weight:700;padding:10px 20px;border-radius:12px;text-decoration:none;white-space:nowrap;transition:all .15s;"
                               onmouseenter="this.style.background='rgba(255,255,255,.3)'" onmouseleave="this.style.background='rgba(255,255,255,.2)'">
                                <i class="fas fa-door-open" style="margin-right:6px;"></i>View Rooms
                            </a>
                            <a href="{{ route('checkout.index') }}" style="background:#fff;color:#dc2626;font-size:13px;font-weight:700;padding:10px 20px;border-radius:12px;text-decoration:none;white-space:nowrap;box-shadow:0 3px 10px rgba(0,0,0,.2);transition:all .15s;"
                               onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform=''">
                                <i class="fas fa-sign-out-alt" style="margin-right:6px;"></i>Check-Outs
                            </a>
                        </div>
                    </div>
                    @endif

                    @php
                        $dashboardShortcuts = [];
                        // Module-gated shortcuts
                        if (\App\Models\Module::isEnabled('extra-billing')) {
                            $dashboardShortcuts[] = ['route' => route('food-billing.index'), 'icon' => 'fa-utensils', 'title' => 'Food Billing', 'sub' => 'Food charges', 'bg' => 'linear-gradient(135deg,#f97316,#ea580c)'];
                        }
                        if (\App\Models\Module::isEnabled('restaurant') && \App\Services\PermissionService::check('restaurant.view')) {
                            $dashboardShortcuts[] = ['route' => route('restaurant.index'), 'icon' => 'fa-concierge-bell', 'title' => 'Restaurant', 'sub' => 'Table orders', 'bg' => 'linear-gradient(135deg,#f43f5e,#e11d48)'];
                        }
                        if (!$isRestaurantOnly) {
                            if (\App\Models\Module::isEnabled('payment_links')) {
                                $dashboardShortcuts[] = ['route' => route('payment_links.config'), 'icon' => 'fa-credit-card', 'title' => 'Payments', 'sub' => 'Links', 'bg' => 'linear-gradient(135deg,#8b5cf6,#6366f1)'];
                            }
                            if (\App\Models\Module::isEnabled('pathik')) {
                                $dashboardShortcuts[] = ['route' => route('pathik.index'), 'icon' => 'fa-id-card', 'title' => 'Pathik', 'sub' => 'Portal', 'bg' => 'linear-gradient(135deg,#7c3aed,#a855f7)'];
                            }
                            if (\App\Models\Module::isEnabled('slot-search-engine')) {
                                $dashboardShortcuts[] = ['route' => route('slot-search.index'), 'icon' => 'fa-search', 'title' => 'Slot Search', 'sub' => 'Availability matrix', 'bg' => 'linear-gradient(135deg,#6366f1,#4f46e5)'];
                            }
                            if (\App\Models\Module::isEnabled('ota_whatsapp_sync')) {
                                $otaShortcutSub = ($otaPendingCount ?? 0) > 0 ? ($otaPendingCount . ' pending review') : 'WhatsApp imports';
                                $dashboardShortcuts[] = ['route' => route('ota-bookings.index'), 'icon' => 'fa-inbox', 'title' => 'OTA Bookings', 'sub' => $otaShortcutSub, 'bg' => 'linear-gradient(135deg,#f59e0b,#d97706)', 'badge' => ($otaPendingCount ?? 0)];
                            }
                            if (\App\Models\Module::isEnabled('email-parser')) {
                                $dashboardShortcuts[] = ['route' => route('email-parser.config'), 'icon' => 'fa-envelope-open-text', 'title' => 'Email Parser', 'sub' => 'OTA email import', 'bg' => 'linear-gradient(135deg,#0891b2,#0e7490)'];
                            }
                        }
                    @endphp

                    @php
                        $epEnabled = \App\Models\Module::isEnabled('email-parser');
                        $epHotelId = (int) session('crm_hotel_id');
                        $epConflicts = $epEnabled
                            ? \App\Models\OtaBookingConflict::unresolvedCountForHotel($epHotelId)
                            : 0;
                        $epNewToday = $epEnabled
                            ? \App\Models\Booking::where('hotel_id', $epHotelId)
                                ->whereNotNull('external_booking_id')
                                ->whereDate('created_at', today())
                                ->count()
                            : 0;
                    @endphp
                    {{-- KPI Stats — conditional by role --}}
                    <div data-widget="kpi-row-1" class="db-widget-wrap">
                    @if($isRestaurantOnly)
                    {{-- Restaurant-only KPI row --}}
                    <div class="kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
                        <a href="{{ route('restaurant.orders.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#f43f5e,#e11d48);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-concierge-bell kpi-icon"></i>
                            <div class="kpi-label">Active Orders</div>
                            <div class="kpi-num" data-count="{{ $restActiveOrders }}">{{ $restActiveOrders }}</div>
                            <div class="kpi-sub">Open / KOT / Served</div>
                        </a>
                        @if($restPendingQr > 0)
                        <a href="{{ route('restaurant.orders.index') }}" class="kpi-card kpi-card-sm"
                           style="background:linear-gradient(135deg,#f59e0b,#d97706);animation:pulse-dirty 1.8s infinite;position:relative;overflow:visible;">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <span style="position:absolute;top:-7px;right:-7px;width:20px;height:20px;background:#dc2626;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:#fff;animation:dirty-badge-pop 1s ease-in-out infinite;z-index:2;line-height:1;">{{ $restPendingQr }}</span>
                            <i class="fas fa-qrcode kpi-icon" style="animation:dirty-icon-shake 2.2s ease-in-out infinite;display:inline-block;"></i>
                            <div class="kpi-label">QR Pending</div>
                            <div class="kpi-num" data-count="{{ $restPendingQr }}">{{ $restPendingQr }}</div>
                            <div class="kpi-sub">Needs approval</div>
                        </a>
                        @endif
                        <a href="{{ route('restaurant.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#10b981,#059669);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-chair kpi-icon"></i>
                            <div class="kpi-label">Tables</div>
                            <div class="kpi-num" data-count="{{ $restTablesOccupied }}">{{ $restTablesOccupied }}</div>
                            <div class="kpi-sub">{{ $restTablesTotal }} total</div>
                        </a>
                        <a href="{{ route('restaurant.bills.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-rupee-sign kpi-icon"></i>
                            <div class="kpi-label">Food Revenue</div>
                            <div class="kpi-num" data-count="{{ $restTodayRevenue }}" data-prefix="₹" data-format="currency">₹{{ number_format($restTodayRevenue) }}</div>
                            <div class="kpi-sub">Today</div>
                        </a>
                        <a href="{{ route('restaurant.menu.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-utensils kpi-icon"></i>
                            <div class="kpi-label">Menu Items</div>
                            <div class="kpi-num" data-count="{{ $restMenuItems }}">{{ $restMenuItems }}</div>
                            <div class="kpi-sub">Active</div>
                        </a>
                    </div>
                    @else
                    {{-- Standard hotel-wide KPI row --}}
                    <div class="kpi-grid kpi-grid-8" style="display:grid;grid-template-columns:repeat(8,1fr);gap:10px;">
                        <a href="{{ route('checkin.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-sign-in-alt kpi-icon"></i>
                            <div class="kpi-label">Check-Ins</div>
                            <div class="kpi-num" data-count="{{ $todayCheckins->count() }}">{{ $todayCheckins->count() }}</div>
                            <div class="kpi-sub">Today</div>
                        </a>
                        <a href="{{ route('checkout.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-sign-out-alt kpi-icon"></i>
                            <div class="kpi-label">Check-Outs</div>
                            <div class="kpi-num" data-count="{{ $todayCheckouts->count() }}">{{ $todayCheckouts->count() }}</div>
                            <div class="kpi-sub">Today</div>
                        </a>
                        <a href="{{ route('rooms.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#10b981,#059669);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-door-open kpi-icon"></i>
                            <div class="kpi-label">Available</div>
                            <div class="kpi-num" data-count="{{ $availableRooms }}">{{ $availableRooms }}</div>
                            <div class="kpi-sub">of {{ $totalRooms }} rooms</div>
                        </a>
                        <a href="{{ route('rooms.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#f43f5e,#be185d);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-bed kpi-icon"></i>
                            <div class="kpi-label">Occupied</div>
                            <div class="kpi-num" data-count="{{ $occupiedRooms }}">{{ $occupiedRooms }}</div>
                            <div class="kpi-sub">{{ $occupancyRate }}% occ.</div>
                        </a>
                        @if(($dirtyRooms ?? 0) > 0)
                        <a href="{{ route('rooms.index') }}?status=dirty" class="kpi-card kpi-card-sm"
                           style="background:linear-gradient(135deg,#f97316,#ea580c);animation:pulse-dirty 1.8s infinite,dirty-bg-flash 2.6s ease-in-out infinite;position:relative;overflow:visible;">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <span style="position:absolute;top:-7px;right:-7px;width:20px;height:20px;background:#dc2626;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:#fff;animation:dirty-badge-pop 1s ease-in-out infinite;z-index:2;line-height:1;">!</span>
                            <i class="fas fa-broom kpi-icon" style="animation:dirty-icon-shake 2.2s ease-in-out infinite;display:inline-block;"></i>
                            <div class="kpi-label">Needs Cleaning</div>
                            <div class="kpi-num" data-count="{{ $dirtyRooms }}">{{ $dirtyRooms }}</div>
                            <div class="kpi-sub" style="animation:dirty-badge-pop 1.8s ease-in-out infinite;">⚠ Act now</div>
                        </a>
                        @endif
                        @canDo('reports.view')
                        <a href="{{ route('reports.revenue') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-rupee-sign kpi-icon"></i>
                            <div class="kpi-label">Today Revenue</div>
                            <div class="kpi-num" data-count="{{ $todayRevenue }}" data-prefix="₹" data-format="currency">₹{{ number_format($todayRevenue) }}</div>
                            <div class="kpi-sub">Collected</div>
                        </a>
                        <a href="{{ route('reports.revenue') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-chart-line kpi-icon"></i>
                            <div class="kpi-label">Month Revenue</div>
                            <div class="kpi-num" data-count="{{ $monthRevenue }}" data-prefix="₹" data-format="currency">₹{{ number_format($monthRevenue) }}</div>
                            <div class="kpi-sub">{{ now()->format('M Y') }}</div>
                        </a>
                        @endCanDo
                        <a href="{{ route('bookings.index', ['payment_status' => 'pending']) }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#d97706,#b45309);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-exclamation-triangle kpi-icon"></i>
                            <div class="kpi-label">Pending Pay</div>
                            <div class="kpi-num" data-count="{{ $pendingPayments }}">{{ $pendingPayments }}</div>
                            <div class="kpi-sub">Needs attention</div>
                        </a>
                        <a href="{{ route('customers.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#0891b2,#0e7490);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-users kpi-icon"></i>
                            <div class="kpi-label">Total Guests</div>
                            <div class="kpi-num" data-count="{{ $totalCustomers }}">{{ $totalCustomers }}</div>
                            <div class="kpi-sub">+{{ $newCustomersMonth }} this month</div>
                        </a>
                        @if(\App\Models\Module::isEnabled('booking-widget') && ($websitePendingCount ?? 0) > 0)
                        <a href="{{ route('bookings.index') }}?status=website_pending" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#ec4899,#be185d);">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <i class="fas fa-globe kpi-icon"></i>
                            <div class="kpi-label">Web Bookings</div>
                            <div class="kpi-num" data-count="{{ $websitePendingCount ?? 0 }}">{{ $websitePendingCount ?? 0 }}</div>
                            <div class="kpi-sub">Pending confirm</div>
                        </a>
                        @endif
                        @if(\App\Models\Module::isEnabled('inventory') && ($lowStockCount ?? 0) > 0)
                        <a href="{{ route('inventory.index') }}" class="kpi-card kpi-card-sm" style="background:linear-gradient(135deg,#dc2626,#b91c1c);position:relative;">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            <span style="position:absolute;top:-7px;right:-7px;width:20px;height:20px;background:#fff;border-radius:50%;border:2px solid #dc2626;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;color:#dc2626;z-index:2;line-height:1;">{{ $lowStockCount }}</span>
                            <i class="fas fa-exclamation-triangle kpi-icon"></i>
                            <div class="kpi-label">Low Stock</div>
                            <div class="kpi-num" data-count="{{ $lowStockCount ?? 0 }}">{{ $lowStockCount ?? 0 }}</div>
                            <div class="kpi-sub">Items need restock</div>
                        </a>
                        @endif
                        @if(\App\Models\Module::isEnabled('ota_whatsapp_sync'))
                        <a href="{{ route('ota-bookings.index') }}" class="kpi-card kpi-card-sm"
                           style="background:linear-gradient(135deg,#f59e0b,#d97706);position:relative;{{ ($otaPendingCount ?? 0) > 0 ? 'animation:pulse-dirty 1.8s infinite;' : '' }}">
                            <div class="kpi-shine"></div><div class="kpi-shine2"></div>
                            @if(($otaPendingCount ?? 0) > 0)
                            <span style="position:absolute;top:-7px;right:-7px;width:20px;height:20px;background:#dc2626;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;color:#fff;animation:dirty-badge-pop 1s ease-in-out infinite;z-index:2;line-height:1;">{{ $otaPendingCount }}</span>
                            @endif
                            <i class="fas fa-inbox kpi-icon"></i>
                            <div class="kpi-label">OTA Imports</div>
                            <div class="kpi-num" data-count="{{ $otaPendingCount ?? 0 }}">{{ $otaPendingCount ?? 0 }}</div>
                            <div class="kpi-sub">{{ ($otaPendingCount ?? 0) > 0 ? 'Pending review' : 'All clear' }}</div>
                        </a>
                        @endif
                    </div>
                    @endif
                    </div>{{-- /kpi-row-1 widget --}}

                    @if($isRestaurantOnly && \App\Models\Module::isEnabled('restaurant'))
                    {{-- ── Quick Order Table Map (restaurant-only) ────────────────────── --}}
                    <div data-widget="quick-table-map" class="db-widget-wrap">
                    <div class="db-card" style="overflow:hidden;padding:0;">

                        {{-- Header --}}
                        <div style="padding:14px 18px;background:linear-gradient(135deg,#fff1f2,#fce7f3);border-bottom:1px solid #fce7f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;background:linear-gradient(135deg,#f43f5e,#e11d48);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 10px rgba(244,63,94,.3);">
                                    <i class="fas fa-concierge-bell" style="color:#fff;font-size:14px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#1e293b;font-size:15px;">Quick Order</div>
                                    <div style="font-size:11px;color:#be185d;" id="qtmUpdatedAt">Tap a table to start an order</div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span id="qtmPendingQrBadge" style="display:none;background:#dc2626;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;animation:pulse-dirty 1.8s infinite;">
                                    <i class="fas fa-qrcode"></i> <span id="qtmPendingQrCount">0</span> QR pending
                                </span>
                                <span id="qtmSpinner" style="display:none;width:18px;height:18px;border:2px solid #fecdd3;border-top-color:#f43f5e;border-radius:50%;animation:spin 0.7s linear infinite;"></span>
                                <a href="{{ route('restaurant.index') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#f43f5e;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;box-shadow:0 2px 8px rgba(244,63,94,.3);">
                                    Full View <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div style="padding:8px 18px;background:#fafafa;border-bottom:1px solid #f1f5f9;display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                            <div style="display:inline-flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block;"></span><span style="font-size:11px;color:#475569;font-weight:600;">Free</span></div>
                            <div style="display:inline-flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#f97316;display:inline-block;"></span><span style="font-size:11px;color:#475569;font-weight:600;">Occupied</span></div>
                            <div style="display:inline-flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span><span style="font-size:11px;color:#475569;font-weight:600;">Needs Cleaning</span></div>
                            <div style="display:inline-flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#374151;display:inline-block;"></span><span style="font-size:11px;color:#475569;font-weight:600;">Unavailable</span></div>
                        </div>

                        {{-- Table Grid --}}
                        <div style="padding:14px 16px;">
                        @if($restAllTables->isEmpty())
                            <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                                <i class="fas fa-chair" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
                                <p style="font-size:14px;font-weight:600;">No tables set up yet</p>
                                <a href="{{ route('restaurant.index') }}" style="display:inline-block;margin-top:10px;padding:8px 18px;background:#f43f5e;color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;">Go to Restaurant →</a>
                            </div>
                        @else
                        <div id="qtmGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;">
                            @foreach($restAllTables as $table)
                            @php
                                $qtmBg = match($table->status) {
                                    'free'        => 'background:#dcfce7;border:2px solid #16a34a;',
                                    'occupied'    => 'background:#ffedd5;border:2px solid #ea580c;',
                                    'dirty'       => 'background:#fee2e2;border:2px solid #dc2626;',
                                    'unavailable' => 'background:#e5e7eb;border:2px solid #374151;opacity:.7;',
                                    default       => 'background:#f8fafc;border:2px solid #e2e8f0;',
                                };
                                $qtmDot = match($table->status) {
                                    'free'        => '#22c55e',
                                    'occupied'    => '#f97316',
                                    'dirty'       => '#ef4444',
                                    'unavailable' => '#374151',
                                    default       => '#9ca3af',
                                };
                                $qtmLabel = match($table->status) {
                                    'free'        => '#15803d',
                                    'occupied'    => '#c2410c',
                                    'dirty'       => '#b91c1c',
                                    'unavailable' => '#6b7280',
                                    default       => '#374151',
                                };
                                $isClickable = $table->status !== 'unavailable';
                            @endphp
                            <div id="qtm-table-{{ $table->id }}"
                                 data-table-id="{{ $table->id }}"
                                 data-status="{{ $table->status }}"
                                 data-order-id="{{ $table->activeOrder?->id ?? '' }}"
                                 data-table-name="{{ $table->name }}"
                                 data-capacity="{{ $table->capacity }}"
                                 onclick="qtmHandleClick(this)"
                                 style="border-radius:14px;padding:12px 10px;cursor:{{ $isClickable ? 'pointer' : 'default' }};position:relative;transition:transform .15s,box-shadow .15s;min-height:110px;display:flex;flex-direction:column;justify-content:space-between;-webkit-tap-highlight-color:transparent;{{ $qtmBg }}"
                                 onmouseenter="if(this.dataset.status!=='unavailable'){this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.12)';}"
                                 onmouseleave="this.style.transform='';this.style.boxShadow='';">

                                {{-- Status dot --}}
                                <div data-role="dot" style="position:absolute;top:9px;right:9px;width:10px;height:10px;border-radius:50%;background:{{ $qtmDot }};"></div>

                                {{-- Table name --}}
                                <div style="font-size:15px;font-weight:800;color:#1e293b;">{{ $table->name }}</div>
                                <div style="font-size:11px;color:#64748b;margin-top:2px;"><i class="fas fa-users" style="font-size:9px;"></i> {{ $table->capacity }}</div>

                                <div data-role="bottom" style="margin-top:8px;">
                                    <div style="font-size:11px;font-weight:700;color:{{ $qtmLabel }};">{{ $table->statusLabel() }}</div>
                                    @if($table->activeOrder)
                                    <div style="font-size:10px;color:#92400e;margin-top:3px;font-weight:600;">{{ $table->activeOrder->order_number }}</div>
                                    <div style="font-size:11px;color:#c2410c;font-weight:700;">₹{{ number_format($table->activeOrder->total, 2) }}</div>
                                    @elseif($table->status === 'free')
                                    <div style="margin-top:4px;display:inline-flex;align-items:center;gap:4px;background:#dcfce7;border:1px solid #86efac;border-radius:6px;padding:2px 7px;font-size:10px;font-weight:700;color:#15803d;">
                                        <i class="fas fa-plus" style="font-size:9px;"></i> Order
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        </div>

                        {{-- Footer: last refreshed --}}
                        <div style="padding:8px 18px;background:#fafafa;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                            <span style="font-size:11px;color:#94a3b8;">Auto-refreshes every 20 s</span>
                            <span id="qtmLastRefresh" style="font-size:11px;color:#64748b;font-weight:600;"></span>
                        </div>

                    </div>
                    </div>{{-- /quick-table-map widget --}}

                    {{-- Quick Order JS --}}
                    <script>
                    (function(){
                        var orderStoreUrl   = '{{ route("restaurant.orders.store") }}';
                        var ordersBaseUrl   = '{{ url("restaurant/orders") }}';
                        var tablesJsonUrl   = '{{ route("dashboard.restaurant_tables") }}';
                        var restaurantUrl   = '{{ route("restaurant.index") }}';
                        var csrfToken       = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        // Map status → styles
                        var statusStyles = {
                            free:        { bg:'#dcfce7', border:'#16a34a', dot:'#22c55e', label:'#15803d' },
                            occupied:    { bg:'#ffedd5', border:'#ea580c', dot:'#f97316', label:'#c2410c' },
                            dirty:       { bg:'#fee2e2', border:'#dc2626', dot:'#ef4444', label:'#b91c1c' },
                            unavailable: { bg:'#e5e7eb', border:'#374151', dot:'#374151', label:'#6b7280' },
                        };

                        window.qtmHandleClick = function(card) {
                            var status   = card.dataset.status;
                            var tableId  = card.dataset.tableId;
                            var orderId  = card.dataset.orderId;
                            var name     = card.dataset.tableName;
                            var capacity = card.dataset.capacity;

                            if (status === 'unavailable') return;

                            if (status === 'dirty') {
                                if (confirm('Table "' + name + '" needs cleaning. Go to full restaurant view to update status?')) {
                                    window.location.href = restaurantUrl;
                                }
                                return;
                            }

                            if (status === 'occupied' && orderId) {
                                window.location.href = ordersBaseUrl + '/' + orderId;
                                return;
                            }

                            if (status === 'free') {
                                if (!confirm('Start new order for ' + name + ' (' + capacity + ' seats)?')) return;
                                var form = document.createElement('form');
                                form.method = 'POST';
                                form.action = orderStoreUrl;
                                form.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '">'
                                              + '<input type="hidden" name="table_id" value="' + tableId + '">';
                                document.body.appendChild(form);
                                form.submit();
                            }
                        };

                        function qtmRefresh() {
                            var spinner = document.getElementById('qtmSpinner');
                            if (spinner) spinner.style.display = 'inline-block';

                            fetch(tablesJsonUrl, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
                                .then(function(r){ return r.json(); })
                                .then(function(data) {
                                    // Update each table card
                                    (data.tables || []).forEach(function(t) {
                                        var card = document.getElementById('qtm-table-' + t.id);
                                        if (!card) return;
                                        var s = statusStyles[t.status] || statusStyles['unavailable'];

                                        // Update data attrs
                                        card.dataset.status  = t.status;
                                        card.dataset.orderId = t.order_id || '';

                                        // Background + border
                                        card.style.background = s.bg;
                                        card.style.border     = '2px solid ' + s.border;
                                        card.style.opacity    = t.status === 'unavailable' ? '0.7' : '1';
                                        card.style.cursor     = t.status === 'unavailable' ? 'default' : 'pointer';

                                        // Status dot
                                        var dot = card.querySelector('[data-role="dot"]');
                                        if (!dot) { dot = card.querySelector('div[style*="border-radius:50%"]'); }
                                        if (dot) dot.style.background = s.dot;

                                        // Rebuild bottom section
                                        var bottom = card.querySelector('[data-role="bottom"]');
                                        if (bottom) {
                                            var statusHtml = '<div style="font-size:11px;font-weight:700;color:' + s.label + ';">' + t.status_label + '</div>';
                                            if (t.order_id) {
                                                statusHtml += '<div style="font-size:10px;color:#92400e;margin-top:3px;font-weight:600;">' + t.order_number + '</div>'
                                                           + '<div style="font-size:11px;color:#c2410c;font-weight:700;">₹' + t.order_total + '</div>';
                                            } else if (t.status === 'free') {
                                                statusHtml += '<div style="margin-top:4px;display:inline-flex;align-items:center;gap:4px;background:#dcfce7;border:1px solid #86efac;border-radius:6px;padding:2px 7px;font-size:10px;font-weight:700;color:#15803d;"><i class=\'fas fa-plus\' style=\'font-size:9px;\'></i> Order</div>';
                                            }
                                            bottom.innerHTML = statusHtml;
                                        }
                                    });

                                    // Pending QR badge
                                    var badge = document.getElementById('qtmPendingQrBadge');
                                    var qrCount = document.getElementById('qtmPendingQrCount');
                                    if (badge && qrCount) {
                                        if (data.pending_qr > 0) {
                                            qrCount.textContent = data.pending_qr;
                                            badge.style.display = 'inline-flex';
                                        } else {
                                            badge.style.display = 'none';
                                        }
                                    }

                                    // Last refresh time
                                    var ts = document.getElementById('qtmLastRefresh');
                                    if (ts) ts.textContent = 'Updated ' + data.updated_at;
                                    var updLabel = document.getElementById('qtmUpdatedAt');
                                    if (updLabel) updLabel.textContent = 'Live · ' + data.updated_at;
                                })
                                .catch(function(){})
                                .finally(function(){
                                    if (spinner) spinner.style.display = 'none';
                                });
                        }

                        // Initial refresh after 2s, then every 20s
                        setTimeout(qtmRefresh, 2000);
                        setInterval(qtmRefresh, 20000);
                    })();
                    </script>
                    @endif

                    @if(!$isRestaurantOnly)
                    {{-- ── Revenue Trend & Occupancy ────────────────────────────────── --}}
                    <div data-widget="revenue-trend" class="db-widget-wrap">
                    <div class="db-card" style="overflow:hidden;padding:0;">
                        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:12px;background:linear-gradient(135deg,#ecfdf5,#e0f2fe);flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#0ea5e9);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,.3);">
                                    <i class="fas fa-chart-area" style="color:#fff;font-size:14px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#1e293b;font-size:15px;">Revenue & Occupancy Trend</div>
                                    <div style="font-size:11px;color:#0ea5e9;" id="rtRangeLabel">Last 7 days</div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <button type="button" data-rt-range="7d"  onclick="rtSetRange('7d',this)"  style="padding:6px 12px;border-radius:8px;border:1.5px solid #10b981;background:#10b981;color:#fff;font-size:12px;font-weight:700;cursor:pointer;">7D</button>
                                <button type="button" data-rt-range="30d" onclick="rtSetRange('30d',this)" style="padding:6px 12px;border-radius:8px;border:1.5px solid #cbd5e1;background:#fff;color:#475569;font-size:12px;font-weight:700;cursor:pointer;">30D</button>
                                <button type="button" data-rt-range="90d" onclick="rtSetRange('90d',this)" style="padding:6px 12px;border-radius:8px;border:1.5px solid #cbd5e1;background:#fff;color:#475569;font-size:12px;font-weight:700;cursor:pointer;">90D</button>
                                <button type="button" data-rt-range="12m" onclick="rtSetRange('12m',this)" style="padding:6px 12px;border-radius:8px;border:1.5px solid #cbd5e1;background:#fff;color:#475569;font-size:12px;font-weight:700;cursor:pointer;">12M</button>
                            </div>
                        </div>

                        <div style="padding:14px 18px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;border-bottom:1px solid #f1f5f9;background:#fafbfc;">
                            <div>
                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Total</div>
                                <div id="rtTotal" style="font-size:18px;font-weight:900;color:#10b981;margin-top:2px;">₹0</div>
                                <div id="rtTotalDelta" class="rt-delta" style="font-size:11px;font-weight:700;margin-top:3px;color:#94a3b8;">—</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Avg / Period</div>
                                <div id="rtAvg" style="font-size:18px;font-weight:900;color:#0ea5e9;margin-top:2px;">₹0</div>
                                <div id="rtAvgDelta" class="rt-delta" style="font-size:11px;font-weight:700;margin-top:3px;color:#94a3b8;">—</div>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Peak</div>
                                <div id="rtPeak" style="font-size:18px;font-weight:900;color:#7c3aed;margin-top:2px;">₹0</div>
                                <div id="rtPeakDelta" class="rt-delta" style="font-size:11px;font-weight:700;margin-top:3px;color:#94a3b8;">—</div>
                            </div>
                        </div>

                        <div id="rtRevenueChart" style="min-height:300px;padding:8px 4px 0;"></div>
                        <div style="padding:0 18px 4px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Occupancy %</div>
                        <div id="rtOccupancyChart" style="min-height:120px;padding:0 4px 12px;"></div>
                    </div>
                    </div>{{-- /revenue-trend widget --}}
                    @endif

                    <script>
                    (function(){
                        var revChart = null, occChart = null, currentRange = '7d', loading = false;
                        var endpoint = "{{ route('dashboard.revenue_trend') }}";

                        function fmtINR(v){
                            v = Math.round(v||0);
                            if (v >= 10000000) return '₹' + (v/10000000).toFixed(2) + 'Cr';
                            if (v >= 100000)   return '₹' + (v/100000).toFixed(2) + 'L';
                            if (v >= 1000)     return '₹' + (v/1000).toFixed(1) + 'K';
                            return '₹' + v;
                        }

                        function ensureApex(cb){
                            if (typeof ApexCharts !== 'undefined') { cb(); return; }
                            var tries = 0;
                            var t = setInterval(function(){
                                if (typeof ApexCharts !== 'undefined' || ++tries > 40) {
                                    clearInterval(t);
                                    if (typeof ApexCharts !== 'undefined') cb();
                                }
                            }, 100);
                        }

                        function renderRevenue(labels, data, prevData){
                            var hasPrev = Array.isArray(prevData) && prevData.some(function(v){ return Number(v) > 0; });
                            var series = [{ name: 'Current', type: 'area', data: data }];
                            if (hasPrev) series.push({ name: 'Prior period', type: 'line', data: prevData });
                            var opts = {
                                chart: { type: 'line', height: 280, toolbar: { show: false }, animations: { speed: 400 }, fontFamily: 'Inter, sans-serif' },
                                series: series,
                                xaxis: { categories: labels, labels: { style: { fontSize: '11px', colors: '#64748b' } }, axisBorder:{show:false}, axisTicks:{show:false} },
                                yaxis: { labels: { formatter: fmtINR, style: { fontSize: '11px', colors: '#64748b' } } },
                                stroke: { curve: 'smooth', width: hasPrev ? [3,2] : [3], dashArray: hasPrev ? [0,5] : [0] },
                                colors: hasPrev ? ['#10b981', '#94a3b8'] : ['#10b981'],
                                fill: hasPrev
                                    ? { type: ['gradient','solid'], gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops:[0,90,100] }, opacity: [1, 0] }
                                    : { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0,90,100] } },
                                dataLabels: { enabled: false },
                                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                                legend: { show: hasPrev, position: 'top', horizontalAlign: 'right', fontSize: '11px', markers: { width: 10, height: 10 } },
                                tooltip: { y: { formatter: function(v){ return '₹' + Number(v||0).toLocaleString('en-IN'); } } },
                                noData: { text: 'No revenue in this period', style: { color:'#94a3b8', fontSize:'13px' } },
                            };
                            if (revChart) { revChart.destroy(); revChart = null; }
                            revChart = new ApexCharts(document.querySelector('#rtRevenueChart'), opts);
                            revChart.render();
                        }

                        function renderOccupancy(labels, data){
                            var opts = {
                                chart: { type: 'bar', height: 110, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'Inter, sans-serif' },
                                series: [{ name: 'Occupancy %', data: data }],
                                xaxis: { categories: labels, labels: { show: false }, axisBorder:{show:false}, axisTicks:{show:false} },
                                yaxis: { max: 100, labels: { formatter: function(v){ return Math.round(v) + '%'; }, style:{ fontSize:'10px', colors:'#94a3b8' } } },
                                colors: ['#0ea5e9'],
                                plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
                                dataLabels: { enabled: false },
                                grid: { show: false, padding: { left: 0, right: 0, top: 0, bottom: 0 } },
                                tooltip: { y: { formatter: function(v){ return v + '%'; } }, x: { formatter: function(_,o){ return labels[o.dataPointIndex]; } } },
                            };
                            if (occChart) { occChart.updateOptions(opts, true, true); }
                            else { occChart = new ApexCharts(document.querySelector('#rtOccupancyChart'), opts); occChart.render(); }
                        }

                        window.rtSetRange = function(range, btn){
                            if (loading || range === currentRange) return;
                            currentRange = range;
                            document.querySelectorAll('[data-rt-range]').forEach(function(b){
                                if (b.dataset.rtRange === range) {
                                    b.style.background = '#10b981'; b.style.color = '#fff'; b.style.borderColor = '#10b981';
                                } else {
                                    b.style.background = '#fff'; b.style.color = '#475569'; b.style.borderColor = '#cbd5e1';
                                }
                            });
                            var labelMap = { '7d':'Last 7 days', '30d':'Last 30 days', '90d':'Last 90 days', '12m':'Last 12 months' };
                            document.getElementById('rtRangeLabel').innerText = labelMap[range] || '';
                            loadRT();
                        };

                        function rangeLabel(range){
                            return ({ '7d':'prior 7d', '30d':'prior 30d', '90d':'prior 90d', '12m':'prior 12m' })[range] || 'prior period';
                        }

                        function setDelta(elId, deltaPct, prevValue){
                            var el = document.getElementById(elId);
                            if (!el) return;
                            if (deltaPct === null || typeof deltaPct === 'undefined') {
                                el.innerHTML =
                                    '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:6px;background:#ecfdf5;color:#10b981;font-size:11px;font-weight:800;">▲ New</span>' +
                                    '<span style="color:#94a3b8;font-weight:600;margin-left:6px;">vs ' + rangeLabel(currentRange) + ' (₹0)</span>';
                                return;
                            }
                            var up = deltaPct > 0, down = deltaPct < 0;
                            var color = up ? '#10b981' : (down ? '#ef4444' : '#64748b');
                            var bg    = up ? '#ecfdf5' : (down ? '#fef2f2' : '#f1f5f9');
                            var arrow = up ? '▲' : (down ? '▼' : '■');
                            var sign  = up ? '+' : '';
                            el.innerHTML =
                                '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:6px;background:' + bg + ';color:' + color + ';font-size:11px;font-weight:800;">' +
                                    arrow + ' ' + sign + deltaPct.toFixed(1) + '%' +
                                '</span>' +
                                '<span style="color:#94a3b8;font-weight:600;margin-left:6px;">vs ' + rangeLabel(currentRange) + ' (' + fmtINR(prevValue) + ')</span>';
                        }

                        function loadRT(){
                            loading = true;
                            fetch(endpoint + '?range=' + currentRange, { credentials: 'same-origin' })
                                .then(function(r){ return r.ok ? r.json() : null; })
                                .then(function(d){
                                    loading = false;
                                    if (!d) return;
                                    document.getElementById('rtTotal').innerText = fmtINR(d.total);
                                    document.getElementById('rtAvg').innerText   = fmtINR(d.avg);
                                    document.getElementById('rtPeak').innerText  = fmtINR(d.peak);
                                    setDelta('rtTotalDelta', d.delta_total, d.prev_total);
                                    setDelta('rtAvgDelta',   d.delta_avg,   d.prev_avg);
                                    setDelta('rtPeakDelta',  d.delta_peak,  d.prev_peak);
                                    ensureApex(function(){
                                        renderRevenue(d.labels, d.revenue, d.prev_revenue);
                                        renderOccupancy(d.labels, d.occupancy);
                                    });
                                })
                                .catch(function(){ loading = false; });
                        }

                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', loadRT);
                        } else { loadRT(); }
                    })();
                    </script>

                    {{-- Shortcuts + Quick Actions — side-by-side ──────────────────────── --}}
                    <div data-widget="shortcuts-actions-pair" class="db-widget-wrap">
                    <div class="shortcuts-actions-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

                        {{-- Left: Shortcuts --}}
                        @if(count($dashboardShortcuts) > 0)
                        <div class="db-card">
                            <div class="db-card-title" style="margin-bottom:14px;">Shortcuts</div>
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                @foreach($dashboardShortcuts as $shortcut)
                                <a href="{{ $shortcut['route'] }}" class="qa-btn" style="background:#f8fafc;" onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='#f8fafc'">
                                    <div style="position:relative;width:42px;height:42px;flex-shrink:0;">
                                        <div style="width:42px;height:42px;background:{{ $shortcut['bg'] }};border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,0,0,.15);">
                                            <i class="fas {{ $shortcut['icon'] }}" style="color:#fff;font-size:14px;"></i>
                                        </div>
                                        @if(!empty($shortcut['badge']) && $shortcut['badge'] > 0)
                                        <span style="position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:#dc2626;border-radius:999px;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;color:#fff;padding:0 3px;line-height:1;animation:dirty-badge-pop 1s ease-in-out infinite;">{{ $shortcut['badge'] }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $shortcut['title'] }}</div>
                                        <div style="font-size:12px;color:#94a3b8;">{{ $shortcut['sub'] }}</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Right: Quick Actions --}}
                        @if(!$isRestaurantOnly)
                        <div class="db-card">
                            <div class="db-card-title" style="margin-bottom:14px;">Quick Actions</div>
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                @canDo('bookings.create')
                                <a href="{{ route('bookings.create') }}" class="qa-btn" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);" onmouseenter="this.style.background='linear-gradient(135deg,#dbeafe,#bfdbfe)'" onmouseleave="this.style.background='linear-gradient(135deg,#eff6ff,#dbeafe)'">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(59,130,246,.3);flex-shrink:0;">
                                        <i class="fas fa-plus" style="color:#fff;font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#1e40af;font-size:14px;">New Booking</div>
                                        <div style="font-size:12px;color:#93c5fd;">Create reservation</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#93c5fd;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endCanDo
                                @canDo('checkin.process')
                                <a href="{{ route('checkin.index') }}" class="qa-btn" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);" onmouseenter="this.style.background='linear-gradient(135deg,#dcfce7,#bbf7d0)'" onmouseleave="this.style.background='linear-gradient(135deg,#f0fdf4,#dcfce7)'">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(16,185,129,.3);flex-shrink:0;">
                                        <i class="fas fa-sign-in-alt" style="color:#fff;font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#065f46;font-size:14px;">Process Check-In</div>
                                        <div style="font-size:12px;color:#6ee7b7;">{{ $todayCheckins->count() }} pending</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#6ee7b7;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endCanDo
                                @canDo('checkout.process')
                                <a href="{{ route('checkout.index') }}" class="qa-btn" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);" onmouseenter="this.style.background='linear-gradient(135deg,#fef3c7,#fde68a)'" onmouseleave="this.style.background='linear-gradient(135deg,#fffbeb,#fef3c7)'">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(245,158,11,.3);flex-shrink:0;">
                                        <i class="fas fa-sign-out-alt" style="color:#fff;font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#92400e;font-size:14px;">Process Check-Out</div>
                                        <div style="font-size:12px;color:#fcd34d;">{{ $todayCheckouts->count() }} pending</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#fcd34d;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endCanDo
                                @canDo('guests.create')
                                <a href="{{ route('customers.create') }}" class="qa-btn" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);" onmouseenter="this.style.background='linear-gradient(135deg,#ede9fe,#ddd6fe)'" onmouseleave="this.style.background='linear-gradient(135deg,#faf5ff,#ede9fe)'">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(139,92,246,.3);flex-shrink:0;">
                                        <i class="fas fa-user-plus" style="color:#fff;font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#4c1d95;font-size:14px;">Add Guest</div>
                                        <div style="font-size:12px;color:#c4b5fd;">New guest profile</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#c4b5fd;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endCanDo
                                @if(\App\Models\Module::isEnabled('extra-billing'))
                                @canDo('bookings.view')
                                <a href="{{ route('bookings.index', ['status' => 'checked_in']) }}" class="qa-btn" style="background:linear-gradient(135deg,#fff1f2,#ffe4e6);" onmouseenter="this.style.background='linear-gradient(135deg,#ffe4e6,#fecdd3)'" onmouseleave="this.style.background='linear-gradient(135deg,#fff1f2,#ffe4e6)'">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#f43f5e,#e11d48);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(244,63,94,.3);flex-shrink:0;">
                                        <i class="fas fa-receipt" style="color:#fff;font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:#9f1239;font-size:14px;">Post Room Charge</div>
                                        <div style="font-size:12px;color:#fda4af;">In-house guests</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="color:#fda4af;font-size:11px;margin-left:auto;"></i>
                                </a>
                                @endCanDo
                                @endif
                            </div>
                        </div>
                        @endif

                    </div>
                    </div>{{-- /shortcuts-actions-pair widget --}}

                    {{-- Slot Availability Widget --}}
                    @if(!$isRestaurantOnly)
                    @canDo('reports.view')
                    <div data-widget="slot-availability" class="db-widget-wrap">
                    @if($hasSlotModule)
                    {{-- Slot Banner — above the week grid --}}
                    <a href="{{ route('reports.slot_availability') }}"
                       style="display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#4c1d95,#6d28d9,#7c3aed);border-radius:16px;padding:18px 22px;margin-bottom:14px;text-decoration:none;position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s;box-shadow:0 8px 28px rgba(109,40,217,.35);"
                       onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 14px 36px rgba(109,40,217,.45)'"
                       onmouseleave="this.style.transform='';this.style.boxShadow='0 8px 28px rgba(109,40,217,.35)'">
                        <div style="position:absolute;right:-20px;top:-20px;width:100px;height:100px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;"></div>
                        <div style="position:absolute;right:40px;bottom:-30px;width:70px;height:70px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none;"></div>
                        <div style="width:50px;height:50px;background:rgba(255,255,255,.18);backdrop-filter:blur(4px);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(255,255,255,.25);">
                            <i class="fas fa-calendar-check" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:16px;font-weight:800;color:#fff;letter-spacing:-.2px;">Slot Availability</div>
                            <div style="font-size:12px;color:rgba(255,255,255,.75);margin-top:3px;">Check real-time room slot status</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                            <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.15);border-radius:20px;padding:4px 10px;font-size:11px;font-weight:700;color:#fff;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#4ade80;display:inline-block;animation:qaSlotPulse 1.6s ease-in-out infinite;"></span>Live
                            </span>
                            <i class="fas fa-arrow-right" style="color:rgba(255,255,255,.7);font-size:13px;"></i>
                        </div>
                    </a>
                    <style>@keyframes qaSlotPulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.3);}}</style>
                    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
                        <div style="padding:16px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f5f3ff,#ede9fe);flex-wrap:wrap;gap:12px;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <div style="width:42px;height:42px;background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(124,58,237,.3);">
                                    <i class="fas fa-clock" style="color:#fff;font-size:16px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#1e293b;font-size:16px;">Slot Availability — This Week</div>
                                    <div style="font-size:12px;color:#6d28d9;">{{ $slotWeekStart->format('d M') }} – {{ $slotWeekStart->copy()->addDays(6)->format('d M Y') }}</div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                {{-- Week navigation --}}
                                <a href="{{ route('dashboard', array_merge(request()->query(), ['slot_week'=>$slotWeekStart->copy()->subWeek()->toDateString()])) }}"
                                   style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #ddd6fe;color:#7c3aed;text-decoration:none;background:#fff;transition:all .15s;" onmouseenter="this.style.background='#f5f3ff'" onmouseleave="this.style.background='#fff'">
                                    <i class="fas fa-chevron-left" style="font-size:11px;"></i>
                                </a>
                                <a href="{{ route('dashboard', array_merge(request()->except('slot_week'), [])) }}"
                                   style="padding:0 12px;height:34px;display:flex;align-items:center;border-radius:10px;border:1px solid #ddd6fe;color:#7c3aed;font-size:13px;font-weight:600;text-decoration:none;background:#fff;transition:all .15s;" onmouseenter="this.style.background='#f5f3ff'" onmouseleave="this.style.background='#fff'">This Week</a>
                                <a href="{{ route('dashboard', array_merge(request()->query(), ['slot_week'=>$slotWeekStart->copy()->addWeek()->toDateString()])) }}"
                                   style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #ddd6fe;color:#7c3aed;text-decoration:none;background:#fff;transition:all .15s;" onmouseenter="this.style.background='#f5f3ff'" onmouseleave="this.style.background='#fff'">
                                    <i class="fas fa-chevron-right" style="font-size:11px;"></i>
                                </a>
                                <a href="{{ route('reports.slot_availability') }}"
                                   style="padding:0 14px;height:34px;display:flex;align-items:center;gap:6px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 3px 8px rgba(124,58,237,.3);">
                                    <i class="fas fa-external-link-alt" style="font-size:10px;"></i> Full Report
                                </a>
                            </div>
                        </div>
                        <div style="padding:20px;overflow-x:auto;">
                            <table style="width:100%;border-collapse:collapse;min-width:520px;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;padding:8px 12px;font-size:12px;font-weight:700;color:#64748b;white-space:nowrap;border-bottom:2px solid #f1f5f9;">Time Slot</th>
                                        @foreach($dashboardSlotAvailability as $day)
                                        <th style="text-align:center;padding:8px 8px;font-size:12px;font-weight:700;color:{{ $day['isToday'] ? '#7c3aed' : '#64748b' }};white-space:nowrap;border-bottom:2px solid {{ $day['isToday'] ? '#a78bfa' : '#f1f5f9' }};background:{{ $day['isToday'] ? 'linear-gradient(180deg,#f5f3ff,transparent)' : 'transparent' }};">
                                            <div>{{ $day['label'] }}</div>
                                            <div style="font-size:10px;font-weight:500;color:{{ $day['isToday'] ? '#8b5cf6' : '#94a3b8' }};">{{ $day['sublabel'] }}</div>
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboardSlots as $slotIdx => $slot)
                                    <tr style="border-bottom:1px solid #f8fafc;">
                                        <td style="padding:10px 12px;white-space:nowrap;">
                                            <div style="font-weight:700;color:#1e293b;font-size:13px;">{{ $slot->name }}</div>
                                            <div style="font-size:11px;color:#94a3b8;">{{ $slot->start_time }}–{{ $slot->end_time }}</div>
                                        </td>
                                        @foreach($dashboardSlotAvailability as $day)
                                        @php
                                            $sd = $day['slots'][$slotIdx] ?? null;
                                            $sdColor = $sd ? $sd['color'] : 'green';
                                            $sdPct = $sd ? $sd['pct'] : 0;
                                            $bgMap = ['green'=>'#f0fdf4','amber'=>'#fffbeb','red'=>'#fff1f2'];
                                            $txtMap = ['green'=>'#16a34a','amber'=>'#d97706','red'=>'#dc2626'];
                                            $barMap = ['green'=>'#22c55e','amber'=>'#f59e0b','red'=>'#ef4444'];
                                        @endphp
                                        <td style="padding:6px 4px;text-align:center;background:{{ $day['isToday'] ? '#faf5ff' : 'transparent' }};">
                                            @if($sd)
                                            @php
                                                $bookedRooms = $sd['booked_rooms'] ?? [];
                                                $freeRooms   = $sd['free_rooms']   ?? [];
                                            @endphp
                                            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;min-width:70px;">
                                                {{-- Count badge --}}
                                                <span style="font-weight:800;color:{{ $txtMap[$sdColor] }};font-size:13px;line-height:1;background:{{ $bgMap[$sdColor] }};padding:2px 8px;border-radius:999px;">
                                                    {{ $sd['available'] }}<span style="font-weight:400;color:#94a3b8;font-size:11px;">/{{ $sd['total'] }}</span>
                                                </span>
                                                {{-- Room pills --}}
                                                <div style="display:flex;flex-direction:column;gap:2px;width:100%;">
                                                    @foreach($bookedRooms as $br)
                                                    <div title="{{ $br['guest_name'] ?? 'Guest' }}" style="background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:2px 5px;font-size:10px;line-height:1.3;color:#b91c1c;text-align:left;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:80px;">
                                                        <span style="font-weight:700;">R{{ $br['room_number'] ?? '—' }}</span>
                                                    </div>
                                                    @endforeach
                                                    @foreach($freeRooms as $rn)
                                                    <div style="background:#dcfce7;border:1px solid #86efac;border-radius:6px;padding:2px 5px;font-size:10px;line-height:1.3;color:#15803d;text-align:left;white-space:nowrap;">
                                                        <span style="font-weight:700;">R{{ $rn }}</span> <span style="color:#4ade80;">free</span>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div style="display:flex;align-items:center;gap:16px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Available (&lt;60% booked)</span></div>
                                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Filling up (60–99%)</span></div>
                                <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Fully booked (100%)</span></div>
                                <div style="margin-left:auto;display:flex;gap:10px;align-items:center;">
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#fee2e2;border:1px solid #fca5a5;"></span><span style="font-size:12px;color:#64748b;">Booked room</span>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#dcfce7;border:1px solid #86efac;"></span><span style="font-size:12px;color:#64748b;">Free room</span>
                                </div>
                            </div>

                            {{-- Slot cell hover tooltip --}}
                            <div id="slotCellTooltip" style="display:none;position:fixed;z-index:9999;background:#1e293b;color:#fff;border-radius:12px;padding:12px 14px;font-size:12px;max-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.25);pointer-events:none;line-height:1.5;"></div>
                            <script>
                            (function() {
                                var tip = document.getElementById('slotCellTooltip');
                                document.querySelectorAll('.slot-cell-wrap').forEach(function(el) {
                                    el.addEventListener('mouseenter', function(e) {
                                        var booked = JSON.parse(el.dataset.booked || '[]');
                                        var free   = JSON.parse(el.dataset.free   || '[]');
                                        var slot   = el.dataset.slot;
                                        var day    = el.dataset.day;
                                        var html   = '<div style="font-weight:700;margin-bottom:6px;color:#a78bfa;">' + slot + ' · ' + day + '</div>';
                                        if (booked.length > 0) {
                                            html += '<div style="color:#fca5a5;font-weight:600;margin-bottom:3px;">Booked rooms:</div>';
                                            booked.forEach(function(r) {
                                                html += '<div style="padding:2px 0;color:#fecaca;">&#9679; Room ' + r.room_number + ' — ' + r.guest_name + '</div>';
                                            });
                                        }
                                        if (free.length > 0) {
                                            html += '<div style="color:#86efac;font-weight:600;margin-top:6px;margin-bottom:3px;">Free rooms:</div>';
                                            free.forEach(function(r) {
                                                html += '<div style="padding:2px 0;color:#bbf7d0;">&#9679; Room ' + r + '</div>';
                                            });
                                        }
                                        if (booked.length === 0 && free.length === 0) {
                                            html += '<div style="color:#94a3b8;">No room data</div>';
                                        }
                                        tip.innerHTML = html;
                                        tip.style.display = 'block';
                                        positionTip(e);
                                    });
                                    el.addEventListener('mousemove', positionTip);
                                    el.addEventListener('mouseleave', function() { tip.style.display = 'none'; });
                                });
                                function positionTip(e) {
                                    var x = e.clientX + 14, y = e.clientY + 14;
                                    if (x + 260 > window.innerWidth)  x = e.clientX - 260;
                                    if (y + 200 > window.innerHeight) y = e.clientY - 200;
                                    tip.style.left = x + 'px';
                                    tip.style.top  = y + 'px';
                                }
                            })();
                            </script>
                        </div>
                    </div>
                    @endif
                    </div>{{-- /slot-availability widget --}}
                    @endCanDo
                    @endif

                    {{-- Recent Bookings + Room Availability — side-by-side ──────────────── --}}
                    @if(!$isRestaurantOnly)
                    <div data-widget="recent-room-pair" class="db-widget-wrap">
                    <div class="recent-room-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

                        {{-- Left: Recent Bookings --}}
                        @canDo('bookings.view')
                        <div class="db-card" style="display:flex;flex-direction:column;min-height:240px;">
                            <div class="db-card-header">
                                <span class="db-card-title">Recent Bookings</span>
                                <a href="{{ route('bookings.index') }}" class="db-card-link">View All <i class="fas fa-arrow-right"></i></a>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;flex:1;">
                                @forelse($recentBookings as $booking)
                                <a href="{{ route('bookings.show', $booking->id) }}" class="booking-row">
                                    <div class="booking-avatar">{{ substr($booking->customer?->name ?? 'G', 0, 1) }}</div>
                                    <div class="booking-info">
                                        <div class="booking-name">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                                        <div class="booking-sub">{{ $booking->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($booking->room?->room_number ?? '—')) }} &bull; {{ $booking->check_in_date->format('d M') }} &ndash; {{ $booking->check_out_date->format('d M') }}</div>
                                    </div>
                                    <div class="booking-meta">
                                        @canDo('reports.view')
                                        <div class="booking-amount">₹{{ number_format($booking->total_amount) }}</div>
                                        @endCanDo
                                        <span class="badge-{{ $booking->status_color }}" style="font-size:10px;">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                                    </div>
                                </a>
                                @empty
                                <div style="text-align:center;padding:32px;color:#94a3b8;">
                                    <i class="fas fa-calendar-times" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
                                    <p style="font-size:14px;">No recent bookings</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                        @endCanDo

                        {{-- Right: Room Availability Checker --}}
                        <div class="db-card" style="overflow:hidden;padding:0;">
                            <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-door-open" style="color:#fff;font-size:13px;"></i>
                                    </div>
                                    <div style="font-weight:800;color:#1e293b;font-size:14px;">Room Availability</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="date" id="availDatePicker" value="{{ now()->toDateString() }}"
                                        style="border:1.5px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:13px;color:#1e293b;background:#fff;outline:none;cursor:pointer;"
                                        onchange="loadAvailability(this.value)">
                                    <button onclick="loadAvailability(document.getElementById('availDatePicker').value)"
                                        style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                        <i class="fas fa-search" style="margin-right:5px;"></i>Check
                                    </button>
                                </div>
                            </div>
                            <div id="availBody" style="padding:14px 16px;max-height:340px;overflow-y:auto;">
                                <div style="text-align:center;color:#94a3b8;padding:12px 0;font-size:13px;"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Loading…</div>
                            </div>
                        </div>

                    </div>
                    </div>{{-- /recent-room-pair widget --}}
                    @endif

                    {{-- Booking Calendar --}}
                    @if(!$isRestaurantOnly)
                    @canDo('reports.view')
                    <div data-widget="booking-calendar" class="db-widget-wrap">
                            <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
                            <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <div style="width:42px;height:42px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(6,182,212,.3);">
                                        <i class="fas fa-calendar-alt" style="color:#fff;font-size:16px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;color:#1e293b;font-size:16px;">Booking Calendar</div>
                                        <div style="font-size:12px;color:#64748b;">{{ $calStart->format('F Y') }} — arrivals &amp; departures</div>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <a href="{{ route('dashboard', ['cal_year'=>$prevMonth->year,'cal_month'=>$prevMonth->month]) }}"
                                       style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                                        <i class="fas fa-chevron-left" style="font-size:12px;"></i>
                                    </a>
                                    <a href="{{ route('dashboard') }}"
                                       style="padding:0 14px;height:36px;display:flex;align-items:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">Today</a>
                                    <a href="{{ route('dashboard', ['cal_year'=>$nextMonth->year,'cal_month'=>$nextMonth->month]) }}"
                                       style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                                        <i class="fas fa-chevron-right" style="font-size:12px;"></i>
                                    </a>
                                </div>
                            </div>
                            <div style="padding:20px;">
                                {{-- Day headers --}}
                                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:6px;">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                                    <div style="text-align:center;font-size:12px;font-weight:700;color:#94a3b8;padding:6px 0;letter-spacing:.04em;">{{ $dow }}</div>
                                    @endforeach
                                </div>
                                {{-- Weeks --}}
                                @if(count($calWeeks) > 0)
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    @foreach($calWeeks as $week)
                                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;">
                                        @foreach($week as $cell)
                                        @php
                                            $hasGuests = ($cell['checkins'] + $cell['checkouts'] + $cell['staying']) > 0;
                                            $ttData = $hasGuests ? htmlspecialchars(json_encode([
                                                'date'     => $cell['date']->format('D, d M Y'),
                                                'checkins' => $cell['checkin_guests'],
                                                'checkouts'=> $cell['checkout_guests'],
                                                'staying'  => $cell['staying_guests'],
                                            ]), ENT_QUOTES, 'UTF-8') : '';
                                        @endphp
                                        @php $isWhDay = !empty($cell['whole_hotel']); @endphp
                                        <a href="{{ route('bookings.index', ['check_in_date'=>$cell['ds']]) }}"
                                           class="cal-cell {{ $isWhDay ? 'whole-hotel' : ($cell['isToday'] ? 'today' : ($cell['inMonth'] ? 'in-month' : 'out-month')) }}"
                                           @if($hasGuests) data-cal-guests="{!! $ttData !!}" @endif
                                           data-ds="{{ $cell['ds'] }}"
                                           onclick="event.preventDefault();openDaySummary('{{ $cell['ds'] }}')">
                                            <span class="cal-day-num" style="color:{{ $isWhDay ? '#b91c1c' : ($cell['isToday'] ? '#0891b2' : ($cell['inMonth'] ? '#1e293b' : '#cbd5e1')) }};">{{ $cell['day'] }}</span>
                                            <div style="display:flex;flex-direction:column;gap:3px;margin-top:auto;">
                                                @if($isWhDay)
                                                <div style="display:flex;align-items:center;gap:3px;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                                                    <span style="font-size:10px;color:#b91c1c;font-weight:700;line-height:1;">Whole Hotel</span>
                                                </div>
                                                @else
                                                @if($cell['checkins'] > 0)
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#06b6d4;flex-shrink:0;"></span>
                                                    <span style="font-size:11px;color:#0891b2;font-weight:700;line-height:1;">{{ $cell['checkins'] }} in</span>
                                                </div>
                                                @endif
                                                @if($cell['checkouts'] > 0)
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></span>
                                                    <span style="font-size:11px;color:#b45309;font-weight:700;line-height:1;">{{ $cell['checkouts'] }} out</span>
                                                </div>
                                                @endif
                                                @if($cell['staying'] > 0)
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0;"></span>
                                                    <span style="font-size:11px;color:#047857;font-weight:700;line-height:1;">{{ $cell['staying'] }} stay</span>
                                                </div>
                                                @endif
                                                @endif
                                            </div>
                                        </a>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div style="text-align:center;padding:32px;color:#94a3b8;font-size:14px;">Calendar unavailable</div>
                                @endif
                                <div style="display:flex;align-items:center;gap:20px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                                    <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Check-in</span></div>
                                    <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Check-out</span></div>
                                    <div style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">In-house</span></div>
                                    <div style="display:flex;align-items:center;gap:6px;margin-left:auto;"><span style="width:10px;height:10px;border-radius:50%;border:2px solid #22d3ee;background:#ecfeff;display:inline-block;"></span><span style="font-size:12px;color:#64748b;">Today</span></div>
                                </div>
                            </div>
                        </div>
                    </div>{{-- /booking-calendar widget --}}
                    @endCanDo
                    @endif

                    @if(!$isRestaurantOnly)
                    {{-- Today's Arrivals & Departures (always render for customisation) --}}
                    <div data-widget="arrivals-departures" class="db-widget-wrap">
                    @if($todayCheckins->count() > 0 || $todayCheckouts->count() > 0)
                    <div class="arrivals-grid">
                        @if($todayCheckins->count() > 0)
                        <div class="db-card">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                                <div style="width:38px;height:38px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-sign-in-alt" style="color:#fff;font-size:14px;"></i>
                                </div>
                                <div style="font-weight:800;color:#1e293b;font-size:15px;">Today's Arrivals ({{ $todayCheckins->count() }})</div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @foreach($todayCheckins as $booking)
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#ecfeff,#e0f2fe);border-radius:12px;">
                                    <div>
                                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                                        <div style="font-size:12px;color:#64748b;">{{ $booking->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($booking->room?->room_number ?? '—')) }} &bull; {{ $booking->nights }} night(s)</div>
                                    </div>
                                    @canDo('checkin.process')
                                    <a href="{{ route('checkin.show', $booking->id) }}" style="background:linear-gradient(135deg,#06b6d4,#0891b2);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(6,182,212,.3);">Check In</a>
                                    @endCanDo
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($todayCheckouts->count() > 0)
                        <div class="db-card">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                                <div style="width:38px;height:38px;background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-sign-out-alt" style="color:#fff;font-size:14px;"></i>
                                </div>
                                <div style="font-weight:800;color:#1e293b;font-size:15px;">Today's Departures ({{ $todayCheckouts->count() }})</div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @foreach($todayCheckouts as $booking)
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-radius:12px;">
                                    <div>
                                        <div style="font-weight:700;color:#1e293b;font-size:14px;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                                        <div style="font-size:12px;color:#64748b;">{{ $booking->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($booking->room?->room_number ?? '—')) }}
                                            @canDo('reports.view') &bull; Due: ₹{{ number_format($booking->balance_due) }} @endCanDo
                                        </div>
                                    </div>
                                    @canDo('checkout.process')
                                    <a href="{{ route('checkout.show', $booking->id) }}" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:12px;padding:7px 16px;border-radius:10px;text-decoration:none;font-weight:700;box-shadow:0 3px 8px rgba(245,158,11,.3);">Check Out</a>
                                    @endCanDo
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    </div>{{-- /arrivals-departures widget --}}
                    @endif



                </div>{{-- /dashboard-main --}}

                {{-- Mobile responsiveness --}}
                <style>
                @media (max-width: 1024px) {
                    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
                    .occ-rev-grid { grid-template-columns: 1fr !important; }
                    .qa-recent-grid { grid-template-columns: 1fr !important; }
                    .avail-grid { grid-template-columns: 1fr !important; }
                    .recent-room-grid { grid-template-columns: 1fr !important; }
                    .shortcuts-actions-grid { grid-template-columns: 1fr !important; }
                }
                @media (max-width: 600px) {
                    .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
                    .kpi-num { font-size: 1.7rem !important; }
                    .cal-cell { min-height: 60px !important; }
                    .cal-day-num { font-size: .85rem !important; }
                }
                </style>

                {{-- Calendar tooltip --}}
                <div id="calTooltip"></div>

                <script>
                (function() {
                    var tooltip = document.getElementById('calTooltip');
                    var hideTimer = null;

                    function buildHtml(data) {
                        var html = '<div class="tt-date">' + data.date + '</div>';
                        var sections = [
                            { key: 'checkins',  label: 'Check-In',  color: '#06b6d4' },
                            { key: 'checkouts', label: 'Check-Out', color: '#f59e0b' },
                            { key: 'staying',   label: 'In-House',  color: '#10b981' },
                        ];
                        sections.forEach(function(s) {
                            var guests = data[s.key];
                            if (!guests || guests.length === 0) return;
                            html += '<div class="tt-section" style="color:' + s.color + ';">' + s.label + ' (' + guests.length + ')</div>';
                            guests.forEach(function(g) {
                                html += '<div class="tt-row">' +
                                    '<span class="tt-dot" style="background:' + s.color + ';"></span>' +
                                    '<span class="tt-name">' + g.name + '</span>' +
                                    '<span class="tt-room">Rm ' + g.room + '</span>' +
                                '</div>';
                            });
                        });
                        return html;
                    }

                    function positionTooltip(e) {
                        var vw = window.innerWidth, vh = window.innerHeight;
                        var tw = tooltip.offsetWidth || 220, th = tooltip.offsetHeight || 120;
                        var x = e.clientX + 14, y = e.clientY + 14;
                        if (x + tw > vw - 10) x = e.clientX - tw - 10;
                        if (y + th > vh - 10) y = e.clientY - th - 10;
                        tooltip.style.left = x + 'px';
                        tooltip.style.top  = y + 'px';
                    }

                    document.querySelectorAll('[data-cal-guests]').forEach(function(cell) {
                        var data = null;
                        try { data = JSON.parse(cell.getAttribute('data-cal-guests')); } catch(e) {}
                        if (!data) return;

                        cell.addEventListener('mouseenter', function(e) {
                            clearTimeout(hideTimer);
                            tooltip.innerHTML = buildHtml(data);
                            positionTooltip(e);
                            tooltip.classList.add('visible');
                        });
                        cell.addEventListener('mousemove', positionTooltip);
                        cell.addEventListener('mouseleave', function() {
                            hideTimer = setTimeout(function() { tooltip.classList.remove('visible'); }, 80);
                        });
                    });
                })();
                </script>

                {{-- Count-up animation --}}
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('[data-count]').forEach(function(el) {
                        var target = parseFloat(el.getAttribute('data-count')) || 0;
                        var fmt = el.getAttribute('data-format');
                        var prefix = el.getAttribute('data-prefix') || '';
                        var duration = 900;
                        var start = performance.now();
                        function update(now) {
                            var elapsed = now - start;
                            var progress = Math.min(elapsed / duration, 1);
                            var ease = 1 - Math.pow(1 - progress, 3);
                            var val = Math.round(target * ease);
                            if (fmt === 'currency') {
                                el.textContent = prefix + val.toLocaleString('en-IN');
                            } else {
                                el.textContent = prefix + val.toLocaleString('en-IN');
                            }
                            if (progress < 1) requestAnimationFrame(update);
                        }
                        requestAnimationFrame(update);
                    });
                });
                </script>

                {{-- Day Summary Modal --}}
                <div id="daySummaryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);" onclick="if(event.target===this)closeDaySummary()">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-t-2xl">
                            <div>
                                <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-day text-cyan-500 mr-2"></i><span id="dsmDate">—</span></h3>
                                <p class="text-xs text-gray-400 mt-0.5">Booking activity for this day</p>
                            </div>
                            <button onclick="closeDaySummary()" class="text-gray-400 hover:text-gray-600 w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                        <div id="dsmBody" class="overflow-y-auto flex-1 p-5 space-y-4">
                            <div class="text-center py-8"><i class="fas fa-spinner fa-spin text-cyan-400 text-2xl"></i></div>
                        </div>
                    </div>
                </div>
                <script>
                var availabilityRoute = '{{ route("dashboard.availability") }}';
                var bookingsCreateRoute = '{{ route("bookings.create") }}';

                function openDaySummary(ds) {
                    document.getElementById('daySummaryModal').classList.remove('hidden');
                    document.getElementById('dsmDate').textContent = '...';
                    document.getElementById('dsmBody').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-cyan-400 text-2xl"></i></div>';

                    var summaryPromise = fetch('{{ route("calendar.day_summary") }}?date=' + ds, { headers: {'X-Requested-With':'XMLHttpRequest'} }).then(r => r.json());
                    var availPromise   = fetch(availabilityRoute + '?date=' + ds, { headers: {'X-Requested-With':'XMLHttpRequest'} }).then(r => r.json());

                    Promise.all([summaryPromise, availPromise])
                        .then(([data, avail]) => {
                            document.getElementById('dsmDate').textContent = data.date || ds;
                            let html = '';
                            const section = (title, color, icon, items) => {
                                if (!items || !items.length) return '';
                                let rows = items.map(b => `
                                    <a href="${b.url}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors text-sm">
                                        <span class="w-8 h-8 rounded-full bg-${color}-100 flex items-center justify-center text-${color}-600 flex-shrink-0">
                                            <i class="fas fa-door-open text-xs"></i>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-800 truncate">${b.guest}</div>
                                            <div class="text-xs text-gray-400">Room ${b.room} · ${b.type}${b.time_slot ? ' · <span class="text-violet-600 font-medium">'+b.time_slot+'</span>' : ''}</div>
                                        </div>
                                        <span class="text-xs bg-${color}-50 text-${color}-700 rounded-full px-2 py-0.5 capitalize font-medium">${b.status.replace('_',' ')}</span>
                                    </a>`).join('');
                                return `<div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-${color}-500 flex-shrink-0"></span>
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">${title} (${items.length})</span>
                                    </div>
                                    <div class="space-y-1">${rows}</div>
                                </div>`;
                            };
                            html += section('Check-Ins',  'cyan',  'sign-in-alt', data.checkins);
                            html += section('Check-Outs', 'amber', 'sign-out-alt',data.checkouts);
                            html += section('In-House',   'green', 'home',        data.staying);

                            if (avail.available && avail.available.length > 0) {
                                let pricingLabel = pt => pt === 'per_slot' ? 'Slot' : (pt === 'per_hour' ? 'Hourly' : 'Nightly');
                                let availRows = avail.available.map(r => `
                                    <a href="${r.booking_url}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50 transition-colors text-sm">
                                        <span class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                            <i class="fas fa-door-open text-xs"></i>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-800">Room ${r.room_number}</div>
                                            <div class="text-xs text-gray-400">${r.type} · ${pricingLabel(r.pricing_type)}</div>
                                        </div>
                                        <span class="text-xs bg-emerald-50 text-emerald-700 rounded-full px-2 py-0.5 font-medium">Book →</span>
                                    </a>`).join('');
                                if (html) html += '<div class="border-t border-gray-100 my-2"></div>';
                                html += `<div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Available Rooms (${avail.available.length})</span>
                                    </div>
                                    <div class="space-y-1">${availRows}</div>
                                </div>`;
                            }

                            document.getElementById('dsmBody').innerHTML = html || '<p class="text-center text-gray-400 py-6 text-sm">No bookings for this day.</p>';
                        })
                        .catch(() => {
                            document.getElementById('dsmBody').innerHTML = '<p class="text-center text-red-400 py-6 text-sm">Failed to load data.</p>';
                        });
                }
                function closeDaySummary() { document.getElementById('daySummaryModal').classList.add('hidden'); }
                document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDaySummary(); });
                </script>

                <script>
                function loadAvailability(date) {
                    var body = document.getElementById('availBody');
                    body.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:12px 0;font-size:13px;"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Loading…</div>';
                    fetch(availabilityRoute + '?date=' + date, { headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(r => r.json())
                        .then(data => {
                            if (data.error) { body.innerHTML = '<p style="text-align:center;color:#ef4444;padding:12px 0;font-size:13px;">'+data.error+'</p>'; return; }
                            var avail = data.available || [], occ = data.occupied || [], dirty = data.dirty || [];
                            var pricingLabel = function(pt) { return pt === 'per_slot' ? 'Slot' : (pt === 'per_hour' ? 'Hourly' : 'Nightly'); };

                            if (avail.length === 0 && occ.length === 0 && dirty.length === 0) {
                                body.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:12px 0;font-size:13px;">No rooms found.</p>';
                                return;
                            }

                            /* ── summary bar ── */
                            var summary = '<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">';
                            summary += '<span style="display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#065f46;">'
                                     + '<span style="width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;"></span>'
                                     + avail.length + ' Available</span>';
                            summary += '<span style="display:inline-flex;align-items:center;gap:5px;background:#fff1f2;border:1px solid #fecdd3;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#9f1239;">'
                                     + '<span style="width:7px;height:7px;border-radius:50%;background:#f43f5e;display:inline-block;"></span>'
                                     + occ.length + ' Occupied</span>';
                            if (dirty.length > 0) {
                                summary += '<span style="display:inline-flex;align-items:center;gap:5px;background:#fff7ed;border:1px solid #fed7aa;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#c2410c;">'
                                         + '<span style="width:7px;height:7px;border-radius:50%;background:#f97316;display:inline-block;"></span>'
                                         + dirty.length + ' Needs Cleaning</span>';
                            }
                            summary += '</div>';

                            /* ── chip builder ── */
                            var chipWrap = function(chips) {
                                return '<div style="display:flex;flex-wrap:wrap;gap:6px;">' + chips + '</div>';
                            };

                            /* ── available chips ── */
                            var availChips = '';
                            avail.forEach(function(r) {
                                availChips += '<a href="' + r.booking_url + '" title="' + r.type + ' · ' + pricingLabel(r.pricing_type) + ' — click to book"'
                                    + ' style="display:inline-flex;align-items:center;gap:6px;padding:5px 11px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:20px;text-decoration:none;font-size:12px;font-weight:700;color:#065f46;transition:all .15s;white-space:nowrap;"'
                                    + ' onmouseenter="this.style.background=\'linear-gradient(135deg,#dcfce7,#bbf7d0)\';this.style.boxShadow=\'0 2px 8px rgba(16,185,129,.2)\'"'
                                    + ' onmouseleave="this.style.background=\'linear-gradient(135deg,#f0fdf4,#dcfce7)\';this.style.boxShadow=\'none\'">'
                                    + '<i class="fas fa-door-open" style="font-size:10px;color:#10b981;"></i>'
                                    + '<span>' + r.room_number + '</span>'
                                    + '<span style="font-size:10px;color:#6ee7b7;font-weight:500;">' + r.type + '</span>'
                                    + '</a>';
                            });

                            /* ── occupied chips ── */
                            var occChips = '';
                            occ.forEach(function(r) {
                                var isIn = r.status === 'checked_in';
                                occChips += '<a href="' + r.booking_url + '" title="' + r.guest + ' · ' + r.type + ' · ' + r.status.replace('_',' ') + '"'
                                    + ' style="display:inline-flex;align-items:center;gap:6px;padding:5px 11px;background:linear-gradient(135deg,#fff1f2,#ffe4e6);border:1px solid #fecdd3;border-radius:20px;text-decoration:none;font-size:12px;font-weight:700;color:#9f1239;transition:all .15s;white-space:nowrap;"'
                                    + ' onmouseenter="this.style.background=\'linear-gradient(135deg,#ffe4e6,#fecdd3)\';this.style.boxShadow=\'0 2px 8px rgba(244,63,94,.2)\'"'
                                    + ' onmouseleave="this.style.background=\'linear-gradient(135deg,#fff1f2,#ffe4e6)\';this.style.boxShadow=\'none\'">'
                                    + '<i class="fas fa-bed" style="font-size:10px;color:#f43f5e;"></i>'
                                    + '<span>' + r.room_number + '</span>'
                                    + '<span style="font-size:10px;color:#fda4af;font-weight:500;">' + (r.guest ? r.guest.split(' ')[0] : r.type) + '</span>'
                                    + (isIn ? '<span style="width:5px;height:5px;border-radius:50%;background:#10b981;display:inline-block;flex-shrink:0;" title="Checked In"></span>' : '')
                                    + '</a>';
                            });

                            var html = summary;
                            if (avail.length > 0) {
                                html += '<div style="margin-bottom:10px;">'
                                      + '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Available — tap to book</div>'
                                      + chipWrap(availChips)
                                      + '</div>';
                            }
                            if (occ.length > 0) {
                                html += '<div>'
                                      + '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Occupied</div>'
                                      + chipWrap(occChips)
                                      + '</div>';
                            }

                            /* ── dirty chips ── */
                            if (dirty.length > 0) {
                                var dirtyChips = '';
                                dirty.forEach(function(r) {
                                    dirtyChips += '<span title="' + r.room_number + ' — Needs Cleaning"'
                                        + ' style="display:inline-flex;align-items:center;gap:6px;padding:5px 11px;background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:20px;font-size:12px;font-weight:700;color:#c2410c;white-space:nowrap;">'
                                        + '<i class="fas fa-broom" style="font-size:10px;color:#f97316;"></i>'
                                        + '<span>' + r.room_number + '</span>'
                                        + '<span style="font-size:10px;color:#fb923c;font-weight:500;">' + r.type + '</span>'
                                        + '</span>';
                                });
                                html += '<div style="margin-top:10px;">'
                                      + '<div style="font-size:11px;font-weight:700;color:#c2410c;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">🧹 Needs Cleaning</div>'
                                      + chipWrap(dirtyChips)
                                      + '</div>';
                            }

                            body.innerHTML = html;
                        })
                        .catch(function() {
                            body.innerHTML = '<p style="text-align:center;color:#ef4444;padding:12px 0;font-size:13px;">Failed to load availability.</p>';
                        });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    var _avPicker = document.getElementById('availDatePicker');
                    if (_avPicker) loadAvailability(_avPicker.value);
                });
                </script>

                {{-- ⚙ Dashboard Preferences JS ─────────────────────────────── --}}
                <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
                <script>
                (function() {
                    var SAVE_URL         = '{{ route("dashboard.preferences.save") }}';
                    var SAVE_DEFAULT_URL = '{{ route("dashboard.preferences.save_default") }}';
                    var RESET_URL        = '{{ route("dashboard.preferences.reset") }}';
                    var CSRF             = '{{ csrf_token() }}';

                    var savedOrder   = @json($dashWidgetOrder);
                    var savedHidden  = @json($dashHiddenWidgets);
                    var saveTimer    = null;
                    var sortable     = null;

                    // ── Apply stored order & visibility on page load ──────────────
                    document.addEventListener('DOMContentLoaded', function() {
                        applyPreferences(savedOrder, savedHidden);
                    });

                    function applyPreferences(order, hidden) {
                        var main = document.getElementById('dbMain');
                        if (!main) return;

                        // Reorder: move widgets to match saved order
                        if (order && order.length) {
                            order.slice().reverse().forEach(function(key) {
                                var el = main.querySelector('[data-widget="' + key + '"]');
                                if (el) main.insertBefore(el, main.firstChild);
                            });
                        }

                        // Show/hide
                        main.querySelectorAll('[data-widget]').forEach(function(el) {
                            var k = el.getAttribute('data-widget');
                            if (hidden.indexOf(k) !== -1) {
                                el.classList.add('db-widget-hidden');
                            } else {
                                el.classList.remove('db-widget-hidden');
                            }
                        });
                    }

                    // ── Toggle customize panel ────────────────────────────────────
                    window.dbToggleCustomize = function() {
                        var panel = document.getElementById('dbCustomizePanel');
                        var open = panel.classList.toggle('open');
                        var btn  = document.getElementById('dbCustomizeBtn');
                        if (btn) {
                            btn.style.background = open ? '#f0f9ff' : '#fff';
                            btn.style.borderColor = open ? '#7dd3fc' : '#e2e8f0';
                            btn.style.color = open ? '#0284c7' : '#475569';
                        }
                        if (open && !sortable) {
                            initSortable();
                        }
                    };

                    // ── Init SortableJS on the widget list ────────────────────────
                    function initSortable() {
                        var list = document.getElementById('dbWidgetList');
                        if (!list || typeof Sortable === 'undefined') return;
                        sortable = new Sortable(list, {
                            handle: '.db-drag-handle',
                            animation: 180,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            onEnd: function() {
                                syncOrderFromPanel();
                                savePrefs(); // immediate save on drag end
                            }
                        });
                    }

                    // ── Toggle individual widget visibility ───────────────────────
                    window.dbToggleWidget = function(key, btn) {
                        var main   = document.getElementById('dbMain');
                        var widget = main ? main.querySelector('[data-widget="' + key + '"]') : null;
                        var isOn   = btn.classList.toggle('on');
                        if (widget) {
                            widget.classList.toggle('db-widget-hidden', !isOn);
                        }
                        btn.title = isOn ? 'Click to hide' : 'Click to show';
                        scheduleSave();
                    };

                    // ── Read current order from panel list ────────────────────────
                    function syncOrderFromPanel() {
                        var list = document.getElementById('dbWidgetList');
                        if (!list) return;
                        var order = [];
                        list.querySelectorAll('[data-widget-key]').forEach(function(item) {
                            order.push(item.getAttribute('data-widget-key'));
                        });
                        // Also reorder actual dashboard widgets to match
                        var main = document.getElementById('dbMain');
                        if (main && order.length) {
                            order.slice().reverse().forEach(function(key) {
                                var el = main.querySelector('[data-widget="' + key + '"]');
                                if (el) main.insertBefore(el, main.firstChild);
                            });
                        }
                    }

                    // ── Build current preferences from DOM ────────────────────────
                    function currentPrefs() {
                        var list    = document.getElementById('dbWidgetList');
                        var order   = [];
                        var hidden  = [];
                        if (list) {
                            list.querySelectorAll('[data-widget-key]').forEach(function(item) {
                                var k   = item.getAttribute('data-widget-key');
                                var btn = item.querySelector('.db-toggle');
                                order.push(k);
                                if (btn && !btn.classList.contains('on')) hidden.push(k);
                            });
                        }
                        return { widget_order: order, hidden_widgets: hidden };
                    }

                    // ── Debounced auto-save ───────────────────────────────────────
                    var pendingPrefs = null;

                    function scheduleSave() {
                        pendingPrefs = currentPrefs();
                        clearTimeout(saveTimer);
                        saveTimer = setTimeout(function() {
                            savePrefs(pendingPrefs);
                            pendingPrefs = null;
                        }, 200);
                    }

                    function savePrefs(prefs) {
                        prefs = prefs || currentPrefs();
                        fetch(SAVE_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(prefs)
                        }).then(function(r) {
                            if (r.ok) { pendingPrefs = null; showSaveBadge(); }
                        }).catch(function() {});
                    }

                    // Flush any pending toggle save on page unload
                    window.addEventListener('pagehide', function() {
                        if (pendingPrefs) {
                            clearTimeout(saveTimer);
                            savePrefs(pendingPrefs);
                            pendingPrefs = null;
                        }
                    });

                    // ── Set as hotel default ──────────────────────────────────────
                    window.dbSaveDefault = function() {
                        var prefs = currentPrefs();
                        var btn   = document.getElementById('dbSetDefaultBtn');
                        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:5px;"></i>Saving…'; }
                        fetch(SAVE_DEFAULT_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(prefs)
                        }).then(function(r) {
                            if (r.ok) {
                                showSaveBadge('Hotel default saved!');
                                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-hotel" style="margin-right:5px;"></i>Set as Hotel Default'; }
                            }
                        });
                    };

                    // ── Reset to default ──────────────────────────────────────────
                    window.dbResetPrefs = function() {
                        if (!confirm('Reset your dashboard layout to the hotel default? Your personal preferences will be removed.')) return;
                        fetch(RESET_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({})
                        }).then(function(r) {
                            if (r.ok) window.location.reload();
                        });
                    };

                    // ── Show "Saved" badge briefly ────────────────────────────────
                    function showSaveBadge(text) {
                        var badge = document.getElementById('dbSaveBadge');
                        if (!badge) return;
                        badge.innerHTML = '<i class="fas fa-check-circle"></i> ' + (text || 'Saved');
                        badge.classList.add('show');
                        setTimeout(function() { badge.classList.remove('show'); }, 2200);
                    }
                })();
                </script>

                {{-- ══════════════════════════════════════════════════════════════════
                     LIVE DASHBOARD JAVASCRIPT
                     • closeAgenda()  — dismisses Today's Agenda modal
                     • loadLiveFeed() — polls /dashboard/live-feed every 30s
                     • pollKpi()      — polls /dashboard/kpi-live every 60s
                ══════════════════════════════════════════════════════════════════ --}}
                <script>
                (function () {
                    'use strict';

                    var LIVE_FEED_URL = '{{ route('dashboard.live_feed') }}';
                    var KPI_LIVE_URL  = '{{ route('dashboard.kpi_live') }}';
                    var CSRF          = document.querySelector('meta[name=csrf-token]')?.content || '';

                    // ── Agenda Modal ──────────────────────────────────────────────
                    window.closeAgenda = function () {
                        var overlay = document.getElementById('agendaOverlay');
                        if (!overlay) return;
                        overlay.style.transition = 'opacity .25s';
                        overlay.style.opacity = '0';
                        setTimeout(function () { overlay.remove(); }, 260);
                    };
                    // Dismiss on backdrop click
                    var overlay = document.getElementById('agendaOverlay');
                    if (overlay) {
                        overlay.addEventListener('click', function (e) {
                            if (e.target === overlay) closeAgenda();
                        });
                    }

                    // ── Helpers ───────────────────────────────────────────────────
                    function escHtml(str) {
                        return String(str || '')
                            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    }


                    // ── KPI Live Refresh ──────────────────────────────────────────
                    function animateNum(el, target, prefix, isCurrency) {
                        var current = parseFloat(el.getAttribute('data-count') || '0');
                        if (current === target) return;
                        var steps   = 20;
                        var delta   = (target - current) / steps;
                        var step    = 0;
                        var timer   = setInterval(function () {
                            step++;
                            current += delta;
                            if (step >= steps) {
                                current = target;
                                clearInterval(timer);
                            }
                            el.setAttribute('data-count', target);
                            if (isCurrency) {
                                el.textContent = (prefix || '') + Number(Math.round(current)).toLocaleString('en-IN');
                            } else {
                                el.textContent = Math.round(current);
                            }
                        }, 30);
                    }

                    function pollKpi() {
                        fetch(KPI_LIVE_URL, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (!data || data.error) return;

                            var map = [
                                { label: 'Check-Ins',    value: data.checkins },
                                { label: 'Check-Outs',   value: data.checkouts },
                                { label: 'Available',    value: data.available },
                                { label: 'Occupied',     value: data.occupied },
                                { label: 'Needs Cleaning', value: data.dirty },
                                { label: 'Pending Pay',  value: data.pending },
                                { label: 'Today Revenue',  value: data.todayRevenue,  prefix: '₹', currency: true },
                                { label: 'Month Revenue',  value: data.monthRevenue,  prefix: '₹', currency: true },
                            ];

                            document.querySelectorAll('.kpi-card .kpi-num').forEach(function (el) {
                                var label = el.closest('.kpi-card')?.querySelector('.kpi-label')?.textContent?.trim();
                                var match = map.find(function (m) { return m.label === label; });
                                if (match && match.value !== undefined) {
                                    animateNum(el, match.value, match.prefix || '', !!match.currency);
                                }
                            });

                            // Update occupancy sub-text
                            document.querySelectorAll('.kpi-card').forEach(function (card) {
                                var lbl = card.querySelector('.kpi-label');
                                if (lbl && lbl.textContent.trim() === 'Occupied') {
                                    var sub = card.querySelector('.kpi-sub');
                                    if (sub && data.occupancy !== undefined) sub.textContent = data.occupancy + '% occ.';
                                }
                            });

                            // Pulse the dirty card if rooms > 0
                            if (data.dirty > 0) {
                                document.querySelectorAll('.kpi-card').forEach(function (card) {
                                    var lbl = card.querySelector('.kpi-label');
                                    if (lbl && lbl.textContent.trim() === 'Needs Cleaning') {
                                        card.style.animation = 'pulse-dirty 2s infinite';
                                    }
                                });
                            }
                        })
                        .catch(function () {});
                    }

                    // First KPI refresh 60s after page load, then every 60s
                    setTimeout(function () {
                        pollKpi();
                        setInterval(pollKpi, 60000);
                    }, 60000);

                })();
                </script>
                @endsection