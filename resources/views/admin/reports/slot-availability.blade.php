@extends('layouts.admin')
@section('title', 'Slot Availability')
@section('page-title', 'Slot Availability Report')
@section('page-subtitle', 'View time-slot occupancy by date')

@section('content')
<div class="max-w-full">

    {{-- Filter --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <form method="GET" action="{{ route('reports.slot_availability') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $from->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $to->toDateString() }}" class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i>Filter</button>
                <a href="{{ route('reports.slot_availability.export', ['date_from'=>$from->toDateString(),'date_to'=>$to->toDateString()]) }}"
                   class="btn-secondary"><i class="fas fa-download mr-2"></i>CSV</a>
            </div>
        </form>
    </div>

    @if($slots->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-14 h-14 rounded-full bg-violet-50 flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-clock text-violet-300 text-2xl"></i>
        </div>
        <p class="text-gray-500 font-medium">No active time slots configured.</p>
        <a href="{{ route('time-slots.index') }}" class="btn-primary mt-4 text-sm inline-flex">
            <i class="fas fa-cog mr-2"></i>Configure Time Slots
        </a>
    </div>
    @elseif($rooms->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <p class="text-gray-500 font-medium">No rooms with Per Slot pricing exist yet.</p>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <p class="text-sm text-gray-500">
                <i class="fas fa-door-open text-violet-400 mr-1"></i>
                <strong class="text-gray-700">{{ $rooms->count() }}</strong> slot rooms available.
                Green = available slots, Red = fully booked.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Date</th>
                        @foreach($slots as $slot)
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 whitespace-nowrap">
                            <div>{{ $slot->name }}</div>
                            <div class="text-xs text-gray-400 font-normal">{{ $slot->start_time }}–{{ $slot->end_time }}</div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($availability as $day)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-700 whitespace-nowrap">{{ $day['label'] }}</td>
                        @foreach($day['slots'] as $s)
                        @php
                            $pct = $s['total'] > 0 ? round($s['booked'] / $s['total'] * 100) : 0;
                            $color = $pct >= 100 ? 'red' : ($pct >= 60 ? 'amber' : 'green');
                        @endphp
                        <td class="px-4 py-3 text-center">
                            @php
                                $bookedRooms = $s['booked_rooms'] ?? [];
                                $freeRooms   = $s['free_rooms']   ?? [];
                                $colorMap = ['green'=>'#16a34a','amber'=>'#d97706','red'=>'#dc2626'];
                                $bgColorMap = ['green'=>'#f0fdf4','amber'=>'#fffbeb','red'=>'#fff1f2'];
                            @endphp
                            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;min-width:80px;">
                                <span style="font-weight:800;font-size:13px;color:{{ $colorMap[$color] }};background:{{ $bgColorMap[$color] }};padding:2px 10px;border-radius:999px;">
                                    {{ $s['available'] }}<span style="font-weight:400;color:#94a3b8;font-size:11px;">/{{ $s['total'] }}</span>
                                </span>
                                <div style="display:flex;flex-direction:column;gap:2px;width:100%;">
                                    @foreach($bookedRooms as $br)
                                    <div title="{{ $br['guest_name'] }}" style="background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:2px 6px;font-size:11px;line-height:1.4;color:#b91c1c;text-align:left;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:90px;">
                                        <span style="font-weight:700;">R{{ $br['room_number'] }}</span> {{ \Illuminate\Support\Str::limit($br['guest_name'], 10, '') }}
                                    </div>
                                    @endforeach
                                    @foreach($freeRooms as $rn)
                                    <div style="background:#dcfce7;border:1px solid #86efac;border-radius:6px;padding:2px 6px;font-size:11px;line-height:1.4;color:#15803d;text-align:left;white-space:nowrap;">
                                        <span style="font-weight:700;">R{{ $rn }}</span> <span style="color:#4ade80;">free</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<div id="rptSlotTooltip" style="display:none;position:fixed;z-index:9999;background:#1e293b;color:#fff;border-radius:12px;padding:12px 14px;font-size:12px;max-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.25);pointer-events:none;line-height:1.6;"></div>
<script>
(function() {
    var tip = document.getElementById('rptSlotTooltip');
    if (!tip) return;
    document.querySelectorAll('.rpt-slot-cell').forEach(function(el) {
        el.addEventListener('mouseenter', function(e) {
            var booked = JSON.parse(el.dataset.booked || '[]');
            var free   = JSON.parse(el.dataset.free   || '[]');
            var slot   = el.dataset.slot;
            var day    = el.dataset.day;
            var html   = '<div style="font-weight:700;margin-bottom:6px;color:#a78bfa;">' + slot + ' · ' + day + '</div>';
            if (booked.length > 0) {
                html += '<div style="color:#fca5a5;font-weight:600;margin-bottom:3px;">Booked:</div>';
                booked.forEach(function(r) {
                    html += '<div style="color:#fecaca;">&#9679; Room ' + r.room_number + ' — ' + r.guest_name + '</div>';
                });
            }
            if (free.length > 0) {
                html += '<div style="color:#86efac;font-weight:600;margin-top:6px;margin-bottom:3px;">Free:</div>';
                free.forEach(function(r) {
                    html += '<div style="color:#bbf7d0;">&#9679; Room ' + r + '</div>';
                });
            }
            if (!booked.length && !free.length) html += '<div style="color:#94a3b8;">No room data</div>';
            tip.innerHTML = html;
            tip.style.display = 'block';
            posTip(e);
        });
        el.addEventListener('mousemove', posTip);
        el.addEventListener('mouseleave', function() { tip.style.display = 'none'; });
    });
    function posTip(e) {
        var x = e.clientX + 14, y = e.clientY + 14;
        if (x + 260 > window.innerWidth)  x = e.clientX - 260;
        if (y + 200 > window.innerHeight) y = e.clientY - 200;
        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
    }
})();
</script>
@endpush
@endsection
