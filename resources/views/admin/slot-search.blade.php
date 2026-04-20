@extends('layouts.admin')
@section('title', 'Slot Search Engine')
@section('page-title', 'Slot Search Engine')
@section('page-subtitle', 'Multi-hotel slot availability — hotels as rows, slot types as columns.')

@section('content')
@push('styles')
<style>
/* ── Root ── */
:root {
  --c-green:  #16a34a; --c-green-bg:  #f0fdf4; --c-green-bd:  #86efac;
  --c-amber:  #d97706; --c-amber-bg:  #fffbeb; --c-amber-bd:  #fcd34d;
  --c-red:    #dc2626; --c-red-bg:    #fff1f2; --c-red-bd:    #fca5a5;
  --c-purple: #7c3aed; --c-purple-lt: #f5f3ff; --c-purple-bd: #ddd6fe;
  --c-slate:  #64748b; --c-border: #f1f5f9; --c-card: #fff;
}

/* ── Page wrapper ── */
.sse-wrap { max-width:1540px;margin:0 auto;padding:22px 14px; }

/* ── KPI cards ── */
.kpi-row { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px; }
.kpi-card { background:var(--c-card);border-radius:16px;padding:18px 20px;
            border:1px solid var(--c-border);box-shadow:0 1px 6px rgba(0,0,0,.05);
            display:flex;align-items:center;gap:14px; }
.kpi-icon { width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.kpi-label { font-size:11px;font-weight:600;color:var(--c-slate);text-transform:uppercase;letter-spacing:.6px;line-height:1; }
.kpi-value { font-size:26px;font-weight:800;color:#1e293b;line-height:1.1;margin-top:4px; }
.kpi-sub   { font-size:11px;color:var(--c-slate);margin-top:2px; }

/* ── Card shell ── */
.sse-card { background:var(--c-card);border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);
            border:1px solid var(--c-border);overflow:hidden; }

/* ── Card header ── */
.sse-hdr { padding:16px 22px;background:linear-gradient(135deg,#f5f3ff,#ede9fe);
           border-bottom:1px solid var(--c-border);display:flex;align-items:center;
           justify-content:space-between;gap:12px;flex-wrap:wrap; }
.sse-hdr-icon { width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,var(--c-purple),#6d28d9);
               display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(124,58,237,.3);flex-shrink:0; }

/* ── Filter bar ── */
.sse-filters { padding:14px 22px;border-bottom:1px solid var(--c-border);background:#fafafa;
               display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap; }
.ff-label { font-size:11px;font-weight:600;color:var(--c-slate);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px; }
.ff-inp { border:1px solid #e2e8f0;border-radius:10px;padding:7px 11px;font-size:13px;color:#1e293b;
          outline:none;background:#fff;transition:border .15s;display:block; }
.ff-inp:focus { border-color:#a78bfa; }
.btn { display:inline-flex;align-items:center;gap:7px;padding:0 18px;height:38px;border-radius:10px;
       border:none;cursor:pointer;font-size:13px;font-weight:600;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-purple { background:linear-gradient(135deg,var(--c-purple),#6d28d9);color:#fff;box-shadow:0 3px 8px rgba(124,58,237,.3); }
.btn-purple:hover { opacity:.9;color:#fff; }
.btn-outline { background:#fff;border:1px solid var(--c-purple-bd);color:var(--c-purple); }
.btn-outline:hover { background:var(--c-purple-lt);color:var(--c-purple); }
.btn-ghost { background:#fff;border:1px solid #e2e8f0;color:#374151; }
.btn-ghost:hover { background:#f9fafb; }

/* ── Hotel search box ── */
.sse-searchbar { padding:12px 22px;border-bottom:1px solid var(--c-border);background:#fff;display:flex;align-items:center;gap:10px; }
.sse-searchbox { flex:1;max-width:340px;border:1px solid #e2e8f0;border-radius:10px;padding:8px 14px 8px 38px;
                 font-size:13px;color:#1e293b;outline:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E") no-repeat 12px center;
                 transition:border .15s; }
.sse-searchbox:focus { border-color:#a78bfa; }

/* ── Matrix table ── */
.sse-table-wrap { overflow-x:auto;padding:0; }
.sse-table { width:100%;border-collapse:collapse;table-layout:auto; }

/* Sticky slot header */
.sse-table thead th { position:sticky;top:0;z-index:10;background:#fff;
                      border-bottom:2px solid var(--c-border);padding:12px 10px;
                      font-size:12px;font-weight:700;color:var(--c-slate);white-space:nowrap;text-align:center; }
.sse-table thead th.hotel-col { text-align:left;min-width:200px;padding-left:18px; }
.sse-table thead th .slot-head-name { font-size:12px;font-weight:700;color:#1e293b; }
.sse-table thead th .slot-head-time { font-size:10px;color:var(--c-slate);margin-top:2px; }

/* Hotel rows */
.sse-hotel-row td { padding:0;border-bottom:1px solid #f8fafc; }
.sse-hotel-row:hover td { background:#fafcff; }
.hotel-name-cell { padding:14px 18px;cursor:pointer;white-space:nowrap;vertical-align:middle; }
.hotel-chevron { display:inline-flex;align-items:center;justify-content:center;
                 width:22px;height:22px;border-radius:6px;border:1px solid #e2e8f0;
                 color:var(--c-slate);font-size:10px;margin-right:10px;transition:all .2s;flex-shrink:0;background:#fff; }
.hotel-chevron.open { background:var(--c-purple-lt);border-color:var(--c-purple-bd);color:var(--c-purple);transform:rotate(90deg); }
.hotel-name { font-size:14px;font-weight:700;color:#1e293b; }
.hotel-meta { font-size:11px;color:var(--c-slate);margin-top:2px; }

/* Slot cells */
.slot-cell { padding:10px 8px;text-align:center;cursor:pointer;vertical-align:middle;transition:background .15s; }
.slot-cell:hover { background:#f5f3ff; }
.slot-cell.na { cursor:default;color:#d1d5db;font-size:12px; }
.slot-badge-wrap { display:inline-flex;flex-direction:column;align-items:center;gap:4px; }
.slot-badge { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:30px;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s; }
.slot-badge.green { background:var(--c-green-bg);color:var(--c-green);border:1px solid var(--c-green-bd); }
.slot-badge.amber { background:var(--c-amber-bg);color:var(--c-amber);border:1px solid var(--c-amber-bd); }
.slot-badge.red   { background:var(--c-red-bg);  color:var(--c-red);  border:1px solid var(--c-red-bd); }
.slot-badge:hover { filter:brightness(.95);transform:scale(1.03); }
.badge-icon { font-size:10px; }
.badge-counts { display:flex;gap:6px;font-size:10px;font-weight:500; }
.badge-free   { color:var(--c-green); }
.badge-booked { color:var(--c-red); }

/* Room-expand rows */
.room-expand-row { display:none; }
.room-expand-row td { padding:0;background:#faf9ff;border-bottom:1px solid #ede9fe; }
.room-expand-inner { padding:8px 18px 8px 52px;display:flex;align-items:center;gap:10px;flex-wrap:wrap; }
.room-slot-chip { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid; }
.room-slot-chip.green { background:var(--c-green-bg);color:var(--c-green);border-color:var(--c-green-bd); }
.room-slot-chip.amber { background:var(--c-amber-bg);color:var(--c-amber);border-color:var(--c-amber-bd); }
.room-slot-chip.red   { background:var(--c-red-bg);color:var(--c-red);border-color:var(--c-red-bd); }
.room-chip-label { font-size:11px;color:var(--c-slate);font-weight:600;min-width:70px;flex-shrink:0; }

/* WH badge */
.wh-badge { display:inline-flex;align-items:center;gap:4px;background:#fef3c7;border:1px solid #fde68a;color:#92400e;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;margin-left:6px; }

/* ── Side panel ── */
#ssePanel { position:fixed;top:0;right:-420px;height:100%;width:410px;z-index:9000;
             background:#fff;box-shadow:-4px 0 24px rgba(0,0,0,.12);transition:right .28s cubic-bezier(.4,0,.2,1);
             overflow-y:auto;display:flex;flex-direction:column; }
#ssePanel.open { right:0; }
#ssePanelOverlay { display:none;position:fixed;inset:0;z-index:8999;background:rgba(0,0,0,.2); }
#ssePanelOverlay.open { display:block; }
.panel-hdr { padding:20px 20px 14px;border-bottom:1px solid var(--c-border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;position:sticky;top:0;background:#fff;z-index:1; }
.panel-close { width:34px;height:34px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--c-slate);flex-shrink:0; }
.panel-close:hover { background:#f1f5f9; }
.panel-body { padding:16px 20px;flex:1; }
.panel-slot-info { background:linear-gradient(135deg,var(--c-purple-lt),#ede9fe);border-radius:12px;padding:12px 14px;margin-bottom:16px; }
.panel-section { margin-bottom:14px; }
.panel-sec-title { font-size:11px;font-weight:700;color:var(--c-slate);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px; }
.panel-room-pill { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;margin:3px;border:1px solid; }
.panel-room-pill.free   { background:var(--c-green-bg);color:var(--c-green);border-color:var(--c-green-bd); }
.panel-room-pill.booked { background:var(--c-red-bg);color:var(--c-red);border-color:var(--c-red-bd); }
.panel-room-pill.wh     { background:#fef3c7;color:#92400e;border-color:#fde68a; }
.panel-date-row { padding:10px 12px;border-radius:10px;border:1px solid var(--c-border);margin-bottom:8px; }
.panel-date-label { font-size:12px;font-weight:700;color:#1e293b;margin-bottom:6px; }
.panel-wh-banner { background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:8px; }

/* ── Legend ── */
.sse-legend { display:flex;align-items:center;gap:16px;padding:12px 22px;border-top:1px solid var(--c-border);flex-wrap:wrap; }
.leg-item { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c-slate); }
.leg-dot { width:10px;height:10px;border-radius:50%;display:inline-block; }

/* ── Empty states ── */
.sse-empty { text-align:center;padding:60px 24px; }
.sse-empty-icon { width:70px;height:70px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px; }

/* ── Responsive ── */
@media (max-width:900px) {
  .kpi-row { grid-template-columns:repeat(2,1fr); }
}
@media (max-width:600px) {
  .kpi-row { grid-template-columns:1fr; }
  #ssePanel { width:96vw; }
}

/* ── Print ── */
@media print {
  #ssePanel, #ssePanelOverlay, .sse-filters, .sse-searchbar,
  .sse-hdr-actions, .kpi-row, .no-print { display:none !important; }
  .sse-table-wrap { overflow:visible !important; }
  .sse-table { font-size:11px; }
  .sse-card { box-shadow:none; }
}

/* ── Full-screen mode: hide sidebar on Slot Search page ── */
#sidebar,
#sidebar-overlay { display:none !important; }
#main-wrap { margin-left:0 !important; }
</style>
@endpush

<div class="sse-wrap" id="ssePrintArea">

{{-- ── KPI CARDS ── --}}
@if($kpi)
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
            <i class="fas fa-hotel" style="color:#fff;font-size:17px;"></i>
        </div>
        <div>
            <div class="kpi-label">Hotels Searched</div>
            <div class="kpi-value">{{ $kpi['total_hotels'] }}</div>
            <div class="kpi-sub">{{ $availableHotels->count() }} available</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:linear-gradient(135deg,#16a34a,#15803d);">
            <i class="fas fa-check-circle" style="color:#fff;font-size:17px;"></i>
        </div>
        <div>
            <div class="kpi-label">Free Room-Days</div>
            <div class="kpi-value">{{ number_format($kpi['free_room_days']) }}</div>
            <div class="kpi-sub">{{ 100 - $kpi['pct_booked'] }}% of capacity</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
            <i class="fas fa-bed" style="color:#fff;font-size:17px;"></i>
        </div>
        <div>
            <div class="kpi-label">Booked Room-Days</div>
            <div class="kpi-value">{{ number_format($kpi['booked_room_days']) }}</div>
            <div class="kpi-sub">{{ $kpi['pct_booked'] }}% occupancy</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
            <i class="fas fa-calendar-alt" style="color:#fff;font-size:17px;"></i>
        </div>
        <div>
            <div class="kpi-label">Date Range</div>
            <div class="kpi-value" style="font-size:18px;">
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }}
                <span style="font-weight:400;font-size:14px;color:var(--c-slate);">to</span>
                {{ \Carbon\Carbon::parse($dateTo)->format('d M') }}
            </div>
            <div class="kpi-sub">{{ count($slotColumns ?? []) }} slot type(s)</div>
        </div>
    </div>
</div>
@endif

{{-- ── MAIN CARD ── --}}
<div class="sse-card">

    {{-- Card header --}}
    <div class="sse-hdr">
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="sse-hdr-icon"><i class="fas fa-clock" style="color:#fff;font-size:17px;"></i></div>
            <div>
                <div style="font-weight:800;color:#1e293b;font-size:16px;">Slot Availability Matrix</div>
                <div style="font-size:12px;color:#6d28d9;">
                    Hotels as rows &middot; Slot types as columns
                    @if(isset($dateFrom) && isset($dateTo) && $matrix !== null)
                    &middot; {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    @endif
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;" class="sse-hdr-actions no-print">
            @if($matrix !== null)
            @php
                $pdfParams = http_build_query(array_filter([
                    'date_from'  => $dateFrom ?? '',
                    'date_to'    => $dateTo   ?? '',
                    'status'     => ($statusFilter ?? 'all') !== 'all' ? $statusFilter : null,
                    'autoprint'  => '1',
                ]));
                $pdfUrl = route('slot-search.pdf') . '?' . $pdfParams;
            @endphp
            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-ghost" style="text-decoration:none;">
                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> Export PDF
            </a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('slot-search.index') }}" class="sse-filters no-print">
        <div>
            <span class="ff-label">From Date</span>
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="ff-inp" required>
        </div>
        <div>
            <span class="ff-label">To Date</span>
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="ff-inp" required>
        </div>
        @if($isMultiHotel)
        <div>
            <span class="ff-label">Hotels</span>
            <select name="hotel_ids[]" multiple class="ff-inp" style="min-width:140px;height:38px;">
                @foreach($availableHotels as $h)
                <option value="{{ $h->id }}" {{ in_array($h->id, $filterHotelIds ?? []) ? 'selected' : '' }}>{{ $h->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($flatSlots->isNotEmpty())
        <div>
            <span class="ff-label">Slot Types</span>
            <select name="slot_ids[]" multiple class="ff-inp" style="min-width:160px;height:38px;">
                @foreach($flatSlots->unique(fn($s) => $s->name . '|' . $s->start_time)->values() as $slot)
                <option value="{{ $slot->id }}" {{ in_array($slot->id, $slotIds ?? []) ? 'selected' : '' }}>
                    {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }})
                </option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <span class="ff-label">Availability</span>
            <select name="status" class="ff-inp" style="min-width:130px;">
                <option value="all"     {{ ($statusFilter ?? 'all') === 'all'     ? 'selected' : '' }}>All</option>
                <option value="free"    {{ ($statusFilter ?? 'all') === 'free'    ? 'selected' : '' }}>Fully Free</option>
                <option value="partial" {{ ($statusFilter ?? 'all') === 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="booked"  {{ ($statusFilter ?? 'all') === 'booked'  ? 'selected' : '' }}>Fully Booked</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-purple">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="{{ route('slot-search.index') }}" class="btn btn-ghost" style="text-decoration:none;">
                <i class="fas fa-undo" style="font-size:10px;"></i> Reset
            </a>
        </div>
    </form>

    {{-- Hotel search bar --}}
    @if($matrix !== null && count($matrix) > 0)
    <div class="sse-searchbar no-print">
        <input type="text" id="hotelSearchInput" class="sse-searchbox" placeholder="Search hotels…" oninput="filterHotels(this.value)">
        <span style="font-size:12px;color:var(--c-slate);" id="hotelCount">{{ count($matrix) }} hotel{{ count($matrix) === 1 ? '' : 's' }}</span>
    </div>
    @endif

    {{-- ── MATRIX TABLE ── --}}
    @if($matrix === null)
    {{-- Initial state --}}
    <div class="sse-empty">
        <div class="sse-empty-icon" style="background:#f5f3ff;">
            <i class="fas fa-search" style="font-size:28px;color:#7c3aed;"></i>
        </div>
        <div style="font-size:17px;font-weight:700;color:#1e293b;margin-bottom:8px;">Choose your search criteria</div>
        <div style="font-size:14px;color:var(--c-slate);max-width:400px;margin:0 auto;line-height:1.7;">
            Pick a date range and click <strong>Search</strong> to see availability across all hotels and slot types at a glance.
        </div>
    </div>

    @elseif(empty($matrix))
    <div class="sse-empty">
        <div class="sse-empty-icon" style="background:#f0fdf4;">
            <i class="fas fa-check-circle" style="font-size:28px;color:#16a34a;"></i>
        </div>
        <div style="font-size:17px;font-weight:700;color:#1e293b;margin-bottom:8px;">No hotels match your filters</div>
        <div style="font-size:14px;color:var(--c-slate);">Try adjusting the availability filter or adding more hotels to your account.</div>
    </div>

    @else
    {{-- Availability matrix --}}
    <div class="sse-table-wrap">
        <table class="sse-table" id="sseMatrix">
            <thead>
                <tr>
                    <th class="hotel-col">
                        <div style="font-size:12px;font-weight:700;color:var(--c-slate);">Hotel</div>
                    </th>
                    @foreach($slotColumns as $col)
                    <th style="min-width:130px;">
                        <div class="slot-head-name">{{ $col['name'] }}</div>
                        <div class="slot-head-time">{{ $col['time'] }}</div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $hotelIdx => $hotel)
                {{-- Hotel main row --}}
                <tr class="sse-hotel-row" data-hotel-name="{{ strtolower($hotel['hotel_name']) }}" id="hotel-row-{{ $hotelIdx }}">
                    {{-- Hotel name cell --}}
                    <td>
                        <div class="hotel-name-cell" onclick="toggleExpand({{ $hotelIdx }})">
                            <span class="hotel-chevron" id="chevron-{{ $hotelIdx }}">&#8250;</span>
                            <span class="hotel-name">{{ $hotel['hotel_name'] }}</span>
                            @if($hotel['is_wh_any'])
                            <span class="wh-badge"><i class="fas fa-hotel" style="font-size:8px;"></i> WH</span>
                            @endif
                            <div class="hotel-meta">{{ $hotel['rooms_count'] }} slot room{{ $hotel['rooms_count'] === 1 ? '' : 's' }}</div>
                        </div>
                    </td>
                    {{-- Slot cells --}}
                    @foreach($slotColumns as $col)
                    @php $sd = $hotel['slots'][$col['key']] ?? null; @endphp
                    @if($sd && $sd['has_slot'])
                    <td class="slot-cell"
                        onclick="openPanel({{ json_encode($hotel) }}, '{{ $col['key'] }}', '{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}', '{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}')"
                        title="Click for details">
                        <div class="slot-badge-wrap">
                            <span class="slot-badge {{ $sd['color'] }}">
                                @if($sd['color'] === 'green')
                                <i class="fas fa-check-circle badge-icon"></i>
                                @elseif($sd['color'] === 'amber')
                                <i class="fas fa-exclamation-circle badge-icon"></i>
                                @else
                                <i class="fas fa-times-circle badge-icon"></i>
                                @endif
                                {{ $sd['worst_free'] }} / {{ $sd['total_rooms'] }}
                            </span>
                            <div class="badge-counts">
                                <span class="badge-free"><i class="fas fa-circle" style="font-size:7px;"></i> {{ $sd['worst_free'] }} free</span>
                                <span class="badge-booked"><i class="fas fa-circle" style="font-size:7px;"></i> {{ $sd['worst_booked'] }} booked</span>
                            </div>
                        </div>
                    </td>
                    @elseif($sd)
                    <td class="slot-cell na">—</td>
                    @else
                    <td class="slot-cell na">—</td>
                    @endif
                    @endforeach
                </tr>
                {{-- Room-expand rows --}}
                @foreach($hotel['rooms'] as $room)
                <tr class="room-expand-row" id="expand-{{ $hotelIdx }}" data-hotel="{{ $hotelIdx }}">
                    <td colspan="{{ count($slotColumns) + 1 }}">
                        <div class="room-expand-inner">
                            <span class="room-chip-label" style="font-weight:700;color:#1e293b;">
                                <i class="fas fa-door-open" style="font-size:10px;color:var(--c-slate);"></i>
                                R{{ $room['number'] }}
                            </span>
                            @foreach($slotColumns as $col)
                            @php
                                $sd = $hotel['slots'][$col['key']] ?? null;
                                if (!$sd || !$sd['has_slot']) { $chipColor = 'no-slot'; $chipLabel = '—'; }
                                else {
                                    $bookedOnAnyDay = false;
                                    foreach ($sd['dates'] as $ds => $dd) {
                                        $isBooked = collect($dd['booked_rooms'])->contains(fn($br) => $br['room_number'] == $room['number']);
                                        if ($isBooked) { $bookedOnAnyDay = true; break; }
                                    }
                                    $freeOnAnyDay = !$bookedOnAnyDay;
                                    $chipColor = $bookedOnAnyDay ? ($freeOnAnyDay ? 'amber' : 'red') : 'green';
                                    $chipLabel = $bookedOnAnyDay ? 'Has Bookings' : 'All Free';
                                }
                            @endphp
                            @if($chipColor !== 'no-slot')
                            <span class="room-slot-chip {{ $chipColor }}" style="font-size:10px;">
                                {{ $col['name'] }}: <strong>{{ $chipLabel }}</strong>
                            </span>
                            @endif
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="sse-legend">
        <div class="leg-item"><span class="leg-dot" style="background:#22c55e;"></span> Fully Available (0% booked)</div>
        <div class="leg-item"><span class="leg-dot" style="background:#f59e0b;"></span> Partial (some bookings)</div>
        <div class="leg-item"><span class="leg-dot" style="background:#ef4444;"></span> Fully Booked (100%)</div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
            <span class="wh-badge"><i class="fas fa-hotel" style="font-size:8px;"></i> WH</span>
            <span style="font-size:12px;color:var(--c-slate);">Whole-Hotel booking on at least one day</span>
        </div>
    </div>
    @endif

</div>{{-- /sse-card --}}
</div>{{-- /sse-wrap --}}

{{-- ── SIDE PANEL (detail popup) ── --}}
<div id="ssePanelOverlay" onclick="closePanel()"></div>
<div id="ssePanel">
    <div class="panel-hdr">
        <div>
            <div id="panelHotelName" style="font-size:15px;font-weight:800;color:#1e293b;"></div>
            <div id="panelSlotInfo" style="font-size:12px;color:var(--c-slate);margin-top:2px;"></div>
        </div>
        <button class="panel-close" onclick="closePanel()"><i class="fas fa-times"></i></button>
    </div>
    <div class="panel-body" id="panelBody"></div>
</div>

<script>
// ── Expand/collapse hotel rooms ──────────────────────────────
function toggleExpand(idx) {
    var rows = document.querySelectorAll('[id="expand-' + idx + '"]');
    var chev = document.getElementById('chevron-' + idx);
    var isOpen = chev.classList.contains('open');
    rows.forEach(function(r) { r.style.display = isOpen ? 'none' : 'table-row'; });
    chev.classList.toggle('open', !isOpen);
}

// ── Hotel search filter ──────────────────────────────────────
function filterHotels(q) {
    q = q.toLowerCase().trim();
    var rows = document.querySelectorAll('.sse-hotel-row');
    var visible = 0;
    rows.forEach(function(r) {
        var match = !q || r.dataset.hotelName.indexOf(q) !== -1;
        r.style.display = match ? '' : 'none';
        if (match) visible++;
        // Also hide expand rows for hidden hotels
        var idx = r.id.replace('hotel-row-', '');
        var expRows = document.querySelectorAll('[id="expand-' + idx + '"]');
        expRows.forEach(function(er) {
            var chev = document.getElementById('chevron-' + idx);
            if (!match || !chev || !chev.classList.contains('open')) er.style.display = 'none';
        });
    });
    var cnt = document.getElementById('hotelCount');
    if (cnt) cnt.textContent = visible + ' hotel' + (visible === 1 ? '' : 's');
}

// ── Side panel ───────────────────────────────────────────────
var _panelData = null;

function openPanel(hotel, slotKey, dateFromLabel, dateToLabel) {
    _panelData = { hotel: hotel, slotKey: slotKey };
    var sd = hotel.slots[slotKey];
    if (!sd) return;

    document.getElementById('panelHotelName').textContent = hotel.hotel_name;
    document.getElementById('panelSlotInfo').textContent  = sd.slot_name + ' · ' + sd.slot_time + ' · ' + dateFromLabel + ' – ' + dateToLabel;

    var body = '';

    // Summary strip
    body += '<div class="panel-slot-info" style="display:flex;gap:16px;margin-bottom:16px;">';
    body += '<div style="text-align:center;"><div style="font-size:22px;font-weight:800;color:var(--c-purple);">' + sd.worst_free + '</div><div style="font-size:11px;color:var(--c-slate);">Min free rooms</div></div>';
    body += '<div style="text-align:center;"><div style="font-size:22px;font-weight:800;color:#dc2626;">' + sd.worst_booked + '</div><div style="font-size:11px;color:var(--c-slate);">Max booked rooms</div></div>';
    body += '<div style="text-align:center;"><div style="font-size:22px;font-weight:800;color:#1e293b;">' + sd.total_rooms + '</div><div style="font-size:11px;color:var(--c-slate);">Total rooms</div></div>';
    body += '</div>';

    // Per-date breakdown
    body += '<div class="panel-sec-title">Day-by-day Breakdown</div>';
    var dates = sd.dates || {};
    var dateKeys = Object.keys(dates).sort();
    if (dateKeys.length === 0) {
        body += '<div style="color:var(--c-slate);font-size:13px;">No data for this date range.</div>';
    } else {
        dateKeys.forEach(function(ds) {
            var dd = dates[ds];
            var d = new Date(ds);
            var dayLabel = d.toLocaleDateString('en-GB', { weekday:'short', day:'numeric', month:'short' });
            var dotColor = dd.whole_hotel ? '#f59e0b' : (dd.available === 0 ? '#ef4444' : (dd.booked_count > 0 ? '#f59e0b' : '#22c55e'));
            body += '<div class="panel-date-row">';
            body += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">';
            body += '<div class="panel-date-label" style="display:flex;align-items:center;gap:7px;"><span style="width:8px;height:8px;border-radius:50%;background:' + dotColor + ';display:inline-block;flex-shrink:0;"></span>' + dayLabel + '</div>';
            if (dd.whole_hotel) {
                body += '<span style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;"><i class="fas fa-hotel" style="font-size:8px;"></i> Whole Hotel</span>';
            } else {
                body += '<span style="font-size:12px;font-weight:600;color:' + (dd.available === 0 ? '#dc2626' : '#16a34a') + ';">' + dd.available + ' free</span>';
            }
            body += '</div>';
            body += '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
            (dd.booked_rooms || []).forEach(function(br) {
                body += '<span class="panel-room-pill ' + (br.whole_hotel ? 'wh' : 'booked') + '" title="' + br.guest_name + '">';
                body += '<i class="fas fa-circle" style="font-size:7px;"></i> R' + br.room_number;
                body += '<span style="font-weight:400;margin-left:3px;font-size:10px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + br.guest_name + '</span>';
                body += '</span>';
            });
            (dd.free_rooms || []).forEach(function(rn) {
                body += '<span class="panel-room-pill free"><i class="fas fa-circle" style="font-size:7px;"></i> R' + rn + '</span>';
            });
            body += '</div>';
            body += '</div>';
        });
    }

    document.getElementById('panelBody').innerHTML = body;
    document.getElementById('ssePanel').classList.add('open');
    document.getElementById('ssePanelOverlay').classList.add('open');
}

function closePanel() {
    document.getElementById('ssePanel').classList.remove('open');
    document.getElementById('ssePanelOverlay').classList.remove('open');
}

// Close panel on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePanel();
});
</script>
@endsection
