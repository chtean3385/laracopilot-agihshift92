@extends('layouts.admin')
@section('title','Revenue Report')
@section('page-title','Revenue Report')
@section('page-subtitle','Payment collection analysis')
@section('content')
<div class="space-y-5">
    <form method="GET" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-wrap gap-4 items-end">
        <div><label class="form-label">From</label><input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
        <a href="{{ route('reports.revenue') }}" class="btn-secondary">Reset</a>
    </form>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Total Revenue</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">₹{{ number_format($totalRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Cash</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($cashRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">Card</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($cardRevenue) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
            <div class="text-xs text-gray-400 uppercase font-semibold">UPI</div>
            <div class="text-2xl font-black text-gray-700 mt-1">₹{{ number_format($upiRevenue) }}</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-bold text-gray-800">Transactions ({{ $payments->count() }})</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $p)
                    @php $isWH = $p->booking && $p->booking->is_whole_hotel; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 text-xs text-gray-500">{{ $p->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $p->booking->customer->name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-xs font-mono text-cyan-600">{{ $p->booking->booking_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-sm">
                            @if($isWH)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                    <i class="fas fa-hotel" style="font-size:10px;"></i> Whole Hotel
                                </span>
                            @else
                                {{ $p->booking->room?->room_number ?? '—' }}
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm">{{ ucfirst($p->payment_method) }}</td>
                        <td class="px-6 py-3 text-sm font-bold text-emerald-600 text-right">₹{{ number_format($p->amount) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No transactions in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
