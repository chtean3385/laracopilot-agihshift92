@extends('layouts.platform')
@section('title', 'Push Notification History')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fas fa-bell" style="color:#7c3aed;margin-right:8px;"></i>Push Notification History
        </h1>
        <p style="color:#6b7280;font-size:13px;margin:0;">All push notifications sent via the platform.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('platform.notifications.settings') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f1f5f9;color:#374151;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="{{ route('platform.notifications.send') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <i class="fas fa-paper-plane"></i> Send New
        </a>
    </div>
</div>

<div style="background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    @if(session('success'))
    <div style="background:#dcfce7;border-bottom:1px solid #86efac;padding:13px 20px;color:#15803d;font-size:14px;font-weight:600;">
        <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
    </div>
    @endif

    @forelse($notifications as $n)
    <div style="padding:18px 22px;border-bottom:1px solid #f1f5f9;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
            <div style="flex:1;">
                <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:4px;">{{ $n->title }}</div>
                <div style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:8px;">{{ $n->body }}</div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#94a3b8;"><i class="fas fa-bullseye" style="margin-right:4px;"></i>Target: <strong>{{ ucfirst($n->target) }}</strong></span>
                    <span style="font-size:11px;color:#94a3b8;"><i class="fas fa-user" style="margin-right:4px;"></i>By: {{ $n->sent_by }}</span>
                    @if($n->action_url)
                    <a href="{{ $n->action_url }}" target="_blank" style="font-size:11px;color:#7c3aed;"><i class="fas fa-link" style="margin-right:4px;"></i>{{ Str::limit($n->action_url, 50) }}</a>
                    @endif
                </div>
            </div>
            <div style="text-align:right;min-width:120px;">
                <div style="margin-bottom:6px;">
                    <span style="font-size:20px;font-weight:900;color:#7c3aed;">{{ number_format($n->delivered_count) }}</span>
                    <span style="font-size:11px;color:#94a3b8;"> / {{ number_format($n->sent_count) }} delivered</span>
                </div>
                <div style="font-size:11px;color:#94a3b8;">{{ $n->sent_at ? \Carbon\Carbon::parse($n->sent_at)->diffForHumans() : 'Pending' }}</div>
            </div>
        </div>
    </div>
    @empty
    <div style="padding:50px;text-align:center;color:#94a3b8;">
        <i class="fas fa-bell-slash" style="font-size:32px;margin-bottom:12px;display:block;"></i>
        No push notifications sent yet.
        <div style="margin-top:12px;"><a href="{{ route('platform.notifications.send') }}" style="color:#7c3aed;font-weight:700;">Send your first one →</a></div>
    </div>
    @endforelse
</div>

@if($notifications->hasPages())
<div style="margin-top:16px;">{{ $notifications->links() }}</div>
@endif

@endsection
