@extends('layouts.admin')
@section('title','Inventory Movements Report')
@section('page-title','Inventory Movements Report')
@section('page-subtitle','Stock-in, usage and adjustments over a date range')
@section('content')

<div style="margin-bottom:14px;">
    <a href="{{ route('reports.index') }}" style="color:#2563eb;text-decoration:none;font-size:13px;">← Back to Reports</a>
</div>

<form method="GET" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
    <div>
        <label style="display:block;font-size:12px;color:#475569;font-weight:600;margin-bottom:4px;">From</label>
        <input type="date" name="date_from" value="{{ $from->toDateString() }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:13px;">
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#475569;font-weight:600;margin-bottom:4px;">To</label>
        <input type="date" name="date_to" value="{{ $to->toDateString() }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:13px;">
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#475569;font-weight:600;margin-bottom:4px;">Type</label>
        <select name="type" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:13px;">
            <option value="">All types</option>
            <option value="in"     {{ $type==='in'?'selected':'' }}>Stock In</option>
            <option value="out"    {{ $type==='out'?'selected':'' }}>Usage / Out</option>
            <option value="adjust" {{ $type==='adjust'?'selected':'' }}>Adjustment</option>
        </select>
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#475569;font-weight:600;margin-bottom:4px;">Item</label>
        <select name="item_id" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:13px;min-width:180px;">
            <option value="">All items</option>
            @foreach($items as $it)
                <option value="{{ $it->id }}" {{ (string)$itemId===(string)$it->id?'selected':'' }}>{{ $it->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
    <a href="{{ route('reports.inventory_movements') }}" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;">Reset</a>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="{{ route('reports.inventory_movements', array_merge(request()->only('date_from','date_to','type','item_id'), ['export'=>'pdf'])) }}"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('reports.inventory_movements', array_merge(request()->only('date_from','date_to','type','item_id'), ['export'=>'csv'])) }}"
           style="background:#16a34a;color:#fff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="fas fa-file-csv"></i> CSV
        </a>
    </div>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:20px;">
    <div style="background:#fff;border:1px solid #bbf7d0;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#15803d;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Stock In</div>
        <div style="font-size:24px;font-weight:800;color:#15803d;margin-top:4px;">+{{ number_format($totals['in'], 2) }}</div>
    </div>
    <div style="background:#fff;border:1px solid #fecaca;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#b91c1c;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Usage / Out</div>
        <div style="font-size:24px;font-weight:800;color:#b91c1c;margin-top:4px;">-{{ number_format($totals['out'], 2) }}</div>
    </div>
    <div style="background:#fff;border:1px solid #ddd6fe;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#6d28d9;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Adjustments</div>
        <div style="font-size:24px;font-weight:800;color:#6d28d9;margin-top:4px;">{{ number_format($totals['adjust'], 2) }}</div>
    </div>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total Movements</div>
        <div style="font-size:24px;font-weight:800;color:#1e293b;margin-top:4px;">{{ $totals['count'] }}</div>
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead style="background:#f8fafc;">
            <tr>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Date</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Item</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Category</th>
                <th style="text-align:center;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Type</th>
                <th style="text-align:right;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Qty</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">By</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $m)
            @php
                $color = match($m->type){'in'=>'#15803d','out'=>'#b91c1c','adjust'=>'#6d28d9',default=>'#475569'};
                $bg    = match($m->type){'in'=>'#dcfce7','out'=>'#fee2e2','adjust'=>'#ede9fe',default=>'#f1f5f9'};
                $sign  = $m->type==='in' ? '+' : ($m->type==='out' ? '-' : '');
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 14px;color:#64748b;white-space:nowrap;">{{ \Carbon\Carbon::parse($m->created_at)->format('d M Y, H:i') }}</td>
                <td style="padding:10px 14px;color:#1e293b;font-weight:600;">{{ $m->item->name ?? '—' }}</td>
                <td style="padding:10px 14px;color:#64748b;">{{ $m->item->category->name ?? '—' }}</td>
                <td style="padding:10px 14px;text-align:center;">
                    <span style="background:{{ $bg }};color:{{ $color }};padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;">{{ $m->type }}</span>
                </td>
                <td style="padding:10px 14px;text-align:right;color:{{ $color }};font-weight:700;">{{ $sign }}{{ number_format((float)$m->quantity, 2) }} {{ $m->item->unit ?? '' }}</td>
                <td style="padding:10px 14px;color:#64748b;">{{ $m->creator->name ?? '—' }}</td>
                <td style="padding:10px 14px;color:#475569;">{{ $m->notes ?: '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="padding:30px;text-align:center;color:#94a3b8;">No movements in the selected range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>@media print{.no-print,.sidebar,.topbar,header,nav,form{display:none!important;}body,html{background:#fff!important;}}</style>
@endsection
