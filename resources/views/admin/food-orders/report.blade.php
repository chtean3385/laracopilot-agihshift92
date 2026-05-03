@extends('layouts.admin')
@section('title', 'Food Orders Report')

@section('content')
<div style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-chart-bar" style="color:#f97316;"></i> Food Orders Report</h1>
            <p style="color:#64748b;margin:4px 0 0 0;font-size:14px;">{{ $from }} → {{ $to }}</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('food-orders.report', array_merge(request()->query(), ['export'=>1])) }}" style="padding:10px 16px;background:#16a34a;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-download"></i> Export CSV</a>
            <a href="{{ route('food-orders.index') }}" style="padding:10px 16px;background:#fff;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;"><i class="fas fa-arrow-left"></i> Orders</a>
        </div>
    </div>

    <form method="GET" style="background:#fff;border-radius:14px;padding:14px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);display:grid;grid-template-columns:140px 140px 140px 140px auto;gap:10px;align-items:end;">
        <div><label style="font-size:11px;font-weight:700;color:#64748b;">FROM</label><input type="date" name="from" value="{{ $from }}" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-size:11px;font-weight:700;color:#64748b;">TO</label><input type="date" name="to" value="{{ $to }}" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-size:11px;font-weight:700;color:#64748b;">ROOM</label><input type="text" name="room" value="{{ $room }}" placeholder="any" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-size:11px;font-weight:700;color:#64748b;">STATUS</label><select name="status" style="width:100%;padding:9px;border:1.5px solid #e2e8f0;border-radius:8px;">
            <option value="">All</option>
            @foreach(['pending','in_progress','approved','cancelled'] as $s)
            <option value="{{ $s }}" {{ $status===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select></div>
        <button type="submit" style="padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;">Apply</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #f97316;box-shadow:0 2px 8px rgba(0,0,0,.04);"><div style="font-size:12px;color:#64748b;font-weight:700;">TOTAL ORDERS</div><div style="font-size:26px;font-weight:800;color:#1e293b;">{{ $totalOrders }}</div></div>
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #16a34a;box-shadow:0 2px 8px rgba(0,0,0,.04);"><div style="font-size:12px;color:#64748b;font-weight:700;">REVENUE (APPROVED)</div><div style="font-size:26px;font-weight:800;color:#16a34a;">₹ {{ number_format((float)$totalRevenue, 2) }}</div></div>
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #3b82f6;box-shadow:0 2px 8px rgba(0,0,0,.04);"><div style="font-size:12px;color:#64748b;font-weight:700;">AVG ORDER VALUE</div><div style="font-size:26px;font-weight:800;color:#1e293b;">₹ {{ number_format((float)$avgOrder, 2) }}</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;">
        <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <h3 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 12px 0;">Status Breakdown</h3>
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;"><th style="padding:8px;text-align:left;color:#64748b;">Status</th><th style="padding:8px;text-align:right;color:#64748b;">Count</th><th style="padding:8px;text-align:right;color:#64748b;">Total</th></tr></thead>
                <tbody>
                    @forelse($statusBreakdown as $s)
                    <tr style="border-top:1px solid #f1f5f9;"><td style="padding:8px;font-weight:600;text-transform:capitalize;">{{ str_replace('_',' ',$s->status) }}</td><td style="padding:8px;text-align:right;">{{ $s->count }}</td><td style="padding:8px;text-align:right;font-weight:700;">₹ {{ number_format((float)$s->total, 2) }}</td></tr>
                    @empty
                    <tr><td colspan="3" style="padding:20px;text-align:center;color:#94a3b8;">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <h3 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 12px 0;">Revenue by Category</h3>
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;"><th style="padding:8px;text-align:left;color:#64748b;">Category</th><th style="padding:8px;text-align:right;color:#64748b;">Qty</th><th style="padding:8px;text-align:right;color:#64748b;">Total</th></tr></thead>
                <tbody>
                    @forelse($revenueByCategory as $c)
                    <tr style="border-top:1px solid #f1f5f9;"><td style="padding:8px;font-weight:600;">{{ $c->category }}</td><td style="padding:8px;text-align:right;">{{ $c->qty }}</td><td style="padding:8px;text-align:right;font-weight:700;">₹ {{ number_format((float)$c->total, 2) }}</td></tr>
                    @empty
                    <tr><td colspan="3" style="padding:20px;text-align:center;color:#94a3b8;">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;">
        <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <h3 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 12px 0;">Top Items</h3>
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;"><th style="padding:8px;text-align:left;color:#64748b;">Item</th><th style="padding:8px;text-align:right;color:#64748b;">Qty</th><th style="padding:8px;text-align:right;color:#64748b;">Total</th></tr></thead>
                <tbody>
                    @forelse($topItems as $i)
                    <tr style="border-top:1px solid #f1f5f9;"><td style="padding:8px;font-weight:600;">{{ $i->name }}</td><td style="padding:8px;text-align:right;">{{ $i->qty }}</td><td style="padding:8px;text-align:right;font-weight:700;">₹ {{ number_format((float)$i->total, 2) }}</td></tr>
                    @empty
                    <tr><td colspan="3" style="padding:20px;text-align:center;color:#94a3b8;">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <h3 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 12px 0;">Top Rooms (Approved)</h3>
            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;"><th style="padding:8px;text-align:left;color:#64748b;">Room</th><th style="padding:8px;text-align:right;color:#64748b;">Orders</th><th style="padding:8px;text-align:right;color:#64748b;">Total</th></tr></thead>
                <tbody>
                    @forelse($byRoom as $r)
                    <tr style="border-top:1px solid #f1f5f9;"><td style="padding:8px;font-weight:600;">{{ $r->room_number }}</td><td style="padding:8px;text-align:right;">{{ $r->orders }}</td><td style="padding:8px;text-align:right;font-weight:700;">₹ {{ number_format((float)$r->total, 2) }}</td></tr>
                    @empty
                    <tr><td colspan="3" style="padding:20px;text-align:center;color:#94a3b8;">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($cancelled->isNotEmpty())
    <div style="background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
        <h3 style="font-size:15px;font-weight:800;color:#b91c1c;margin:0 0 12px 0;"><i class="fas fa-times-circle"></i> Cancelled Orders ({{ $cancelled->count() }})</h3>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;"><th style="padding:8px;text-align:left;color:#64748b;">Order #</th><th style="padding:8px;text-align:left;color:#64748b;">Room</th><th style="padding:8px;text-align:left;color:#64748b;">Reason</th><th style="padding:8px;text-align:right;color:#64748b;">Total</th><th style="padding:8px;text-align:right;color:#64748b;">When</th></tr></thead>
            <tbody>
                @foreach($cancelled as $c)
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:8px;"><a href="{{ route('food-orders.show', $c->id) }}" style="color:#f97316;text-decoration:none;font-weight:700;">{{ $c->order_number }}</a></td>
                    <td style="padding:8px;">{{ $c->room_number }}</td>
                    <td style="padding:8px;color:#64748b;">{{ $c->cancellation_reason ?: '—' }}</td>
                    <td style="padding:8px;text-align:right;">₹ {{ number_format((float)$c->total_amount, 2) }}</td>
                    <td style="padding:8px;text-align:right;color:#94a3b8;font-size:12px;">{{ $c->created_at->format('d/m H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
