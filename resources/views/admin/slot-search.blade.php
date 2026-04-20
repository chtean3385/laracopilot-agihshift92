@extends('layouts.admin')
@section('title', 'Slot Search Engine')
@section('page-title', 'Slot Search Engine')
@section('page-subtitle', 'Search slot availability across date ranges, time slots and rooms.')

@section('content')
<style>
/* ── Card shell ── */
.ss-card {
    background: #fff; border-radius: 20px; padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f1f5f9;
    margin-bottom: 18px;
}
/* ── Filter bar ── */
.ss-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr auto;
    gap: 12px; align-items: end;
}
@media(max-width:1100px){ .ss-filter-grid { grid-template-columns: 1fr 1fr 1fr; } }
@media(max-width:680px) { .ss-filter-grid { grid-template-columns: 1fr 1fr; } }
@media(max-width:420px) { .ss-filter-grid { grid-template-columns: 1fr; } }

.ss-label {
    font-size: 11px; font-weight: 800; color: #475569;
    display: block; margin-bottom: 5px;
    text-transform: uppercase; letter-spacing: .05em;
}
.ss-input {
    width: 100%; padding: 9px 12px; border-radius: 11px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    font-size: 13px; color: #1e293b; font-weight: 600; outline: none;
    transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
}
.ss-input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); background: #fff; }

/* ── Multi-select dropdown ── */
.ss-select-wrap { position: relative; }
.ss-multi-display {
    width: 100%; padding: 9px 36px 9px 12px; border-radius: 11px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    font-size: 13px; color: #1e293b; font-weight: 600;
    cursor: pointer; user-select: none; min-height: 41px;
    display: flex; align-items: center;
    transition: border-color .15s; box-sizing: border-box;
}
.ss-multi-display:hover { border-color: #cbd5e1; }
.ss-multi-display.open { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); background: #fff; }
.ss-chevron {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 11px; pointer-events: none; transition: transform .15s;
}
.ss-multi-display.open + .ss-dropdown,
.ss-dropdown.open { display: block; }
.ss-dropdown {
    position: absolute; top: calc(100% + 5px); left: 0; right: 0; z-index: 200;
    background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0;
    box-shadow: 0 8px 28px rgba(0,0,0,.13); display: none;
    max-height: 220px; overflow-y: auto;
}
.ss-dropdown-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px; cursor: pointer; font-size: 13px; font-weight: 600; color: #1e293b;
    transition: background .1s;
}
.ss-dropdown-item:hover { background: #f8fafc; }
.ss-dropdown-item input[type=checkbox] { width: 14px; height: 14px; accent-color: #7c3aed; cursor: pointer; flex-shrink: 0; }
.ss-dropdown-item.select-all { border-bottom: 1px solid #f1f5f9; color: #7c3aed; font-size: 12px; }
.ss-dropdown-item .ss-meta { color: #94a3b8; font-size: 11px; margin-left: 3px; }

/* ── Buttons ── */
.ss-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 11px; border: none;
    font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: all .15s;
}
.ss-btn-primary { background: linear-gradient(135deg,#7c3aed,#6d28d9); color: #fff; }
.ss-btn-primary:hover { background: linear-gradient(135deg,#6d28d9,#5b21b6); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(124,58,237,.35); }
.ss-btn-secondary { background: #f8fafc; color: #475569; border: 1.5px solid #e2e8f0; }
.ss-btn-secondary:hover { background: #f1f5f9; }

/* ── Summary cards ── */
.ss-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 18px; }
.ss-sum-card { border-radius: 14px; padding: 13px 16px; text-align: center; border: 1.5px solid transparent; }
.ss-sum-num   { font-size: 1.7rem; font-weight: 900; line-height: 1; }
.ss-sum-label { font-size: 11px; font-weight: 700; margin-top: 4px; }
.ss-sum-total  { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
.ss-sum-avail  { background: #f0fdf4; border-color: #bbf7d0; color: #059669; }
.ss-sum-partial{ background: #fffbeb; border-color: #fde68a; color: #d97706; }
.ss-sum-full   { background: #fff1f2; border-color: #fca5a5; color: #dc2626; }
.ss-sum-wh     { background: #faf5ff; border-color: #ddd6fe; color: #7c3aed; }

/* ── Hotel group header ── */
.ss-hotel-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; margin-bottom: 14px;
    border-bottom: 2px solid #f1f5f9;
}
.ss-hotel-badge {
    background: linear-gradient(135deg,#7c3aed,#6d28d9);
    color: #fff; border-radius: 10px; padding: 4px 12px;
    font-size: 12px; font-weight: 800;
}

/* ── Slot sub-header ── */
.ss-slot-header {
    display: flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px;
    padding: 9px 14px; margin-bottom: 10px; flex-wrap: wrap;
}
.ss-slot-name { font-size: 13px; font-weight: 800; color: #1e293b; }
.ss-slot-time { font-size: 11px; color: #64748b; font-weight: 600; }

/* ── Room-matrix table ── */
.ss-matrix-wrap { overflow-x: auto; border-radius: 12px; border: 1.5px solid #f1f5f9; margin-bottom: 18px; }
.ss-matrix {
    width: 100%; border-collapse: collapse; min-width: 480px;
}
.ss-matrix th {
    background: #f8fafc; padding: 9px 12px; text-align: left;
    font-size: 11px; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1.5px solid #f1f5f9; white-space: nowrap;
}
.ss-matrix th.room-col { text-align: center; min-width: 90px; max-width: 120px; }
.ss-matrix td {
    padding: 9px 12px; border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}
.ss-matrix tr:last-child td { border-bottom: none; }
.ss-matrix tr:hover td { background: #fafafa; }
.ss-matrix td.room-col { text-align: center; }

/* ── Room pills ── */
.ss-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 700;
    white-space: nowrap; cursor: default;
}
.ss-pill-free   { background: #f0fdf4; color: #059669; border: 1px solid #bbf7d0; }
.ss-pill-booked { background: #fff1f2; color: #dc2626; border: 1px solid #fca5a5; cursor: pointer; text-decoration: none; }
.ss-pill-booked:hover { background: #fee2e2; }
.ss-pill-na     { background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; }

/* ── Whole-hotel banner row ── */
.ss-wh-banner td {
    background: linear-gradient(90deg,#fff1f2,#ffe4e6) !important;
    border-left: 4px solid #f87171;
}
.ss-wh-text {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 800; color: #dc2626;
}

/* ── Date cell ── */
.ss-date-cell { min-width: 90px; }
.ss-date-day  { font-size: 13px; font-weight: 800; color: #0f172a; }
.ss-date-sub  { font-size: 10px; color: #94a3b8; font-weight: 600; }
.ss-today     { background: linear-gradient(135deg,#ecfeff,#e0f2fe) !important; }
.ss-today .ss-date-day { color: #0891b2; }

/* ── Empty / no-module states ── */
.ss-empty { text-align: center; padding: 60px 24px; color: #94a3b8; }
.ss-empty-icon { font-size: 3rem; margin-bottom: 12px; opacity: .4; }
.ss-empty-title { font-size: 16px; font-weight: 800; color: #64748b; margin-bottom: 8px; }
.ss-empty-sub { font-size: 13px; }
</style>

@php
    $allRooms   = $allRooms ?? collect();
    $allSlots   = $allSlots ?? collect();
    $matrix     = $matrix ?? null;
    $summary    = $summary ?? null;
    $isMultiHotel   = $isMultiHotel ?? false;
    $availableHotels= $availableHotels ?? collect();
    $dateFrom   = $dateFrom ?? \Carbon\Carbon::today()->toDateString();
    $dateTo     = $dateTo   ?? \Carbon\Carbon::today()->addDays(7)->toDateString();
    $slotIds    = $slotIds  ?? [];
    $filterHotelIds = $filterHotelIds ?? [];
    $statusFilter   = $statusFilter   ?? 'all';
@endphp

@if($flatRooms->where('pricing_type', 'per_slot')->isEmpty())
<div class="ss-card">
    <div class="ss-empty">
        <div class="ss-empty-icon"><i class="fas fa-clock"></i></div>
        <div class="ss-empty-title">No Per-Slot Rooms Found</div>
        <div class="ss-empty-sub">Add at least one room with <strong>per-slot pricing</strong> to use the Slot Search Engine.</div>
        <a href="{{ route('rooms.index') }}" class="ss-btn ss-btn-primary" style="margin-top:18px;text-decoration:none;display:inline-flex;">
            <i class="fas fa-door-open"></i> Manage Rooms
        </a>
    </div>
</div>
@elseif($allSlots->isEmpty())
<div class="ss-card">
    <div class="ss-empty">
        <div class="ss-empty-icon"><i class="fas fa-clock"></i></div>
        <div class="ss-empty-title">No Time Slots Configured</div>
        <div class="ss-empty-sub">Define time slots in Settings before searching.</div>
        <a href="{{ route('time-slots.index') }}" class="ss-btn ss-btn-primary" style="margin-top:18px;text-decoration:none;display:inline-flex;">
            <i class="fas fa-clock"></i> Configure Slots
        </a>
    </div>
</div>
@else

{{-- ── Filter card ── --}}
<div class="ss-card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;flex-shrink:0;">
            <i class="fas fa-search"></i>
        </div>
        <div>
            <div style="font-size:14px;font-weight:800;color:#1e293b;">Search Slot Availability</div>
            <div style="font-size:11px;color:#94a3b8;">Filter by date range, time slot{{ $isMultiHotel ? ', hotel' : '' }} and status</div>
        </div>
    </div>

    <form method="GET" action="{{ route('slot-search.index') }}" id="ssForm">
        <div class="ss-filter-grid">

            {{-- Date From --}}
            <div>
                <label class="ss-label"><i class="fas fa-calendar" style="margin-right:4px;"></i>Date From</label>
                <input type="date" name="date_from" class="ss-input"
                    value="{{ $dateFrom }}"
                    max="{{ \Carbon\Carbon::today()->addDays(90)->toDateString() }}"
                    oninput="syncDateTo(this)">
            </div>

            {{-- Date To --}}
            <div>
                <label class="ss-label"><i class="fas fa-calendar-check" style="margin-right:4px;"></i>Date To</label>
                <input type="date" name="date_to" id="ssDateTo" class="ss-input"
                    value="{{ $dateTo }}"
                    max="{{ \Carbon\Carbon::today()->addDays(90)->toDateString() }}">
            </div>

            {{-- Slot Multi-Select --}}
            <div>
                <label class="ss-label"><i class="fas fa-clock" style="margin-right:4px;"></i>Time Slots</label>
                <div class="ss-select-wrap" id="slotWrap">
                    <div class="ss-multi-display" id="slotDisplay" onclick="toggleDD('slot')">
                        <span id="slotText" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ count($slotIds) === 0 || count($slotIds) === $allSlots->count() ? 'All Slots' : ($allSlots->whereIn('id', $slotIds)->count() . ' slot(s) selected') }}</span>
                        <i class="fas fa-chevron-down ss-chevron"></i>
                    </div>
                    <div class="ss-dropdown" id="slotDropdown">
                        <div class="ss-dropdown-item select-all" onclick="selectAll('slot')">
                            <input type="checkbox" id="slotAll" {{ count($slotIds) === 0 ? 'checked' : '' }}>
                            <span>All Slots</span>
                        </div>
                        @foreach($allSlots as $s)
                        <div class="ss-dropdown-item" onclick="toggleCB('slot',{{ $s->id }})">
                            <input type="checkbox" name="slot_ids[]" id="slot_{{ $s->id }}" value="{{ $s->id }}" {{ in_array($s->id, $slotIds) ? 'checked' : '' }}>
                            <span>{{ $s->name }}<span class="ss-meta">{{ $s->start_time }}–{{ $s->end_time }}</span></span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Hotel Multi-Select (only when managing 2+ hotels) --}}
            @if($isMultiHotel)
            <div>
                <label class="ss-label"><i class="fas fa-hotel" style="margin-right:4px;"></i>Hotels</label>
                <div class="ss-select-wrap" id="hotelWrap">
                    <div class="ss-multi-display" id="hotelDisplay" onclick="toggleDD('hotel')">
                        <span id="hotelText" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ count($filterHotelIds) === 0 || count($filterHotelIds) === $availableHotels->count() ? 'All Hotels' : ($availableHotels->whereIn('id', $filterHotelIds)->count() . ' hotel(s)') }}</span>
                        <i class="fas fa-chevron-down ss-chevron"></i>
                    </div>
                    <div class="ss-dropdown" id="hotelDropdown">
                        <div class="ss-dropdown-item select-all" onclick="selectAll('hotel')">
                            <input type="checkbox" id="hotelAll" {{ count($filterHotelIds) === 0 ? 'checked' : '' }}>
                            <span>All Hotels</span>
                        </div>
                        @foreach($availableHotels as $h)
                        <div class="ss-dropdown-item" onclick="toggleCB('hotel',{{ $h->id }})">
                            <input type="checkbox" name="hotel_ids[]" id="hotel_{{ $h->id }}" value="{{ $h->id }}" {{ in_array($h->id, $filterHotelIds) ? 'checked' : '' }}>
                            <span>{{ $h->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Status + Buttons --}}
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label class="ss-label"><i class="fas fa-filter" style="margin-right:4px;"></i>Status</label>
                <div style="display:flex;gap:7px;flex-wrap:wrap;">
                    <select name="status" class="ss-input" style="max-width:140px;flex-shrink:0;">
                        <option value="all"    {{ $statusFilter === 'all'    ? 'selected' : '' }}>All</option>
                        <option value="free"   {{ $statusFilter === 'free'   ? 'selected' : '' }}>Free Only</option>
                        <option value="booked" {{ $statusFilter === 'booked' ? 'selected' : '' }}>Booked</option>
                    </select>
                    <button type="submit" class="ss-btn ss-btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    @if(request()->has('date_from'))
                    <a href="{{ route('slot-search.index') }}" class="ss-btn ss-btn-secondary" style="text-decoration:none;">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ── Results ── --}}
@if($matrix !== null)

    {{-- Summary cards --}}
    @if($summary && $summary['total'] > 0)
    <div class="ss-summary">
        <div class="ss-sum-card ss-sum-total">
            <div class="ss-sum-num">{{ $summary['total'] }}</div>
            <div class="ss-sum-label">Slot-Days</div>
        </div>
        <div class="ss-sum-card ss-sum-avail">
            <div class="ss-sum-num" style="color:#059669;">{{ $summary['free'] }}</div>
            <div class="ss-sum-label">Free</div>
        </div>
        <div class="ss-sum-card ss-sum-full">
            <div class="ss-sum-num" style="color:#dc2626;">{{ $summary['booked'] }}</div>
            <div class="ss-sum-label">Booked</div>
        </div>
        @if($summary['wh'] > 0)
        <div class="ss-sum-card ss-sum-wh">
            <div class="ss-sum-num" style="color:#7c3aed;">{{ $summary['wh'] }}</div>
            <div class="ss-sum-label">Whole Hotel</div>
        </div>
        @endif
    </div>
    @endif

    @if(empty($matrix))
    <div class="ss-card">
        <div class="ss-empty">
            <div class="ss-empty-icon"><i class="fas fa-search"></i></div>
            <div class="ss-empty-title">No Results Match Your Filters</div>
            <div class="ss-empty-sub">Try adjusting your date range, slots, or status filter.</div>
        </div>
    </div>
    @else

    {{-- Hotel groups --}}
    @foreach($matrix as $hotelData)
    <div class="ss-card" style="padding:20px 20px 8px;">

        {{-- Hotel header (only when multi-hotel) --}}
        @if($isMultiHotel)
        <div class="ss-hotel-header">
            <span class="ss-hotel-badge"><i class="fas fa-hotel" style="margin-right:5px;"></i>{{ $hotelData['hotel_name'] }}</span>
            <span style="font-size:12px;color:#94a3b8;font-weight:600;">{{ $hotelData['rooms']->count() }} rooms &bull; {{ count($hotelData['slots']) }} slot(s)</span>
        </div>
        @endif

        {{-- Slot sections --}}
        @foreach($hotelData['slots'] as $slotData)
        @php $rooms = $hotelData['rooms']; @endphp
        <div class="ss-slot-header">
            <i class="fas fa-clock" style="color:#7c3aed;"></i>
            <span class="ss-slot-name">{{ $slotData['slot_name'] }}</span>
            <span class="ss-slot-time">{{ $slotData['slot_time'] }}</span>
            <span style="margin-left:auto;font-size:11px;color:#94a3b8;">{{ count($slotData['dates']) }} day(s)</span>
        </div>

        <div class="ss-matrix-wrap">
            <table class="ss-matrix">
                <thead>
                    <tr>
                        <th class="ss-date-cell">Date</th>
                        @foreach($rooms as $room)
                        <th class="room-col" title="{{ ucfirst($room->type) }} &bull; {{ $room->pricing_type }}">
                            {{ $room->room_number }}
                            @if($room->pricing_type !== 'per_slot')
                            <div style="font-size:9px;font-weight:600;color:#94a3b8;text-transform:none;letter-spacing:0;">{{ $room->pricing_type }}</div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($slotData['dates'] as $ds => $dateData)
                @php
                    $dateObj = \Carbon\Carbon::parse($ds);
                    $isToday = $dateObj->isToday();
                @endphp
                @if($dateData['whole_hotel'])
                {{-- Whole-hotel banner row --}}
                <tr class="ss-wh-banner {{ $isToday ? 'ss-today' : '' }}">
                    <td class="ss-date-cell">
                        <div class="ss-date-day">{{ $dateObj->format('d M') }}</div>
                        <div class="ss-date-sub">{{ $dateObj->format('D, Y') }}</div>
                    </td>
                    <td colspan="{{ $rooms->count() }}">
                        <div class="ss-wh-text">
                            <i class="fas fa-hotel"></i>
                            WHOLE HOTEL BOOKED &mdash;
                            <strong>{{ $dateData['whole_hotel']['guest_name'] }}</strong>
                            @if(!empty($dateData['whole_hotel']['check_in_date']) && !empty($dateData['whole_hotel']['check_out_date']))
                            <span style="font-size:11px;color:#6d28d9;margin-left:6px;">
                                ({{ \Carbon\Carbon::parse($dateData['whole_hotel']['check_in_date'])->format('d M') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($dateData['whole_hotel']['check_out_date'])->format('d M Y') }})
                            </span>
                            @endif
                            <a href="{{ route('bookings.show', $dateData['whole_hotel']['booking_id']) }}"
                                style="font-size:11px;font-weight:700;color:#7c3aed;text-decoration:none;margin-left:4px;"
                                target="_blank" title="View booking">
                                #{{ $dateData['whole_hotel']['booking_num'] }} <i class="fas fa-external-link-alt" style="font-size:9px;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @else
                {{-- Regular per-room row --}}
                <tr class="{{ $isToday ? 'ss-today' : '' }}">
                    <td class="ss-date-cell">
                        <div class="ss-date-day">{{ $dateObj->format('d M') }}</div>
                        <div class="ss-date-sub">{{ $dateObj->format('D, Y') }}</div>
                    </td>
                    @foreach($rooms as $room)
                    @php $cell = $dateData['rooms'][$room->id] ?? ['status' => 'na']; @endphp
                    <td class="room-col">
                        @if($cell['status'] === 'free')
                            <span class="ss-pill ss-pill-free"><i class="fas fa-check-circle" style="font-size:9px;"></i> Free</span>
                        @elseif($cell['status'] === 'booked')
                            <a href="{{ route('bookings.show', $cell['booking_id']) }}"
                                class="ss-pill ss-pill-booked" target="_blank"
                                title="{{ ($cell['room_number'] ?? '') . ' – ' . ($cell['guest_name'] ?? 'Guest') }}">
                                <i class="fas fa-user" style="font-size:9px;"></i>
                                @if(!empty($cell['room_number']))Rm {{ $cell['room_number'] }} &ndash; @endif{{ \Illuminate\Support\Str::limit($cell['guest_name'] ?? 'Guest', 12) }}
                            </a>
                        @else
                            <span class="ss-pill ss-pill-na">N/A</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif
                @endforeach
                </tbody>
            </table>
        </div>
        @endforeach

    </div>
    @endforeach

    @endif {{-- end if empty matrix --}}

@else
{{-- Initial state — no search submitted --}}
<div class="ss-card">
    <div class="ss-empty">
        <div class="ss-empty-icon"><i class="fas fa-magnifying-glass" style="font-size:2.8rem;"></i></div>
        <div class="ss-empty-title">Ready to Search</div>
        <div class="ss-empty-sub">
            Pick your date range{{ $isMultiHotel ? ', hotels,' : '' }} and time slots, then click <strong>Search</strong>.
            <br>Results show a room-by-room availability grid for each slot.
        </div>
        <button onclick="document.getElementById('ssForm').submit()" class="ss-btn ss-btn-primary" style="margin-top:18px;">
            <i class="fas fa-calendar-check"></i> View Today + Next 7 Days
        </button>
    </div>
</div>
@endif

@endif {{-- end if allRooms / allSlots --}}

<script>
// ── Dropdown toggle logic ────────────────────────────────────────────────────
var openDD = null;
function toggleDD(type) {
    var dropdown = document.getElementById(type + 'Dropdown');
    var display  = document.getElementById(type + 'Display');
    if (!dropdown) return;
    var isOpen = dropdown.classList.contains('open');
    closeAllDD();
    if (!isOpen) {
        dropdown.classList.add('open');
        display.classList.add('open');
        openDD = type;
    }
}
function closeAllDD() {
    ['slot','hotel'].forEach(function(t) {
        var d = document.getElementById(t + 'Dropdown');
        var disp = document.getElementById(t + 'Display');
        if (d) d.classList.remove('open');
        if (disp) disp.classList.remove('open');
    });
    openDD = null;
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#slotWrap') && !e.target.closest('#hotelWrap')) closeAllDD();
});

function toggleCB(type, id) {
    var cb = document.getElementById(type + '_' + id);
    if (cb && event.target !== cb) cb.checked = !cb.checked;
    updateAllChk(type);
    updateText(type);
}

function selectAll(type) {
    var allChk = document.getElementById(type + 'All');
    var cbs    = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    var newState = !allChk.checked;
    allChk.checked = newState;
    cbs.forEach(function(cb) { cb.checked = !newState; });
    updateText(type);
}

function updateAllChk(type) {
    var allChk = document.getElementById(type + 'All');
    if (!allChk) return;
    var cbs = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    allChk.checked = Array.from(cbs).every(function(cb) { return !cb.checked; });
}

function updateText(type) {
    var allChk  = document.getElementById(type + 'All');
    var cbs     = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    var textEl  = document.getElementById(type + 'Text');
    if (!textEl) return;
    var selected = Array.from(cbs).filter(function(cb) { return cb.checked; });
    if (!allChk || allChk.checked || selected.length === 0) {
        textEl.textContent = type === 'slot' ? 'All Slots' : 'All Hotels';
    } else if (selected.length === 1) {
        var label = selected[0].parentElement.querySelector('span');
        textEl.textContent = label ? label.textContent.trim().split('\n')[0].trim() : selected[0].value;
    } else {
        textEl.textContent = selected.length + (type === 'slot' ? ' slot(s) selected' : ' hotel(s)');
    }
}

// ── Date validation ──────────────────────────────────────────────────────────
function syncDateTo(fromInput) {
    var toInput = document.getElementById('ssDateTo');
    if (toInput && fromInput.value && toInput.value < fromInput.value) {
        toInput.value = fromInput.value;
    }
}
</script>
@endsection
