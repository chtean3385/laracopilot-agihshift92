<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order->order_number }} — {{ $hotel->name }}</title>
    <meta http-equiv="refresh" content="20">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; color: #1e293b; padding: 18px; }
        .card { background: #fff; border-radius: 18px; padding: 22px; max-width: 480px; margin: 0 auto; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .icon { width: 78px; height: 78px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #fff; margin: 0 auto 16px; }
        h1 { font-size: 22px; font-weight: 800; text-align: center; }
        .order-num { text-align: center; color: #64748b; font-size: 14px; margin-top: 6px; }
        .status-pill { display: block; text-align: center; padding: 6px 16px; border-radius: 16px; font-size: 13px; font-weight: 800; margin: 14px auto 18px; max-width: 220px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #f1f5f9; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #64748b; }
        .value { font-weight: 700; }
        .items { background: #f8fafc; border-radius: 12px; padding: 14px; margin-top: 16px; }
        .item { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total { display: flex; justify-content: space-between; padding-top: 12px; margin-top: 10px; border-top: 1.5px dashed #cbd5e1; font-weight: 800; font-size: 16px; }
        .note { background: #eff6ff; color: #1e40af; padding: 12px 14px; border-radius: 12px; font-size: 13px; margin-top: 18px; text-align: center; }
        .back { display: block; text-align: center; margin-top: 20px; padding: 12px; background: #f97316; color: #fff; border-radius: 12px; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        @php
            $color = $order->statusColor();
            $icon  = match($order->status) {
                'pending'     => 'fa-hourglass-half',
                'in_progress' => 'fa-fire',
                'approved'    => 'fa-check',
                'cancelled'   => 'fa-times',
                default       => 'fa-receipt',
            };
        @endphp
        <div class="icon" style="background:{{ $color }};"><i class="fas {{ $icon }}"></i></div>
        <h1>
            @if($order->status === 'pending')      Order Received! @endif
            @if($order->status === 'in_progress')  Being Prepared @endif
            @if($order->status === 'approved')     Order Approved @endif
            @if($order->status === 'cancelled')    Order Cancelled @endif
        </h1>
        <div class="order-num">{{ $order->order_number }}</div>
        <div class="status-pill" style="background:{{ $color }}22;color:{{ $color }};">{{ $order->statusLabel() }}</div>

        <div class="info-row"><span class="label">Room</span><span class="value">{{ $order->room_number }}</span></div>
        @if($order->guest_name)<div class="info-row"><span class="label">Name</span><span class="value">{{ $order->guest_name }}</span></div>@endif
        <div class="info-row"><span class="label">Placed</span><span class="value">{{ $order->created_at->format('h:i A') }}</span></div>

        <div class="items">
            @foreach($order->items as $i)
            <div class="item"><span>{{ $i->name }} × {{ $i->quantity }}</span><span>₹ {{ number_format((float)$i->total, 2) }}</span></div>
            @endforeach
            <div class="total"><span>Total</span><span>₹ {{ number_format((float)$order->total_amount, 2) }}</span></div>
        </div>

        @if($order->status === 'pending')
        <div class="note"><i class="fas fa-info-circle"></i> Our team will confirm your order shortly. We may call you to verify.</div>
        @elseif($order->status === 'in_progress')
        <div class="note"><i class="fas fa-fire"></i> Your food is being prepared. It will be delivered to your room soon.</div>
        @elseif($order->status === 'approved')
        <div class="note"><i class="fas fa-check"></i> Your order has been approved and added to your room bill.</div>
        @elseif($order->status === 'cancelled')
        <div class="note" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-times"></i> This order was cancelled.{{ $order->cancellation_reason ? ' Reason: '.$order->cancellation_reason : '' }}</div>
        @endif

        <a href="{{ route('public.food-menu.show', ['slug' => $hotel->slug, 'room' => $order->room_number]) }}" class="back"><i class="fas fa-utensils"></i> Order More</a>
    </div>
</body>
</html>
