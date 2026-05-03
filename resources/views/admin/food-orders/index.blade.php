@extends('layouts.admin')
@section('title', 'Food Orders')

@section('content')
<div style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-receipt" style="color:#f97316;"></i> Food Orders</h1>
            <p style="color:#64748b;margin:4px 0 0 0;font-size:14px;">{{ $pendingCount }} order(s) waiting for action.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('food-orders.report') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="{{ route('food-menu.dashboard') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-utensils"></i> Menu</a>
        </div>
    </div>

    @if(session('success')) <div style="background:#dcfce7;color:#15803d;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div> @endif
    @if(session('error'))   <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin-bottom:16px;">{{ session('error') }}</div> @endif

    <form method="GET" style="background:#fff;border-radius:14px;padding:14px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);display:grid;grid-template-columns:160px 140px 140px 140px auto;gap:10px;align-items:end;">
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">STATUS</label>
            <select name="status" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
                <option value="">All</option>
                <option value="pending"     {{ $status==='pending'?'selected':'' }}>Pending</option>
                <option value="in_progress" {{ $status==='in_progress'?'selected':'' }}>In Progress</option>
                <option value="approved"    {{ $status==='approved'?'selected':'' }}>Approved</option>
                <option value="cancelled"   {{ $status==='cancelled'?'selected':'' }}>Cancelled</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">ROOM</label>
            <input type="text" name="room" value="{{ $room }}" placeholder="e.g. 101" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">FROM</label>
            <input type="date" name="from" value="{{ $from }}" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">TO</label>
            <input type="date" name="to" value="{{ $to }}" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" style="padding:10px 18px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">Filter</button>
            <a href="{{ route('food-orders.index') }}" style="padding:10px 14px;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;font-weight:700;text-decoration:none;font-size:13px;">Reset</a>
        </div>
    </form>

    <div style="background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Order #</th>
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Room</th>
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Guest</th>
                    <th style="padding:12px;text-align:left;color:#64748b;font-weight:700;">Phone</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Items</th>
                    <th style="padding:12px;text-align:right;color:#64748b;font-weight:700;">Total</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Status</th>
                    <th style="padding:12px;text-align:center;color:#64748b;font-weight:700;">Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px;font-weight:700;color:#1e293b;">{{ $o->order_number }}</td>
                    <td style="padding:12px;"><span style="background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:8px;font-weight:700;font-size:12px;">{{ $o->room_number }}</span></td>
                    <td style="padding:12px;color:#475569;">{{ $o->guest_name ?: '—' }}</td>
                    <td style="padding:12px;color:#475569;">{{ $o->guest_phone ?: '—' }}</td>
                    <td style="padding:12px;text-align:center;color:#64748b;">{{ $o->items->sum('quantity') }}</td>
                    <td style="padding:12px;text-align:right;font-weight:700;">₹ {{ number_format((float)$o->total_amount, 2) }}</td>
                    <td style="padding:12px;text-align:center;"><span style="background:{{ $o->statusColor() }}22;color:{{ $o->statusColor() }};padding:3px 10px;border-radius:8px;font-size:11px;font-weight:700;">{{ $o->statusLabel() }}</span></td>
                    <td style="padding:12px;text-align:center;color:#94a3b8;font-size:12px;">{{ $o->created_at->diffForHumans() }}</td>
                    <td style="padding:12px;text-align:right;"><a href="{{ route('food-orders.show', $o->id) }}" style="color:#f97316;text-decoration:none;font-weight:700;font-size:13px;">View →</a></td>
                </tr>
                @empty
                <tr><td colspan="9" style="padding:50px;text-align:center;color:#94a3b8;"><i class="fas fa-receipt" style="font-size:30px;display:block;margin-bottom:10px;"></i>No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $orders->links() }}</div>
</div>
@endsection
