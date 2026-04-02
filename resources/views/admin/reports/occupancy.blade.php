@extends('layouts.admin')
@section('title','Occupancy Report')
@section('page-title','Occupancy Report')
@section('page-subtitle','Room utilization analysis')
@section('content')
<div class="space-y-5">
    <form method="GET" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end">
        <div><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
    </form>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Room Booking Count</h3>
            <span class="text-xs text-gray-400">Period: {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pricing</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Period Status</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Bookings in Period</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($roomStats as $room)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-bold text-gray-800">{{ $room->room_number }}</td>
                        <td class="px-6 py-3 text-sm">{{ ucfirst($room->type) }}</td>
                        <td class="px-6 py-3 text-sm">
                            @if($room->pricing_type === 'per_slot')
                                <span class="inline-flex items-center gap-1 text-violet-600">
                                    <i class="fas fa-clock text-xs"></i> Slot
                                </span>
                            @elseif($room->pricing_type === 'per_hour')
                                <span class="inline-flex items-center gap-1 text-blue-600">
                                    <i class="fas fa-hourglass-half text-xs"></i> Hourly
                                </span>
                            @else
                                <span class="text-gray-500">Nightly</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @if($room->bookings_count > 0)
                                <span class="badge-red">Booked</span>
                            @else
                                <span class="badge-green">Free</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-gray-700">{{ $room->bookings_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
