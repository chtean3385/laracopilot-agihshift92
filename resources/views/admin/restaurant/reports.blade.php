@extends('layouts.admin')

@section('title', 'Restaurant Reports')

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('restaurant.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tables</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">📊 Restaurant Reports</h1>
            <p class="text-gray-500 text-sm">Revenue and billing summary</p>
        </div>
    </div>
</div>

{{-- Date Filter --}}
<form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <div class="flex gap-3 flex-wrap items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="btn-primary text-sm">Filter</button>
        <a href="{{ route('restaurant.reports') }}" class="btn-secondary text-sm">This Month</a>
    </div>
</form>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-sm text-gray-500 mb-1">Total Revenue</div>
        <div class="text-2xl font-bold text-gray-800">₹{{ number_format($totalRevenue, 2) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-sm text-gray-500 mb-1">Total Tax</div>
        <div class="text-2xl font-bold text-gray-800">₹{{ number_format($totalTax, 2) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-sm text-gray-500 mb-1">Direct Bills</div>
        <div class="text-2xl font-bold text-green-600">{{ $directBills }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-sm text-gray-500 mb-1">Room Bills</div>
        <div class="text-2xl font-bold text-blue-600">{{ $roomBills }}</div>
    </div>
</div>

{{-- Bills Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="font-bold text-gray-800">Bills ({{ $bills->count() }})</h3>
    </div>
    @if($bills->isEmpty())
    <div class="text-center py-12 text-gray-400">
        <p>No bills found for this period</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left px-4 py-3 text-gray-600">Bill #</th>
                    <th class="text-left px-4 py-3 text-gray-600">Table</th>
                    <th class="text-center px-4 py-3 text-gray-600">Type</th>
                    <th class="text-right px-4 py-3 text-gray-600">Subtotal</th>
                    <th class="text-right px-4 py-3 text-gray-600">Tax</th>
                    <th class="text-right px-4 py-3 text-gray-600">Total</th>
                    <th class="text-left px-4 py-3 text-gray-600">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $bill->bill_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $bill->order->table->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($bill->bill_type === 'room')
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">🛏️ Room</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">💵 Direct</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($bill->subtotal, 2) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($bill->tax_amount, 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">₹{{ number_format($bill->total, 2) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $bill->created_at->format('d M Y h:i A') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('restaurant.bills.print', $bill->id) }}" target="_blank"
                            class="text-xs text-blue-600 hover:underline">🖨️ Print</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t-2 border-gray-300">
                    <td colspan="3" class="px-4 py-3 font-bold text-gray-800">TOTAL</td>
                    <td class="px-4 py-3 text-right font-bold">₹{{ number_format($bills->sum('subtotal'), 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold">₹{{ number_format($totalTax, 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800">₹{{ number_format($totalRevenue, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
@endsection