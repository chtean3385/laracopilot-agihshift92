@extends('layouts.admin')

@section('title', 'Restaurant Bills')

@section('content')
{{-- ═════ MOBILE-FIRST POS HEADER ═════ --}}
<div style="position:sticky;top:0;z-index:30;background:#fff;border-bottom:1px solid #f1f5f9;box-shadow:0 1px 6px rgba(0,0,0,.04);">
    <div style="padding:10px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#475569;text-decoration:none;">
            <i class="fas fa-arrow-left" style="font-size:10px;"></i> Dashboard
        </a>
        <a href="{{ route('restaurant.index') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;font-weight:700;color:#991b1b;text-decoration:none;">
            <i class="fas fa-chair" style="font-size:10px;"></i> Tables
        </a>
        <div style="flex:1;min-width:0;text-align:right;">
            <h1 style="font-size:17px;font-weight:900;color:#0f172a;margin:0;">🧾 Bills</h1>
            <p style="font-size:11px;color:#94a3b8;margin:0;">{{ $bills->total() ?? 0 }} settled bills</p>
        </div>
    </div>
</div>

<div style="padding:14px 16px 100px;">

@if(session('success'))
    <div class="alert-success mb-4">{{ session('success') }}</div>
@endif

<style>
.bc-card{ background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:10px;position:relative; }
.bc-top{ display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px; }
.bc-badges{ display:flex;gap:4px;flex-wrap:wrap; }
.bc-badge{ font-size:10px;font-weight:800;padding:3px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px; }
.bc-meta{ display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#64748b;margin-bottom:10px; }
.bc-total{ font-size:18px;font-weight:900;color:#0f172a; }
@media(max-width:480px){
    .bc-card{ padding:12px; }
    .bc-total{ font-size:16px; }
}
</style>

    @if($bills->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
        <div style="font-size:48px;margin-bottom:12px;">🧾</div>
        <p style="font-size:15px;font-weight:700;color:#475569;">No bills yet</p>
        <p style="font-size:12px;margin-top:4px;">Bills appear after orders are settled</p>
    </div>
    @else
    @foreach($bills as $bill)
    <div class="bc-card">
        <div class="bc-top">
            <div>
                <div style="font-family:'SF Mono',Menlo,monospace;font-size:15px;font-weight:900;color:#0f172a;">{{ $bill->bill_number }}</div>
                <div class="bc-meta">
                    <span><i class="fas fa-clock" style="font-size:10px;color:#94a3b8;"></i> {{ $bill->created_at->format('d M, h:i A') }}</span>
                    @if($bill->order)
                    <span><i class="fas fa-hashtag" style="font-size:10px;color:#94a3b8;"></i> {{ $bill->order->order_number }}</span>
                    @endif
                    @if($bill->order && $bill->order->table)
                    <span><i class="fas fa-chair" style="font-size:10px;color:#94a3b8;"></i> {{ $bill->order->table->name }}</span>
                    @endif
                </div>
            </div>
            <div class="bc-total">₹{{ number_format($bill->total, 0) }}</div>
        </div>
        <div class="bc-badges">
            @if($bill->bill_type === 'room')
                <span class="bc-badge" style="background:#dbeafe;color:#1e40af;">🛏️ Room</span>
            @else
                <span class="bc-badge" style="background:#dcfce7;color:#15803d;">💵 Direct</span>
            @endif
            <span class="bc-badge" style="background:#f1f5f9;color:#475569;">
                {{ strtoupper($bill->payment_method ?? '—') }}
            </span>
        </div>
        <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #e2e8f0;display:flex;justify-content:flex-end;gap:8px;">
            <a href="{{ route('restaurant.bills.print', $bill->id) }}" target="_blank"
                style="padding:7px 12px;background:#0f172a;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                <i class="fas fa-print" style="font-size:10px;"></i> Print
            </a>
        </div>
    </div>
    @endforeach

    <div style="margin-top:16px;">
        {{ $bills->links() }}
    </div>
    @endif

</div>{{-- /padding wrapper --}}
@endsection