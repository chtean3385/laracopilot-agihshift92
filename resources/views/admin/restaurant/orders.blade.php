@extends('layouts.admin')

@section('title', 'Restaurant Orders')

@section('content')
<div class="content-header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('restaurant.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Tables</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">📋 Restaurant Orders</h1>
            <p class="text-gray-500 text-sm">All restaurant orders (newest first)</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('restaurant.bills.index') }}" class="btn-secondary">🧾 Bills</a>
            <a href="{{ route('restaurant.qr.index') }}" class="btn-secondary">📱 QR Codes</a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-danger mb-4">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    @if($orders->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <div class="text-6xl mb-4">📋</div>
        <p class="text-lg font-medium">No orders yet</p>
        <p class="text-sm mt-1">Orders will appear here once guests start ordering</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left px-4 py-3 text-gray-600">Order #</th>
                    <th class="text-left px-4 py-3 text-gray-600">Source</th>
                    <th class="text-left px-4 py-3 text-gray-600">Table / Room</th>
                    <th class="text-center px-4 py-3 text-gray-600">Items</th>
                    <th class="text-center px-4 py-3 text-gray-600">Status</th>
                    <th class="text-center px-4 py-3 text-gray-600">Approval</th>
                    <th class="text-center px-4 py-3 text-gray-600">Payment</th>
                    <th class="text-right px-4 py-3 text-gray-600">Total</th>
                    <th class="text-left px-4 py-3 text-gray-600">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $order->order_number }}</td>
                    <td class="px-4 py-3">
                        @if($order->source === 'guest_qr')
                            <span style="padding:3px 9px;border-radius:10px;background:#fef3c7;color:#a16207;font-size:11px;font-weight:700;">📱 Guest QR</span>
                        @else
                            <span style="padding:3px 9px;border-radius:10px;background:#e0f2fe;color:#075985;font-size:11px;font-weight:700;">👨‍🍳 Staff</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        @if($order->table)
                            <i class="fas fa-chair text-gray-400 mr-1"></i> {{ $order->table->name }}
                        @elseif($order->room_number)
                            <i class="fas fa-door-open text-gray-400 mr-1"></i> Room {{ $order->room_number }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $order->items->sum('quantity') }}</td>
                    <td class="px-4 py-3 text-center">{!! $order->statusBadge() !!}</td>
                    <td class="px-4 py-3 text-center">
                        @if($order->approval_status === 'pending')
                            <span style="padding:3px 9px;border-radius:10px;background:#fed7aa;color:#9a3412;font-size:11px;font-weight:700;">⏳ Pending</span>
                        @elseif($order->approval_status === 'approved')
                            <span style="padding:3px 9px;border-radius:10px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;">✓ Approved</span>
                        @elseif($order->approval_status === 'rejected')
                            <span style="padding:3px 9px;border-radius:10px;background:#fee2e2;color:#b91c1c;font-size:11px;font-weight:700;">✕ Rejected</span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($order->payment_status === 'paid')
                            <span style="padding:3px 9px;border-radius:10px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;">✓ Paid</span>
                        @else
                            <span style="padding:3px 9px;border-radius:10px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:700;">Unpaid</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">₹{{ number_format($order->total, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('restaurant.orders.show', $order->id) }}" class="text-blue-600 hover:underline text-xs font-semibold">View →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
