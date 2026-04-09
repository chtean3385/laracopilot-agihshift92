@extends('layouts.platform')
@section('title', 'Push Notification History')

@section('content')

@php
$totalTokens = DB::table('fcm_tokens')->count();
$fcmEnabled  = DB::table('platform_firebase_settings')->value('push_enabled');
$hasVapid    = !empty(DB::table('platform_firebase_settings')->value('firebase_vapid_key'));
$hasSA       = !empty(DB::table('platform_firebase_settings')->value('service_account_json'));
$hasLegacy   = !empty(DB::table('platform_firebase_settings')->value('fcm_server_key'));
@endphp

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

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:12px;padding:13px 18px;margin-bottom:16px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif
@if(session('warning'))
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:13px 18px;margin-bottom:16px;color:#92400e;font-size:14px;font-weight:600;">
    <i class="fas fa-exclamation-triangle" style="margin-right:7px;"></i>{{ session('warning') }}
</div>
@endif

{{-- ── Diagnostic Banner ─────────────────────────────────────────────── --}}
@php
$issues = [];
if (!$fcmEnabled)  $issues[] = ['Push notifications are disabled.', 'Enable them in <a href="' . route('platform.notifications.settings') . '" style="color:#92400e;font-weight:800;">Settings</a>.', 'red'];
if (!$hasSA && !$hasLegacy) $issues[] = ['No send method configured (Service Account or Server Key).', 'Add one in <a href="' . route('platform.notifications.settings') . '" style="color:#92400e;font-weight:800;">Settings</a>.', 'red'];
if (!$hasVapid)    $issues[] = ['VAPID key is missing — hotel browsers cannot register for push.', 'Add it in <a href="' . route('platform.notifications.settings') . '" style="color:#92400e;font-weight:800;">Settings → VAPID Key field</a>.', 'red'];
if ($totalTokens === 0) $issues[] = ['No hotel devices registered.', 'Once the VAPID key is saved, hotel users will be prompted to allow notifications when they log into the hotel CRM.', 'amber'];
@endphp

@if(count($issues))
<div style="background:#fff;border:1px solid #f1f5f9;border-radius:14px;margin-bottom:18px;overflow:hidden;">
    <div style="padding:13px 18px;background:linear-gradient(135deg,#fef2f2,#fff7ed);border-bottom:1px solid #fed7aa;display:flex;align-items:center;gap:9px;">
        <i class="fas fa-stethoscope" style="color:#ef4444;font-size:15px;"></i>
        <span style="font-size:13px;font-weight:800;color:#1e293b;">Configuration Issues Detected — Fix these to deliver notifications</span>
    </div>
    @foreach($issues as [$issue, $fix, $type])
    <div style="padding:12px 18px;border-bottom:1px solid #f8fafc;display:flex;align-items:flex-start;gap:12px;">
        <div style="width:24px;height:24px;background:{{ $type === 'red' ? '#ef4444' : '#f59e0b' }};border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <i class="fas {{ $type === 'red' ? 'fa-times' : 'fa-exclamation' }}" style="color:#fff;font-size:10px;"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $issue }}</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">{!! $fix !!}</div>
        </div>
    </div>
    @endforeach
    @if($totalTokens > 0)
    <div style="padding:11px 18px;background:#f0fdf4;border-top:1px solid #dcfce7;font-size:12px;color:#15803d;font-weight:600;">
        <i class="fas fa-mobile-alt" style="margin-right:6px;"></i>{{ $totalTokens }} device(s) registered and ready to receive.
    </div>
    @endif
</div>
@else
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:12px 18px;margin-bottom:16px;font-size:13px;font-weight:700;color:#15803d;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>All systems configured.
    <span style="font-weight:400;color:#16a34a;margin-left:8px;">{{ $totalTokens }} device(s) registered.</span>
</div>
@endif

{{-- ── Notification List ─────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    @forelse($notifications as $n)
    @php
    $delivered = (int)$n->delivered_count;
    $sent      = (int)($n->token_count ?? $n->sent_count);
    $failed    = (int)($n->sent_count - $n->delivered_count);
    $isOk      = $delivered > 0;
    $hasError  = !$isOk;
    $logText   = $n->send_log ?? null;
    @endphp
    <div style="padding:18px 22px;border-bottom:1px solid #f1f5f9;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">

            {{-- Left: Content --}}
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="display:inline-flex;width:8px;height:8px;border-radius:50%;background:{{ $isOk ? '#10b981' : '#ef4444' }};flex-shrink:0;"></span>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">{{ $n->title }}</div>
                </div>
                <div style="font-size:13px;color:#64748b;line-height:1.5;margin-bottom:8px;">{{ $n->body }}</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <span style="font-size:11px;color:#94a3b8;"><i class="fas fa-bullseye" style="margin-right:3px;"></i>Target: <strong>{{ ucfirst($n->target) }}</strong></span>
                    <span style="font-size:11px;color:#94a3b8;"><i class="fas fa-user" style="margin-right:3px;"></i>{{ $n->sent_by }}</span>
                    <span style="font-size:11px;color:#94a3b8;"><i class="fas fa-clock" style="margin-right:3px;"></i>{{ $n->sent_at ? \Carbon\Carbon::parse($n->sent_at)->diffForHumans() : '–' }}</span>
                    @if($n->action_url)
                    <a href="{{ $n->action_url }}" target="_blank" style="font-size:11px;color:#7c3aed;"><i class="fas fa-link" style="margin-right:3px;"></i>{{ Str::limit($n->action_url, 40) }}</a>
                    @endif
                </div>
            </div>

            {{-- Right: Stats --}}
            <div style="text-align:right;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin-bottom:4px;">
                    @if($isOk)
                    <span style="padding:3px 10px;background:#dcfce7;color:#15803d;border-radius:20px;font-size:11px;font-weight:700;"><i class="fas fa-check" style="margin-right:3px;"></i>Delivered</span>
                    @else
                    <span style="padding:3px 10px;background:#fee2e2;color:#b91c1c;border-radius:20px;font-size:11px;font-weight:700;"><i class="fas fa-times" style="margin-right:3px;"></i>0 Delivered</span>
                    @endif
                </div>
                <div style="font-size:11px;color:#94a3b8;">
                    <span style="color:{{ $isOk ? '#10b981' : '#94a3b8' }};font-weight:700;font-size:18px;">{{ $delivered }}</span>
                    / {{ $sent }} tokens
                    @if($failed > 0) <span style="color:#ef4444;">· {{ $failed }} failed</span> @endif
                </div>
            </div>
        </div>

        {{-- Log / Error row --}}
        @if($logText)
        <div style="margin-top:10px;padding:10px 14px;background:{{ $isOk ? '#f8fafc' : '#fef2f2' }};border-radius:9px;border-left:3px solid {{ $isOk ? '#94a3b8' : '#ef4444' }};display:flex;align-items:flex-start;gap:8px;">
            <i class="fas {{ $isOk ? 'fa-info-circle' : 'fa-exclamation-circle' }}" style="color:{{ $isOk ? '#94a3b8' : '#ef4444' }};font-size:12px;margin-top:2px;flex-shrink:0;"></i>
            <div style="font-size:12px;color:{{ $isOk ? '#64748b' : '#b91c1c' }};line-height:1.5;">{{ $logText }}</div>
        </div>
        @elseif($hasError)
        <div style="margin-top:10px;padding:10px 14px;background:#fef2f2;border-radius:9px;border-left:3px solid #ef4444;font-size:12px;color:#b91c1c;">
            <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>No delivery log saved — this notification was sent before logging was added.
        </div>
        @endif
    </div>
    @empty
    <div style="padding:60px;text-align:center;color:#94a3b8;">
        <i class="fas fa-bell-slash" style="font-size:36px;margin-bottom:14px;display:block;"></i>
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:6px;">No push notifications sent yet</div>
        <div style="font-size:13px;margin-bottom:16px;">Fix the configuration issues above, then send your first notification.</div>
        <a href="{{ route('platform.notifications.send') }}" style="padding:10px 22px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-block;">
            <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send First Notification
        </a>
    </div>
    @endforelse

</div>

@if($notifications->hasPages())
<div style="margin-top:16px;">{{ $notifications->links() }}</div>
@endif

@endsection
