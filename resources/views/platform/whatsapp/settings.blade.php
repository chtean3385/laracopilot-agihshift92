@extends('layouts.platform')
@section('title', 'WhatsApp Platform Settings')

@section('content')

<div style="max-width:820px;margin:0 auto;">

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">WhatsApp Platform Settings</h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Configure Meta credentials and the shared number for the CRM platform.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('platform.whatsapp.numbers') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#e0f2fe;color:#0369a1;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #bae6fd;">
            <i class="fas fa-sim-card"></i> Hotel Numbers
        </a>
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

{{-- Two-mode explainer --}}
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:18px 20px;margin-bottom:24px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
    <div style="display:flex;gap:12px;align-items:flex-start;">
        <div style="width:36px;height:36px;background:#25D366;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab fa-whatsapp" style="color:#fff;font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1e40af;">Shared (Basic Plan)</div>
            <div style="font-size:12px;color:#3b82f6;margin-top:2px;line-height:1.5;">All hotels share the <strong>CRM's number</strong>. One-click activation. No credentials needed by hotel.</div>
        </div>
    </div>
    <div style="display:flex;gap:12px;align-items:flex-start;">
        <div style="width:36px;height:36px;background:#7c3aed;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-plug" style="color:#fff;font-size:14px;"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#5b21b6;">Own Number (Pro Plan)</div>
            <div style="font-size:12px;color:#7c3aed;margin-top:2px;line-height:1.5;">Hotel connects their <strong>own WABA</strong> via embedded signup. Full independence.</div>
        </div>
    </div>
    <div style="display:flex;gap:12px;align-items:flex-start;">
        <div style="width:36px;height:36px;background:#0284c7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-sim-card" style="color:#fff;font-size:14px;"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#0369a1;">Managed Number (New)</div>
            <div style="font-size:12px;color:#0284c7;margin-top:2px;line-height:1.5;">You register the hotel's number under <strong>your WABA</strong>. They get their own number. One billing from you. <a href="{{ route('platform.whatsapp.numbers') }}" style="color:#0369a1;font-weight:700;">Manage →</a></div>
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('warning'))
<div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
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
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:#1877F2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab fa-meta" style="color:#fff;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Meta App Credentials</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">From Meta Developer Console → Your App → App Settings → Basic</div>
        </div>
        <div style="margin-left:auto;display:flex;gap:6px;">
            <span style="background:#ede9fe;color:#6d28d9;font-size:11px;font-weight:600;padding:3px 9px;border-radius:12px;">
                <i class="fas fa-plug" style="font-size:10px;"></i> Used for: Hotel's Own Number Signup
            </span>
            <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;padding:3px 9px;border-radius:12px;">
                <i class="fas fa-shield-alt" style="font-size:10px;"></i> Webhook Security
            </span>
        </div>
    </div>

    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:12px;color:#6d28d9;line-height:1.6;">
        <i class="fas fa-info-circle"></i> &nbsp;These credentials power two things: <strong>(1)</strong> the in-app signup popup that Pro/Premium hotels use to connect their own WhatsApp number, and <strong>(2)</strong> the webhook signature verification to keep incoming messages secure.
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Meta App ID</label>
            <input type="text" name="meta_app_id" value="{{ old('meta_app_id', $settings->meta_app_id) }}"
                placeholder="e.g. 1390491206132028"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">developers.facebook.com → Your App → App Settings → Basic → <strong>App ID</strong></div>
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Meta App Secret</label>
            <input type="password" name="meta_app_secret" value="{{ old('meta_app_secret', $settings->meta_app_secret) }}"
                placeholder="Keep this secret — never share"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">App Settings → Basic → <strong>App Secret</strong> (click Show to reveal)</div>
        </div>
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                Business Login Configuration ID
                <span style="font-size:11px;font-weight:500;color:#7c3aed;margin-left:8px;">— for the hotel's own number signup popup</span>
            </label>
            <input type="text" name="meta_config_id" value="{{ old('meta_config_id', $settings->meta_config_id) }}"
                placeholder="e.g. 1257571323252701"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                Your App → <strong>Facebook Login for Business</strong> → Configurations → Copy the Configuration ID
                &nbsp;·&nbsp; Create a config with permissions: <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">whatsapp_business_management</code> and <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">whatsapp_business_messaging</code>
            </div>
        </div>
    </div>
</div>

{{-- Section 2: Shared CRM Number --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:linear-gradient(135deg,#25D366,#128C7E);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fab fa-whatsapp" style="color:#fff;font-size:20px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Platform Shared Number</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">The CRM's own verified WhatsApp Business number</div>
        </div>
        <div style="margin-left:auto;">
            <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:600;padding:3px 9px;border-radius:12px;">
                <i class="fab fa-whatsapp" style="font-size:10px;"></i> Used for: Basic Plan Hotels Only
            </span>
        </div>
    </div>

    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:12px;color:#15803d;line-height:1.6;">
        <i class="fas fa-info-circle"></i> &nbsp;Hotels on the <strong>Basic plan</strong> do not connect their own number. All their messages are sent from this number. Enter your platform's System User token and the phone number details below.
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:18px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                System User Access Token
                <span style="font-size:11px;font-weight:500;color:#15803d;margin-left:8px;">— permanent token, never expires</span>
            </label>
            <textarea name="saas_token" rows="3"
                placeholder="Paste the System User token here (long string starting with EAA...)"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:monospace;box-sizing:border-box;resize:vertical;">{{ old('saas_token', $settings->saas_token) }}</textarea>
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                <strong>How to get:</strong> WhatsApp Business Manager (business.facebook.com) → System Users → Create a System User (Admin role) → Generate Token → select your WhatsApp App → grant <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">whatsapp_business_messaging</code> + <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">whatsapp_business_management</code>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Phone Number ID
                    <span style="font-size:11px;font-weight:500;color:#15803d;margin-left:4px;">— platform's number</span>
                </label>
                <input type="text" name="saas_phone_number_id" value="{{ old('saas_phone_number_id', $settings->saas_phone_number_id) }}"
                    placeholder="e.g. 111222333444555"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">WhatsApp Manager → Phone Numbers → click the number → <strong>Phone Number ID</strong></div>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                    WhatsApp Business Account ID (WABA)
                    <span style="font-size:11px;font-weight:500;color:#15803d;margin-left:4px;">— platform's account</span>
                </label>
                <input type="text" name="saas_waba_id" value="{{ old('saas_waba_id', $settings->saas_waba_id) }}"
                    placeholder="e.g. 222333444555666"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">WhatsApp Business Manager → Accounts → WhatsApp Accounts → <strong>Account ID</strong></div>
            </div>
        </div>
    </div>
</div>

{{-- Section 3: Webhook --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
        <div style="width:40px;height:40px;background:#f3f4f6;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-satellite-dish" style="color:#6b7280;font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Webhook Configuration</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">Receives delivery receipts and template approval status from Meta — for all hotels</div>
        </div>
    </div>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:18px;">
        <div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;">
            <i class="fas fa-link"></i> &nbsp;Webhook Callback URL — paste this into Meta App → Webhooks
        </div>
        <div style="font-family:monospace;font-size:13px;color:#1e293b;word-break:break-all;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;">{{ url('/webhook/whatsapp') }}</div>
    </div>

    <div>
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Verify Token</label>
        <input type="text" name="webhook_verify_token" value="{{ old('webhook_verify_token', $settings->webhook_verify_token) }}"
            placeholder="Any secret string you choose — e.g. hotel_crm_webhook_2026"
            style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
            Enter this exact string in Meta App → Webhooks → Edit → <strong>Verify Token</strong> field. &nbsp;·&nbsp;
            Subscribe to: <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">messages</code> and <code style="background:#f3f4f6;padding:1px 4px;border-radius:4px;">message_template_status_update</code>
        </div>
    </div>
</div>

{{-- Signature bypass toggle (dev mode) --}}
<div style="background:{{ $settings->skip_signature_check ? '#fef2f2' : '#fff' }};border:1px solid {{ $settings->skip_signature_check ? '#fca5a5' : '#e5e7eb' }};border-radius:16px;padding:20px 28px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;">
    <div>
        <div style="font-size:15px;font-weight:700;color:{{ $settings->skip_signature_check ? '#b91c1c' : '#111827' }};display:flex;align-items:center;gap:8px;">
            <i class="fas fa-shield-alt"></i> Bypass Webhook Signature Check
            @if($settings->skip_signature_check)
            <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;letter-spacing:.5px;">DEV MODE ON</span>
            @endif
        </div>
        <div style="font-size:13px;color:#6b7280;margin-top:4px;">
            Enable this when Meta webhook is pointing to the <strong>Replit dev URL</strong> — Replit's proxy strips the
            <code style="font-size:11px;background:#f3f4f6;padding:1px 5px;border-radius:4px;">X-Hub-Signature-256</code> header, causing all incoming messages to be rejected.
            <strong style="color:#b91c1c;">Keep OFF in production</strong> (resort.dreamstechnology.in passes headers correctly).
        </div>
    </div>
    <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;flex-shrink:0;">
        <input type="checkbox" name="skip_signature_check" value="1" {{ $settings->skip_signature_check ? 'checked' : '' }}
            style="opacity:0;width:0;height:0;" id="skipSigToggle">
        <span id="skipSigSpan" style="position:absolute;top:0;left:0;right:0;bottom:0;border-radius:28px;transition:.3s;background:{{ $settings->skip_signature_check ? '#ef4444' : '#d1d5db' }};"></span>
        <span id="skipSigKnob" style="position:absolute;top:3px;left:{{ $settings->skip_signature_check ? '27px' : '3px' }};width:22px;height:22px;border-radius:50%;background:#fff;transition:.3s;"></span>
    </label>
</div>

{{-- Active toggle --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px 28px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;">
    <div>
        <div style="font-size:15px;font-weight:700;color:#111827;">Activate Shared Number</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px;">When enabled, Basic plan hotels can instantly activate WhatsApp using the platform's shared number above. Pro/Premium hotels are unaffected — they connect their own number.</div>
    </div>
    <label style="position:relative;display:inline-block;width:52px;height:28px;cursor:pointer;flex-shrink:0;margin-left:20px;">
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
    <div style="font-size:13px;color:#6b7280;margin-bottom:16px;">Send a test message to verify the shared number is working correctly.</div>
    <form method="POST" action="{{ route('platform.whatsapp.test') }}" style="display:flex;gap:12px;align-items:flex-end;">
        @csrf
        <div style="flex:1;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">Phone Number (with country code, no + or spaces)</label>
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

const skipSigToggle = document.getElementById('skipSigToggle');
const skipSigSpan   = document.getElementById('skipSigSpan');
const skipSigKnob   = document.getElementById('skipSigKnob');
if (skipSigToggle) {
    skipSigToggle.addEventListener('change', () => {
        if (skipSigToggle.checked) {
            skipSigSpan.style.background = '#ef4444';
            skipSigKnob.style.left = '27px';
        } else {
            skipSigSpan.style.background = '#d1d5db';
            skipSigKnob.style.left = '3px';
        }
    });
}
</script>

@endsection
