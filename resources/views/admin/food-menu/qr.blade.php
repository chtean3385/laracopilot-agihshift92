@extends('layouts.admin')
@section('title', 'QR Codes')

@section('content')
<div style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-qrcode" style="color:#f97316;"></i> Room QR Codes</h1>
            <p style="color:#64748b;margin:4px 0 0 0;font-size:14px;">Print one QR per room. When guests scan it, the room number is auto-filled and they can place orders.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="button" onclick="window.print()" style="padding:10px 18px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;"><i class="fas fa-print"></i> Print All</button>
            <a href="{{ route('food-menu.dashboard') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#1e40af;font-size:13px;" class="no-print">
        <i class="fas fa-info-circle"></i> <strong>How it works:</strong> Each QR opens the menu with the room number pre-filled. The general hotel QR (top) lets guests pick their own room — useful for the lobby or restaurant area. Click <strong>Download</strong> on any QR to save it as an SVG file.
    </div>

    @if($rooms->isEmpty())
    <div style="background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <i class="fas fa-bed" style="font-size:36px;color:#94a3b8;margin-bottom:12px;"></i>
        <p style="color:#64748b;margin:0;">No rooms configured. Add rooms first to print per-room QR codes.</p>
    </div>
    @else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;" class="qr-grid">
        <div style="background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);page-break-inside:avoid;border:2px dashed #f97316;">
            <div style="font-size:11px;font-weight:700;color:#f97316;text-transform:uppercase;margin-bottom:6px;">{{ $hotel->name }}</div>
            <div style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:10px;">General Menu</div>
            <div style="display:flex;justify-content:center;margin-bottom:10px;">{!! $qrSvgs['__general__'] !!}</div>
            <div style="font-size:11px;color:#64748b;margin-bottom:8px;">Scan to open menu — choose your room</div>
            <a href="{{ route('food-menu.qr.download') }}" class="no-print" style="display:inline-block;padding:6px 12px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> Download SVG</a>
        </div>

        @foreach($rooms as $room)
        <div style="background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);page-break-inside:avoid;">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;">{{ $hotel->name }}</div>
            <div style="font-size:14px;font-weight:600;color:#475569;margin-bottom:4px;">Room</div>
            <div style="font-size:32px;font-weight:800;color:#f97316;margin-bottom:10px;line-height:1;">{{ $room->room_number }}</div>
            <div style="display:flex;justify-content:center;margin-bottom:10px;">{!! $qrSvgs[$room->id] !!}</div>
            <div style="font-size:11px;color:#64748b;margin-bottom:8px;">Scan to view menu &amp; order</div>
            <a href="{{ route('food-menu.qr.download', ['room' => $room->room_number]) }}" class="no-print" style="display:inline-block;padding:6px 12px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> Download SVG</a>
        </div>
        @endforeach
    </div>
    @endif
</div>

<style>
.qr-grid svg { width: 160px; height: 160px; }
@media print {
    .no-print, .sidebar, .topbar, header, nav { display: none !important; }
    body, html { background: #fff !important; }
    .qr-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
}
</style>
@endsection
