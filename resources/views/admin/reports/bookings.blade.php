@extends('layouts.admin')
@section('title','Bookings Report')
@section('page-title','Bookings Report')
@section('page-subtitle','All bookings in selected period')
@section('content')
<div class="space-y-5">
    <form method="GET" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end no-print">
        <div><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
        <a href="{{ route('reports.bookings') }}" class="btn-secondary">Reset</a>
        <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('reports.bookings', array_merge(request()->only('date_from','date_to'), ['export'=>'pdf'])) }}"
               style="padding:8px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-pdf"></i>PDF
            </a>
            <a href="{{ route('reports.bookings', array_merge(request()->only('date_from','date_to'), ['export'=>'csv'])) }}"
               style="padding:8px 14px;background:#16a34a;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-file-csv"></i>CSV
            </a>
        </div>
    </form>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($statusCounts as $status => $count)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">{{ ucfirst(str_replace('_',' ',$status)) }}</div>
            <div class="text-3xl font-black mt-1 text-gray-800">{{ $count }}</div>
        </div>
        @endforeach
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking #</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Check-In</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nights</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $b)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 text-xs font-mono text-cyan-600">{{ $b->booking_number }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $b->customer?->name ?? '(Deleted Guest)' }}</td>
                        <td class="px-6 py-3 text-sm">{{ $b->room?->room_number ?? 'Whole Hotel' }}</td>
                        <td class="px-6 py-3 text-sm">{{ $b->check_in_date->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm">{{ $b->nights }}</td>
                        <td class="px-6 py-3 text-sm font-bold text-right">₹{{ number_format($b->total_amount) }}</td>
                        <td class="px-6 py-3"><span class="badge-{{ $b->status_color }}">{{ ucfirst(str_replace('_',' ',$b->status)) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No bookings in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
