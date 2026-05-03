<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $hotel->name }} — Restaurant QR Codes</title>
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
    .card.general { border-color: #dc2626; border-style: dashed; }
    .card.room    { border-color: #0ea5e9; }
    .hotel { font-size: 12px; font-weight: bold; color: #dc2626; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .label { font-size: 16px; font-weight: bold; color: #475569; margin-top: 8px; }
    .name  { font-size: 48px; font-weight: bold; color: #dc2626; margin: 4px 0 14px; line-height: 1; }
    .name.room { color: #0ea5e9; }
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
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
    }
    .how-title { text-align: center; font-size: 13px; font-weight: bold; color: #b91c1c; margin: 14px 0 0; letter-spacing: 1px; }
    .url { font-size: 10px; color: #94a3b8; margin-top: 14px; word-break: break-all; }
    .footer { font-size: 10px; color: #94a3b8; text-align: center; margin-top: 12px; }
</style>
</head>
<body>

{{-- General QR --}}
<div class="card general">
    <div class="hotel">{{ $hotel->name }}</div>
    <div class="general-title">Restaurant Menu</div>
    <div class="qr">{!! $qrSvgs['__general__'] !!}</div>
    <div class="scan-call">SCAN TO VIEW MENU & ORDER</div>
    <div class="how-title">HOW IT WORKS</div>
    <div class="how">
        <ol style="margin:0;padding-left:18px;">
            <li>Open your <strong>phone camera</strong></li>
            <li>Point it at this QR code &amp; tap the link</li>
            <li>Choose your room or table</li>
            <li>Browse the menu &amp; add items to your cart</li>
            <li>Place the order — staff confirm and serve</li>
        </ol>
    </div>
    <div class="url">{{ $baseUrl }}</div>
    <div class="footer">Generated {{ now()->format('d M Y, H:i') }}</div>
</div>

{{-- Per-table QRs --}}
@foreach($tables as $table)
<div class="card">
    <div class="hotel">{{ $hotel->name }}</div>
    <div class="label">TABLE</div>
    <div class="name">{{ $table->name }}</div>
    <div class="qr">{!! $qrSvgs['table_' . $table->id] !!}</div>
    <div class="scan-call">SCAN TO ORDER</div>
    <div class="how-title">HOW TO ORDER</div>
    <div class="how">
        <ol style="margin:0;padding-left:18px;">
            <li>Open your <strong>phone camera</strong></li>
            <li>Point it at this QR &amp; tap the link</li>
            <li>Browse the menu, add items to cart</li>
            <li>Place the order — our staff will serve at this table</li>
        </ol>
    </div>
    <div class="url">{{ $baseUrl }}/table/{{ $table->name }}</div>
    <div class="footer">Bill is settled at the table.</div>
</div>
@endforeach

{{-- Per-room QRs --}}
@foreach($rooms as $room)
<div class="card room">
    <div class="hotel">{{ $hotel->name }}</div>
    <div class="label">ROOM</div>
    <div class="name room">{{ $room->room_number }}</div>
    <div class="qr">{!! $qrSvgs['room_' . $room->id] !!}</div>
    <div class="scan-call">SCAN TO ORDER FOOD</div>
    <div class="how-title">HOW TO ORDER</div>
    <div class="how">
        <ol style="margin:0;padding-left:18px;">
            <li>Open your <strong>phone camera</strong></li>
            <li>Point it at this QR &amp; tap the link</li>
            <li>Browse the menu &amp; add items to your cart</li>
            <li>Enter your name, then place the order</li>
            <li>Our staff will confirm &amp; deliver to Room <strong>{{ $room->room_number }}</strong></li>
        </ol>
    </div>
    <div class="url">{{ $baseUrl }}/room/{{ $room->room_number }}</div>
    <div class="footer">Charges will be added to your room bill.</div>
</div>
@endforeach

</body>
</html>
