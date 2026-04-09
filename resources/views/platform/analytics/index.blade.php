@extends('layouts.platform')
@section('title', 'SaaS Analytics')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fas fa-chart-line" style="color:#7c3aed;margin-right:8px;"></i>SaaS Analytics Dashboard
        </h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Real-time view of all hotels, engagement, revenue and growth.</p>
    </div>
    <a href="{{ route('platform.analytics.campaigns') }}"
        style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;">
        <i class="fas fa-bullhorn"></i> Send Campaign
    </a>
</div>

{{-- ApexCharts CDN --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js" defer></script>

@livewire('platform.analytics-dashboard')

@endsection
