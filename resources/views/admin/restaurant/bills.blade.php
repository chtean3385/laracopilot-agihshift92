@extends('layouts.admin')

@section('title', 'Restaurant Bills')

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('restaurant.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tables</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">🧾 Restaurant Bills</h1>
            <p class="text-gray-500 text-sm">All settled restaurant bills</p>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    @if($bills->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <div class="text-6xl mb-4">🧾</div>
        <p class="text-lg font-medium">No bills yet</p>
        <p class="text-sm mt-1">Bills will appear here after orders are settled</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left px-4 py-3 text-gray-600">Bill #</th>
                    <th class="text-left px-4 py-3 text-gray-600">Table</th>
                    <th class="text-left px-4 py-3 text-gray-600">Order #</th>
                    <th class="text-center px-4 py-3 text-gray-600">Type</th>
                    <th class="text-center px-4 py-3 text-gray-600">Payment</th>
                    <th class="text-right px-4 py-3 text-gray-600">Total</th>
                    <th class="text-left px-4 py-3 text-gray-600">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $bill->bill_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $bill->order->table->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $bill->order->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($bill->bill_type === 'room')
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">🛏️ Room</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">💵 Direct</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">
                        {{ strtoupper($bill->payment_method ?? '—') }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">
                        ₹{{ number_format($bill->total, 2) }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ $bill->created_at->format('d M Y h:i A') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('restaurant.bills.print', $bill->id) }}"
                            target="_blank"
                            class="text-xs text-blue-600 hover:underline">🖨️ Print</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $bills->links() }}
    </div>
    @endif
</div>
@endsection