@extends('layouts.admin')
@section('title', 'Slot Search Engine')
@section('page-title', 'Slot Search Engine')
@section('page-subtitle', 'Slot availability matrix — dates as columns, time slots as rows, all hotels merged.')

@section('content')
@push('styles')
<style>
/* ── Layout ── */
.ss-page { max-width:1440px;margin:0 auto;padding:24px 16px; }
.ss-card { background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden; }

/* ── Header ── */
.ss-header { padding:18px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f5f3ff,#ede9fe);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px; }
.ss-header-icon { width:46px;height:46px;background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(124,58,237,.3);flex-shrink:0; }

/* ── Filter bar ── */
.ss-filter-bar { padding:16px 24px;border-bottom:1px solid #f1f5f9;background:#fafafa;display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap; }
.ss-filter-label { font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px; }
.ss-filter-inp { border:1px solid #e2e8f0;border-radius:10px;padding:7px 11px;font-size:13px;color:#1e293b;outline:none;background:#fff;transition:border .15s;display:block; }
.ss-filter-inp:focus { border-color:#a78bfa; }
.ss-btn { display:inline-flex;align-items:center;gap:7px;padding:0 18px;height:38px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:600;transition:all .15s;text-decoration:none; }
.ss-btn-primary { background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 3px 8px rgba(124,58,237,.3); }
.ss-btn-primary:hover { opacity:.9;color:#fff; }
.ss-btn-outline { background:#fff;border:1px solid #ddd6fe;color:#7c3aed; }
.ss-btn-outline:hover { background:#f5f3ff;color:#7c3aed; }
.ss-btn-pdf { background:#fff;border:1px solid #d1d5db;color:#374151; }
.ss-btn-pdf:hover { background:#f9fafb;color:#374151; }

/* ── Summary bar ── */
.ss-sum-bar { padding:12px 24px;border-bottom:1px solid #f1f5f9;background:#fff;display:flex;gap:14px;flex-wrap:wrap; }
.ss-sum-pill { display:flex;align-items:center;gap:7px;padding:5px 13px;border-radius:30px;font-size:13px;font-weight:600; }

/* ── Matrix table ── */
.ss-table-wrap { padding:20px;overflow-x:auto; }
.ss-table { width:100%;border-collapse:collapse; }
.ss-table th { font-size:12px;font-weight:700;color:#64748b;padding:8px 10px;border-bottom:2px solid #f1f5f9;white-space:nowrap;text-align:center; }
.ss-table th.slot-col { text-align:left; }
.ss-table th.today-col { color:#7c3aed;border-bottom-color:#a78bfa;background:linear-gradient(180deg,#f5f3ff,transparent); }
.ss-slot-label { padding:10px 14px;white-space:nowrap;vertical-align:middle; }
.ss-slot-name { font-weight:700;color:#1e293b;font-size:13px;line-height:1.3; }
.ss-slot-time { font-size:11px;color:#94a3b8;margin-top:2px; }
.ss-cell { text-align:center;padding:6px 4px;vertical-align:top; }
.ss-cell.today-col { background:#faf5ff; }
.ss-cell-inner { display:inline-flex;flex-direction:column;align-items:center;gap:4px;min-width:76px;cursor:default; }
.ss-badge { font-weight:800;font-size:13px;line-height:1;padding:3px 10px;border-radius:999px;display:inline-block; }
.ss-badge.green { background:#f0fdf4;color:#16a34a; }
.ss-badge.amber { background:#fffbeb;color:#d97706; }
.ss-badge.red   { background:#fff1f2;color:#dc2626; }
.ss-pill { border-radius:6px;padding:2px 6px;font-size:10px;line-height:1.4;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:90px;display:block;margin-bottom:1px; }
.ss-pill.booked { background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c; }
.ss-pill.free   { background:#dcfce7;border:1px solid #86efac;color:#15803d; }
.ss-wh-banner { margin-top:3px;border-radius:7px;padding:3px 7px;background:#fef3c7;border:1px solid #fde68a;font-size:9px;color:#92400e;line-height:1.4;max-width:90px;text-align:left; }

/* ── Legend ── */
.ss-legend { display:flex;align-items:center;gap:16px;padding:12px 20px;border-top:1px solid #f1f5f9;flex-wrap:wrap; }
.ss-legend-item { display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b; }
.ss-dot   { width:10px;height:10px;border-radius:50%;display:inline-block; }
.ss-swatch{ width:10px;height:10px;border-radius:3px;display:inline-block; }

/* ── Tooltip ── */
#ssTooltip { display:none;position:fixed;z-index:9999;background:#1e293b;color:#fff;border-radius:12px;padding:12px 14px;font-size:12px;max-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.25);pointer-events:none;line-height:1.6; }

/* ── PDF / print ── */
.ss-pdf-header { display:none;padding:0 0 10px 0;border-bottom:1px solid #e5e7eb;margin-bottom:12px; }
@media print {
    body * { visibility:hidden; }
    #ssPrintArea, #ssPrintArea * { visibility:visible; }
    #ssPrintArea { position:fixed;top:0;left:0;width:100%;background:#fff;padding:0; }
    .ss-filter-bar, .ss-header-actions, #ssTooltip, .no-print { display:none !important; }
    .ss-table-wrap { padding:8px 0;overflow:visible !important; }
    .ss-table { font-size:10px; }
    .ss-pill { max-width:76px;font-size:9px; }
    .ss-badge { font-size:11px;padding:2px 7px; }
    .ss-card { box-shadow:none !important;border:1px solid #e5e7eb !important; }
    .ss-header { print-color-adjust:exact;-webkit-print-color-adjust:exact; }
    .ss-pdf-header { display:block !important; }
    .ss-sum-bar { flex-wrap:nowrap; }
}
</style>
@endpush

<div class="ss-page" id="ssPrintArea">

{{-- Page heading --}}
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;" class="no-print">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#1e293b;margin:0;">Slot Search Engine</h1>
        <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Slot availability matrix — time slots as rows, dates as columns, all hotels merged.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:13px;color:#64748b;">{{ \Carbon\Carbon::now()->format('D, d M Y') }}</span>
        <span style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">
            {{ session('crm_hotel_name') }}
        </span>
    </div>
</div>

{{-- Main card --}}
<div class="ss-card">

    {{-- ── Card header ── --}}
    <div class="ss-header">
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="ss-header-icon"><i class="fas fa-clock" style="color:#fff;font-size:17px;"></i></div>
            <div>
                <div style="font-weight:800;color:#1e293b;font-size:16px;">Slot Availability Matrix</div>
                <div style="font-size:12px;color:#6d28d9;">
                    @if($matrix !== null)
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                        &nbsp;&middot;&nbsp; {{ count($matrix) }} slot type{{ count($matrix) === 1 ? '' : 's' }}
                        @if($isMultiHotel) &nbsp;&middot;&nbsp; {{ $availableHotels->count() }} hotels merged @endif
                    @else
                        Select a date range and click Search
                    @endif
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;" class="ss-header-actions">
            @if($matrix !== null)
            <button type="button" onclick="printSlotSearch()" class="ss-btn ss-btn-pdf">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            @endif
            <a href="{{ route('dashboard') }}" class="ss-btn ss-btn-outline">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- ── Filter bar ── --}}
    <form method="GET" action="{{ route('slot-search.index') }}" class="ss-filter-bar no-print">
        <div>
            <span class="ss-filter-label">From Date</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="ss-filter-inp" required>
        </div>
        <div>
            <span class="ss-filter-label">To Date</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="ss-filter-inp" required>
        </div>
        @if($isMultiHotel)
        <div>
            <span class="ss-filter-label">Hotels</span>
            <select name="hotel_ids[]" multiple class="ss-filter-inp" style="min-width:140px;height:38px;">
                @foreach($availableHotels as $h)
                <option value="{{ $h->id }}" {{ in_array($h->id, $filterHotelIds ?? []) ? 'selected' : '' }}>{{ $h->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($flatSlots->isNotEmpty())
        <div>
            <span class="ss-filter-label">Time Slots</span>
            <select name="slot_ids[]" multiple class="ss-filter-inp" style="min-width:170px;height:38px;">
                @foreach($flatSlots->unique(fn($s) => $s->name . '|' . $s->start_time)->values() as $slot)
                <option value="{{ $slot->id }}" {{ in_array($slot->id, $slotIds ?? []) ? 'selected' : '' }}>
                    {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }})
                </option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <span class="ss-filter-label">Status</span>
            <select name="status" class="ss-filter-inp" style="min-width:120px;">
                <option value="all"    {{ ($statusFilter ?? 'all') === 'all'    ? 'selected' : '' }}>All</option>
                <option value="free"   {{ ($statusFilter ?? 'all') === 'free'   ? 'selected' : '' }}>Free Only</option>
                <option value="booked" {{ ($statusFilter ?? 'all') === 'booked' ? 'selected' : '' }}>Booked Only</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="ss-btn ss-btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="{{ route('slot-search.index') }}" class="ss-btn ss-btn-outline">
                <i class="fas fa-times" style="font-size:11px;"></i> Reset
            </a>
        </div>
    </form>

    {{-- ── Summary bar ── --}}
    @if($matrix !== null && !empty($summary))
    <div class="ss-sum-bar">
        <div class="ss-sum-pill" style="background:#f5f3ff;color:#6d28d9;">
            <i class="fas fa-th" style="font-size:11px;"></i> {{ $summary['total'] }} slot-days searched
        </div>
        <div class="ss-sum-pill" style="background:#f0fdf4;color:#16a34a;">
            <i class="fas fa-check-circle" style="font-size:11px;"></i> {{ $summary['free'] }} free
        </div>
        <div class="ss-sum-pill" style="background:#fff1f2;color:#dc2626;">
            <i class="fas fa-bed" style="font-size:11px;"></i> {{ $summary['booked'] }} booked
        </div>
        @if(($summary['wh'] ?? 0) > 0)
        <div class="ss-sum-pill" style="background:#fef9c3;color:#92400e;">
            <i class="fas fa-hotel" style="font-size:11px;"></i> {{ $summary['wh'] }} whole-hotel day(s)
        </div>
        @endif
    </div>
    @endif

    {{-- ── PDF print header (only visible when printing) ── --}}
    <div class="ss-pdf-header" id="ssPdfHeader" style="padding:16px 24px 10px;">
        <strong style="font-size:15px;color:#1e293b;">Slot Availability Report — {{ session('crm_hotel_name') }}</strong><br>
        <span style="font-size:12px;color:#64748b;">
            Period: {{ isset($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '' }}
            – {{ isset($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '' }}
            &nbsp;|&nbsp; Generated: {{ \Carbon\Carbon::now()->format('d M Y h:i A') }}
        </span>
    </div>

    {{-- ── Matrix / Empty states ── --}}
    @if($matrix === null)
    {{-- Initial state: no search yet --}}
    <div style="text-align:center;padding:64px 24px;">
        <div style="width:72px;height:72px;background:#f5f3ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-search" style="font-size:28px;color:#7c3aed;"></i>
        </div>
        <div style="font-size:17px;font-weight:700;color:#1e293b;margin-bottom:8px;">Set your search criteria</div>
        <div style="font-size:14px;color:#64748b;max-width:380px;margin:0 auto;line-height:1.6;">
            Choose a date range above and click <strong>Search</strong> to see the slot availability matrix — time slots as rows, dates as columns, all hotels merged.
        </div>
    </div>

    @elseif(empty($matrix))
    {{-- Search returned no results --}}
    <div style="text-align:center;padding:64px 24px;">
        <div style="width:72px;height:72px;background:#f0fdf4;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-check-circle" style="font-size:28px;color:#16a34a;"></i>
        </div>
        <div style="font-size:17px;font-weight:700;color:#1e293b;margin-bottom:8px;">No results match your filters</div>
        <div style="font-size:14px;color:#64748b;">Try adjusting the Status filter or date range, or make sure hotels have time slots and per-slot rooms configured.</div>
    </div>

    @else
    {{-- ──────────────────────────────────────────────────────
         Slot × Date matrix — same style as dashboard widget
         Rows    = unique time slot types (merged across hotels)
         Columns = dates in the selected range
    ────────────────────────────────────────────────────── --}}
    <div class="ss-table-wrap">
        <table class="ss-table">
            <thead>
                <tr>
                    <th class="slot-col" style="text-align:left;min-width:160px;padding-left:14px;">Time Slot</th>
                    @foreach($dates as $day)
                    <th class="{{ $day['isToday'] ? 'today-col' : '' }}" style="min-width:90px;">
                        <div>{{ $day['label'] }}</div>
                        <div style="font-size:10px;font-weight:500;color:{{ $day['isToday'] ? '#8b5cf6' : '#94a3b8' }};">{{ $day['sublabel'] }}</div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $slotRow)
                <tr style="border-bottom:1px solid #f8fafc;">
                    <td class="ss-slot-label">
                        <div class="ss-slot-name">{{ $slotRow['slot_name'] }}</div>
                        <div class="ss-slot-time">{{ $slotRow['slot_time'] }}</div>
                    </td>
                    @foreach($dates as $day)
                    @php
                        $ds          = $day['date'];
                        $sd          = $slotRow['dates'][$ds] ?? null;
                        $sdColor     = $sd ? $sd['color'] : 'green';
                        $bookedRooms = $sd['booked_rooms'] ?? [];
                        $freeRooms   = $sd['free_rooms']   ?? [];
                        $whList      = $sd['whole_hotel_list'] ?? [];
                    @endphp
                    <td class="ss-cell {{ $day['isToday'] ? 'today-col' : '' }}">
                        @if($sd)
                        <div class="ss-cell-inner slot-cell-wrap"
                             data-booked="{{ htmlspecialchars(json_encode($bookedRooms), ENT_QUOTES) }}"
                             data-free="{{ htmlspecialchars(json_encode($freeRooms), ENT_QUOTES) }}"
                             data-wh="{{ htmlspecialchars(json_encode($whList), ENT_QUOTES) }}"
                             data-slot="{{ htmlspecialchars($slotRow['slot_name'], ENT_QUOTES) }}"
                             data-day="{{ $day['label'] }} {{ $day['sublabel'] }}">
                            {{-- Availability count badge --}}
                            <span class="ss-badge {{ $sdColor }}">
                                {{ $sd['available'] }}<span style="font-weight:400;color:#94a3b8;font-size:11px;">/{{ $sd['total'] }}</span>
                            </span>
                            {{-- Room pills --}}
                            <div style="display:flex;flex-direction:column;width:100%;">
                                @foreach($bookedRooms as $br)
                                <div class="ss-pill booked" title="{{ $br['guest_name'] ?? '' }}">
                                    <span style="font-weight:700;">R{{ $br['room_number'] }}</span>
                                </div>
                                @endforeach
                                @foreach($freeRooms as $rn)
                                <div class="ss-pill free">
                                    <span style="font-weight:700;">R{{ $rn }}</span> <span style="color:#4ade80;font-size:9px;">free</span>
                                </div>
                                @endforeach
                            </div>
                            {{-- Whole-hotel banner --}}
                            @if(!empty($whList))
                            <div class="ss-wh-banner">
                                <i class="fas fa-hotel" style="font-size:9px;"></i>
                                Whole Hotel &mdash; {{ $whList[0]['guest_name'] ?? '' }}
                            </div>
                            @endif
                        </div>
                        @else
                        <div style="color:#e2e8f0;font-size:11px;padding:4px 0;">—</div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="ss-legend">
        <div class="ss-legend-item"><span class="ss-dot" style="background:#22c55e;"></span> Available (&lt;60% booked)</div>
        <div class="ss-legend-item"><span class="ss-dot" style="background:#f59e0b;"></span> Filling up (60–99%)</div>
        <div class="ss-legend-item"><span class="ss-dot" style="background:#ef4444;"></span> Fully booked (100%)</div>
        <div style="margin-left:auto;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div class="ss-legend-item"><span class="ss-swatch" style="background:#fee2e2;border:1px solid #fca5a5;"></span> Booked room</div>
            <div class="ss-legend-item"><span class="ss-swatch" style="background:#dcfce7;border:1px solid #86efac;"></span> Free room</div>
            <div class="ss-legend-item"><span class="ss-swatch" style="background:#fef3c7;border:1px solid #fde68a;"></span> Whole-hotel</div>
        </div>
    </div>
    @endif

</div>{{-- /ss-card --}}
</div>{{-- /ss-page --}}

{{-- Hover tooltip --}}
<div id="ssTooltip"></div>

<script>
(function () {
    var tip = document.getElementById('ssTooltip');
    document.querySelectorAll('.slot-cell-wrap').forEach(function (el) {
        el.addEventListener('mouseenter', function (e) {
            var booked = JSON.parse(el.dataset.booked || '[]');
            var free   = JSON.parse(el.dataset.free   || '[]');
            var wh     = JSON.parse(el.dataset.wh     || '[]');
            var html   = '<div style="font-weight:700;margin-bottom:6px;color:#a78bfa;">'
                       + el.dataset.slot + ' &bull; ' + el.dataset.day + '</div>';
            if (wh.length) {
                html += '<div style="color:#fcd34d;font-weight:600;margin-bottom:3px;"><i class="fas fa-hotel"></i> Whole Hotel Booking</div>';
                wh.forEach(function (w) {
                    html += '<div style="color:#fde68a;padding:1px 0;">Guest: ' + w.guest_name + '</div>';
                });
            }
            if (booked.length) {
                html += '<div style="color:#fca5a5;font-weight:600;margin:6px 0 3px;">Booked rooms:</div>';
                booked.forEach(function (r) {
                    html += '<div style="color:#fecaca;">&#9679; Room ' + r.room_number + ' &mdash; ' + r.guest_name + '</div>';
                });
            }
            if (free.length) {
                html += '<div style="color:#86efac;font-weight:600;margin:6px 0 3px;">Free rooms:</div>';
                free.forEach(function (r) {
                    html += '<div style="color:#bbf7d0;">&#9679; Room ' + r + '</div>';
                });
            }
            if (!booked.length && !free.length && !wh.length) {
                html += '<div style="color:#94a3b8;">No room data</div>';
            }
            tip.innerHTML = html;
            tip.style.display = 'block';
            posTip(e);
        });
        el.addEventListener('mousemove', posTip);
        el.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
    });

    function posTip(e) {
        var x = e.clientX + 16, y = e.clientY + 16;
        if (x + 260 > window.innerWidth)  x = e.clientX - 270;
        if (y + 220 > window.innerHeight) y = e.clientY - 230;
        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
    }
})();

function printSlotSearch() {
    var hdr = document.getElementById('ssPdfHeader');
    if (hdr) hdr.style.display = 'block';
    window.print();
    setTimeout(function () {
        if (hdr) hdr.style.removeProperty('display');
    }, 800);
}
</script>
@endsection
