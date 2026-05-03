<!DOCTYPE html>
<html lang="en">
<head>
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $bill->bill_number }}</title>
    <link rel="icon" type="image/png" href="{{ asset('hotel-crm-logo.png') }}">
    
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; font-size: 12px; background: #fff; }
    .bill-wrap { width: 80mm; margin: 0 auto; padding: 8px; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #000; margin: 6px 0; }
    .row { display: flex; justify-content: space-between; margin: 2px 0; }
    .item-name { flex: 1; }
    .item-qty { width: 25px; text-align: center; }
    .item-price { width: 55px; text-align: right; }
    .item-total { width: 60px; text-align: right; font-weight: bold; }
    .hotel-name { font-size: 14px; font-weight: bold; }
    .total-row { font-size: 13px; font-weight: bold; }

    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }
        html, body { width: 80mm; }
        .bill-wrap { width: 80mm; padding: 4px; }
        .no-print { display: none !important; }
    }

    @media screen {
        body { background: #f0f0f0; padding: 20px; }
        .bill-wrap {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            padding: 12px;
        }
    }
</style>
</head>
<body>

{{-- Header --}}
<div class="center">
    <div class="hotel-name">{{ $setting['resort_name'] ?? 'Hotel CRM' }}</div>
    @if(!empty($setting['address']))
    <div style="font-size:11px;">{{ $setting['address'] }}</div>
    @endif
    @if(!empty($setting['phone']))
    <div style="font-size:11px;">Ph: {{ $setting['phone'] }}</div>
    @endif
    <div class="divider"></div>
    <div class="bold" style="font-size:14px;">RESTAURANT BILL</div>
    <div class="divider"></div>
</div>

{{-- Bill Info --}}
<div class="row"><span>Bill #</span><span class="bold">{{ $bill->bill_number }}</span></div>
<div class="row"><span>Order #</span><span>{{ $bill->order->order_number }}</span></div>
<div class="row"><span>{{ $bill->order->table ? 'Table' : ($bill->order->room_number ? 'Room' : 'Order') }}</span><span>{{ $bill->order->table?->name ?? ($bill->order->room_number ?? $bill->order->order_number) }}</span></div>
<div class="row"><span>Date</span><span>{{ $bill->created_at->format('d/m/Y h:i A') }}</span></div>
@if($bill->booking?->customer)
<div class="row"><span>Guest</span><span>{{ $bill->booking->customer->name }}</span></div>
@endif

<div class="divider"></div>

{{-- Items Header --}}
<div class="row bold" style="font-size:11px;">
    <span class="item-name">ITEM</span>
    <span class="item-qty">QTY</span>
    <span class="item-price">PRICE</span>
    <span class="item-total">TOTAL</span>
</div>
<div class="divider"></div>

{{-- Items --}}
@foreach($bill->order->items as $item)
<div class="row">
    <span class="item-name">
        {{ $item->food_type === 'veg' ? '[V]' : ($item->food_type === 'nonveg' ? '[N]' : '[B]') }}
        {{ $item->item_name }}
    </span>
    <span class="item-qty">{{ $item->quantity }}</span>
    <span class="item-price">{{ number_format($item->final_price, 2) }}</span>
    <span class="item-total">{{ number_format($item->subtotal, 2) }}</span>
</div>
@if($item->kot_note)
<div style="font-size:11px; margin-left:10px; color:#555;">↳ {{ $item->kot_note }}</div>
@endif
@endforeach

<div class="divider"></div>

{{-- Totals --}}
<div class="row"><span>Subtotal</span><span>₹{{ number_format($bill->subtotal, 2) }}</span></div>
<div class="row"><span>GST ({{ $bill->tax_rate }}%)</span><span>₹{{ number_format($bill->tax_amount, 2) }}</span></div>
<div class="divider"></div>
<div class="row total-row">
    <span>TOTAL</span>
    <span>₹{{ number_format($bill->total, 2) }}</span>
</div>
<div class="divider"></div>

{{-- Payment --}}
<div class="row">
    <span>Payment</span>
    <span class="bold">{{ $bill->paymentMethodLabel() }}</span>
</div>
@if($bill->payment_reference)
<div class="row"><span>Ref</span><span>{{ $bill->payment_reference }}</span></div>
@endif

<div class="divider"></div>
<div class="center" style="font-size:11px; margin-top:8px;">
    <div>Thank you for dining with us!</div>
    <div style="margin-top:4px;">Please visit again</div>
</div>

{{-- Print Button --}}
<div class="no-print" style="margin-top:20px; text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px; cursor:pointer; background:#1e293b; color:#fff; border:none; border-radius:6px; font-size:13px;">🖨️ Print Bill</button>
    <button onclick="if(window.opener){window.close();}else{window.history.back();}" style="padding:8px 20px; cursor:pointer; margin-left:10px; background:#64748b; color:#fff; border:none; border-radius:6px; font-size:13px;">✕ Close</button>
</div>

<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>