@extends('layouts.admin')
@section('title', 'Restaurant QR Codes')

@section('content')
<div style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <a href="{{ route('restaurant.menu.index') }}" style="font-size:13px;color:#2563eb;text-decoration:none;">← Back to Menu</a>
            <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:6px 0 2px;"><i class="fas fa-qrcode" style="color:#dc2626;"></i> Restaurant QR Codes</h1>
            <p style="color:#64748b;margin:0;font-size:14px;">Print and place QR codes on tables and in rooms. Guests scan to view menu and order.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;" class="no-print">
            <button type="button" onclick="window.print()" style="padding:10px 18px;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;"><i class="fas fa-print"></i> Print Page</button>
        </div>
    </div>

    <div class="no-print" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#1e40af;font-size:13px;">
        <i class="fas fa-info-circle"></i>
        <strong>How it works:</strong> Place a <strong>table QR</strong> on each dining table — guests can browse the menu and place an order without opening an account; staff approve it from the table map. Place a <strong>room QR</strong> in each guest room for in-room dining; approved orders are billed to that room. The <strong>general QR</strong> at reception lets guests order without a specific table or room.
    </div>

    {{-- General QR --}}
    <h2 style="font-size:16px;font-weight:800;color:#334155;margin:8px 0 12px;">General (Reception / Lobby)</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;" class="qr-grid">
        <div style="background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);page-break-inside:avoid;border:2px dashed #dc2626;">
            <div style="font-size:11px;font-weight:700;color:#dc2626;text-transform:uppercase;margin-bottom:4px;letter-spacing:.5px;">{{ $hotel->name }}</div>
            <div style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:8px;">Restaurant Menu</div>
            <div style="display:flex;justify-content:center;margin-bottom:10px;">{!! $qrSvgs['__general__'] !!}</div>
            <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:6px;">SCAN TO VIEW MENU</div>
            <div class="no-print" style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('restaurant.qr.download') }}" style="padding:6px 12px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> SVG</a>
                <a href="{{ route('restaurant.qr.download', ['format' => 'png']) }}" style="padding:6px 12px;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> PNG</a>
            </div>
        </div>
    </div>

    {{-- Table QRs --}}
    <h2 style="font-size:16px;font-weight:800;color:#334155;margin:24px 0 12px;">Table QR Codes ({{ $tables->count() }})</h2>
    @if($tables->isEmpty())
    <div style="background:#fff;border-radius:14px;padding:30px;text-align:center;color:#64748b;">No tables configured. Add tables on the Restaurant page first.</div>
    @else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;" class="qr-grid">
        @foreach($tables as $table)
        <div style="background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);page-break-inside:avoid;border:1px solid #e2e8f0;">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:.5px;">{{ $hotel->name }}</div>
            <div style="font-size:13px;font-weight:600;color:#475569;margin-bottom:2px;">Table</div>
            <div style="font-size:24px;font-weight:800;color:#dc2626;margin-bottom:8px;line-height:1;">{{ $table->name }}</div>
            <div style="display:flex;justify-content:center;margin-bottom:10px;">{!! $qrSvgs['table_' . $table->id] !!}</div>
            <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:6px;">SCAN TO ORDER</div>
            <div class="no-print" style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('restaurant.qr.download', ['table' => $table->name]) }}" style="padding:6px 12px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> SVG</a>
                <a href="{{ route('restaurant.qr.download', ['table' => $table->name, 'format' => 'png']) }}" style="padding:6px 12px;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> PNG</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Room QRs --}}
    <h2 style="font-size:16px;font-weight:800;color:#334155;margin:24px 0 12px;">In-Room QR Codes ({{ $rooms->count() }})</h2>
    @if($rooms->isEmpty())
    <div style="background:#fff;border-radius:14px;padding:30px;text-align:center;color:#64748b;">No rooms configured. Add rooms first.</div>
    @else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;" class="qr-grid">
        @foreach($rooms as $room)
        <div style="background:#fff;border-radius:14px;padding:18px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);page-break-inside:avoid;border:1px solid #e2e8f0;">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:.5px;">{{ $hotel->name }}</div>
            <div style="font-size:13px;font-weight:600;color:#475569;margin-bottom:2px;">Room</div>
            <div style="font-size:24px;font-weight:800;color:#0ea5e9;margin-bottom:8px;line-height:1;">{{ $room->room_number }}</div>
            <div style="display:flex;justify-content:center;margin-bottom:10px;">{!! $qrSvgs['room_' . $room->id] !!}</div>
            <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:6px;">SCAN TO ORDER</div>
            <div class="no-print" style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('restaurant.qr.download', ['room' => $room->room_number]) }}" style="padding:6px 12px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> SVG</a>
                <a href="{{ route('restaurant.qr.download', ['room' => $room->room_number, 'format' => 'png']) }}" style="padding:6px 12px;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700;"><i class="fas fa-download"></i> PNG</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<style>
.qr-grid svg { width: 170px; height: 170px; }
@media print {
    .no-print, .sidebar, .topbar, header, nav { display: none !important; }
    body, html { background: #fff !important; }
    .qr-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important; }
}
</style>
@endsection
