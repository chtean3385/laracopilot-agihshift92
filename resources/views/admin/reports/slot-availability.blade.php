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
@endsection
