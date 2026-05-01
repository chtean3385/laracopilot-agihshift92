@extends('layouts.platform')
@section('title', 'SaaS Analytics')

@section('content')

{{-- ApexCharts loaded from platform layout (/js/apexcharts.min.js) --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#0f172a;margin:0 0 4px;display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:10px;box-shadow:0 3px 10px rgba(124,58,237,.3);">
                <i class="fas fa-chart-line" style="color:#fff;font-size:15px;"></i>
            </span>
            SaaS Analytics
        </h1>
        <p style="color:#64748b;font-size:13px;margin:0;margin-left:46px;">Real-time · Predictive · Actionable insights for your hotel network</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('platform.notifications.send') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#fff;color:#374151;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <i class="fas fa-bell" style="color:#7c3aed;"></i> Push Notif
        </a>
        <a href="{{ route('platform.analytics.campaigns') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(124,58,237,.3);">
            <i class="fas fa-bullhorn"></i> Send Campaign
        </a>
    </div>
</div>

@livewire('platform.analytics-dashboard')

@endsection
