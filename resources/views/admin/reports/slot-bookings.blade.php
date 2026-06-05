@extends('layouts.admin')
@section('title', 'Slot Bookings Report')
@section('page-title', 'Slot Bookings Report')
@section('page-subtitle', 'Revenue, utilization & detail for all time-slot bookings')

@section('content')
<div class="max-w-full space-y-6">

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" action="{{ route('reports.slot_bookings') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $from->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $to->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Slot</label>
                <select name="slot_id" class="form-input" style="min-width:160px;">
                    <option value="">All Slots</option>
                    @foreach($slots as $slot)
                    <option value="{{ $slot->id }}" {{ $filterSlot == $slot->id ? 'selected' : '' }}>
                        {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i>Filter</button>
                <a href="{{ route('reports.slot_bookings.export', array_filter(['date_from'=>$from->toDateString(),'date_to'=>$to->toDateString(),'slot_id'=>$filterSlot])) }}"
                   class="btn-secondary"><i class="fas fa-download mr-2"></i>CSV</a>
                <a href="{{ route('reports.slot_bookings') }}" class="btn-secondary"><i class="fas fa-times mr-1"></i>Reset</a>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Bookings</span>
                <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center">
                    <i class="fas fa-calendar-check text-violet-500 text-sm"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-gray-800">{{ $totalBookings }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $from->format('d M') }} – {{ $to->format('d M Y') }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Revenue</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-rupee-sign text-emerald-500 text-sm"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-gray-800">₹{{ number_format($totalRevenue) }}</div>
            <div class="text-xs text-gray-400 mt-1">Across all slot bookings</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Avg per Booking</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-amber-500 text-sm"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-gray-800">₹{{ number_format($avgRevenue) }}</div>
            <div class="text-xs text-gray-400 mt-1">Average booking value</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Now</span>
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
                    <i class="fas fa-door-open text-green-500 text-sm"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-gray-800">{{ $statusBreakdown['checked_in'] ?? 0 }}</div>
            <div class="text-xs text-gray-400 mt-1">Currently checked in</div>
        </div>
    </div>

    {{-- Per-Slot Breakdown --}}
    @if(!empty($slotBreakdown) && $totalBookings > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50 flex items-center gap-3">
            <i class="fas fa-layer-group text-violet-500"></i>
            <h3 class="font-bold text-gray-800">Per-Slot Breakdown</h3>
            <span class="text-xs text-gray-400 ml-auto">{{ $from->format('d M') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($slotBreakdown as $sb)
            @if($sb['count'] > 0)
            @php
                $pct = $totalRevenue > 0 ? round($sb['revenue'] / $totalRevenue * 100) : 0;
            @endphp
            <div class="px-6 py-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                    <i class="fas fa-clock text-violet-400 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-gray-800">{{ $sb['slot']->name }}</span>
                        <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">{{ $sb['slot']->start_time }}–{{ $sb['slot']->end_time }}</span>
                        @foreach($sb['statuses'] as $status => $cnt)
                        <span class="text-xs rounded-full px-2 py-0.5 font-medium
                            {{ $status === 'checked_in' ? 'bg-green-100 text-green-700' : ($status === 'confirmed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $cnt }} {{ ucfirst(str_replace('_',' ',$status)) }}
                        </span>
                        @endforeach
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5" style="max-width:200px;">
                            <div class="bg-violet-500 h-1.5 rounded-full" style="width:{{ $pct }}%;"></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $pct }}% of revenue</span>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-bold text-violet-700 text-lg">₹{{ number_format($sb['revenue']) }}</div>
                    <div class="text-xs text-gray-400">{{ $sb['count'] }} {{ Str::plural('booking', $sb['count']) }}</div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Bookings Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-slate-50 flex items-center gap-3">
            <i class="fas fa-list text-gray-500"></i>
            <h3 class="font-bold text-gray-800">Slot Bookings</h3>
            <span class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">{{ $totalBookings }}</span>
        </div>
        @if($bookings->isEmpty())
        <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-full bg-violet-50 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-calendar-times text-violet-200 text-2xl"></i>
            </div>
            <p class="text-gray-500 font-medium">No slot bookings found for this period.</p>
            <p class="text-gray-400 text-sm mt-1">Try adjusting the date range or slot filter above.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Booking #</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Room</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Slot</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Guest</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($bookings as $b)
                    @php
                        $statusColors = [
                            'confirmed'   => 'bg-blue-100 text-blue-700',
                            'checked_in'  => 'bg-green-100 text-green-700',
                            'checked_out' => 'bg-gray-100 text-gray-600',
                            'cancelled'   => 'bg-red-100 text-red-600',
                        ];
                        $sc = $statusColors[$b->status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('bookings.show', $b->id) }}" class="font-semibold text-violet-600 hover:text-violet-800">{{ $b->booking_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $b->booking_date?->format('D, d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-gray-700">R{{ $b->room?->room_number ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($b->timeSlot)
                            <div class="font-medium text-gray-800">{{ $b->timeSlot->name }}</div>
                            <div class="text-xs text-gray-400">{{ $b->timeSlot->start_time }}–{{ $b->timeSlot->end_time }}</div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $b->customer?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $sc }}">
                                {{ ucfirst(str_replace('_',' ',$b->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800">₹{{ number_format($b->total_amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right font-semibold text-gray-600">Total Revenue:</td>
                        <td class="px-4 py-3 text-right font-extrabold text-violet-700 text-base">₹{{ number_format($totalRevenue) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
