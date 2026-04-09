@extends('layouts.platform')
@section('title', 'Firebase Push Settings')

@section('content')

@php
$saved = [
    'firebase_project_id'          => !empty($config->firebase_project_id),
    'firebase_api_key'             => !empty($config->firebase_api_key),
    'firebase_messaging_sender_id' => !empty($config->firebase_messaging_sender_id),
    'firebase_app_id'              => !empty($config->firebase_app_id),
    'firebase_vapid_key'           => !empty($config->firebase_vapid_key),
    'fcm_server_key'               => !empty($config->fcm_server_key),
];
$allDone = array_sum($saved) === count($saved);
$readyCount = array_sum($saved);
@endphp

<div style="max-width:780px;">

{{-- Header --}}
<div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fas fa-bell" style="color:#7c3aed;margin-right:8px;"></i>Firebase Push Settings
        </h1>
        <p style="color:#6b7280;font-size:13px;margin:0;">Configure Firebase Cloud Messaging to deliver push notifications to hotel users.</p>
    </div>
    @if($allDone)
    <span style="margin-left:auto;padding:6px 14px;background:#dcfce7;color:#15803d;border-radius:20px;font-size:12px;font-weight:700;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Fully Configured</span>
    @else
    <span style="margin-left:auto;padding:6px 14px;background:#fef3c7;color:#92400e;border-radius:20px;font-size:12px;font-weight:700;"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i>{{ $readyCount }}/6 fields set</span>
    @endif
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif

{{-- Configuration Status Card --}}
<div style="background:#fff;border-radius:16px;padding:18px 22px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:22px;">
    <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:14px;"><i class="fas fa-tasks" style="color:#7c3aed;margin-right:7px;"></i>Configuration Status</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
        @foreach([
            ['Project ID',          'firebase_project_id',          'fas fa-project-diagram'],
            ['API Key',             'firebase_api_key',             'fas fa-key'],
            ['Sender ID',           'firebase_messaging_sender_id', 'fas fa-satellite-dish'],
            ['App ID',              'firebase_app_id',              'fas fa-mobile-alt'],
            ['VAPID Key',           'firebase_vapid_key',           'fas fa-certificate'],
            ['Server Key (Legacy)', 'fcm_server_key',               'fas fa-server'],
        ] as [$lbl, $field, $icon])
        <div style="padding:12px;border-radius:11px;background:{{ $saved[$field] ? '#f0fdf4' : '#fef3c7' }};border:1px solid {{ $saved[$field] ? '#86efac' : '#fde68a' }};display:flex;align-items:center;gap:9px;">
            <div style="width:28px;height:28px;background:{{ $saved[$field] ? '#15803d' : '#d97706' }};border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $saved[$field] ? 'fas fa-check' : $icon }}" style="color:#fff;font-size:11px;"></i>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:{{ $saved[$field] ? '#15803d' : '#92400e' }};">{{ $lbl }}</div>
                <div style="font-size:10px;color:{{ $saved[$field] ? '#16a34a' : '#d97706' }};">{{ $saved[$field] ? '✓ Saved' : '⚠ Missing' }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Step-by-step guide --}}
@if(!$allDone)
<div style="background:linear-gradient(135deg,#eff6ff,#faf5ff);border:1px solid #c7d2fe;border-radius:16px;padding:20px 22px;margin-bottom:22px;">
    <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:14px;"><i class="fas fa-map-signs" style="color:#7c3aed;margin-right:7px;"></i>Where to find each value in Firebase Console</div>

    <div style="display:flex;flex-direction:column;gap:12px;">

        @if(!$saved['firebase_project_id'] || !$saved['firebase_api_key'] || !$saved['firebase_messaging_sender_id'] || !$saved['firebase_app_id'])
        <div style="background:#fff;border-radius:12px;padding:14px 16px;border-left:4px solid #3b82f6;">
            <div style="font-size:12px;font-weight:800;color:#1e40af;margin-bottom:6px;">
                <i class="fas fa-map-marker-alt" style="margin-right:6px;"></i>Project ID, API Key, Sender ID, App ID
            </div>
            <div style="font-size:12px;color:#374151;line-height:1.7;">
                <strong>1.</strong> Go to <a href="https://console.firebase.google.com/project/{{ $config->firebase_project_id ?? 'YOUR_PROJECT' }}/settings/general" target="_blank" style="color:#7c3aed;font-weight:700;">Firebase Console → Project Settings → General</a><br>
                <strong>2.</strong> Scroll down to <em>"Your apps"</em> section → click your Web app<br>
                <strong>3.</strong> Click <strong>"SDK setup and configuration"</strong> → copy values from the <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">firebaseConfig</code> object
            </div>
        </div>
        @endif

        @if(!$saved['firebase_vapid_key'])
        <div style="background:#fff;border-radius:12px;padding:14px 16px;border-left:4px solid #f59e0b;">
            <div style="font-size:12px;font-weight:800;color:#92400e;margin-bottom:6px;">
                <i class="fas fa-certificate" style="margin-right:6px;"></i>VAPID Key — <span style="font-weight:700;color:#ef4444;">Missing</span>
            </div>
            <div style="font-size:12px;color:#374151;line-height:1.7;margin-bottom:8px;">
                <strong>1.</strong> Go to <a href="https://console.firebase.google.com/project/{{ $config->firebase_project_id ?? 'YOUR_PROJECT' }}/settings/cloudmessaging" target="_blank" style="color:#7c3aed;font-weight:700;">Project Settings → Cloud Messaging</a><br>
                <strong>2.</strong> Scroll to <strong>"Web configuration"</strong> → <strong>"Web Push certificates"</strong><br>
                <strong>3.</strong> You should see a Key pair already generated (it was visible in your screenshot) — click on it to copy the <strong>full public key</strong><br>
                <strong>4.</strong> It starts with <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">BB9srpLPm...</code> — copy the whole string and paste below
            </div>
            <div style="background:#fef3c7;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e;">
                <i class="fas fa-lightbulb" style="margin-right:5px;"></i>
                <strong>Tip:</strong> The VAPID key is the long string under "Key pair" in Web Push certificates. It is the <em>public key</em> only (starts with <code>B</code>).
            </div>
        </div>
        @endif

        @if(!$saved['fcm_server_key'])
        <div style="background:#fff;border-radius:12px;padding:14px 16px;border-left:4px solid #ef4444;">
            <div style="font-size:12px;font-weight:800;color:#b91c1c;margin-bottom:6px;">
                <i class="fas fa-server" style="margin-right:6px;"></i>Server Key (Legacy) — <span style="font-weight:700;color:#ef4444;">Missing · Legacy API is Disabled</span>
            </div>
            <div style="font-size:12px;color:#374151;line-height:1.7;margin-bottom:8px;">
                <strong>Your Firebase project has <em>Cloud Messaging API (Legacy)</em> disabled.</strong> To get a Server Key:<br><br>
                <strong>1.</strong> Go to <a href="https://console.firebase.google.com/project/{{ $config->firebase_project_id ?? 'hotel-crm-e9e34' }}/settings/cloudmessaging" target="_blank" style="color:#7c3aed;font-weight:700;">Project Settings → Cloud Messaging</a><br>
                <strong>2.</strong> Find the <strong>"Cloud Messaging API (Legacy)"</strong> card showing "Disabled"<br>
                <strong>3.</strong> Click the <strong>3-dot menu (⋮)</strong> on the top-right of that card<br>
                <strong>4.</strong> Select <strong>"Enable"</strong> — it activates in a few seconds<br>
                <strong>5.</strong> Reload the page — you'll now see a <strong>Server Key</strong> under that card — copy and paste it below
            </div>
            <div style="background:#fee2e2;border-radius:8px;padding:10px 12px;font-size:12px;color:#b91c1c;">
                <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                Without the Server Key, push notifications cannot be delivered. The Legacy API is still required for the simple HTTP send method we use.
            </div>
        </div>
        @endif

    </div>
</div>
@endif

{{-- Settings Form --}}
<div style="background:#fff;border-radius:16px;padding:24px 26px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:18px;">
    <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:18px;"><i class="fas fa-sliders-h" style="color:#7c3aed;margin-right:7px;"></i>Firebase Configuration</div>

    <form action="{{ route('platform.notifications.settings.save') }}" method="POST">
        @csrf

        {{-- Enable toggle --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:14px 16px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
            <div>
                <div style="font-size:13px;font-weight:700;color:#1e293b;">Enable Push Notifications</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">When on, hotel users receive a browser permission prompt and bell notifications.</div>
            </div>
            <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;flex-shrink:0;">
                <input type="hidden" name="push_enabled" value="0">
                <input type="checkbox" name="push_enabled" value="1" {{ $config->push_enabled ? 'checked' : '' }}
                    style="width:44px;height:24px;accent-color:#7c3aed;cursor:pointer;">
            </label>
        </div>

        @php
        $fields = [
            ['firebase_project_id',          'Project ID',           'text', 'e.g. hotel-crm-e9e34',                    'From: Firebase Console → Project Settings → General'],
            ['firebase_api_key',             'API Key',              'text', 'AIzaSy...',                               'From: Your apps → Web app → SDK config (apiKey)'],
            ['firebase_messaging_sender_id', 'Messaging Sender ID',  'text', 'e.g. 884314304438',                       'From: Cloud Messaging tab → Sender ID  (you have this ✓)'],
            ['firebase_app_id',              'App ID',               'text', '1:884314304438:web:abc123',               'From: Your apps → Web app → SDK config (appId)'],
            ['firebase_vapid_key',           'VAPID Key',            'text', 'BB9srpLPmGKDvpy... (full key pair)',      '⚠ From: Cloud Messaging → Web configuration → Web Push certificates → Key pair'],
            ['fcm_server_key',               'Server Key (Legacy)',   'text', 'AAAA... (enable Legacy API first)',       '⚠ From: Cloud Messaging → Legacy API card (must enable it first)'],
        ];
        @endphp

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        @foreach($fields as [$field, $label, $type, $placeholder, $hint])
        <div style="margin-bottom:4px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;">
                @if($saved[$field])
                <span style="display:inline-flex;width:16px;height:16px;background:#15803d;border-radius:50%;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-check" style="color:#fff;font-size:8px;"></i>
                </span>
                @else
                <span style="display:inline-flex;width:16px;height:16px;background:#f59e0b;border-radius:50%;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-exclamation" style="color:#fff;font-size:8px;"></i>
                </span>
                @endif
                {{ $label }}
            </label>
            <input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $config->$field) }}"
                placeholder="{{ $placeholder }}"
                style="width:100%;padding:9px 12px;border:2px solid {{ $saved[$field] ? '#86efac' : '#fde68a' }};border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;background:{{ $saved[$field] ? '#f0fdf4' : '#fffbeb' }};outline:none;transition:border-color .2s;"
                onfocus="this.style.borderColor='#7c3aed';this.style.background='#fdf4ff'"
                onblur="this.style.borderColor='{{ $saved[$field] ? '#86efac' : '#fde68a' }}';this.style.background='{{ $saved[$field] ? '#f0fdf4' : '#fffbeb' }}'">
            <div style="font-size:10px;color:#94a3b8;margin-top:3px;line-height:1.4;">{{ $hint }}</div>
        </div>
        @endforeach
        </div>

        @if($errors->any())
        <div style="background:#fee2e2;border-radius:9px;padding:10px 14px;margin-top:12px;color:#b91c1c;font-size:13px;">
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div style="display:flex;gap:10px;margin-top:18px;align-items:center;">
            <button type="submit" style="padding:10px 24px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(124,58,237,.3);">
                <i class="fas fa-save" style="margin-right:6px;"></i>Save Settings
            </button>
            <a href="{{ route('platform.notifications.send') }}" style="padding:10px 20px;background:#f1f5f9;color:#374151;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-paper-plane" style="color:#7c3aed;"></i> Send Notification
            </a>
            @if($allDone)
            <span style="margin-left:auto;font-size:12px;font-weight:600;color:#15803d;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>All fields configured — push is ready!</span>
            @endif
        </div>
    </form>
</div>

{{-- Quick links --}}
<div style="background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:800;color:#1e293b;margin-bottom:10px;"><i class="fas fa-external-link-alt" style="color:#7c3aed;margin-right:6px;"></i>Firebase Console Quick Links</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @php $pid = $config->firebase_project_id ?: 'YOUR_PROJECT'; @endphp
        @foreach([
            ['General Settings', "https://console.firebase.google.com/project/{$pid}/settings/general", 'fas fa-cog'],
            ['Cloud Messaging', "https://console.firebase.google.com/project/{$pid}/settings/cloudmessaging", 'fas fa-bell'],
            ['Your Apps', "https://console.firebase.google.com/project/{$pid}/settings/general#your-apps", 'fas fa-mobile-alt'],
        ] as [$lbl, $url, $icon])
        <a href="{{ $url }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:#f8fafc;color:#374151;border:1px solid #e2e8f0;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;"
           onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
            <i class="{{ $icon }}" style="color:#7c3aed;font-size:11px;"></i>{{ $lbl }}
            <i class="fas fa-external-link-alt" style="font-size:9px;color:#94a3b8;"></i>
        </a>
        @endforeach
    </div>
</div>

{{-- Device Stats --}}
@php
$totalTokens   = DB::table('fcm_tokens')->count();
$webTokens     = DB::table('fcm_tokens')->where('platform','web')->count();
$androidTokens = DB::table('fcm_tokens')->where('platform','android')->count();
@endphp
<div style="background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
    <div style="font-size:12px;font-weight:800;color:#1e293b;margin-bottom:12px;"><i class="fas fa-mobile-alt" style="color:#06b6d4;margin-right:6px;"></i>Registered Devices</div>
    <div style="display:flex;gap:24px;flex-wrap:wrap;">
        @foreach([['Total', $totalTokens, '#7c3aed'], ['Web Browser', $webTokens, '#06b6d4'], ['Android', $androidTokens, '#10b981']] as [$lbl, $val, $color])
        <div>
            <div style="font-size:24px;font-weight:900;color:{{ $color }};">{{ $val }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $lbl }}</div>
        </div>
        @endforeach
    </div>
</div>

</div>
@endsection
