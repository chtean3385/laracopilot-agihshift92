<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $hotel->name }} — Food Menu QR Codes</title>
<style>
    @page { margin: 14mm; size: A4 portrait; }
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; margin: 0; padding: 0; }
    .card {
        width: 100%;
        box-sizing: border-box;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px 18px;
        text-align: center;
        page-break-after: always;
        page-break-inside: avoid;
    }
    .card.general { border-color: #f97316; border-style: dashed; }
    .hotel { font-size: 12px; font-weight: bold; color: #f97316; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .room-label { font-size: 16px; font-weight: bold; color: #475569; margin-top: 8px; }
    .room-num { font-size: 56px; font-weight: bold; color: #f97316; margin: 4px 0 14px; line-height: 1; }
    .general-title { font-size: 26px; font-weight: bold; color: #1e293b; margin: 6px 0 14px; }
    .qr { margin: 10px auto 18px; }
    .qr svg { width: 260px; height: 260px; }
    .scan-call { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 10px; letter-spacing: 1px; }
    .how {
        text-align: left;
        font-size: 13px;
        color: #475569;
        line-height: 1.7;
        max-width: 380px;
        margin: 14px auto 8px;
        padding: 12px 18px 12px 36px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
    }
    .how-title {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        color: #c2410c;
        margin: 14px 0 0;
        letter-spacing: 1px;
    }
    .url { font-size: 10px; color: #94a3b8; margin-top: 14px; word-break: break-all; }
    .footer { font-size: 10px; color: #94a3b8; text-align: center; margin-top: 12px; }
</style>
</head>
<body>

{{-- General QR (lobby / restaurant) --}}
<div class="card general">
    <div class="hotel">{{ $hotel->name }}</div>
    <div class="general-title">In-Room Dining Menu</div>
    <div class="qr">{!! $qrSvgs['__general__'] !!}</div>
    <div class="scan-call">SCAN TO ORDER FOOD</div>
    <div class="how-title">HOW TO ORDER</div>
    <div class="how">
        <ol style="margin:0;padding-left:18px;">
            <li>Open your <strong>phone camera</strong></li>
            <li>Point it at this QR code &amp; tap the link</li>
            <li>Choose your <strong>room number</strong></li>
            <li>Browse the menu, add items to cart</li>
            <li>Enter your name &amp; submit — staff will deliver to your room and bill it to your stay</li>
        </ol>
    </div>
    <div class="url">{{ $baseUrl }}</div>
    <div class="footer">Generated {{ now()->format('d M Y, H:i') }}</div>
</div>

{{-- Per-room QR cards --}}
@foreach($rooms as $room)
<div class="card">
    <div class="hotel">{{ $hotel->name }}</div>
    <div class="room-label">ROOM</div>
    <div class="room-num">{{ $room->room_number }}</div>
    <div class="qr">{!! $qrSvgs[$room->id] !!}</div>
    <div class="scan-call">SCAN TO ORDER FOOD</div>
    <div class="how-title">HOW TO ORDER</div>
    <div class="how">
        <ol style="margin:0;padding-left:18px;">
            <li>Open your <strong>phone camera</strong></li>
            <li>Point it at this QR code &amp; tap the link</li>
            <li>Browse the menu &amp; add items to your cart</li>
            <li>Enter your name &amp; phone, then place the order</li>
            <li>Our staff will confirm, prepare &amp; deliver to Room <strong>{{ $room->room_number }}</strong></li>
        </ol>
    </div>
    <div class="url">{{ $baseUrl }}/{{ $room->room_number }}</div>
    <div class="footer">Charges will be added to your room bill on delivery.</div>
</div>
@endforeach

</body>
</html>
