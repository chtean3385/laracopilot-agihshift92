@extends('layouts.admin')
@section('title', 'Slot Search Engine')
@section('page-title', 'Slot Search Engine')
@section('page-subtitle', 'Multi-hotel slot availability at a glance.')

@section('content')
@push('styles')
<style>
/* ── Root variables ── */
:root {
  --green:#16a34a; --green-bg:#dcfce7; --green-bd:#86efac; --green-dark:#15803d;
  --red:#dc2626;   --red-bg:#fee2e2;   --red-bd:#fca5a5;   --red-dark:#b91c1c;
  --amber:#d97706; --amber-bg:#fef9c3; --amber-bd:#fde68a;
  --purple:#7c3aed;--purple-bg:#f5f3ff;--purple-bd:#ddd6fe;
  --slate:#64748b; --border:#e5e7eb;   --surface:#fff;
}

/* ── Wrapper ── */
.sse { max-width:1540px;margin:0 auto;padding:20px 14px; }

/* ── KPI strip ── */
.sse-kpi { display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px; }
.kpi-chip { display:inline-flex;align-items:center;gap:8px;background:var(--surface);
            border:1px solid var(--border);border-radius:12px;padding:9px 16px;
            font-size:13px;font-weight:600;color:#1e293b;box-shadow:0 1px 4px rgba(0,0,0,.04);white-space:nowrap; }
.kpi-chip .kc-val { font-size:19px;font-weight:800;line-height:1; }
.kc-green { color:var(--green); }
.kc-red   { color:var(--red); }
.kc-purple{ color:var(--purple); }

/* ── Card ── */
.sse-card { background:var(--surface);border-radius:18px;border:1px solid var(--border);
            box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden; }

/* ── Card header ── */
.sse-hdr { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
           padding:14px 20px;background:linear-gradient(135deg,#f5f3ff,#ede9fe);
           border-bottom:1px solid #ddd6fe; }
.sse-hdr-left { display:flex;align-items:center;gap:12px; }
.sse-hdr-icon { width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--purple),#6d28d9);
                display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(124,58,237,.3); }

/* ── Filter row ── */
.sse-flt { display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap;
           padding:12px 20px;background:#fafafa;border-bottom:1px solid var(--border); }
.flt-group { display:flex;flex-direction:column;gap:3px; }
.flt-label { font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.5px; }
.flt-inp { border:1px solid #e2e8f0;border-radius:9px;padding:6px 10px;font-size:13px;color:#1e293b;
           outline:none;background:#fff;transition:border .15s;height:36px;display:flex;align-items:center; }
.flt-inp:focus { border-color:#a78bfa; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:0 16px;height:36px;border-radius:9px;
       border:none;cursor:pointer;font-size:13px;font-weight:600;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-purple { background:linear-gradient(135deg,var(--purple),#6d28d9);color:#fff;box-shadow:0 2px 7px rgba(124,58,237,.3); }
.btn-purple:hover { opacity:.9;color:#fff; }
.btn-ghost  { background:#fff;border:1px solid #e2e8f0;color:#374151; }
.btn-ghost:hover { background:#f9fafb;color:#374151; }

/* ── Search / hotel filter bar ── */
.sse-searchbar { display:flex;align-items:center;gap:10px;padding:10px 20px;border-bottom:1px solid var(--border); }
.sse-searchbox { border:1px solid #e2e8f0;border-radius:9px;padding:7px 12px 7px 34px;font-size:13px;color:#1e293b;
                 outline:none;width:240px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center;
                 transition:border .15s; }
.sse-searchbox:focus { border-color:#a78bfa; }
.hotel-count-lbl { font-size:12px;color:var(--slate); }

/* ── Table ── */
.sse-tbl-wrap { overflow-x:auto; }
.sse-tbl { width:100%;border-collapse:collapse; }
.sse-tbl thead tr { border-bottom:2px solid var(--border); }

/* Column header */
.sse-tbl thead th { padding:10px 12px;text-align:center;background:#fff;white-space:nowrap;
                    position:sticky;top:0;z-index:5; }
.sse-tbl thead th.col-hotel { text-align:left;min-width:210px;padding-left:18px; }
.col-hdr-name { font-size:13px;font-weight:800;color:#1e293b;margin-bottom:5px; }
.col-hdr-badges { display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap; }
.col-agg-badge { display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:3px 9px;font-size:11px;font-weight:700;border:1px solid; }
.col-agg-badge.green { background:var(--green-bg);color:var(--green);border-color:var(--green-bd); }
.col-agg-badge.red   { background:var(--red-bg);  color:var(--red);  border-color:var(--red-bd); }
.col-agg-badge.amber { background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd); }
.col-agg-badge.wh    { background:#fef3c7;color:#92400e;border-color:#fde68a; }

/* Hotel rows */
.sse-tbl tbody tr.hotel-row { border-bottom:1px solid #f3f4f6;transition:background .12s; }
.sse-tbl tbody tr.hotel-row:hover { background:#f8f9ff; }
.hotel-td { padding:12px 18px;white-space:nowrap;cursor:pointer; }
.hotel-chevron-btn { display:inline-flex;align-items:center;justify-content:center;
                     width:20px;height:20px;border-radius:5px;border:1px solid #e2e8f0;
                     background:#fff;color:var(--slate);font-size:9px;margin-right:8px;
                     transition:all .2s;flex-shrink:0;cursor:pointer; }
.hotel-chevron-btn.open { background:var(--purple-bg);border-color:var(--purple-bd);color:var(--purple);transform:rotate(90deg); }
.hotel-name-txt { font-size:14px;font-weight:700;color:#0f172a; }
.hotel-sub-txt  { font-size:11px;color:var(--slate);margin-top:1px; }
.wh-tag { display:inline-flex;align-items:center;gap:3px;background:#fef3c7;border:1px solid #fde68a;
          color:#92400e;border-radius:20px;padding:1px 7px;font-size:9px;font-weight:700;margin-left:6px;vertical-align:middle; }

/* Slot cells */
.slot-td { padding:10px 8px;text-align:center;cursor:pointer;position:relative; }
.slot-td:hover .slot-cell-inner { background:#f5f3ff; border-radius:10px; }
.slot-td.na { cursor:default; }
.slot-cell-inner { display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:5px 8px;border-radius:8px;transition:background .12s; }
.count-chip { display:inline-flex;align-items:center;gap:3px;border-radius:20px;padding:4px 10px;font-size:13px;font-weight:800;border:1px solid; }
.count-chip.green { background:var(--green-bg);color:var(--green);border-color:var(--green-bd); }
.count-chip.red   { background:var(--red-bg);  color:var(--red);  border-color:var(--red-bd); }
.count-chip.amber { background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd); }

/* Room expand rows */
.expand-row { display:none; }
.expand-row td { padding:6px 18px 6px 50px;background:#f8f7ff;border-bottom:1px solid #ede9fe; }
.expand-inner { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.room-tag { font-size:11px;font-weight:700;color:#334155;min-width:60px; }
.room-chip { display:inline-flex;align-items:center;gap:3px;border-radius:16px;padding:2px 9px;font-size:10px;font-weight:700;border:1px solid; }
.room-chip.green { background:var(--green-bg);color:var(--green);border-color:var(--green-bd); }
.room-chip.red   { background:var(--red-bg);  color:var(--red);  border-color:var(--red-bd); }
.room-chip.amber { background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd); }

/* ── Inline popup card ── */
#ssePopup { display:none;position:fixed;z-index:9999;width:310px;
             background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.18);
             border:1px solid var(--border);overflow:hidden;animation:ppIn .15s ease; }
@keyframes ppIn { from { opacity:0;transform:scale(.96) translateY(-6px); } to { opacity:1;transform:none; } }
.pp-hdr { display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
          padding:14px 16px 10px;border-bottom:1px solid var(--border); }
.pp-hotel { font-size:14px;font-weight:800;color:#0f172a; }
.pp-slot  { font-size:11px;color:var(--slate);margin-top:2px; }
.pp-close { width:28px;height:28px;border-radius:8px;border:1px solid #e2e8f0;
            background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;
            color:var(--slate);flex-shrink:0;font-size:12px; }
.pp-close:hover { background:#f1f5f9; }
.pp-body { padding:12px 16px; }
.pp-section-title { font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;
                    letter-spacing:.6px;margin-bottom:6px;margin-top:10px; }
.pp-section-title:first-child { margin-top:0; }
.pp-room-pill { display:inline-flex;align-items:center;gap:4px;border-radius:7px;
                padding:3px 8px;font-size:11px;font-weight:700;margin:2px;border:1px solid; }
.pp-room-pill.free   { background:var(--green-bg);color:var(--green);border-color:var(--green-bd); }
.pp-room-pill.booked { background:var(--red-bg);  color:var(--red);  border-color:var(--red-bd); }
.pp-room-pill.wh     { background:#fef3c7;color:#92400e;border-color:#fde68a; }
.pp-wh-bar { background:#fef9c3;border:1px solid #fde68a;border-radius:9px;padding:7px 11px;
             font-size:12px;color:#92400e;display:flex;align-items:center;gap:7px;margin-bottom:8px; }
.pp-pills-wrap { display:flex;flex-wrap:wrap;gap:3px; }

/* ── Legend ── */
.sse-legend { display:flex;align-items:center;gap:14px;padding:11px 20px;border-top:1px solid var(--border);flex-wrap:wrap; }
.leg-item { display:flex;align-items:center;gap:5px;font-size:11px;color:var(--slate); }
.leg-dot { width:8px;height:8px;border-radius:50%;display:inline-block; }

/* ── Empty ── */
.sse-empty { text-align:center;padding:56px 24px; }
.sse-empty-ico { width:66px;height:66px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px; }

/* ── Print ── */
@media print {
  #ssePopup,.sse-flt,.sse-searchbar,.sse-hdr-right,.sse-kpi,.no-print{display:none!important;}
  .sse-tbl-wrap{overflow:visible!important;} .sse-tbl{font-size:10px;}
  .sse-card{box-shadow:none!important;}
}

/* ══ FULL-SCREEN OVERRIDE ══ */
#sidebar,
#sidebar-overlay { display:none !important; }

#main-wrap {
    margin-left: 0 !important;
}

#main-wrap > header { display:none !important; }

/* Remove main content padding so topbar sits at very top */
#main-wrap > main {
    padding: 0 !important;
}

/* Page body flush to top */
body { background:#f1f5f9; }

/* Sticky full-width top bar */
#sse-topbar {
    position:sticky;
    top:0;
    z-index:40;
    background:#fff;
    border-bottom:1px solid #e5e7eb;
    box-shadow:0 1px 6px rgba(0,0,0,.06);
}

/* Back arrow button */
.sse-back-btn {
    display:inline-flex;align-items:center;gap:7px;
    background:none;border:none;cursor:pointer;
    padding:0;color:#1e293b;font-size:13px;font-weight:700;
    text-decoration:none;
    transition:color .15s;
    flex-shrink:0;
}
.sse-back-btn:hover { color:var(--purple); }
.sse-back-arrow {
    width:32px;height:32px;border-radius:9px;
    background:#f1f5f9;border:1px solid #e2e8f0;
    display:flex;align-items:center;justify-content:center;
    transition:background .15s;
}
.sse-back-btn:hover .sse-back-arrow { background:var(--purple-bg);border-color:var(--purple-bd); }

/* Filter row in topbar */
#sse-filter-row {
    display:flex;align-items:center;gap:8px;flex-wrap:nowrap;
    overflow-x:auto;
    flex:1;
    min-width:0;
}
#sse-filter-row::-webkit-scrollbar { height:0; }

/* Remove the old separate card-header & filter strip when using topbar */
.sse-card-no-hdr .sse-hdr { display:none; }
</style>
@endpush

{{-- ══ FULL-SCREEN STICKY TOPBAR ══ --}}
<div id="sse-topbar" class="no-print">

    {{-- Row 1: back arrow + title + KPI chips --}}
    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">

        {{-- Back arrow --}}
        <a href="{{ route('dashboard') }}" class="sse-back-btn" title="Back to Dashboard">
            <span class="sse-back-arrow"><i class="fas fa-chevron-left" style="font-size:12px;color:#64748b;"></i></span>
        </a>

        {{-- Title --}}
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--purple),#6d28d9);display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-clock" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:800;color:#0f172a;line-height:1.2;">Slot Search Engine</div>
                <div style="font-size:11px;color:#6d28d9;">
                    @if($matrix !== null && isset($dateFrom))
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    @else
                        Hotels &times; Slot types matrix
                    @endif
                </div>
            </div>
        </div>

        {{-- KPI chips --}}
        @if($kpi)
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:8px;">
            <div class="kpi-chip" style="padding:5px 12px;">
                <i class="fas fa-hotel" style="color:var(--purple);font-size:12px;"></i>
                <span style="color:var(--slate);font-weight:600;font-size:12px;">Hotels</span>
                <span class="kc-val kc-purple" style="font-size:16px;">{{ $kpi['total_hotels'] }}</span>
            </div>
            <div class="kpi-chip" style="padding:5px 12px;">
                <i class="fas fa-check-circle" style="color:var(--green);font-size:12px;"></i>
                <span style="color:var(--slate);font-weight:600;font-size:12px;">Free</span>
                <span class="kc-val kc-green" style="font-size:16px;">{{ $kpi['free_slots'] }}</span>
            </div>
            <div class="kpi-chip" style="padding:5px 12px;">
                <i class="fas fa-times-circle" style="color:var(--red);font-size:12px;"></i>
                <span style="color:var(--slate);font-weight:600;font-size:12px;">Booked</span>
                <span class="kc-val kc-red" style="font-size:16px;">{{ $kpi['booked_slots'] }}</span>
                @if($kpi['pct_booked'] > 0)
                <span style="font-size:11px;color:var(--red);">{{ $kpi['pct_booked'] }}%</span>
                @endif
            </div>
        </div>
        @endif

        {{-- PDF export --}}
        @if($matrix !== null && count($matrix) > 0)
        <div style="margin-left:auto;">
            <button onclick="window.print()" class="btn btn-ghost" style="font-size:12px;height:32px;padding:0 12px;">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
        @endif
    </div>

    {{-- Row 2: filter bar (always visible, fixed) --}}
    <form method="GET" action="{{ route('slot-search.index') }}"
          style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;overflow-x:auto;padding:10px 20px;">

        <div class="flt-group">
            <span class="flt-label">From</span>
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="flt-inp" required style="padding:0 10px;">
        </div>
        <div class="flt-group">
            <span class="flt-label">To</span>
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="flt-inp" required style="padding:0 10px;">
        </div>

        @if($flatSlots->isNotEmpty())
        <div class="flt-group">
            <span class="flt-label">Slot Type</span>
            <select name="slot_ids[]" multiple class="flt-inp" style="min-width:140px;">
                @foreach($flatSlots->unique(fn($s) => $s->name.'|'.$s->start_time)->values() as $slot)
                <option value="{{ $slot->id }}" {{ in_array($slot->id, $slotIds ?? []) ? 'selected' : '' }}>
                    {{ $slot->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        @if($isMultiHotel)
        <div class="flt-group">
            <span class="flt-label">Hotels</span>
            <select name="hotel_ids[]" multiple class="flt-inp" style="min-width:130px;">
                @foreach($availableHotels as $h)
                <option value="{{ $h->id }}" {{ in_array($h->id, $filterHotelIds ?? []) ? 'selected' : '' }}>{{ $h->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="flt-group">
            <span class="flt-label">Availability</span>
            <select name="status" class="flt-inp" style="min-width:130px;">
                <option value="all"     {{ ($statusFilter??'all')==='all'     ? 'selected':'' }}>Show All</option>
                <option value="free"    {{ ($statusFilter??'all')==='free'    ? 'selected':'' }}>Available Only</option>
                <option value="partial" {{ ($statusFilter??'all')==='partial' ? 'selected':'' }}>Partial</option>
                <option value="booked"  {{ ($statusFilter??'all')==='booked'  ? 'selected':'' }}>Fully Booked</option>
            </select>
        </div>

        <div style="display:flex;gap:6px;align-items:flex-end;flex-shrink:0;">
            <button type="submit" class="btn btn-purple">
                <i class="fas fa-search" style="font-size:11px;"></i> Search
            </button>
            <a href="{{ route('slot-search.index') }}" class="btn btn-ghost" title="Reset">
                <i class="fas fa-undo" style="font-size:10px;"></i>
            </a>
        </div>
    </form>
</div>

<div class="sse" id="ssePrintArea" style="padding-top:16px;">

{{-- ── MAIN CARD ── --}}
<div class="sse-card">

    {{-- Hotel search (only when matrix is shown) --}}
    @if($matrix !== null && count($matrix) > 0)
    <div class="sse-searchbar no-print">
        <input type="text" id="hotelSearch" class="sse-searchbox" placeholder="Search hotel name…" oninput="filterHotels(this.value)">
        <span class="hotel-count-lbl" id="hotelCountLbl">{{ count($matrix) }} hotel{{ count($matrix)===1?'':'s' }}</span>
    </div>
    @endif

    {{-- ── MATRIX TABLE ── --}}
    @if($matrix === null)
    <div class="sse-empty">
        <div class="sse-empty-ico" style="background:var(--purple-bg);">
            <i class="fas fa-search" style="font-size:26px;color:var(--purple);"></i>
        </div>
        <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:8px;">Select dates and search</div>
        <div style="font-size:13px;color:var(--slate);max-width:380px;margin:0 auto;line-height:1.7;">
            Set a date range and click <strong>Search</strong> to see availability across all hotels and slot types at a glance.
        </div>
    </div>

    @elseif(empty($matrix))
    <div class="sse-empty">
        <div class="sse-empty-ico" style="background:var(--green-bg);">
            <i class="fas fa-check-circle" style="font-size:26px;color:var(--green);"></i>
        </div>
        <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:8px;">No hotels match your filters</div>
        <div style="font-size:13px;color:var(--slate);">Try adjusting the availability filter or search criteria.</div>
    </div>

    @else
    <div class="sse-tbl-wrap">
        <table class="sse-tbl" id="sseTbl">
            {{-- ── Column headers ── --}}
            <thead>
                <tr>
                    <th class="col-hotel">
                        <div style="font-size:12px;font-weight:700;color:var(--slate);">
                            Hotel <i class="fas fa-sort" style="font-size:10px;margin-left:3px;opacity:.5;"></i>
                        </div>
                    </th>
                    @foreach($slotColumns as $col)
                    @php
                        $agg = $columnAgg[$col['key']] ?? null;
                        $aggStatus = $agg ? $agg['status'] : 'free';
                    @endphp
                    <th style="min-width:150px;padding-bottom:12px;">
                        <div class="col-hdr-name">{{ $col['name'] }}</div>
                        <div style="font-size:10px;color:var(--slate);margin-bottom:5px;">{{ $col['time'] }}</div>
                        <div class="col-hdr-badges">
                            @if($agg)
                            <span class="col-agg-badge green">
                                <i class="fas fa-check" style="font-size:8px;"></i> {{ $agg['total_free'] }} Free
                            </span>
                            @if($agg['total_booked'] > 0)
                            <span class="col-agg-badge red">
                                <i class="fas fa-times" style="font-size:8px;"></i> {{ $agg['total_booked'] }} Booked
                            </span>
                            @endif
                            @if($agg['has_wh'])
                            <span class="col-agg-badge wh">
                                <i class="fas fa-hotel" style="font-size:8px;"></i> WH
                            </span>
                            @endif
                            @endif
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>

            {{-- ── Hotel rows ── --}}
            <tbody>
            @foreach($matrix as $hi => $hotel)
            {{-- Hotel summary row --}}
            <tr class="hotel-row" data-hotel-name="{{ strtolower($hotel['hotel_name']) }}" id="hrow-{{ $hi }}">
                {{-- Hotel name cell --}}
                <td class="hotel-td" onclick="toggleExpand({{ $hi }})">
                    <div style="display:flex;align-items:flex-start;gap:0;">
                        <span class="hotel-chevron-btn" id="chev-{{ $hi }}">&#8250;</span>
                        <div>
                            <div class="hotel-name-txt">
                                {{ $hotel['hotel_name'] }}
                                @if($hotel['is_wh_any'])
                                <span class="wh-tag"><i class="fas fa-hotel" style="font-size:8px;"></i> WH</span>
                                @endif
                            </div>
                            <div class="hotel-sub-txt">{{ $hotel['rooms_count'] }} slot room{{ $hotel['rooms_count']===1?'':'s' }}</div>
                        </div>
                    </div>
                </td>
                {{-- Slot cells --}}
                @foreach($slotColumns as $col)
                @php $sd = $hotel['slots'][$col['key']] ?? null; @endphp
                @if($sd && $sd['has_slot'])
                @php
                    $cellJson = json_encode([
                        'hotel'        => $hotel['hotel_name'],
                        'slot_name'    => $sd['slot_name'],
                        'slot_time'    => $sd['slot_time'],
                        'worst_free'   => $sd['worst_free'],
                        'worst_booked' => $sd['worst_booked'],
                        'total_rooms'  => $sd['total_rooms'],
                        'dates'        => $sd['dates'],
                        'is_wh_any'    => $hotel['is_wh_any'],
                    ], JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP);
                @endphp
                <td class="slot-td sse-cell"
                    data-cell="{{ $cellJson }}"
                    title="{{ $sd['worst_free'] }} free · {{ $sd['worst_booked'] }} booked">
                    <div class="slot-cell-inner">
                        <span class="count-chip green">
                            <i class="fas fa-check" style="font-size:9px;"></i> {{ $sd['worst_free'] }}
                        </span>
                        @if($sd['worst_booked'] > 0)
                        <span class="count-chip red">
                            <i class="fas fa-times" style="font-size:9px;"></i> {{ $sd['worst_booked'] }}
                        </span>
                        @else
                        <span class="count-chip green" style="opacity:.4;">0</span>
                        @endif
                    </div>
                </td>
                @else
                <td class="slot-td na" style="color:#d1d5db;font-size:13px;text-align:center;">—</td>
                @endif
                @endforeach
            </tr>

            {{-- Room-level expand rows --}}
            @foreach($hotel['rooms'] as $room)
            <tr class="expand-row" id="exp-{{ $hi }}">
                <td colspan="{{ count($slotColumns) + 1 }}">
                    <div class="expand-inner">
                        <span class="room-tag">
                            <i class="fas fa-door-open" style="font-size:9px;color:var(--slate);margin-right:3px;"></i>
                            R{{ $room['number'] }}
                        </span>
                        @foreach($slotColumns as $col)
                        @php
                            $sd = $hotel['slots'][$col['key']] ?? null;
                            if (!$sd || !$sd['has_slot']) continue;
                            $isBooked = false; $isFree = false;
                            foreach ($sd['dates'] as $dd) {
                                $match = collect($dd['booked_rooms'])->contains(fn($br) => $br['room_number'] == $room['number']);
                                if ($match) $isBooked = true; else $isFree = true;
                            }
                            $rc = $isBooked && $isFree ? 'amber' : ($isBooked ? 'red' : 'green');
                            $rl = $isBooked && $isFree ? 'Mixed' : ($isBooked ? 'Booked' : 'Free');
                        @endphp
                        <span class="room-chip {{ $rc }}" title="{{ $col['name'] }}">
                            {{ $col['name'] }}: {{ $rl }}
                        </span>
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
        <div class="leg-item"><span class="leg-dot" style="background:#22c55e;"></span> All free</div>
        <div class="leg-item"><span class="leg-dot" style="background:#f59e0b;"></span> Partial</div>
        <div class="leg-item"><span class="leg-dot" style="background:#ef4444;"></span> Fully booked</div>
        <div style="margin-left:auto;display:flex;align-items:center;gap:6px;">
            <span class="wh-tag"><i class="fas fa-hotel" style="font-size:8px;"></i> WH</span>
            <span style="font-size:11px;color:var(--slate);">Whole-Hotel booking</span>
        </div>
    </div>
    @endif

</div>{{-- /sse-card --}}
</div>{{-- /sse --}}

{{-- ── INLINE POPUP ── --}}
<div id="ssePopup">
    <div class="pp-hdr">
        <div>
            <div class="pp-hotel" id="ppHotel"></div>
            <div class="pp-slot"  id="ppSlot"></div>
        </div>
        <button class="pp-close" onclick="closePopup()">&#10005;</button>
    </div>
    <div class="pp-body" id="ppBody"></div>
</div>

<script>
// ── Expand/collapse ──────────────────────────────
function toggleExpand(hi) {
    var rows = document.querySelectorAll('#exp-' + hi);
    var chev = document.getElementById('chev-' + hi);
    var open = chev.classList.contains('open');
    rows.forEach(function(r) { r.style.display = open ? 'none' : 'table-row'; });
    chev.classList.toggle('open', !open);
}

// ── Hotel search ─────────────────────────────────
function filterHotels(q) {
    q = (q || '').toLowerCase().trim();
    var rows = document.querySelectorAll('.hotel-row');
    var vis = 0;
    rows.forEach(function(r) {
        var show = !q || (r.dataset.hotelName || '').indexOf(q) !== -1;
        r.style.display = show ? '' : 'none';
        if (show) vis++;
        // collapse hidden hotels' expand rows
        var hi = r.id.replace('hrow-', '');
        var chev = document.getElementById('chev-' + hi);
        if (!show && chev && chev.classList.contains('open')) {
            document.querySelectorAll('#exp-' + hi).forEach(function(er) { er.style.display = 'none'; });
            chev.classList.remove('open');
        }
    });
    var lbl = document.getElementById('hotelCountLbl');
    if (lbl) lbl.textContent = vis + ' hotel' + (vis === 1 ? '' : 's');
}

// ── Inline popup ─────────────────────────────────
var _pp = null;
function showPopup(e, data) {
    e.stopPropagation();
    if (!data) return;
    _pp = data;

    document.getElementById('ppHotel').textContent = data.hotel;
    document.getElementById('ppSlot').textContent  = data.slot_name + ' · ' + data.slot_time;

    // Gather merged free/booked rooms across all dates
    var allBooked = {}, allFree = {};
    var hasWH = false;
    var dates = data.dates || {};
    Object.keys(dates).sort().forEach(function(ds) {
        var dd = dates[ds];
        if (dd.whole_hotel) { hasWH = true; return; }
        (dd.booked_rooms || []).forEach(function(br) { allBooked[br.room_number] = br.guest_name; });
        (dd.free_rooms || []).forEach(function(rn) { allFree[rn] = true; });
        // remove from free if also booked
        Object.keys(allBooked).forEach(function(rn) { delete allFree[rn]; });
    });

    var body = '';
    if (hasWH) {
        body += '<div class="pp-wh-bar"><i class="fas fa-hotel"></i> Whole Hotel booking on at least one day</div>';
    }

    // Summary counts
    body += '<div style="display:flex;gap:12px;margin-bottom:12px;">';
    body += '<div style="text-align:center;"><div style="font-size:20px;font-weight:800;color:var(--green);">' + data.worst_free + '</div><div style="font-size:10px;color:var(--slate);">Min free / day</div></div>';
    body += '<div style="text-align:center;"><div style="font-size:20px;font-weight:800;color:var(--red);">' + data.worst_booked + '</div><div style="font-size:10px;color:var(--slate);">Max booked / day</div></div>';
    body += '<div style="text-align:center;"><div style="font-size:20px;font-weight:800;color:#1e293b;">' + data.total_rooms + '</div><div style="font-size:10px;color:var(--slate);">Total rooms</div></div>';
    body += '</div>';

    // Free rooms
    var freeKeys = Object.keys(allFree);
    body += '<div class="pp-section-title">Free Rooms (' + freeKeys.length + ')</div>';
    body += '<div class="pp-pills-wrap">';
    if (freeKeys.length === 0) {
        body += '<span style="font-size:12px;color:var(--slate);">None free on all days</span>';
    } else {
        freeKeys.forEach(function(rn) {
            body += '<span class="pp-room-pill free"><i class="fas fa-check" style="font-size:8px;"></i> R' + rn + '</span>';
        });
    }
    body += '</div>';

    // Booked rooms
    var bookedKeys = Object.keys(allBooked);
    body += '<div class="pp-section-title" style="margin-top:10px;">Booked Rooms (' + bookedKeys.length + ')</div>';
    body += '<div class="pp-pills-wrap">';
    if (bookedKeys.length === 0) {
        body += '<span style="font-size:12px;color:var(--slate);">No rooms booked</span>';
    } else {
        bookedKeys.forEach(function(rn) {
            body += '<span class="pp-room-pill booked" title="' + (allBooked[rn] || '') + '">';
            body += '<i class="fas fa-times" style="font-size:8px;"></i> R' + rn;
            body += '<span style="font-weight:400;font-size:10px;margin-left:3px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (allBooked[rn] || '') + '</span>';
            body += '</span>';
        });
    }
    body += '</div>';

    document.getElementById('ppBody').innerHTML = body;

    // Position popup near click
    var pop = document.getElementById('ssePopup');
    pop.style.display = 'block';
    var vw = window.innerWidth, vh = window.innerHeight;
    var pw = 320, ph = 320;
    var x = e.clientX + 12, y = e.clientY + 12;
    if (x + pw > vw - 10) x = e.clientX - pw - 10;
    if (y + ph > vh - 10) y = e.clientY - ph - 10;
    if (x < 8) x = 8;
    if (y < 8) y = 8;
    pop.style.left = x + 'px';
    pop.style.top  = y + 'px';
}

function closePopup() {
    document.getElementById('ssePopup').style.display = 'none';
    _pp = null;
}

// Event delegation — handle clicks on .sse-cell td elements
document.addEventListener('click', function(e) {
    var pop = document.getElementById('ssePopup');
    // Close popup on outside click
    if (pop && pop.style.display !== 'none' && !pop.contains(e.target)) {
        var td = e.target.closest('td.sse-cell');
        if (!td) { closePopup(); return; }
    }
    // Open popup when a cell is clicked
    var td = e.target.closest('td.sse-cell');
    if (td) {
        e.stopPropagation();
        var raw = td.getAttribute('data-cell');
        if (!raw) return;
        try {
            var data = JSON.parse(raw);
            showPopup(e, data);
        } catch(err) {
            console.error('SSE popup parse error:', err, raw);
        }
        return;
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePopup();
});

// Sync thead sticky top with topbar actual height
function syncTheadTop() {
    var bar = document.getElementById('sse-topbar');
    if (!bar) return;
    var h = bar.offsetHeight;
    document.querySelectorAll('.sse-tbl thead th').forEach(function(th) {
        th.style.top = h + 'px';
    });
}
document.addEventListener('DOMContentLoaded', syncTheadTop);
window.addEventListener('resize', syncTheadTop);
// Run immediately (in case DOM is already ready)
syncTheadTop();
</script>
@endsection
