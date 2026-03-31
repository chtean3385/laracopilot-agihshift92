@extends('layouts.platform')

@section('title', 'Plans — Platform Admin')
@section('page-title', 'Subscription Plans')
@section('page-subtitle', 'Manage DB-driven plans — prices and limits applied to all new hotels')

@section('content')

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
    @forelse($plans as $plan)
    @php
        $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
        $isUnlimited = $plan->max_rooms >= 9999;
    @endphp
    <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1.5px solid {{ $plan->is_active ? $plan->color : '#e2e8f0' }};position:relative;overflow:hidden;">

        {{-- Status badge --}}
        @if(!$plan->is_active)
        <div style="position:absolute;top:14px;right:14px;">
            <span class="badge-gray">Inactive</span>
        </div>
        @else
        <div style="position:absolute;top:14px;right:14px;">
            <span class="badge-green">Active</span>
        </div>
        @endif

        {{-- Color accent bar --}}
        <div style="position:absolute;top:0;left:0;right:0;height:4px;background:{{ $plan->color }};border-radius:20px 20px 0 0;"></div>

        <div style="margin-top:8px;">
            <div style="font-size:20px;font-weight:900;color:{{ $plan->color }};letter-spacing:-.5px;">{{ $plan->label }}</div>
            <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-top:2px;text-transform:uppercase;letter-spacing:.08em;">{{ $plan->slug }}</div>
        </div>

        <div style="margin:16px 0;padding:12px;background:#f8fafc;border-radius:12px;">
            <div style="font-size:22px;font-weight:900;color:#1e293b;">Rs {{ number_format($plan->monthly_price) }}<span style="font-size:13px;font-weight:500;color:#94a3b8;">/mo</span></div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Rs {{ number_format($plan->yearly_price) }} / year</div>
        </div>

        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <span style="background:#f1f5f9;color:#475569;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                <i class="fas fa-door-open" style="color:#8b5cf6;margin-right:3px;"></i>
                {{ $isUnlimited ? '∞' : number_format($plan->max_rooms) }} rooms
            </span>
            <span style="background:#f1f5f9;color:#475569;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                <i class="fas fa-users" style="color:#8b5cf6;margin-right:3px;"></i>
                {{ $plan->max_users >= 9999 ? '∞' : number_format($plan->max_users) }} users
            </span>
        </div>

        @if(!empty($features))
        <ul style="margin:0 0 20px;padding:0;list-style:none;">
            @foreach(array_slice($features, 0, 4) as $feat)
            <li style="font-size:12px;color:#475569;padding:3px 0;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-check" style="color:{{ $plan->color }};font-size:10px;flex-shrink:0;"></i>
                {{ $feat }}
            </li>
            @endforeach
            @if(count($features) > 4)
            <li style="font-size:11px;color:#94a3b8;padding:3px 0;">+{{ count($features) - 4 }} more features...</li>
            @endif
        </ul>
        @endif

        <a href="{{ route('platform.plans.edit', $plan->id) }}" class="btn-primary" style="width:100%;justify-content:center;padding:10px;">
            <i class="fas fa-edit"></i> Edit Plan
        </a>
    </div>
    @empty
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;">
        <i class="fas fa-layer-group" style="font-size:40px;margin-bottom:16px;display:block;"></i>
        <p style="font-size:15px;font-weight:600;">No plans found in the database.</p>
        <p style="font-size:13px;">Run the seeder to add the default plans.</p>
    </div>
    @endforelse
</div>

@endsection
