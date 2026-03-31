@extends('layouts.platform')

@section('title', 'Platform Dashboard')
@section('page-title', 'Platform Overview')
@section('page-subtitle', 'SaaS management dashboard — all hotels at a glance')

@section('content')
<div style="display:flex;align-items:center;justify-content:center;min-height:320px;flex-direction:column;gap:16px;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#7c3aed,#4c1d95);border-radius:20px;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-layer-group" style="color:#fff;font-size:28px;"></i>
    </div>
    <div style="text-align:center;">
        <div style="font-size:20px;font-weight:800;color:#1e293b;margin-bottom:6px;">Platform Admin Ready</div>
        <div style="font-size:14px;color:#64748b;">Dashboard stats are coming in the next task.</div>
    </div>
</div>
@endsection
