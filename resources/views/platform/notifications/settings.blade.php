@extends('layouts.platform')
@section('title', 'Firebase Push Settings')

@section('content')

@php
$hasSA     = !empty($config->service_account_json);
$hasLegacy = !empty($config->fcm_server_key);

$saved = [
    'firebase_project_id'          => !empty($config->firebase_project_id),
    'firebase_api_key'             => !empty($config->firebase_api_key),
    'firebase_messaging_sender_id' => !empty($config->firebase_messaging_sender_id),
    'firebase_app_id'              => !empty($config->firebase_app_id),
    'firebase_vapid_key'           => !empty($config->firebase_vapid_key),
    'send_method'                  => $hasSA || $hasLegacy,
];

$readyCount = array_sum($saved);
$allDone    = $readyCount === count($saved);

$activeMethod = null;
if ($hasSA)     $activeMethod = 'v1';
elseif ($hasLegacy) $activeMethod = 'legacy';
@endphp

<div style="max-width:800px;">

{{-- Header --}}
<div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fas fa-bell" style="color:#7c3aed;margin-right:8px;"></i>Firebase Push Settings
        </h1>
        <p style="color:#6b7280;font-size:13px;margin:0;">Configure Firebase Cloud Messaging to deliver push notifications to hotel users.</p>
    </div>
    @if($allDone)
    <span style="margin-left:auto;padding:6px 14px;background:#dcfce7;color:#15803d;border-radius:20px;font-size:12px;font-weight:700;">
        <i class="fas fa-check-circle" style="margin-right:4px;"></i>Fully Configured
    </span>
    @else
    <span style="margin-left:auto;padding:6px 14px;background:#fef3c7;color:#92400e;border-radius:20px;font-size:12px;font-weight:700;">
        <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i>{{ $readyCount }}/{{ count($saved) }} fields set
    </span>
    @endif
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif

{{-- Method explainer cards --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px;">

    {{-- v1 API card (recommended) --}}
    <div style="border-radius:16px;padding:18px;border:2px solid {{ $hasSA ? '#7c3aed' : '#e2e8f0' }};background:{{ $hasSA ? 'linear-gradient(135deg,#fdf4ff,#ede9fe)' : '#f8fafc' }};position:relative;">
        @if($hasSA)
        <span style="position:absolute;top:12px;right:12px;background:#7c3aed;color:#fff;font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;">ACTIVE</span>
        @endif
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-star" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:13px;font-weight:800;color:#1e293b;">FCM HTTP v1 API</div>
                <div style="font-size:11px;color:#10b981;font-weight:700;">Recommended · Current Standard</div>
            </div>
        </div>
        <p style="font-size:12px;color:#374151;margin:0 0 8px;line-height:1.6;">
            Uses a <strong>Service Account JSON</strong> file + short-lived OAuth 2.0 tokens. No static key.
            Works on all new Firebase projects (post-June 2023).
        </p>
        <ul style="font-size:11px;color:#6b7280;margin:0;padding-left:16px;line-height:1.8;">
            <li>Auto-rotating tokens — more secure</li>
            <li>Full support for Web, Android, iOS payloads</li>
            <li>No Legacy API enabling needed</li>
        </ul>
    </div>

    {{-- Legacy card --}}
    <div style="border-radius:16px;padding:18px;border:2px solid {{ (!$hasSA && $hasLegacy) ? '#f59e0b' : '#e2e8f0' }};background:{{ (!$hasSA && $hasLegacy) ? '#fffbeb' : '#f8fafc' }};position:relative;opacity:{{ !$hasSA && $hasLegacy ? '1' : '0.7' }}">
        @if(!$hasSA && $hasLegacy)
        <span style="position:absolute;top:12px;right:12px;background:#f59e0b;color:#fff;font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;">ACTIVE (FALLBACK)</span>
        @endif
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:36px;height:36px;background:#f59e0b;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:13px;font-weight:800;color:#1e293b;">Legacy Server Key</div>
                <div style="font-size:11px;color:#ef4444;font-weight:700;">Deprecated · Disabled on new projects</div>
            </div>
        </div>
        <p style="font-size:12px;color:#374151;margin:0 0 8px;line-height:1.6;">
            Static server key from the Cloud Messaging (Legacy) API. Disabled by default on new Firebase projects
            and officially deprecated by Google.
        </p>
        <ul style="font-size:11px;color:#6b7280;margin:0;padding-left:16px;line-height:1.8;">
            <li>Must manually enable "Legacy API" in Firebase Console</li>
            <li>Will stop working entirely in future</li>
            <li>Use only if you cannot use v1</li>
        </ul>
    </div>
</div>

{{-- Config Status --}}
<div style="background:#fff;border-radius:16px;padding:18px 22px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:22px;">
    <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:14px;"><i class="fas fa-tasks" style="color:#7c3aed;margin-right:7px;"></i>Configuration Status</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
        @foreach([
            ['Project ID',    'firebase_project_id',          'fas fa-project-diagram'],
            ['API Key',       'firebase_api_key',             'fas fa-key'],
            ['Sender ID',     'firebase_messaging_sender_id', 'fas fa-satellite-dish'],
            ['App ID',        'firebase_app_id',              'fas fa-mobile-alt'],
            ['VAPID Key',     'firebase_vapid_key',           'fas fa-certificate'],
            ['Send Method',   'send_method',                  'fas fa-paper-plane'],
        ] as [$lbl, $field, $icon])
        <div style="padding:12px;border-radius:11px;background:{{ $saved[$field] ? '#f0fdf4' : '#fef3c7' }};border:1px solid {{ $saved[$field] ? '#86efac' : '#fde68a' }};display:flex;align-items:center;gap:9px;">
            <div style="width:28px;height:28px;background:{{ $saved[$field] ? '#15803d' : '#d97706' }};border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $saved[$field] ? 'fas fa-check' : $icon }}" style="color:#fff;font-size:11px;"></i>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:{{ $saved[$field] ? '#15803d' : '#92400e' }};">{{ $lbl }}</div>
                <div style="font-size:10px;color:{{ $saved[$field] ? '#16a34a' : '#d97706' }};">
                    @if($field === 'send_method')
                        {{ $hasSA ? '✓ v1 API (SA)' : ($hasLegacy ? '⚡ Legacy Key' : '⚠ Not set') }}
                    @else
                        {{ $saved[$field] ? '✓ Saved' : '⚠ Missing' }}
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Settings Form --}}
<div style="background:#fff;border-radius:16px;padding:24px 26px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:18px;">
    <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:18px;"><i class="fas fa-sliders-h" style="color:#7c3aed;margin-right:7px;"></i>Firebase Configuration</div>

    <form action="{{ route('platform.notifications.settings.save') }}" method="POST">
        @csrf

        {{-- Enable toggle --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;padding:14px 16px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
            <div>
                <div style="font-size:13px;font-weight:700;color:#1e293b;">Enable Push Notifications</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">When on, hotel users receive a browser permission prompt and in-app bell notifications.</div>
            </div>
            <label style="position:relative;display:inline-block;cursor:pointer;flex-shrink:0;">
                <input type="hidden" name="push_enabled" value="0">
                <input type="checkbox" name="push_enabled" value="1" {{ $config->push_enabled ? 'checked' : '' }}
                    style="width:44px;height:24px;accent-color:#7c3aed;cursor:pointer;">
            </label>
        </div>

        {{-- SDK config fields --}}
        <div style="font-size:12px;font-weight:800;color:#374151;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
            <i class="fas fa-code" style="color:#06b6d4;margin-right:5px;"></i>Firebase SDK Config
            <span style="font-size:10px;font-weight:400;color:#94a3b8;margin-left:6px;text-transform:none;">From: Firebase Console → Project Settings → General → Your apps → Web app</span>
        </div>

        @php
        $sdkFields = [
            ['firebase_project_id',          'Project ID',          'e.g. hotel-crm-e9e34'],
            ['firebase_api_key',             'API Key',             'AIzaSy...'],
            ['firebase_messaging_sender_id', 'Messaging Sender ID', 'e.g. 884314304438'],
            ['firebase_app_id',              'App ID',              '1:884314304438:web:abc...'],
        ];
        @endphp

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        @foreach($sdkFields as [$field, $label, $ph])
        <div>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;">
                @if($saved[$field])
                <span style="display:inline-flex;width:15px;height:15px;background:#15803d;border-radius:50%;align-items:center;justify-content:center;"><i class="fas fa-check" style="color:#fff;font-size:7px;"></i></span>
                @else
                <span style="display:inline-flex;width:15px;height:15px;background:#f59e0b;border-radius:50%;align-items:center;justify-content:center;"><i class="fas fa-exclamation" style="color:#fff;font-size:7px;"></i></span>
                @endif
                {{ $label }}
            </label>
            <input type="text" name="{{ $field }}" value="{{ old($field, $config->$field) }}" placeholder="{{ $ph }}"
                style="width:100%;padding:9px 12px;border:2px solid {{ $saved[$field] ? '#86efac' : '#e2e8f0' }};border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;background:{{ $saved[$field] ? '#f0fdf4' : '#fff' }};outline:none;"
                onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='{{ $saved[$field] ? '#86efac' : '#e2e8f0' }}'">
        </div>
        @endforeach
        </div>

        {{-- VAPID Key --}}
        <div style="margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;">
                @if($saved['firebase_vapid_key'])
                <span style="display:inline-flex;width:15px;height:15px;background:#15803d;border-radius:50%;align-items:center;justify-content:center;"><i class="fas fa-check" style="color:#fff;font-size:7px;"></i></span>
                @else
                <span style="display:inline-flex;width:15px;height:15px;background:#f59e0b;border-radius:50%;align-items:center;justify-content:center;"><i class="fas fa-exclamation" style="color:#fff;font-size:7px;"></i></span>
                @endif
                VAPID Key (Web Push)
                <span style="font-size:10px;font-weight:400;color:#94a3b8;">From: Cloud Messaging tab → Web configuration → Web Push certificates → Key pair</span>
            </label>
            <input type="text" name="firebase_vapid_key" value="{{ old('firebase_vapid_key', $config->firebase_vapid_key) }}"
                placeholder="BB9srpLPmGKDvpy... (the full public key pair)"
                style="width:100%;padding:9px 12px;border:2px solid {{ $saved['firebase_vapid_key'] ? '#86efac' : '#fde68a' }};border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;background:{{ $saved['firebase_vapid_key'] ? '#f0fdf4' : '#fffbeb' }};outline:none;"
                onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='{{ $saved['firebase_vapid_key'] ? '#86efac' : '#fde68a' }}'">
            @if(!$saved['firebase_vapid_key'])
            <p style="font-size:11px;color:#d97706;margin:4px 0 0;"><i class="fas fa-lightbulb" style="margin-right:4px;"></i>Your key was visible in your screenshot starting with <code>BB9srpLPm...</code> — click on the key pair in Firebase to copy the full string.</p>
            @endif
        </div>

        <hr style="border:none;border-top:1px solid #f1f5f9;margin:0 0 20px;">

        {{-- === SEND METHOD SECTION === --}}
        <div style="font-size:12px;font-weight:800;color:#374151;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px;">
            <i class="fas fa-paper-plane" style="color:#7c3aed;margin-right:5px;"></i>Send Method
            <span style="font-size:10px;font-weight:400;color:#94a3b8;text-transform:none;margin-left:6px;">Choose ONE — Service Account (recommended) or Legacy Server Key (fallback)</span>
        </div>

        {{-- Option A: Service Account JSON (v1) --}}
        <div style="border:2px solid {{ $hasSA ? '#7c3aed' : '#e2e8f0' }};border-radius:14px;padding:18px;margin-bottom:14px;background:{{ $hasSA ? 'linear-gradient(135deg,#fdf4ff,#ede9fe)' : '#fafafa' }};">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:30px;height:30px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-star" style="color:#fff;font-size:12px;"></i>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:800;color:#1e293b;">Option A — Service Account JSON <span style="color:#7c3aed;">(Recommended)</span></div>
                    <div style="font-size:11px;color:#6b7280;">FCM HTTP v1 API · Works on all new Firebase projects · No Legacy key needed</div>
                </div>
                @if($hasSA)<span style="margin-left:auto;background:#7c3aed;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Active</span>@endif
            </div>

            {{-- How to get it --}}
            <div style="background:rgba(255,255,255,.8);border-radius:10px;padding:12px 14px;margin-bottom:12px;border-left:4px solid #7c3aed;">
                <div style="font-size:11px;font-weight:800;color:#5b21b6;margin-bottom:6px;"><i class="fas fa-map-signs" style="margin-right:5px;"></i>How to get your Service Account JSON:</div>
                <ol style="font-size:11px;color:#374151;margin:0;padding-left:16px;line-height:1.9;">
                    <li>Open <a href="https://console.firebase.google.com/project/{{ $config->firebase_project_id ?: 'hotel-crm-e9e34' }}/settings/serviceaccounts/adminsdk" target="_blank" style="color:#7c3aed;font-weight:700;">Firebase Console → Project Settings → Service accounts</a></li>
                    <li>Select <strong>Node.js</strong> (or any language — doesn't matter)</li>
                    <li>Click <strong>"Generate new private key"</strong></li>
                    <li>A <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">.json</code> file downloads — open it and paste the entire contents below</li>
                </ol>
            </div>

            <label style="font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;display:block;">
                @if($hasSA)
                <span style="display:inline-flex;width:15px;height:15px;background:#7c3aed;border-radius:50%;align-items:center;justify-content:center;margin-right:5px;"><i class="fas fa-check" style="color:#fff;font-size:7px;"></i></span>Service Account JSON
                @else
                <span style="display:inline-flex;width:15px;height:15px;background:#e2e8f0;border-radius:50%;align-items:center;justify-content:center;margin-right:5px;"><i class="fas fa-file-code" style="color:#94a3b8;font-size:7px;"></i></span>Service Account JSON
                @endif
                <span style="font-size:10px;font-weight:400;color:#94a3b8;">Paste the full JSON contents of the downloaded key file</span>
            </label>
            <textarea name="service_account_json" rows="6"
                placeholder='{&#10;  "type": "service_account",&#10;  "project_id": "hotel-crm-e9e34",&#10;  "private_key_id": "...",&#10;  "private_key": "-----BEGIN RSA PRIVATE KEY-----\n...",&#10;  "client_email": "firebase-adminsdk-...@hotel-crm-e9e34.iam.gserviceaccount.com",&#10;  ...&#10;}'
                style="width:100%;padding:10px 12px;border:2px solid {{ $hasSA ? '#7c3aed' : '#e2e8f0' }};border-radius:9px;font-size:12px;color:#374151;box-sizing:border-box;background:{{ $hasSA ? '#fdf4ff' : '#fff' }};font-family:monospace;resize:vertical;outline:none;"
                onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='{{ $hasSA ? '#7c3aed' : '#e2e8f0' }}'">{{ $hasSA ? '(Saved — paste again only to update)' : '' }}</textarea>
            @error('service_account_json')
            <p style="color:#ef4444;font-size:12px;margin:4px 0 0;"><i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>{{ $message }}</p>
            @enderror
        </div>

        {{-- Option B: Legacy Key --}}
        <div style="border:2px solid {{ (!$hasSA && $hasLegacy) ? '#f59e0b' : '#e2e8f0' }};border-radius:14px;padding:18px;margin-bottom:20px;background:{{ (!$hasSA && $hasLegacy) ? '#fffbeb' : '#fafafa' }};opacity:{{ $hasSA ? '0.6' : '1' }}">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:30px;height:30px;background:#f59e0b;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:12px;"></i>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:800;color:#1e293b;">Option B — Legacy Server Key <span style="color:#ef4444;">(Deprecated)</span></div>
                    <div style="font-size:11px;color:#6b7280;">Only if Option A is not possible · Must enable Legacy API in Firebase Console first</div>
                </div>
                @if(!$hasSA && $hasLegacy)<span style="margin-left:auto;background:#f59e0b;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Active</span>@endif
                @if($hasSA)<span style="margin-left:auto;background:#e2e8f0;color:#94a3b8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Overridden by v1</span>@endif
            </div>

            @if(!$hasLegacy && !$hasSA)
            <div style="background:#fee2e2;border-radius:10px;padding:12px 14px;margin-bottom:12px;border-left:4px solid #ef4444;">
                <div style="font-size:11px;font-weight:800;color:#b91c1c;margin-bottom:6px;"><i class="fas fa-ban" style="margin-right:5px;"></i>To enable the Legacy Server Key:</div>
                <ol style="font-size:11px;color:#374151;margin:0;padding-left:16px;line-height:1.9;">
                    <li>Go to <a href="https://console.firebase.google.com/project/{{ $config->firebase_project_id ?: 'hotel-crm-e9e34' }}/settings/cloudmessaging" target="_blank" style="color:#7c3aed;font-weight:700;">Cloud Messaging settings</a></li>
                    <li>Find <strong>"Cloud Messaging API (Legacy)"</strong> showing "Disabled"</li>
                    <li>Click the <strong>3-dot ⋮ menu</strong> → <strong>Enable</strong></li>
                    <li>Reload the page — copy the <strong>Server Key</strong> that appears and paste below</li>
                </ol>
            </div>
            @endif

            <label style="font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;display:block;">
                Server Key (Legacy)
                <span style="font-size:10px;font-weight:400;color:#94a3b8;">From: Cloud Messaging → Legacy API → Server Key</span>
            </label>
            <input type="text" name="fcm_server_key" value="{{ old('fcm_server_key', $config->fcm_server_key) }}"
                placeholder="AAAA... (only after enabling Legacy API)"
                style="width:100%;padding:9px 12px;border:2px solid {{ $hasLegacy ? '#f59e0b' : '#e2e8f0' }};border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;background:{{ $hasLegacy ? '#fffbeb' : '#fff' }};outline:none;"
                onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='{{ $hasLegacy ? '#f59e0b' : '#e2e8f0' }}'">
        </div>

        @if($errors->any())
        <div style="background:#fee2e2;border-radius:9px;padding:10px 14px;margin-bottom:14px;color:#b91c1c;font-size:13px;">
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div style="display:flex;gap:10px;align-items:center;">
            <button type="submit" style="padding:10px 24px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(124,58,237,.3);">
                <i class="fas fa-save" style="margin-right:6px;"></i>Save Settings
            </button>
            <a href="{{ route('platform.notifications.send') }}" style="padding:10px 20px;background:#f1f5f9;color:#374151;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-paper-plane" style="color:#7c3aed;"></i>Send Notification
            </a>
            @if($allDone)
            <span style="margin-left:auto;font-size:12px;font-weight:600;color:#15803d;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Ready to send!</span>
            @endif
        </div>
    </form>
</div>

{{-- Quick links --}}
<div style="background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:14px;">
    <div style="font-size:12px;font-weight:800;color:#1e293b;margin-bottom:10px;"><i class="fas fa-external-link-alt" style="color:#7c3aed;margin-right:6px;"></i>Firebase Console Quick Links</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @php $pid = $config->firebase_project_id ?: 'hotel-crm-e9e34'; @endphp
        @foreach([
            ['Service Accounts',  "https://console.firebase.google.com/project/{$pid}/settings/serviceaccounts/adminsdk", 'fas fa-user-shield', '#7c3aed'],
            ['Cloud Messaging',   "https://console.firebase.google.com/project/{$pid}/settings/cloudmessaging",           'fas fa-bell',        '#06b6d4'],
            ['General Settings',  "https://console.firebase.google.com/project/{$pid}/settings/general",                  'fas fa-cog',         '#10b981'],
        ] as [$lbl, $url, $icon, $color])
        <a href="{{ $url }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f8fafc;color:#374151;border:1px solid #e2e8f0;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;"
           onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f8fafc'">
            <i class="{{ $icon }}" style="color:{{ $color }};font-size:11px;"></i>{{ $lbl }}
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
