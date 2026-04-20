@extends('layouts.admin')
@section('title', 'Slot Search Engine')
@section('page-title', 'Slot Search Engine')
@section('page-subtitle', 'Search slot availability across date ranges, time slots and rooms.')

@section('content')
<style>
.ss-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid #f1f5f9;
}
.ss-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr auto;
    gap: 14px;
    align-items: end;
}
@media(max-width: 900px) {
    .ss-filter-grid { grid-template-columns: 1fr 1fr; }
    .ss-filter-grid .ss-btn-col { grid-column: span 2; }
}
@media(max-width: 540px) {
    .ss-filter-grid { grid-template-columns: 1fr; }
    .ss-filter-grid .ss-btn-col { grid-column: span 1; }
}
.ss-label {
    font-size: 12px; font-weight: 700; color: #475569;
    display: block; margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .04em;
}
.ss-input {
    width: 100%; padding: 10px 12px; border-radius: 12px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    font-size: 13px; color: #1e293b; font-weight: 600;
    outline: none; transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.ss-input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); background: #fff; }
.ss-select-wrap { position: relative; }
.ss-multi-display {
    width: 100%; padding: 10px 36px 10px 12px; border-radius: 12px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    font-size: 13px; color: #1e293b; font-weight: 600;
    cursor: pointer; user-select: none; min-height: 42px;
    display: flex; align-items: center;
    transition: border-color .15s; box-sizing: border-box;
    position: relative;
}
.ss-multi-display:hover { border-color: #cbd5e1; }
.ss-multi-display.open { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); background: #fff; }
.ss-chevron {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 11px; pointer-events: none;
    transition: transform .15s;
}
.ss-multi-display.open .ss-chevron { transform: translateY(-50%) rotate(180deg); }
.ss-dropdown {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 100;
    background: #fff; border-radius: 14px; border: 1.5px solid #e2e8f0;
    box-shadow: 0 8px 28px rgba(0,0,0,.12); overflow: hidden;
    display: none; max-height: 240px; overflow-y: auto;
}
.ss-dropdown.open { display: block; }
.ss-dropdown-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; cursor: pointer; font-size: 13px;
    font-weight: 600; color: #1e293b; transition: background .12s;
}
.ss-dropdown-item:hover { background: #f8fafc; }
.ss-dropdown-item input[type=checkbox] { width: 15px; height: 15px; accent-color: #7c3aed; cursor: pointer; flex-shrink: 0; }
.ss-dropdown-item.select-all { border-bottom: 1px solid #f1f5f9; color: #7c3aed; font-size: 12px; }
.ss-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #ede9fe; color: #7c3aed; border-radius: 8px;
    padding: 2px 8px; font-size: 11px; font-weight: 700; margin: 1px;
}
.ss-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 12px; border: none;
    font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: all .15s;
}
.ss-btn-primary { background: linear-gradient(135deg,#7c3aed,#6d28d9); color: #fff; }
.ss-btn-primary:hover { background: linear-gradient(135deg,#6d28d9,#5b21b6); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(124,58,237,.35); }
.ss-btn-secondary { background: #f8fafc; color: #475569; border: 1.5px solid #e2e8f0; }
.ss-btn-secondary:hover { background: #f1f5f9; }

/* Summary cards */
.ss-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px; }
.ss-sum-card {
    border-radius: 14px; padding: 14px 16px; text-align: center;
    border: 1.5px solid transparent;
}
.ss-sum-card .ss-sum-num { font-size: 1.8rem; font-weight: 900; line-height: 1; }
.ss-sum-card .ss-sum-label { font-size: 11px; font-weight: 700; margin-top: 4px; }
.ss-sum-total  { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
.ss-sum-avail  { background: #f0fdf4; border-color: #bbf7d0; color: #059669; }
.ss-sum-partial{ background: #fffbeb; border-color: #fde68a; color: #d97706; }
.ss-sum-full   { background: #fff1f2; border-color: #fca5a5; color: #dc2626; }
.ss-sum-wh     { background: #faf5ff; border-color: #ddd6fe; color: #7c3aed; }

/* Results table */
.ss-table-wrap { overflow-x: auto; border-radius: 14px; border: 1.5px solid #f1f5f9; }
.ss-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.ss-table th {
    background: #f8fafc; padding: 10px 14px; text-align: left;
    font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1.5px solid #f1f5f9; white-space: nowrap;
}
.ss-table td {
    padding: 11px 14px; border-bottom: 1px solid #f8fafc;
    font-size: 13px; vertical-align: middle;
}
.ss-table tr:last-child td { border-bottom: none; }
.ss-table tr:hover td { background: #fafafa; }

/* Status badges */
.ss-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.ss-badge-green  { background: #f0fdf4; color: #059669; }
.ss-badge-amber  { background: #fffbeb; color: #d97706; }
.ss-badge-red    { background: #fff1f2; color: #dc2626; }
.ss-badge-purple { background: #faf5ff; color: #7c3aed; }

/* Availability bar */
.ss-avail-bar { height: 8px; border-radius: 99px; background: #f1f5f9; overflow: hidden; min-width: 80px; }
.ss-avail-fill { height: 100%; border-radius: 99px; transition: width .4s; }
.ss-avail-fill.green  { background: linear-gradient(90deg,#34d399,#10b981); }
.ss-avail-fill.amber  { background: linear-gradient(90deg,#fbbf24,#f59e0b); }
.ss-avail-fill.red    { background: linear-gradient(90deg,#f87171,#ef4444); }

/* Room pills */
.ss-room-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; margin: 1px;
}
.ss-room-booked { background: #fff1f2; color: #dc2626; }
.ss-room-free   { background: #f0fdf4; color: #059669; }

/* Empty state */
.ss-empty { text-align: center; padding: 60px 24px; color: #94a3b8; }
.ss-empty-icon { font-size: 3rem; margin-bottom: 12px; opacity: .4; }
.ss-empty-title { font-size: 16px; font-weight: 800; color: #64748b; margin-bottom: 6px; }
.ss-empty-sub { font-size: 13px; }

/* Pagination-like limit notice */
.ss-limit-notice {
    background: #fffbeb; border-radius: 10px; border: 1px solid #fde68a;
    padding: 8px 14px; font-size: 12px; color: #92400e; font-weight: 600;
    display: flex; align-items: center; gap: 6px; margin-bottom: 14px;
}
</style>

{{-- No per-slot rooms warning --}}
@if($allRooms->isEmpty())
<div class="ss-card" style="text-align:center;padding:60px 24px;">
    <div style="font-size:3rem;opacity:.35;margin-bottom:12px;"><i class="fas fa-clock"></i></div>
    <div style="font-size:18px;font-weight:800;color:#334155;margin-bottom:8px;">No Per-Slot Rooms Found</div>
    <div style="font-size:13px;color:#94a3b8;max-width:400px;margin:0 auto;">
        The Slot Search Engine requires at least one room with <strong>per-slot pricing</strong>. Add a per-slot room in Room Settings to get started.
    </div>
    <a href="{{ route('rooms.index') }}" class="ss-btn ss-btn-primary" style="margin-top:20px;text-decoration:none;display:inline-flex;">
        <i class="fas fa-door-open"></i> Go to Rooms
    </a>
</div>
@elseif($allSlots->isEmpty())
<div class="ss-card" style="text-align:center;padding:60px 24px;">
    <div style="font-size:3rem;opacity:.35;margin-bottom:12px;"><i class="fas fa-clock"></i></div>
    <div style="font-size:18px;font-weight:800;color:#334155;margin-bottom:8px;">No Time Slots Configured</div>
    <div style="font-size:13px;color:#94a3b8;max-width:400px;margin:0 auto;">
        Define your hotel's time slots in Settings first, then use this search to find availability.
    </div>
    <a href="{{ route('time-slots.index') }}" class="ss-btn ss-btn-primary" style="margin-top:20px;text-decoration:none;display:inline-flex;">
        <i class="fas fa-clock"></i> Configure Time Slots
    </a>
</div>
@else

{{-- ── Filter Card ── --}}
<div class="ss-card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
        <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
            <i class="fas fa-search"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:800;color:#1e293b;">Search Filters</div>
            <div style="font-size:12px;color:#94a3b8;">Select date range, slots and rooms to search</div>
        </div>
    </div>

    <form method="GET" action="{{ route('slot-search.index') }}" id="ssForm">
        <div class="ss-filter-grid">
            {{-- Date From --}}
            <div>
                <label class="ss-label">Date From</label>
                <input type="date" name="date_from" class="ss-input"
                    value="{{ $dateFrom ?? \Carbon\Carbon::today()->toDateString() }}"
                    max="{{ \Carbon\Carbon::today()->addDays(90)->toDateString() }}">
            </div>

            {{-- Date To --}}
            <div>
                <label class="ss-label">Date To</label>
                <input type="date" name="date_to" class="ss-input"
                    value="{{ $dateTo ?? \Carbon\Carbon::today()->toDateString() }}"
                    max="{{ \Carbon\Carbon::today()->addDays(90)->toDateString() }}">
            </div>

            {{-- Slot Multi-Select --}}
            <div>
                <label class="ss-label">Time Slots</label>
                <div class="ss-select-wrap" id="slotSelectWrap">
                    <div class="ss-multi-display" id="slotDisplay" onclick="toggleDropdown('slot')">
                        <span id="slotDisplayText" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            @if(!empty($slotIds ?? []))
                                @php $selSlots = $allSlots->whereIn('id', $slotIds ?? []); @endphp
                                @if($selSlots->count() === $allSlots->count())
                                    All Slots
                                @elseif($selSlots->count() <= 2)
                                    {{ $selSlots->pluck('name')->join(', ') }}
                                @else
                                    {{ $selSlots->count() }} slots selected
                                @endif
                            @else
                                All Slots
                            @endif
                        </span>
                        <i class="fas fa-chevron-down ss-chevron"></i>
                    </div>
                    <div class="ss-dropdown" id="slotDropdown">
                        <div class="ss-dropdown-item select-all" onclick="toggleSelectAll('slot')">
                            <input type="checkbox" id="slotAllChk" {{ empty($slotIds ?? []) ? 'checked' : '' }}>
                            <span>All Slots</span>
                        </div>
                        @foreach($allSlots as $slot)
                        <div class="ss-dropdown-item" onclick="toggleCheckbox('slot', {{ $slot->id }})">
                            <input type="checkbox" name="slot_ids[]"
                                id="slot_{{ $slot->id }}" value="{{ $slot->id }}"
                                {{ in_array($slot->id, $slotIds ?? []) ? 'checked' : '' }}>
                            <span>{{ $slot->name }}<span style="color:#94a3b8;font-weight:500;margin-left:5px;font-size:11px;">{{ $slot->start_time }}–{{ $slot->end_time }}</span></span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Room Multi-Select --}}
            <div>
                <label class="ss-label">Rooms</label>
                <div class="ss-select-wrap" id="roomSelectWrap">
                    <div class="ss-multi-display" id="roomDisplay" onclick="toggleDropdown('room')">
                        <span id="roomDisplayText" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            @if(!empty($roomIds ?? []))
                                @php $selRooms = $allRooms->whereIn('id', $roomIds ?? []); @endphp
                                @if($selRooms->count() === $allRooms->count())
                                    All Rooms
                                @elseif($selRooms->count() <= 2)
                                    {{ $selRooms->pluck('room_number')->join(', ') }}
                                @else
                                    {{ $selRooms->count() }} rooms
                                @endif
                            @else
                                All Rooms
                            @endif
                        </span>
                        <i class="fas fa-chevron-down ss-chevron"></i>
                    </div>
                    <div class="ss-dropdown" id="roomDropdown">
                        <div class="ss-dropdown-item select-all" onclick="toggleSelectAll('room')">
                            <input type="checkbox" id="roomAllChk" {{ empty($roomIds ?? []) ? 'checked' : '' }}>
                            <span>All Rooms</span>
                        </div>
                        @foreach($allRooms as $room)
                        <div class="ss-dropdown-item" onclick="toggleCheckbox('room', {{ $room->id }})">
                            <input type="checkbox" name="room_ids[]"
                                id="room_{{ $room->id }}" value="{{ $room->id }}"
                                {{ in_array($room->id, $roomIds ?? []) ? 'checked' : '' }}>
                            <span>{{ $room->room_number }}<span style="color:#94a3b8;font-size:11px;margin-left:5px;">{{ ucfirst($room->type ?? '') }}</span></span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Status Filter + Search Button --}}
            <div class="ss-btn-col" style="display:flex;flex-direction:column;gap:8px;">
                <label class="ss-label">Status</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="status" class="ss-input" style="max-width:150px;">
                        <option value="all"         {{ ($statusFilter ?? 'all') === 'all'         ? 'selected' : '' }}>All</option>
                        <option value="available"   {{ ($statusFilter ?? 'all') === 'available'   ? 'selected' : '' }}>Available</option>
                        <option value="partial"     {{ ($statusFilter ?? 'all') === 'partial'     ? 'selected' : '' }}>Partial</option>
                        <option value="full"        {{ ($statusFilter ?? 'all') === 'full'        ? 'selected' : '' }}>Full</option>
                        <option value="whole_hotel" {{ ($statusFilter ?? 'all') === 'whole_hotel' ? 'selected' : '' }}>Whole Hotel</option>
                    </select>
                    <button type="submit" class="ss-btn ss-btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    @if(request()->has('date_from'))
                    <a href="{{ route('slot-search.index') }}" class="ss-btn ss-btn-secondary" style="text-decoration:none;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ── Results ── --}}
@if(isset($results))

    @if(count($results) > 100)
    <div class="ss-limit-notice">
        <i class="fas fa-exclamation-triangle"></i>
        Showing first 100 results. Narrow your date range or add more filters.
    </div>
    @endif

    {{-- Summary Cards --}}
    @if(isset($summary))
    <div class="ss-summary">
        <div class="ss-sum-card ss-sum-total">
            <div class="ss-sum-num">{{ $summary['total'] }}</div>
            <div class="ss-sum-label">Total Slots</div>
        </div>
        <div class="ss-sum-card ss-sum-avail">
            <div class="ss-sum-num" style="color:#059669;">{{ $summary['available'] }}</div>
            <div class="ss-sum-label">Available</div>
        </div>
        <div class="ss-sum-card ss-sum-partial">
            <div class="ss-sum-num" style="color:#d97706;">{{ $summary['partial'] }}</div>
            <div class="ss-sum-label">Partial</div>
        </div>
        <div class="ss-sum-card ss-sum-full">
            <div class="ss-sum-num" style="color:#dc2626;">{{ $summary['full'] }}</div>
            <div class="ss-sum-label">Fully Booked</div>
        </div>
        @if($summary['wh'] > 0)
        <div class="ss-sum-card ss-sum-wh">
            <div class="ss-sum-num" style="color:#7c3aed;">{{ $summary['wh'] }}</div>
            <div class="ss-sum-label">Whole Hotel</div>
        </div>
        @endif
    </div>
    @endif

    {{-- Results Table --}}
    @if(count($results) === 0)
    <div class="ss-card">
        <div class="ss-empty">
            <div class="ss-empty-icon"><i class="fas fa-search"></i></div>
            <div class="ss-empty-title">No Results Found</div>
            <div class="ss-empty-sub">Try adjusting your filters or selecting a different date range.</div>
        </div>
    </div>
    @else
    <div class="ss-card" style="padding:0;">
        <div class="ss-table-wrap">
            <table class="ss-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Slot</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Availability</th>
                        <th>Booked Rooms</th>
                        <th>Free Rooms</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(array_slice($results, 0, 100) as $row)
                <tr>
                    {{-- Date --}}
                    <td>
                        <div style="font-weight:800;color:#0f172a;font-size:13px;">{{ \Carbon\Carbon::parse($row['date'])->format('d M') }}</div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;">{{ \Carbon\Carbon::parse($row['date'])->format('D, Y') }}</div>
                    </td>

                    {{-- Slot Name --}}
                    <td>
                        <span style="font-weight:700;color:#1e293b;">{{ $row['slot_name'] }}</span>
                    </td>

                    {{-- Time --}}
                    <td>
                        <span style="font-size:12px;color:#64748b;font-weight:600;white-space:nowrap;">
                            <i class="fas fa-clock" style="margin-right:4px;color:#7c3aed;"></i>{{ $row['slot_time'] }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($row['status'] === 'whole_hotel')
                            <span class="ss-badge ss-badge-purple"><i class="fas fa-hotel"></i> Whole Hotel</span>
                        @elseif($row['status'] === 'available')
                            <span class="ss-badge ss-badge-green"><i class="fas fa-check-circle"></i> Available</span>
                        @elseif($row['status'] === 'partial')
                            <span class="ss-badge ss-badge-amber"><i class="fas fa-adjust"></i> Partial</span>
                        @else
                            <span class="ss-badge ss-badge-red"><i class="fas fa-times-circle"></i> Full</span>
                        @endif
                    </td>

                    {{-- Availability Bar --}}
                    <td style="min-width:130px;">
                        @if($row['status'] === 'whole_hotel')
                            <div class="ss-avail-bar"><div class="ss-avail-fill red" style="width:100%;"></div></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:3px;font-weight:600;">{{ $row['total'] }}/{{ $row['total'] }} full</div>
                        @else
                            <div class="ss-avail-bar"><div class="ss-avail-fill {{ $row['color'] }}" style="width:{{ $row['pct'] }}%;"></div></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:3px;font-weight:600;">{{ $row['available'] }} free / {{ $row['total'] }} total</div>
                        @endif
                    </td>

                    {{-- Booked Rooms --}}
                    <td style="max-width:200px;">
                        @if($row['status'] === 'whole_hotel')
                            <span class="ss-room-pill ss-room-booked" title="{{ $row['wh_guest'] }}">
                                <i class="fas fa-hotel" style="font-size:9px;"></i> {{ $row['wh_guest'] }}
                                <span style="font-size:10px;opacity:.7;">#{{ $row['wh_booking'] }}</span>
                            </span>
                        @elseif(count($row['bookings']) > 0)
                            @foreach($row['bookings'] as $bk)
                            <a href="{{ route('bookings.show', $bk['booking_id']) }}" class="ss-room-pill ss-room-booked" style="text-decoration:none;" title="{{ $bk['guest_name'] }}">
                                {{ $bk['room_number'] }} – {{ $bk['guest_name'] }}
                            </a>
                            @endforeach
                        @else
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </td>

                    {{-- Free Rooms --}}
                    <td style="max-width:180px;">
                        @if($row['status'] === 'whole_hotel')
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                        @elseif(count($row['free_rooms']) > 0)
                            @foreach($row['free_rooms'] as $fr)
                            <span class="ss-room-pill ss-room-free">{{ $fr }}</span>
                            @endforeach
                        @else
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </td>

                    {{-- Action --}}
                    <td style="text-align:center;white-space:nowrap;">
                        @if($row['status'] === 'available' || $row['status'] === 'partial')
                        <a href="{{ route('bookings.create', ['date' => $row['date']]) }}"
                            style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:11px;font-weight:700;text-decoration:none;transition:opacity .15s;"
                            onmouseenter="this.style.opacity='.85'" onmouseleave="this.style.opacity='1'">
                            <i class="fas fa-plus"></i> Book
                        </a>
                        @else
                        <span style="color:#cbd5e1;font-size:11px;font-weight:600;">N/A</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

@elseif(!request()->has('date_from'))
{{-- Initial state - no search yet --}}
<div class="ss-card">
    <div class="ss-empty">
        <div class="ss-empty-icon"><i class="fas fa-search"></i></div>
        <div class="ss-empty-title">Ready to Search</div>
        <div class="ss-empty-sub">
            Select a date range above and click <strong>Search</strong> to see slot availability.<br>
            You can filter by specific time slots, rooms, and booking status.
        </div>
        <button onclick="document.getElementById('ssForm').submit()" class="ss-btn ss-btn-primary" style="margin-top:20px;">
            <i class="fas fa-calendar-check"></i> Search Today's Availability
        </button>
    </div>
</div>
@endif

@endif {{-- end if allRooms / allSlots --}}

<script>
// ── Multi-select dropdown logic ──────────────────────────────────────────────
function toggleDropdown(type) {
    const display  = document.getElementById(type + 'Display');
    const dropdown = document.getElementById(type + 'Dropdown');
    const isOpen   = dropdown.classList.contains('open');
    closeAll();
    if (!isOpen) {
        display.classList.add('open');
        dropdown.classList.add('open');
    }
}

function closeAll() {
    ['slot', 'room'].forEach(t => {
        const display  = document.getElementById(t + 'Display');
        const dropdown = document.getElementById(t + 'Dropdown');
        if (display)  display.classList.remove('open');
        if (dropdown) dropdown.classList.remove('open');
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#slotSelectWrap') && !e.target.closest('#roomSelectWrap')) {
        closeAll();
    }
});

function toggleCheckbox(type, id) {
    const cb = document.getElementById(type + '_' + id);
    if (cb && event.target !== cb) cb.checked = !cb.checked;
    updateAllChk(type);
    updateDisplay(type);
}

function toggleSelectAll(type) {
    const allChk   = document.getElementById(type + 'AllChk');
    const checkboxes = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    const newState = !allChk.checked;
    allChk.checked = newState;
    checkboxes.forEach(cb => cb.checked = !newState);
    updateDisplay(type);
}

function updateAllChk(type) {
    const allChk     = document.getElementById(type + 'AllChk');
    const checkboxes = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
    allChk.checked   = !anyChecked;
}

function updateDisplay(type) {
    const checkboxes = document.querySelectorAll('input[name="' + type + '_ids[]"]');
    const selected   = Array.from(checkboxes).filter(cb => cb.checked);
    const displayEl  = document.getElementById(type + 'DisplayText');
    const total      = checkboxes.length;

    if (selected.length === 0 || selected.length === total) {
        displayEl.textContent = type === 'slot' ? 'All Slots' : 'All Rooms';
    } else if (selected.length <= 2) {
        displayEl.textContent = selected.map(cb => {
            const label = cb.parentElement.querySelector('span');
            return label ? label.textContent.trim().split('\n')[0].trim() : cb.value;
        }).join(', ');
    } else {
        displayEl.textContent = selected.length + (type === 'slot' ? ' slots selected' : ' rooms');
    }
}

// Date validation: auto-correct if to < from
document.querySelectorAll('input[name="date_from"], input[name="date_to"]').forEach(input => {
    input.addEventListener('change', function() {
        const form   = this.closest('form');
        const from   = form.querySelector('input[name="date_from"]');
        const to     = form.querySelector('input[name="date_to"]');
        if (from.value && to.value && to.value < from.value) {
            to.value = from.value;
        }
    });
});
</script>
@endsection
