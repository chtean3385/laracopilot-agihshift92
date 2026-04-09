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

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:11px;padding:13px 16px;margin-bottom:18px;">
    <ul style="margin:0;padding-left:16px;font-size:13px;color:#b91c1c;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
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
