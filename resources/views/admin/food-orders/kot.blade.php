<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KOT — {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Courier New', monospace; max-width: 320px; margin: 14px auto; padding: 10px; color: #000; font-size: 14px; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .row    { display: flex; justify-content: space-between; padding: 3px 0; }
        .sep    { border-top: 1px dashed #000; margin: 8px 0; }
        h1, h2  { margin: 0; }
        h1      { font-size: 20px; }
        h2      { font-size: 16px; }
        table   { width: 100%; border-collapse: collapse; }
        th, td  { padding: 4px 0; }
        th      { border-bottom: 1px dashed #000; text-align: left; }
        .qty    { text-align: center; width: 40px; }
        @media print { body { margin: 0; padding: 6px; } .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <h1>KITCHEN ORDER TICKET</h1>
        <h2>#{{ $order->order_number }}</h2>
    </div>
    <div class="sep"></div>
    <div class="row"><strong>Room:</strong> <strong style="font-size:18px;">{{ $order->room_number }}</strong></div>
    @if($order->guest_name)<div class="row"><span>Guest:</span><span>{{ $order->guest_name }}</span></div>@endif
    @if($order->guest_phone)<div class="row"><span>Phone:</span><span>{{ $order->guest_phone }}</span></div>@endif
    <div class="row"><span>Time:</span><span>{{ $order->created_at->format('d/m H:i') }}</span></div>
    <div class="sep"></div>

    <table>
        <thead>
            <tr><th>Item</th><th class="qty">Qty</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="qty"><strong>{{ $item->quantity }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->guest_notes)
    <div class="sep"></div>
    <div><strong>Notes:</strong></div>
    <div>{{ $order->guest_notes }}</div>
    @endif

    <div class="sep"></div>
    <div class="center">— END —</div>

    <div class="no-print center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">Print</button>
        <button onclick="window.close()" style="padding:8px 16px;cursor:pointer;">Close</button>
    </div>
</body>
</html>
