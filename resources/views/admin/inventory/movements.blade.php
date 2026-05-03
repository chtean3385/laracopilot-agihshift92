@extends('layouts.admin')

@section('title', 'Stock Movements — ' . $item->name)

@section('content')
<style>
    .page-header{margin-bottom:22px;}
    .page-title{font-size:1.2rem;font-weight:800;color:#1e293b;margin:0 0 4px;}
    .page-sub{font-size:.84rem;color:#64748b;}
    .stock-hero{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:22px;}
    .stock-stat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;flex:1;min-width:140px;}
    .stock-stat-num{font-size:1.6rem;font-weight:900;color:#1e293b;line-height:1;}
    .stock-stat-label{font-size:.75rem;font-weight:600;color:#94a3b8;margin-top:4px;}
    .table-wrap{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    thead th{background:#f8fafc;padding:11px 14px;text-align:left;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;}
    tbody td{padding:11px 14px;border-bottom:1px solid #f1f5f9;font-size:.84rem;color:#374151;vertical-align:middle;}
    tbody tr:last-child td{border-bottom:none;}
    tbody tr:hover{background:#f8fafc;}
    .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;}
    .badge-purchase{background:#dcfce7;color:#16a34a;}
    .badge-usage{background:#fff7ed;color:#ea580c;}
    .badge-wastage{background:#fee2e2;color:#dc2626;}
    .badge-adjustment{background:#eff6ff;color:#2563eb;}
    .empty-state{text-align:center;padding:60px 20px;color:#94a3b8;}
    .empty-state i{font-size:3rem;margin-bottom:14px;display:block;opacity:.4;}
</style>

<div style="margin-bottom:18px;">
    <a href="{{ route('inventory.index') }}" style="color:#64748b;font-size:.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
</div>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-history" style="color:#0369a1;margin-right:8px;"></i>{{ $item->name }}</h1>
    <div class="page-sub">Stock movement history · Unit: {{ $item->unit }}</div>
</div>

<div class="stock-hero">
    <div class="stock-stat">
        <div class="stock-stat-num" style="{{ $item->isLowStock() ? 'color:#dc2626;' : 'color:#16a34a;' }}">{{ number_format($item->current_stock, 2) }}</div>
        <div class="stock-stat-label">Current Stock ({{ $item->unit }})</div>
    </div>
    <div class="stock-stat">
        <div class="stock-stat-num">{{ $item->reorder_level > 0 ? number_format($item->reorder_level, 2) : '—' }}</div>
        <div class="stock-stat-label">Reorder Level</div>
    </div>
    <div class="stock-stat">
        <div class="stock-stat-num">{{ $movements->count() }}</div>
        <div class="stock-stat-label">Total Movements</div>
    </div>
    <div class="stock-stat">
        <div class="stock-stat-num">{{ $item->category ? $item->category->name : '—' }}</div>
        <div class="stock-stat-label">Category</div>
    </div>
</div>

<div class="table-wrap">
    @if($movements->isEmpty())
    <div class="empty-state">
        <i class="fas fa-history"></i>
        <div style="font-size:1rem;font-weight:700;color:#475569;margin-bottom:6px;">No movements yet</div>
        <div style="font-size:.85rem;">Record a purchase or usage from the inventory list.</div>
    </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Notes</th>
                <th>Reference</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $mv)
            <tr>
                <td style="color:#64748b;white-space:nowrap;">{{ $mv->created_at->format('d M Y, h:i A') }}</td>
                <td>
                    @php
                        $badgeClass = match($mv->type) {
                            'purchase'   => 'badge-purchase',
                            'usage'      => 'badge-usage',
                            'wastage'    => 'badge-wastage',
                            'adjustment' => 'badge-adjustment',
                            default      => '',
                        };
                        $icon = match($mv->type) {
                            'purchase'   => 'fa-plus-circle',
                            'usage'      => 'fa-minus-circle',
                            'wastage'    => 'fa-trash-alt',
                            'adjustment' => 'fa-sliders-h',
                            default      => 'fa-circle',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        <i class="fas {{ $icon }}"></i>
                        {{ ucfirst($mv->type) }}
                    </span>
                </td>
                <td style="font-weight:700;">
                    @if(in_array($mv->type, ['purchase', 'adjustment']))
                        <span style="color:#16a34a;">+{{ number_format($mv->quantity, 2) }}</span>
                    @elseif(in_array($mv->type, ['usage', 'wastage']))
                        <span style="color:#dc2626;">−{{ number_format($mv->quantity, 2) }}</span>
                    @endif
                    <span style="color:#94a3b8;font-size:.8rem;font-weight:400;"> {{ $item->unit }}</span>
                </td>
                <td style="color:#475569;max-width:200px;">{{ $mv->notes ?: '—' }}</td>
                <td style="color:#64748b;font-size:.8rem;">
                    @if($mv->reference_type)
                    {{ ucfirst(str_replace('_', ' ', $mv->reference_type)) }} #{{ $mv->reference_id }}
                    @else
                    —
                    @endif
                </td>
                <td style="color:#64748b;">{{ $mv->creator?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
