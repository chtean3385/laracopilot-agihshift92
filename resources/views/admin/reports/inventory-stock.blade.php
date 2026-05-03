@extends('layouts.admin')
@section('title','Inventory Stock Report')
@section('page-title','Inventory Stock Report')
@section('page-subtitle','Current stock levels, low-stock alerts and total inventory value')
@section('content')

<div style="margin-bottom:14px;">
    <a href="{{ route('reports.index') }}" style="color:#2563eb;text-decoration:none;font-size:13px;">← Back to Reports</a>
</div>

<form method="GET" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
    <div>
        <label style="display:block;font-size:12px;color:#475569;font-weight:600;margin-bottom:4px;">Category</label>
        <select name="category_id" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:13px;min-width:180px;">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#1e293b;font-weight:600;cursor:pointer;margin-top:24px;">
            <input type="checkbox" name="low_only" value="1" {{ $onlyLow ? 'checked' : '' }}> Low-stock only
        </label>
    </div>
    <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
    <a href="{{ route('reports.inventory_stock') }}" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;">Reset</a>
    <button type="button" onclick="window.print()" style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;margin-left:auto;"><i class="fas fa-print"></i> Print</button>
</form>

{{-- KPI cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:20px;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Items</div>
        <div style="font-size:26px;font-weight:800;color:#1e293b;margin-top:4px;">{{ $totals['count'] }}</div>
    </div>
    <div style="background:#fff;border:1px solid #fed7aa;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#c2410c;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Low Stock</div>
        <div style="font-size:26px;font-weight:800;color:#ea580c;margin-top:4px;">{{ $totals['low_count'] }}</div>
    </div>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total Quantity</div>
        <div style="font-size:26px;font-weight:800;color:#1e293b;margin-top:4px;">{{ number_format($totals['total_qty'], 2) }}</div>
    </div>
    <div style="background:#fff;border:1px solid #bbf7d0;border-radius:12px;padding:16px;">
        <div style="font-size:12px;color:#15803d;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total Stock Value</div>
        <div style="font-size:26px;font-weight:800;color:#15803d;margin-top:4px;">₹{{ number_format($totals['total_value'], 2) }}</div>
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead style="background:#f8fafc;">
            <tr>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Item</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Category</th>
                <th style="text-align:right;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Stock</th>
                <th style="text-align:left;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Unit</th>
                <th style="text-align:right;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Reorder</th>
                <th style="text-align:right;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Cost</th>
                <th style="text-align:right;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Value</th>
                <th style="text-align:center;padding:10px 14px;color:#475569;font-weight:700;border-bottom:1px solid #e5e7eb;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $it)
            @php $low = $it->isLowStock(); $value = (float)$it->current_stock * (float)$it->cost_price; @endphp
            <tr style="border-bottom:1px solid #f1f5f9;{{ $low ? 'background:#fff7ed;' : '' }}">
                <td style="padding:10px 14px;color:#1e293b;font-weight:600;">{{ $it->name }}</td>
                <td style="padding:10px 14px;color:#64748b;">{{ $it->category->name ?? '—' }}</td>
                <td style="padding:10px 14px;text-align:right;color:#1e293b;font-weight:700;">{{ number_format((float)$it->current_stock, 2) }}</td>
                <td style="padding:10px 14px;color:#64748b;">{{ $it->unit }}</td>
                <td style="padding:10px 14px;text-align:right;color:#64748b;">{{ number_format((float)$it->reorder_level, 2) }}</td>
                <td style="padding:10px 14px;text-align:right;color:#64748b;">₹{{ number_format((float)$it->cost_price, 2) }}</td>
                <td style="padding:10px 14px;text-align:right;color:#1e293b;font-weight:700;">₹{{ number_format($value, 2) }}</td>
                <td style="padding:10px 14px;text-align:center;">
                    @if($low)
                        <span style="background:#fed7aa;color:#9a3412;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;">LOW</span>
                    @else
                        <span style="background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;">OK</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:30px;text-align:center;color:#94a3b8;">No inventory items found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>@media print{.no-print,.sidebar,.topbar,header,nav,form{display:none!important;}body,html{background:#fff!important;}}</style>
@endsection
