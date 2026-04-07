@extends('layouts.platform')
@section('title', 'WhatsApp Platform Settings')

@section('content')

<div style="max-width:820px;margin:0 auto;">

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">WhatsApp Platform Settings</h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Configure the platform-level Meta credentials and shared WhatsApp number used by all hotels on basic plan.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        @if($settings->is_saas_active && $settings->saas_token && $settings->saas_phone_number_id)
        <span style="background:#dcfce7;color:#15803d;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-circle" style="font-size:8px;"></i> Shared Number Active
        </span>
        @else
        <span style="background:#fee2e2;color:#b91c1c;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-circle" style="font-size:8px;"></i> Not Configured
        </span>
        @endif
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-times-circle"></i> {{ session('error') }}
</div>
@endif

<form method="POST" action="{{ route('platform.whatsapp.save') }}">
@csrf

{{-- Section 1: Meta App Credentials --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:#1877F2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab fa-meta" style="color:#fff;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Meta App Credentials</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">From Meta Developer Console → Your App → App Settings → Basic</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Meta App ID</label>
            <input type="text" name="meta_app_id" value="{{ old('meta_app_id', $settings->meta_app_id) }}"
                placeholder="e.g. 123456789012345"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">App Settings → Basic → App ID</div>
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Meta App Secret</label>
            <input type="password" name="meta_app_secret" value="{{ old('meta_app_secret', $settings->meta_app_secret) }}"
                placeholder="Keep this secret"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">App Settings → Basic → App Secret (click Show)</div>
        </div>
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Business Login Configuration ID</label>
            <input type="text" name="meta_config_id" value="{{ old('meta_config_id', $settings->meta_config_id) }}"
                placeholder="e.g. 987654321098765"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Your App → Facebook Login for Business → Configurations → Copy the Configuration ID</div>
        </div>
    </div>
</div>

{{-- Section 2: Shared CRM Number --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:linear-gradient(135deg,#25D366,#128C7E);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab fa-whatsapp" style="color:#fff;font-size:20px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Platform Shared Number</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">The CRM's own verified WhatsApp Business number used by hotels on the basic plan</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:18px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">System User Access Token</label>
            <textarea name="saas_token" rows="3"
                placeholder="Permanent System User token (never expires) — from WhatsApp Business Manager → System Users → Generate Token"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:monospace;box-sizing:border-box;resize:vertical;">{{ old('saas_token', $settings->saas_token) }}</textarea>
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">WhatsApp Business Manager → System Users → Create a System User with admin role → Generate Token with whatsapp_business_messaging and whatsapp_business_management permissions</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Phone Number ID</label>
                <input type="text" name="saas_phone_number_id" value="{{ old('saas_phone_number_id', $settings->saas_phone_number_id) }}"
                    placeholder="e.g. 111222333444555"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">WhatsApp Manager → Phone Numbers → Click the number → Phone Number ID</div>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">WhatsApp Business Account ID (WABA)</label>
                <input type="text" name="saas_waba_id" value="{{ old('saas_waba_id', $settings->saas_waba_id) }}"
                    placeholder="e.g. 222333444555666"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">WhatsApp Business Manager → WhatsApp Accounts → The account ID</div>
            </div>
        </div>
    </div>
</div>

{{-- Section 3: Webhook --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:#f3f4f6;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-satellite-dish" style="color:#6b7280;font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Webhook Configuration</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">Used to receive delivery receipts and template approval status from Meta</div>
        </div>
    </div>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:18px;">
        <div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:4px;">Webhook Callback URL (paste this in Meta App → Webhooks)</div>
        <div style="font-family:monospace;font-size:13px;color:#1e293b;word-break:break-all;">{{ url('/webhook/whatsapp') }}</div>
    </div>

    <div>
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Verify Token</label>
        <input type="text" name="webhook_verify_token" value="{{ old('webhook_verify_token', $settings->webhook_verify_token) }}"
            placeholder="Any secret string you choose — must match what you enter in Meta's Webhooks config"
            style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Enter this same string in Meta App → Webhooks → Edit → Verify Token field. Subscribe to: <strong>messages</strong> and <strong>message_template_status_update</strong></div>
    </div>
</div>

{{-- Active toggle --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px 28px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;">
    <div>
        <div style="font-size:15px;font-weight:700;color:#111827;">Activate Shared Number</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px;">When enabled, hotels on basic plan can instantly activate WhatsApp using this number.</div>
    </div>
    <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;">
        <input type="checkbox" name="is_saas_active" value="1" {{ $settings->is_saas_active ? 'checked' : '' }}
            style="opacity:0;width:0;height:0;" id="saasActiveToggle">
        <span id="saasToggleSpan" style="position:absolute;top:0;left:0;right:0;bottom:0;border-radius:28px;transition:.3s;background:{{ $settings->is_saas_active ? '#25D366' : '#d1d5db' }};"></span>
        <span id="saasToggleKnob" style="position:absolute;top:3px;left:{{ $settings->is_saas_active ? '27px' : '3px' }};width:22px;height:22px;border-radius:50%;background:#fff;transition:.3s;"></span>
    </label>
</div>

<div style="display:flex;gap:12px;">
    <button type="submit" style="padding:12px 28px;background:linear-gradient(135deg,#1877F2,#0d65d9);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;">
        <i class="fas fa-save"></i> Save Settings
    </button>
</div>

</form>

{{-- Test section --}}
@if($settings->saas_token && $settings->saas_phone_number_id)
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px 28px;margin-top:20px;">
    <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:4px;">Test Shared Number</div>
    <div style="font-size:13px;color:#6b7280;margin-bottom:16px;">Send a test message to verify the shared number is working.</div>
    <form method="POST" action="{{ route('platform.whatsapp.test') }}" style="display:flex;gap:12px;align-items:flex-end;">
        @csrf
        <div style="flex:1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Phone Number (with country code)</label>
            <input type="text" name="phone" placeholder="e.g. 919876543210"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>
        <button type="submit" style="padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;">
            <i class="fab fa-whatsapp"></i> Send Test
        </button>
    </form>
</div>
@endif

</div>

<script>
const toggle = document.getElementById('saasActiveToggle');
const span   = document.getElementById('saasToggleSpan');
const knob   = document.getElementById('saasToggleKnob');

toggle.addEventListener('change', () => {
    if (toggle.checked) {
        span.style.background = '#25D366';
        knob.style.left = '27px';
    } else {
        span.style.background = '#d1d5db';
        knob.style.left = '3px';
    }
});
</script>

@endsection
