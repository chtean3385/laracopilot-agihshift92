@extends('layouts.platform')
@section('title', 'Firebase Push Settings')

@section('content')

<div style="max-width:720px;">

<div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fas fa-bell" style="color:#7c3aed;margin-right:8px;"></i>Push Notification Settings
        </h1>
        <p style="color:#6b7280;font-size:13px;margin:0;">Configure Firebase Cloud Messaging to send push notifications to hotel users.</p>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:11px;padding:13px 16px;margin-bottom:18px;color:#15803d;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle" style="margin-right:7px;"></i>{{ session('success') }}
</div>
@endif

<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-bottom:22px;">
    <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:6px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>Setup Guide</div>
    <ol style="margin:0;padding-left:18px;font-size:13px;color:#78350f;line-height:1.8;">
        <li>Create a Firebase project at <a href="https://console.firebase.google.com" target="_blank" style="color:#7c3aed;">console.firebase.google.com</a></li>
        <li>Enable Cloud Messaging and add a Web App to get the config values below</li>
        <li>Generate a VAPID key from Project Settings → Cloud Messaging → Web configuration</li>
        <li>Get the Server Key from Project Settings → Cloud Messaging → Cloud Messaging API (Legacy)</li>
        <li>Place <code style="background:#fff;padding:1px 6px;border-radius:4px;">firebase-messaging-sw.js</code> in the <code style="background:#fff;padding:1px 6px;border-radius:4px;">public/</code> folder (already done)</li>
    </ol>
</div>

<div style="background:#fff;border-radius:16px;padding:24px 26px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
    <form action="{{ route('platform.notifications.settings.save') }}" method="POST">
        @csrf

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
            <div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;">Enable Push Notifications</div>
                <div style="font-size:12px;color:#94a3b8;">When enabled, hotel users will see a browser permission prompt.</div>
            </div>
            <label style="position:relative;display:inline-flex;align-items:center;cursor:pointer;">
                <input type="hidden" name="push_enabled" value="0">
                <input type="checkbox" name="push_enabled" value="1" {{ $config->push_enabled ? 'checked' : '' }}
                    style="width:40px;height:22px;accent-color:#7c3aed;">
            </label>
        </div>

        @php
        $fields = [
            ['firebase_project_id',          'Project ID',          'myapp-12345'],
            ['firebase_api_key',              'API Key',             'AIzaSy...'],
            ['firebase_messaging_sender_id',  'Messaging Sender ID', '123456789'],
            ['firebase_app_id',               'App ID',              '1:123:web:abc123'],
            ['firebase_vapid_key',            'VAPID Key',           'BPsb...'],
            ['fcm_server_key',                'Server Key (Legacy)', 'AAAA...'],
        ];
        @endphp

        @foreach($fields as [$field, $label, $placeholder])
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">{{ $label }}</label>
            <input type="text" name="{{ $field }}" value="{{ old($field, $config->$field) }}"
                placeholder="{{ $placeholder }}"
                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;box-sizing:border-box;">
        </div>
        @endforeach

        @if($errors->any())
        <div style="background:#fee2e2;border-radius:9px;padding:10px 14px;margin-bottom:14px;color:#b91c1c;font-size:13px;">
            <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div style="display:flex;gap:10px;margin-top:4px;">
            <button type="submit" style="padding:10px 22px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-save" style="margin-right:6px;"></i>Save Settings
            </button>
            <a href="{{ route('platform.notifications.send') }}" style="padding:10px 22px;background:#f1f5f9;color:#374151;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-paper-plane"></i> Send Notification
            </a>
        </div>
    </form>
</div>

{{-- Token Stats --}}
@php
$totalTokens = DB::table('fcm_tokens')->count();
$webTokens   = DB::table('fcm_tokens')->where('platform','web')->count();
$androidTokens = DB::table('fcm_tokens')->where('platform','android')->count();
@endphp
<div style="background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-top:18px;">
    <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:12px;"><i class="fas fa-mobile-alt" style="color:#06b6d4;margin-right:6px;"></i>Registered Devices</div>
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
        @foreach([['Total Devices',$totalTokens,'#7c3aed'],['Web',$webTokens,'#06b6d4'],['Android',$androidTokens,'#10b981']] as [$label,$val,$color])
        <div style="text-align:center;min-width:80px;">
            <div style="font-size:24px;font-weight:900;color:{{ $color }};">{{ $val }}</div>
            <div style="font-size:11px;color:#94a3b8;">{{ $label }}</div>
        </div>
        @endforeach
    </div>
</div>

</div>
@endsection
