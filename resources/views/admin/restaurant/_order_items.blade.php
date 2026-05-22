{{-- Order items — mobile card list + totals — re-rendered via AJAX --}}
<style>
.oi-card{ display:flex;align-items:center;gap:10px;padding:10px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:6px; }
.oi-stepper{ display:inline-flex;align-items:center;background:#f1f5f9;border-radius:6px;overflow:hidden; }
.oi-stepper button{ width:28px;height:28px;border:none;background:transparent;font-size:14px;font-weight:800;cursor:pointer;color:#475569; }
.oi-stepper button:active{ background:#cbd5e1; }
.oi-stepper span{ width:28px;text-align:center;font-size:13px;font-weight:700; }
@media(max-width:480px){
    .oi-card{ padding:8px 10px; gap:8px; }
    .oi-name{ font-size:13px !important; }
    .oi-price{ font-size:12px !important; }
}
</style>

<h3 style="font-size:14px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.6px;margin:0 0 10px;">🛒 Order Items <span style="font-weight:500;color:#94a3b8;">({{ $order->items->count() }})</span></h3>

@if($order->items->isEmpty())
<div style="text-align:center;padding:24px 12px;color:#94a3b8;font-size:13px;background:#f8fafc;border-radius:12px;border:1px dashed #e2e8f0;">
    <div style="font-size:28px;margin-bottom:6px;">🍽️</div>
    <p style="font-weight:600;">No items yet</p>
    <p style="font-size:11px;">Scroll down and tap menu items to add</p>
</div>
@else
    @foreach($order->items as $item)
    <div class="oi-card" id="item-row-{{ $item->id }}">
        <div style="width:38px;height:38px;background:{{ $item->food_type === 'veg' ? '#dcfce7' : ($item->food_type === 'nonveg' ? '#fee2e2' : '#e0f2fe') }};border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;">
            {{ $item->food_type === 'veg' ? '🌱' : ($item->food_type === 'nonveg' ? '🍗' : '🥪') }}
        </div>
        <div style="flex:1;min-width:0;">
            <div class="oi-name" style="font-size:14px;font-weight:700;color:#0f172a;">{{ $item->item_name }}</div>
            @if($item->kot_note)
            <div style="font-size:11px;color:#f59e0b;font-weight:600;">📝 {{ $item->kot_note }}</div>
            @endif
            <div class="oi-price" style="font-size:13px;color:#64748b;">₹{{ number_format($item->final_price, 2) }} each</div>
        </div>
        @if($order->isOpen())
        <div class="oi-stepper">
            <button onclick="var v=Math.max(1,parseInt(this.nextElementSibling.textContent)-1);this.nextElementSibling.textContent=v;updateQty({{ $item->id }},v)">-</button>
            <span>{{ $item->quantity }}</span>
            <button onclick="var v=parseInt(this.previousElementSibling.textContent)+1;this.previousElementSibling.textContent=v;updateQty({{ $item->id }},v)">+</button>
        </div>
        <button onclick="removeItem({{ $item->id }})" style="width:28px;height:28px;border:none;background:transparent;color:#ef4444;font-size:14px;cursor:pointer;flex-shrink:0;">
            <i class="fas fa-trash-alt" style="font-size:12px;"></i>
        </button>
        @else
        <div style="font-size:14px;font-weight:800;color:#0f172a;">₹{{ number_format($item->subtotal, 2) }}</div>
        @endif
    </div>
    @endforeach

{{-- Totals --}}
<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-top:10px;">
    <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748b;margin-bottom:4px;">
        <span>Subtotal</span><span>₹{{ number_format($order->subtotal, 2) }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748b;margin-bottom:4px;">
        <span>GST ({{ $order->tax_rate }}%)</span><span>₹{{ number_format($order->tax_amount, 2) }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:900;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:8px;margin-top:6px;">
        <span>Total</span><span>₹{{ number_format($order->total, 2) }}</span>
    </div>
</div>
@endif
