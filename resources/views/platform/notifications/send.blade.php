@extends('layouts.platform')
@section('title', 'Send Push Notification')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="{{ route('platform.notifications.settings') }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;color:#374151;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
        <i class="fas fa-cog"></i> Settings
    </a>
    <a href="{{ route('platform.notifications.history') }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;color:#374151;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;">
        <i class="fas fa-history"></i> History
    </a>
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 3px;">
            <i class="fas fa-paper-plane" style="color:#7c3aed;margin-right:8px;"></i>Send Push Notification
        </h1>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif
@if(session('warning'))
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#92400e;font-size:14px;font-weight:600;">
    <i class="fas fa-exclamation-triangle" style="margin-right:7px;"></i>{{ session('warning') }}
</div>
@endif

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:11px;padding:13px 16px;margin-bottom:18px;">
    <ul style="margin:0;padding-left:16px;font-size:13px;color:#b91c1c;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@php
$totalTokens = DB::table('fcm_tokens')->count();
$cfg = DB::table('platform_firebase_settings')->first();
$hasVapid  = !empty($cfg?->firebase_vapid_key);
$hasSA     = !empty($cfg?->service_account_json);
$hasLegacy = !empty($cfg?->fcm_server_key);
@endphp

@if($totalTokens === 0 || !$hasVapid || (!$hasSA && !$hasLegacy))
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:13px;padding:16px 18px;margin-bottom:18px;">
    <div style="font-size:13px;font-weight:800;color:#b91c1c;margin-bottom:10px;"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>Cannot deliver — fix these issues first:</div>
    <div style="display:flex;flex-direction:column;gap:7px;">
        @if(!$hasVapid)
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#7f1d1d;">
            <span style="background:#ef4444;color:#fff;padding:2px 7px;border-radius:4px;font-weight:700;font-size:10px;">MISSING</span>
            <strong>VAPID Key not saved</strong> — hotel browsers can't register for push without it.
            <a href="{{ route('platform.notifications.settings') }}" style="color:#7c3aed;font-weight:700;margin-left:4px;">→ Add it in Settings</a>
        </div>
        @endif
        @if(!$hasSA && !$hasLegacy)
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#7f1d1d;">
            <span style="background:#ef4444;color:#fff;padding:2px 7px;border-radius:4px;font-weight:700;font-size:10px;">MISSING</span>
            <strong>No send method</strong> — add Service Account JSON (recommended) or Server Key.
            <a href="{{ route('platform.notifications.settings') }}" style="color:#7c3aed;font-weight:700;margin-left:4px;">→ Settings</a>
        </div>
        @endif
        @if($totalTokens === 0)
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#92400e;">
            <span style="background:#f59e0b;color:#fff;padding:2px 7px;border-radius:4px;font-weight:700;font-size:10px;">0 DEVICES</span>
            <strong>No FCM tokens registered.</strong> Save the VAPID key, then a hotel user needs to log in and grant notification permission in their browser.
        </div>
        @endif
    </div>
</div>
@else
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:11px;padding:11px 16px;margin-bottom:18px;font-size:13px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:8px;">
    <i class="fas fa-check-circle"></i>
    <span>Ready to send — <strong>{{ $totalTokens }}</strong> device(s) registered.</span>
    @if($hasSA)<span style="background:#7c3aed;color:#fff;padding:2px 9px;border-radius:12px;font-size:10px;font-weight:700;margin-left:6px;">FCM v1 API</span>@elseif($hasLegacy)<span style="background:#f59e0b;color:#fff;padding:2px 9px;border-radius:12px;font-size:10px;font-weight:700;margin-left:6px;">Legacy API</span>@endif
</div>
@endif

<div style="max-width:640px;">
<div style="background:#fff;border-radius:16px;padding:24px 26px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;">

    <form action="{{ route('platform.notifications.send.post') }}" method="POST">
        @csrf

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Title</label>
            <input type="text" name="title" value="{{ old('title') }}" maxlength="200"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;"
                placeholder="Notification title">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Message</label>
            <textarea name="body" rows="4" maxlength="1000"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;resize:vertical;box-sizing:border-box;"
                placeholder="Notification body text">{{ old('body') }}</textarea>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Action URL <span style="color:#94a3b8;">(optional)</span></label>
            <input type="url" name="action_url" value="{{ old('action_url') }}"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;"
                placeholder="https://...">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">Target Audience</label>
            <div style="display:flex;gap:10px;margin-bottom:12px;">
                @foreach([['all','All Hotels'],['hotel','Specific Hotels'],['plan','By Plan']] as [$val,$lbl])
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;padding:8px 14px;border:2px solid #e2e8f0;border-radius:9px;"
                    onclick="document.querySelectorAll('.target-radio').forEach(el=>el.parentElement.style.borderColor='#e2e8f0');this.style.borderColor='#7c3aed'">
                    <input type="radio" name="target" value="{{ $val }}" class="target-radio" {{ old('target','all') === $val ? 'checked' : '' }}
                        style="accent-color:#7c3aed;" onchange="toggleTargetPicker('{{ $val }}')">
                    {{ $lbl }}
                </label>
                @endforeach
            </div>

            <div id="picker-hotel" style="{{ old('target') === 'hotel' ? '' : 'display:none;' }}max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:9px;padding:8px;">
                @foreach($hotels as $hotel)
                <label style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:7px;cursor:pointer;font-size:13px;color:#374151;"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" name="target_ids[]" value="{{ $hotel->id }}" style="accent-color:#7c3aed;">
                    {{ $hotel->name }}
                    <span style="font-size:11px;color:#94a3b8;margin-left:auto;">{{ strtoupper($hotel->plan) }}</span>
                </label>
                @endforeach
            </div>

            <div id="picker-plan" style="{{ old('target') === 'plan' ? '' : 'display:none;' }}display:flex;gap:8px;flex-wrap:wrap;padding:10px 0;">
                @foreach($plans as $plan)
                <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;cursor:pointer;background:#f8fafc;">
                    <input type="checkbox" name="target_ids[]" value="{{ $plan }}" style="accent-color:#7c3aed;">
                    {{ ucfirst($plan) }}
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit" style="width:100%;padding:11px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-paper-plane"></i> Send Now
        </button>
    </form>
</div>
</div>

<script>
function toggleTargetPicker(target) {
    document.getElementById('picker-hotel').style.display = (target === 'hotel') ? 'block' : 'none';
    document.getElementById('picker-plan').style.display  = (target === 'plan')  ? 'flex'  : 'none';
}
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('.target-radio:checked');
    if (checked) {
        checked.closest('label').style.borderColor = '#7c3aed';
        toggleTargetPicker(checked.value);
    }
});
</script>

@endsection
