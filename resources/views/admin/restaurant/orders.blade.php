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
                        <button type="button" onclick="openOrderModal({{ $order->id }})" class="text-blue-600 hover:underline text-xs font-semibold" style="background:none;border:none;cursor:pointer;">View →</button>
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

{{-- Per-order detail modals (pre-rendered, shown on demand) --}}
@foreach($orders as $order)
<div id="orderModal-{{ $order->id }}" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:60;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;">
    <div style="background:#fff;width:100%;max-width:760px;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">
        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#f8fafc,#fff);">
            <div>
                <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Order</div>
                <div style="font-size:20px;font-weight:800;color:#0f172a;font-family:monospace;">{{ $order->order_number }}</div>
                <div style="font-size:12px;color:#64748b;margin-top:3px;">{{ $order->created_at->format('d M Y, h:i A') }} · {{ $order->created_at->diffForHumans() }}</div>
            </div>
            <button type="button" onclick="closeOrderModal({{ $order->id }})" style="width:34px;height:34px;border-radius:50%;border:none;background:#f1f5f9;color:#64748b;font-size:18px;cursor:pointer;line-height:1;">×</button>
        </div>

        {{-- Meta strip --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;padding:16px 22px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;">
            <div>
                <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:3px;">Source</div>
                <div style="color:#1e293b;font-weight:700;">{{ $order->source === 'guest_qr' ? '📱 Guest QR' : '👨‍🍳 Staff' }}</div>
            </div>
            <div>
                <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:3px;">Where</div>
                <div style="color:#1e293b;font-weight:700;">
                    @if($order->table) <i class="fas fa-chair text-gray-400"></i> {{ $order->table->name }}
                    @elseif($order->room_number) <i class="fas fa-door-open text-gray-400"></i> Room {{ $order->room_number }}
                    @else <span style="color:#94a3b8;">—</span> @endif
                </div>
            </div>
            <div>
                <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:3px;">Status</div>
                <div>{!! $order->statusBadge() !!}</div>
            </div>
            <div>
                <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:3px;">Approval</div>
                <div style="color:#1e293b;font-weight:700;">
                    @if($order->approval_status === 'pending')        <span style="color:#9a3412;">⏳ Pending</span>
                    @elseif($order->approval_status === 'approved')   <span style="color:#15803d;">✓ Approved</span>
                    @elseif($order->approval_status === 'rejected')   <span style="color:#b91c1c;">✕ Rejected</span>
                    @else <span style="color:#94a3b8;">—</span> @endif
                </div>
            </div>
            <div>
                <div style="color:#94a3b8;font-weight:700;text-transform:uppercase;font-size:10px;margin-bottom:3px;">Payment</div>
                <div style="color:#1e293b;font-weight:700;">
                    @if($order->payment_status === 'paid') <span style="color:#15803d;">✓ Paid</span>
                    @else <span style="color:#475569;">Unpaid</span> @endif
                </div>
            </div>
        </div>

        {{-- Guest contact (if any) --}}
        @if($order->guest_name || $order->guest_phone || $order->guest_notes)
        <div style="padding:12px 22px;background:#fff7ed;border-bottom:1px solid #fed7aa;font-size:13px;color:#7c2d12;">
            @if($order->guest_name)<strong>{{ $order->guest_name }}</strong>@endif
            @if($order->guest_phone) · 📞 {{ $order->guest_phone }}@endif
            @if($order->guest_notes)<div style="margin-top:4px;color:#9a3412;font-size:12px;">📝 {{ $order->guest_notes }}</div>@endif
        </div>
        @endif

        {{-- Items --}}
        <div style="padding:18px 22px;">
            <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:10px;">🛒 Items ({{ $order->items->count() }})</div>
            @if($order->items->isEmpty())
                <p style="color:#94a3b8;font-size:13px;text-align:center;padding:18px 0;">No items.</p>
            @else
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:11px;text-transform:uppercase;">
                        <th style="text-align:left;padding:6px 4px;">Item</th>
                        <th style="text-align:center;padding:6px 4px;">Qty</th>
                        <th style="text-align:right;padding:6px 4px;">Price</th>
                        <th style="text-align:right;padding:6px 4px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $it)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 4px;color:#1e293b;">
                            {{ $it->food_type === 'veg' ? '🟢' : ($it->food_type === 'nonveg' ? '🔴' : '🔵') }} {{ $it->item_name }}
                            @if($it->kot_note)<div style="font-size:11px;color:#94a3b8;">{{ $it->kot_note }}</div>@endif
                        </td>
                        <td style="padding:8px 4px;text-align:center;">{{ $it->quantity }}</td>
                        <td style="padding:8px 4px;text-align:right;">₹{{ number_format($it->final_price, 2) }}</td>
                        <td style="padding:8px 4px;text-align:right;font-weight:700;">₹{{ number_format($it->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            {{-- Totals --}}
            <div style="margin-top:14px;padding-top:12px;border-top:1.5px dashed #cbd5e1;font-size:13px;">
                <div style="display:flex;justify-content:space-between;padding:3px 0;color:#64748b;"><span>Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:3px 0;color:#64748b;"><span>GST ({{ $order->tax_rate }}%)</span><span>₹{{ number_format($order->tax_amount, 2) }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:16px;font-weight:800;color:#0f172a;border-top:1px solid #e2e8f0;margin-top:6px;"><span>Total</span><span>₹{{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>

        {{-- Footer actions — contextual to order state --}}
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 22px;border-top:1px solid #e2e8f0;background:#f8fafc;flex-wrap:wrap;">
            <button type="button" onclick="closeOrderModal({{ $order->id }})" style="padding:9px 16px;border:1px solid #cbd5e1;background:#fff;color:#475569;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;">Close</button>

            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                @if($order->isPendingApproval())
                    <form action="{{ route('restaurant.orders.reject', $order->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Decline this guest order?');">
                        @csrf
                        <button type="submit" style="padding:9px 16px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-weight:800;font-size:13px;cursor:pointer;">✕ Decline</button>
                    </form>
                    <form action="{{ route('restaurant.orders.approve', $order->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" style="padding:9px 18px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-weight:800;font-size:13px;cursor:pointer;">✓ Approve &amp; Send to Kitchen</button>
                    </form>
                @elseif($order->isOpen())
                    <a href="{{ route('restaurant.orders.kot.print', $order->id) }}" target="_blank" style="padding:9px 16px;background:#0f172a;color:#fff;border-radius:8px;font-weight:800;font-size:13px;text-decoration:none;">🖨️ Print KOT</a>
                    @if(!$order->isPaid())
                    <a href="{{ route('restaurant.orders.show', $order->id) }}" style="padding:9px 16px;background:#2563eb;color:#fff;border-radius:8px;font-weight:800;font-size:13px;text-decoration:none;">💳 Generate Bill</a>
                    @endif
                @endif
                <a href="{{ route('restaurant.orders.show', $order->id) }}" style="padding:9px 14px;border:1px solid #cbd5e1;background:#fff;color:#1e293b;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;">Open full page ↗</a>
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
function openOrderModal(id) {
    const m = document.getElementById('orderModal-' + id);
    if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeOrderModal(id) {
    const m = document.getElementById('orderModal-' + id);
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}
document.querySelectorAll('[id^="orderModal-"]').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none', document.body.style.overflow = ''; });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('[id^="orderModal-"]').forEach(m => m.style.display = 'none'), document.body.style.overflow = '';
});
</script>
@endsection
