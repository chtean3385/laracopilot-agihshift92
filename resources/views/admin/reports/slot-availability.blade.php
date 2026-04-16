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
                            <div class="rpt-slot-cell inline-block" style="cursor:default;"
                                 data-booked='@json($s['booked_rooms'] ?? [])'
                                 data-free='@json($s['free_rooms'] ?? [])'
                                 data-slot="{{ $s['slot_name'] }}"
                                 data-day="{{ $day['label'] }}">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="font-bold text-{{ $color }}-600">
                                        {{ $s['available'] }}/{{ $s['total'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $s['booked'] }} booked</span>
                                    @if($s['total'] > 0)
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-{{ $color }}-500 rounded-full" style="width:{{ $pct }}%"></div>
                                    </div>
                                    @endif
                                    @if(count($s['booked_rooms'] ?? []) > 0)
                                    <div class="mt-1 text-left w-full">
                                        @foreach($s['booked_rooms'] as $br)
                                        <div class="text-xs text-red-500 truncate" style="max-width:100px;" title="{{ $br['guest_name'] }}">
                                            R{{ $br['room_number'] }}: {{ $br['guest_name'] }}
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
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
