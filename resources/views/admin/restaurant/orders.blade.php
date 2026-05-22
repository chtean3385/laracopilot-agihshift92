@extends('layouts.admin')

@section('title', 'Restaurant Orders')

@section('content')
{{-- ═══ MOBILE-FIRST POS HEADER ═══ --}}
<div style="position:sticky;top:0;z-index:30;background:#fff;border-bottom:1px solid #f1f5f9;box-shadow:0 1px 6px rgba(0,0,0,.04);">
    <div style="padding:10px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;text-decoration:none;">
            <i class="fas fa-arrow-left" style="font-size:10px;"></i> Dashboard
        </a>
        <a href="{{ route('restaurant.index') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;font-weight:700;color:#991b1b;text-decoration:none;">
            <i class="fas fa-chair" style="font-size:10px;"></i> Tables
        </a>
        <div style="flex:1;min-width:0;text-align:right;">
            <h1 style="font-size:17px;font-weight:900;color:#0f172a;margin:0;">📋 Orders</h1>
            <p style="font-size:11px;color:#94a3b8;margin:0;">{{ $orders->total() ?? 0 }} total orders</p>
        </div>
    </div>
</div>

<div style="padding:14px 16px 100px;">

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-danger mb-4">{{ session('error') }}</div>
@endif

<style>
.oc-card{ background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px;position:relative; }
.oc-top{ display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px; }
.oc-badges{ display:flex;gap:4px;flex-wrap:wrap; }
.oc-badge{ font-size:10px;font-weight:800;padding:3px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px; }
.oc-meta{ display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#64748b;margin-bottom:10px; }
.oc-total{ font-size:18px;font-weight:900;color:#0f172a; }
@media(max-width:480px){
    .oc-card{ padding:12px; }
    .oc-total{ font-size:16px; }
}
</style>

    @if($orders->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
        <div style="font-size:48px;margin-bottom:12px;">📋</div>
        <p style="font-size:15px;font-weight:700;color:#475569;">No orders yet</p>
        <p style="font-size:12px;margin-top:4px;">Orders appear once guests start ordering</p>
        <a href="{{ route('restaurant.index') }}" style="display:inline-block;margin-top:14px;padding:10px 20px;background:#0f172a;color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            Go to Tables →
        </a>
    </div>
    @else
    @foreach($orders as $order)
    <div class="oc-card" onclick="openOrderModal({{ $order->id }})" style="cursor:pointer;">
        <div class="oc-top">
            <div>
                <div style="font-family:'SF Mono',Menlo,monospace;font-size:15px;font-weight:900;color:#0f172a;">{{ $order->order_number }}</div>
                <div class="oc-meta">
                    <span><i class="fas fa-clock" style="font-size:10px;color:#94a3b8;"></i> {{ $order->created_at->format('d M, h:i A') }}</span>
                    @if($order->table)
                    <span><i class="fas fa-chair" style="font-size:10px;color:#94a3b8;"></i> {{ $order->table->name }}</span>
                    @elseif($order->room_number)
                    <span><i class="fas fa-door-open" style="font-size:10px;color:#94a3b8;"></i> Rm {{ $order->room_number }}</span>
                    @endif
                    <span><i class="fas fa-shopping-basket" style="font-size:10px;color:#94a3b8;"></i> {{ $order->items->sum('quantity') }} items</span>
                </div>
            </div>
            <div class="oc-total">₹{{ number_format($order->total, 0) }}</div>
        </div>
        <div class="oc-badges">
            @if($order->source === 'guest_qr')
                <span class="oc-badge" style="background:#fef3c7;color:#a16207;">📱 Guest QR</span>
            @else
                <span class="oc-badge" style="background:#e0f2fe;color:#075985;">👨‍🍳 Staff</span>
            @endif
            {!! $order->statusBadge() !!}
            @if($order->approval_status === 'pending')
                <span class="oc-badge" style="background:#fed7aa;color:#9a3412;">⏳ Pending</span>
            @elseif($order->approval_status === 'rejected')
                <span class="oc-badge" style="background:#fee2e2;color:#b91c1c;">✕ Rejected</span>
            @endif
            @if($order->payment_status === 'paid')
                <span class="oc-badge" style="background:#dcfce7;color:#15803d;">✓ Paid</span>
            @else
                <span class="oc-badge" style="background:#f1f5f9;color:#475569;">Unpaid</span>
            @endif
        </div>
    </div>
    @endforeach

    <div style="margin-top:16px;">
        {{ $orders->links() }}
    </div>
    @endif

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
                            {{ $it->food_type === 'veg' ? '🌱' : ($it->food_type === 'nonveg' ? '🍗' : '🥪') }} {{ $it->item_name }}
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
                    <form action="{{ route('restaurant.orders.kot', $order->id) }}" method="POST" style="display:inline;" onsubmit="const self=this;fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(r=>r.json()).then(d=>{if(d.success){const t=document.createElement('div');t.textContent='✅ Sent to kitchen!';t.style.cssText='position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13px;z-index:9999;';document.body.appendChild(t);setTimeout(()=>t.remove(),2200);}});return false;">
                        @csrf
                        <button type="submit" style="padding:9px 16px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-weight:800;font-size:13px;cursor:pointer;">🛡️ Send to Kitchen</button>
                    </form>
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

</div>{{-- /padding wrapper --}}
@endsection