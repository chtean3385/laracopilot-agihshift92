<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KOT — {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 13px; width: 80mm; padding: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .item-row { display: flex; justify-content: space-between; margin: 4px 0; }
        .item-name { flex: 1; }
        .item-qty { width: 30px; text-align: center; font-weight: bold; }
        .note { font-size: 11px; color: #555; margin-left: 10px; }
        .header { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 10px; font-size: 11px; }
        @media print {
            body { width: 80mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="center">
    <div class="header">KITCHEN ORDER TICKET</div>
    <div class="divider"></div>
</div>

<div class="bold">{{ $order->table ? 'Table: ' . $order->table->name : ($order->room_number ? 'Room: ' . $order->room_number : 'Walk-in') }}</div>
<div>Order: {{ $order->order_number }}</div>
<div>Time: {{ $order->created_at->format('d/m/Y h:i A') }}</div>

<div class="divider"></div>

<div class="bold center" style="font-size:12px; margin-bottom:6px;">— ITEMS —</div>

@foreach($order->items as $item)
<div class="item-row">
    <div class="item-qty">{{ $item->quantity }}x</div>
    <div class="item-name">
        {{ $item->food_type === 'veg' ? '[V]' : ($item->food_type === 'nonveg' ? '[N]' : '[B]') }}
        {{ $item->item_name }}
    </div>
</div>
@if($item->kot_note)
<div class="note">↳ {{ $item->kot_note }}</div>
@endif
@endforeach

<div class="divider"></div>
<div class="center footer">
    <div>Total Items: {{ $order->items->count() }}</div>
    <div style="margin-top:8px;">** Please prepare immediately **</div>
</div>

<div class="no-print" style="margin-top:20px; text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px; cursor:pointer;">🖨️ Print KOT</button>
    <button onclick="window.close()" style="padding:8px 20px; cursor:pointer; margin-left:10px;">✕ Close</button>
</div>

<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>