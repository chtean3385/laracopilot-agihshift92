<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Slot Availability Report — {{ $hotelName }}</title>
<style>
/* ─────────────────────────────────────────────────────────────
   PAGE SETUP  –  landscape A4, 1 cm margins
───────────────────────────────────────────────────────────── */
@page {
    size: A4 landscape;
    margin: 1cm 1.2cm;
}

/* Force colors to print exactly as shown (no browser grey-out) */
* {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    box-sizing: border-box;
}

/* ─── Base ─────────────────────────────────────────────────── */
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #fff;
    margin: 0;
    padding: 0;
}

/* ─── Header ───────────────────────────────────────────────── */
.pdf-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #7c3aed;
}
.pdf-title { font-size: 18px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.pdf-sub   { font-size: 11px; color: #64748b; margin-top: 3px; }
.pdf-meta  { text-align: right; font-size: 10px; color: #64748b; line-height: 1.7; }
.pdf-badge {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700;
    background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
}

/* ─── KPI strip ────────────────────────────────────────────── */
.kpi-strip {
    display: flex; gap: 10px; margin-bottom: 14px;
}
.kpi-box {
    flex: 1; border-radius: 10px; padding: 8px 12px;
    border: 1px solid #e2e8f0;
}
.kpi-box .kv { font-size: 20px; font-weight: 800; line-height: 1; margin-top: 4px; }
.kpi-box .kl { font-size: 9px; font-weight: 700; text-transform: uppercase;
               letter-spacing: .5px; color: #64748b; }
.kpi-box .ks { font-size: 9px; color: #94a3b8; margin-top: 2px; }

/* ─── Matrix table ─────────────────────────────────────────── */
.matrix-table {
    width: 100%; border-collapse: collapse;
    table-layout: auto;
}
.matrix-table th {
    font-size: 10px; font-weight: 700; color: #64748b;
    padding: 7px 8px; border-bottom: 2px solid #e2e8f0;
    background: #f8fafc; text-align: center; white-space: nowrap;
}
.matrix-table th.hotel-th {
    text-align: left; min-width: 160px; padding-left: 10px;
    border-right: 1px solid #e2e8f0;
}
.slot-th-name { font-size: 11px; font-weight: 700; color: #1e293b; }
.slot-th-time { font-size: 9px; color: #94a3b8; margin-top: 1px; }

/* Hotel rows */
.hotel-row td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.hotel-row:nth-child(even) td { background: #fafbff; }
.hotel-name-td { padding: 8px 10px !important; border-right: 1px solid #e2e8f0; }
.hotel-name     { font-size: 12px; font-weight: 700; color: #1e293b; }
.hotel-meta     { font-size: 9px; color: #94a3b8; margin-top: 1px; }

/* Cell badges */
.cell-wrap  { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.cell-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
    border-width: 1px; border-style: solid;
}
.cell-badge.green { background: #f0fdf4; color: #16a34a; border-color: #86efac; }
.cell-badge.amber { background: #fffbeb; color: #d97706; border-color: #fcd34d; }
.cell-badge.red   { background: #fff1f2; color: #dc2626; border-color: #fca5a5; }
.cell-badge.na    { background: #f8fafc; color: #cbd5e1; border-color: #e2e8f0; font-size: 10px; }

/* Room pills */
.room-pills  { display: flex; flex-wrap: wrap; gap: 2px; justify-content: center; margin-top: 2px; }
.rpill       { border-radius: 5px; padding: 1px 5px; font-size: 9px; font-weight: 600;
               border-width: 1px; border-style: solid; white-space: nowrap; }
.rpill.booked{ background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
.rpill.free  { background: #dcfce7; color: #15803d; border-color: #86efac; }
.rpill.wh    { background: #fef3c7; color: #92400e; border-color: #fde68a; }

/* WH badge */
.wh-badge { display: inline-block; background: #fef3c7; border: 1px solid #fde68a;
            color: #92400e; border-radius: 20px; padding: 1px 6px;
            font-size: 8px; font-weight: 700; margin-left: 4px; }

/* ─── Legend ───────────────────────────────────────────────── */
.legend {
    display: flex; gap: 14px; align-items: center;
    margin-top: 14px; padding-top: 10px; border-top: 1px solid #e2e8f0;
    flex-wrap: wrap;
}
.leg-item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #64748b; }
.leg-dot  { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.leg-swatch { width: 9px; height: 9px; border-radius: 2px; display: inline-block; border-width:1px; border-style:solid; flex-shrink: 0; }

/* ─── Footer ───────────────────────────────────────────────── */
.pdf-footer {
    margin-top: 12px; padding-top: 8px; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 9px; color: #94a3b8;
}

/* ─── Screen-only: show print button ──────────────────────── */
@media screen {
    body { padding: 24px; background: #f1f5f9; }
    .pdf-page { background: #fff; padding: 24px; border-radius: 14px;
                box-shadow: 0 4px 24px rgba(0,0,0,.1); max-width: 1100px; margin: 0 auto; }
    .print-toolbar {
        max-width: 1100px; margin: 0 auto 16px; display: flex;
        align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .btn-print {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px; border-radius: 10px; border: none; cursor: pointer;
        font-size: 14px; font-weight: 700;
        background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
        box-shadow: 0 3px 10px rgba(124,58,237,.35);
    }
    .btn-close {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 10px; border: 1px solid #ddd6fe; cursor: pointer;
        font-size: 14px; font-weight: 600; background: #fff; color: #7c3aed; text-decoration: none;
    }
}

@media print {
    body   { padding: 0; background: #fff; }
    .pdf-page { padding: 0; box-shadow: none; border-radius: 0; }
    .print-toolbar { display: none !important; }
}
</style>
</head>
<body>

{{-- Screen toolbar (hidden on print) --}}
<div class="print-toolbar">
    <div>
        <div style="font-size:16px;font-weight:800;color:#1e293b;">Slot Availability Report Preview</div>
        <div style="font-size:12px;color:#64748b;">Click "Print / Save PDF" to export as PDF — choose landscape + colour in the dialog.</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <button class="btn-print" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
            Print / Save PDF
        </button>
        <a href="javascript:history.back()" class="btn-close">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Back
        </a>
    </div>
</div>

<div class="pdf-page">

    {{-- Header --}}
    <div class="pdf-header">
        <div>
            <div class="pdf-title">Slot Availability Report</div>
            <div class="pdf-sub">
                {{ $hotelName }}
                @if($availableHotels->count() > 1)
                    &nbsp;&middot;&nbsp; {{ $availableHotels->count() }} hotels
                @endif
                &nbsp;&middot;&nbsp; Hotels as rows, Slot types as columns
            </div>
        </div>
        <div class="pdf-meta">
            <div><span class="pdf-badge">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</span></div>
            <div style="margin-top:5px;">Generated: {{ $generatedAt }}</div>
            <div>{{ count($matrix) }} hotel{{ count($matrix) === 1 ? '' : 's' }} &middot; {{ count($slotColumns) }} slot type{{ count($slotColumns) === 1 ? '' : 's' }}</div>
        </div>
    </div>

    {{-- KPI strip --}}
    @if($kpi)
    <div class="kpi-strip">
        <div class="kpi-box" style="border-color:#ddd6fe;background:#f5f3ff;">
            <div class="kl" style="color:#7c3aed;">Hotels</div>
            <div class="kv" style="color:#7c3aed;">{{ $kpi['total_hotels'] }}</div>
            <div class="ks">in report</div>
        </div>
        <div class="kpi-box" style="border-color:#86efac;background:#f0fdf4;">
            <div class="kl" style="color:#16a34a;">Free Room-Days</div>
            <div class="kv" style="color:#16a34a;">{{ number_format($kpi['free_room_days']) }}</div>
            <div class="ks">{{ 100 - $kpi['pct_booked'] }}% of capacity</div>
        </div>
        <div class="kpi-box" style="border-color:#fca5a5;background:#fff1f2;">
            <div class="kl" style="color:#dc2626;">Booked Room-Days</div>
            <div class="kv" style="color:#dc2626;">{{ number_format($kpi['booked_room_days']) }}</div>
            <div class="ks">{{ $kpi['pct_booked'] }}% occupancy</div>
        </div>
        <div class="kpi-box" style="border-color:#fcd34d;background:#fffbeb;">
            <div class="kl" style="color:#d97706;">Slot Types</div>
            <div class="kv" style="color:#d97706;">{{ count($slotColumns) }}</div>
            <div class="ks">{{ \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1 }} days</div>
        </div>
    </div>
    @endif

    {{-- Matrix table --}}
    @if(empty($matrix) || empty($slotColumns))
    <div style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">No slot availability data for this date range.</div>
    @else
    <table class="matrix-table">
        <thead>
            <tr>
                <th class="hotel-th">Hotel</th>
                @foreach($slotColumns as $col)
                <th style="min-width:110px;">
                    <div class="slot-th-name">{{ $col['name'] }}</div>
                    <div class="slot-th-time">{{ $col['time'] }}</div>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($matrix as $hotel)
            <tr class="hotel-row">
                <td class="hotel-name-td">
                    <div class="hotel-name">
                        {{ $hotel['hotel_name'] }}
                        @if($hotel['is_wh_any'])
                        <span class="wh-badge">&#127981; WH</span>
                        @endif
                    </div>
                    <div class="hotel-meta">{{ $hotel['rooms_count'] }} slot room{{ $hotel['rooms_count'] === 1 ? '' : 's' }}</div>
                </td>
                @foreach($slotColumns as $col)
                @php $sd = $hotel['slots'][$col['key']] ?? null; @endphp
                <td style="text-align:center;">
                    @if($sd && $sd['has_slot'])
                    @php
                        $bgMap  = ['green'=>'#f0fdf4','amber'=>'#fffbeb','red'=>'#fff1f2'];
                        $txtMap = ['green'=>'#16a34a','amber'=>'#d97706','red'=>'#dc2626'];
                        $bdMap  = ['green'=>'#86efac','amber'=>'#fcd34d','red'=>'#fca5a5'];
                        $icoMap = ['green'=>'✓','amber'=>'~','red'=>'✕'];
                        $clr    = $sd['color'];

                        // Collect booked rooms across ALL dates for this cell
                        $allBookedRooms = []; $allFreeRooms = []; $isWH = false;
                        foreach ($sd['dates'] as $ds => $dd) {
                            if ($dd['whole_hotel'] ?? false) { $isWH = true; }
                            foreach ($dd['booked_rooms'] ?? [] as $br) {
                                $allBookedRooms[$br['room_number']] = $br;
                            }
                            foreach ($dd['free_rooms'] ?? [] as $rn) {
                                if (!isset($allBookedRooms[$rn])) $allFreeRooms[$rn] = true;
                            }
                        }
                    @endphp
                    <div class="cell-wrap">
                        <span class="cell-badge {{ $clr }}">
                            {{ $icoMap[$clr] }} {{ $sd['worst_free'] }}/{{ $sd['total_rooms'] }}
                        </span>
                        <div class="room-pills">
                            @foreach($allBookedRooms as $rn => $br)
                            <span class="rpill {{ $br['whole_hotel'] ?? false ? 'wh' : 'booked' }}" title="{{ $br['guest_name'] }}">R{{ $rn }}</span>
                            @endforeach
                            @foreach(array_keys($allFreeRooms) as $rn)
                            <span class="rpill free">R{{ $rn }}</span>
                            @endforeach
                        </div>
                        @if($isWH)
                        <span style="font-size:8px;color:#92400e;background:#fef9c3;border:1px solid #fde68a;border-radius:5px;padding:1px 5px;">Whole Hotel</span>
                        @endif
                    </div>
                    @else
                    <span class="cell-badge na">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Legend --}}
    <div class="legend">
        <div class="leg-item">
            <span class="leg-dot" style="background:#22c55e;"></span>
            Green — Fully Available (0% booked across dates)
        </div>
        <div class="leg-item">
            <span class="leg-dot" style="background:#f59e0b;"></span>
            Amber — Partial (some bookings in date range)
        </div>
        <div class="leg-item">
            <span class="leg-dot" style="background:#ef4444;"></span>
            Red — Fully Booked (100% on worst day)
        </div>
        <div class="leg-item">
            <span class="leg-swatch" style="background:#fee2e2;border-color:#fca5a5;"></span>
            Booked room
        </div>
        <div class="leg-item">
            <span class="leg-swatch" style="background:#dcfce7;border-color:#86efac;"></span>
            Free room
        </div>
        <div class="leg-item">
            <span class="wh-badge" style="margin-left:0;">WH</span>
            Whole-hotel booking
        </div>
        <div style="margin-left:auto;font-size:9px;color:#94a3b8;">
            Badge shows: min free rooms / total rooms across date range
        </div>
    </div>

    {{-- Footer --}}
    <div class="pdf-footer">
        <div>&copy; {{ date('Y') }} {{ $hotelName }} &mdash; Hotel &amp; Resort Management System</div>
        <div>Generated: {{ $generatedAt }} &nbsp;|&nbsp; Slot Availability Report</div>
    </div>

</div>{{-- /pdf-page --}}

<script>
// Auto-trigger print dialog when opened via the Export PDF button
// (only if ?autoprint=1 is in the URL)
if (window.location.search.indexOf('autoprint=1') !== -1) {
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
}
</script>
</body>
</html>
