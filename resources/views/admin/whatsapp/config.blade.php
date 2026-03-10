@extends('layouts.admin')
@section('title', 'WhatsApp Configuration')
@section('page-title', 'WhatsApp Automation')
@section('page-subtitle', 'Configure your WhatsApp messaging provider')

@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-times-circle"></i> {{ session('error') }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    {{-- Main config form --}}
    <div>
        {{-- Provider selector --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
            <div style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px;">Select Provider</div>
            <div style="font-size:13px;color:#94a3b8;margin-bottom:18px;">Choose the WhatsApp API provider you have access to</div>

            @php
            $providers = [
                'meta'      => ['Meta (WhatsApp Business API)', 'fab fa-facebook', '#1877f2', 'Free · Requires Meta Business Verification'],
                'wati'      => ['WATI',                         'fas fa-comment-dots', '#25d366', 'Paid · Fast setup, popular in India'],
                'interakt'  => ['Interakt',                     'fas fa-bolt', '#7c3aed', 'Paid · Popular in India'],
                'gupshup'   => ['Gupshup',                      'fas fa-comments', '#f97316', 'Paid · Large enterprise provider'],
                'twilio'    => ['Twilio',                        'fas fa-phone-alt', '#e11d48', 'Paid · Global, developer-friendly'],
            ];
            $selectedProvider = old('provider', $config->provider ?? 'meta');
            @endphp

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;" id="provider-grid">
                @foreach($providers as $key => [$label, $icon, $color, $hint])
                <label style="cursor:pointer;">
                    <input type="radio" name="provider_select" value="{{ $key }}" {{ $selectedProvider === $key ? 'checked' : '' }} onchange="selectProvider('{{ $key }}')" style="display:none;">
                    <div class="provider-card {{ $selectedProvider === $key ? 'provider-active' : '' }}" id="card-{{ $key }}"
                        style="border:2px solid {{ $selectedProvider === $key ? $color : '#e2e8f0' }};border-radius:14px;padding:14px;text-align:center;transition:all .15s;background:{{ $selectedProvider === $key ? $color.'10' : '#fff' }};">
                        <i class="{{ $icon }}" style="font-size:26px;color:{{ $color }};margin-bottom:8px;display:block;"></i>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $label }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ $hint }}</div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Credentials form --}}
        <form action="{{ route('whatsapp.config.save') }}" method="POST">
            @csrf
            <input type="hidden" name="provider" id="provider-input" value="{{ $selectedProvider }}">

            <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
                <div style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px;">API Credentials</div>
                <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">Enter your provider credentials below</div>

                <div style="display:grid;gap:16px;">
                    <div>
                        <label class="form-label">API Key / Access Token <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="api_key" value="{{ old('api_key', $config->api_key) }}"
                            class="form-input" placeholder="Paste your API key or Bearer token here">
                    </div>

                    <div id="field-phone-number-id" style="display:{{ in_array($selectedProvider, ['meta','wati','gupshup']) ? 'block' : 'none' }};">
                        <label class="form-label" id="label-phone-number-id">Phone Number ID / Server ID</label>
                        <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $config->phone_number_id) }}"
                            class="form-input" placeholder="e.g. 123456789012345">
                    </div>

                    <div id="field-business-account-id" style="display:{{ in_array($selectedProvider, ['meta','twilio']) ? 'block' : 'none' }};">
                        <label class="form-label" id="label-business-account-id">Business Account ID / Account SID</label>
                        <input type="text" name="business_account_id" value="{{ old('business_account_id', $config->business_account_id) }}"
                            class="form-input" placeholder="e.g. WABA ID or Twilio Account SID">
                    </div>

                    <div id="field-webhook-token" style="display:{{ $selectedProvider === 'meta' ? 'block' : 'none' }};">
                        <label class="form-label">Webhook Verify Token</label>
                        <input type="text" name="webhook_verify_token" value="{{ old('webhook_verify_token', $config->webhook_verify_token) }}"
                            class="form-input" placeholder="A random string you set in Meta dashboard">
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                        <div>
                            <div style="font-size:14px;font-weight:700;color:#1e293b;">Activate WhatsApp</div>
                            <div style="font-size:12px;color:#94a3b8;">Enable sending of automated messages</div>
                        </div>
                        <label style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $config->is_active) ? 'checked' : '' }}
                                style="opacity:0;width:0;height:0;" id="toggle-active" onchange="toggleSwitch(this)">
                            <span id="toggle-track" style="position:absolute;inset:0;border-radius:26px;background:{{ old('is_active', $config->is_active) ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                            <span id="toggle-thumb" style="position:absolute;left:{{ old('is_active', $config->is_active) ? '24px' : '2px' }};top:2px;width:22px;height:22px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:left .2s;"></span>
                        </label>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save" style="margin-right:8px;"></i>Save Configuration
                    </button>
                    <a href="{{ route('whatsapp.templates') }}" class="btn-secondary">
                        <i class="fas fa-file-alt" style="margin-right:8px;"></i>Manage Templates
                    </a>
                </div>
            </div>
        </form>

        {{-- Test Send --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px;">
                <i class="fab fa-whatsapp" style="color:#25d366;margin-right:8px;"></i>Send Test Message
            </div>
            <div style="font-size:13px;color:#94a3b8;margin-bottom:18px;">Verify your credentials by sending a real WhatsApp message</div>

            <form action="{{ route('whatsapp.test.send') }}" method="POST">
                @csrf
                <div style="display:grid;gap:14px;">
                    <div>
                        <label class="form-label">Phone Number (with country code)</label>
                        <input type="text" name="phone" class="form-input" placeholder="e.g. 919876543210" value="{{ $config->test_phone ?? '' }}">
                    </div>
                    <div>
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-input" rows="3" placeholder="Hello! This is a test message from Resort CRM.">Hello! This is a test message from your Resort CRM. WhatsApp automation is working!</textarea>
                    </div>
                    <div>
                        <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;">
                            <i class="fab fa-whatsapp"></i> Send Test
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Setup guides sidebar --}}
    <div>
        <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;position:sticky;top:20px;">
            <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:16px;">
                <i class="fas fa-book-open" style="color:#7c3aed;margin-right:8px;"></i>Setup Guide
            </div>

            {{-- Meta guide --}}
            <div class="setup-guide" id="guide-meta" style="display:{{ $selectedProvider === 'meta' ? 'block' : 'none' }};">
                @foreach([
                    ['Create Meta Business Account', 'https://business.facebook.com', 'Go to business.facebook.com and sign up'],
                    ['Set up WhatsApp Business API', 'https://developers.facebook.com/docs/whatsapp', 'Go to Meta for Developers → WhatsApp → API Setup'],
                    ['Get Phone Number ID', 'https://developers.facebook.com/apps', 'Apps → Your App → WhatsApp → API Setup → Phone number ID'],
                    ['Get Access Token', 'https://developers.facebook.com/apps', 'Generate a Permanent Access Token from Graph API Explorer'],
                    ['Approve Message Templates', 'https://business.facebook.com/wa/manage/message-templates/', 'Templates must be approved by Meta before use'],
                ] as $i => [$title, $link, $desc])
                <div style="display:flex;gap:12px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:linear-gradient(135deg,#1877f2,#0d47a1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div>
                        <a href="{{ $link }}" target="_blank" style="font-size:13px;font-weight:700;color:#1877f2;text-decoration:none;">{{ $title }} <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- WATI guide --}}
            <div class="setup-guide" id="guide-wati" style="display:{{ $selectedProvider === 'wati' ? 'block' : 'none' }};">
                @foreach([
                    ['Sign up at WATI', 'https://wati.io', 'Create a WATI account and connect your WhatsApp number'],
                    ['Get API Key', 'https://app.wati.io/settings/api', 'Settings → API → Copy your access token'],
                    ['Get Server Instance', 'https://app.wati.io', 'Your server ID is in your WATI dashboard URL'],
                    ['Create Templates', 'https://app.wati.io/broadcast/templates', 'Create and submit templates for approval'],
                ] as $i => [$title, $link, $desc])
                <div style="display:flex;gap:12px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:linear-gradient(135deg,#25d366,#128c7e);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div>
                        <a href="{{ $link }}" target="_blank" style="font-size:13px;font-weight:700;color:#25d366;text-decoration:none;">{{ $title }} <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Generic guides for other providers --}}
            @foreach(['interakt' => ['#7c3aed','https://app.interakt.ai'], 'gupshup' => ['#f97316','https://www.gupshup.io'], 'twilio' => ['#e11d48','https://console.twilio.com']] as $prov => [$clr, $link])
            <div class="setup-guide" id="guide-{{ $prov }}" style="display:{{ $selectedProvider === $prov ? 'block' : 'none' }};">
                @foreach([
                    ['Sign up / Log in', $link, 'Create or access your account'],
                    ['Find your API Key', $link, 'Look in Settings → API or Developer section'],
                    ['Get your Phone Number ID', $link, 'Copy your WhatsApp sender number or ID'],
                    ['Save & Test', '#', 'Enter credentials above and click Send Test'],
                ] as $i => [$title, $url, $desc])
                <div style="display:flex;gap:12px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:{{ $clr }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div>
                        <a href="{{ $url }}" target="_blank" style="font-size:13px;font-weight:700;color:{{ $clr }};text-decoration:none;">{{ $title }} <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach

            <div style="margin-top:16px;padding:12px 14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                <div style="font-size:12px;color:#64748b;line-height:1.5;">
                    <i class="fas fa-lightbulb" style="color:#f59e0b;margin-right:4px;"></i>
                    <strong>Tip:</strong> All providers require an approved WhatsApp Business account. Free-tier Meta API has limited throughput; paid providers offer better delivery rates.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const providerColors = {
    meta:     '#1877f2', wati: '#25d366', interakt: '#7c3aed',
    gupshup:  '#f97316', twilio: '#e11d48'
};
const phoneFields   = ['meta','wati','gupshup'];
const businessFields= ['meta','twilio'];
const webhookFields = ['meta'];

function selectProvider(key) {
    document.getElementById('provider-input').value = key;
    document.querySelectorAll('.provider-card').forEach(c => {
        c.style.border = '2px solid #e2e8f0';
        c.style.background = '#fff';
    });
    const card = document.getElementById('card-' + key);
    if (card) {
        card.style.border = '2px solid ' + providerColors[key];
        card.style.background = providerColors[key] + '10';
    }
    document.getElementById('field-phone-number-id').style.display   = phoneFields.includes(key)   ? 'block' : 'none';
    document.getElementById('field-business-account-id').style.display = businessFields.includes(key) ? 'block' : 'none';
    document.getElementById('field-webhook-token').style.display      = webhookFields.includes(key) ? 'block' : 'none';
    document.querySelectorAll('.setup-guide').forEach(g => g.style.display = 'none');
    const guide = document.getElementById('guide-' + key);
    if (guide) guide.style.display = 'block';
}

function toggleSwitch(el) {
    const track = document.getElementById('toggle-track');
    const thumb = document.getElementById('toggle-thumb');
    if (el.checked) {
        track.style.background = '#25d366';
        thumb.style.left = '24px';
    } else {
        track.style.background = '#e2e8f0';
        thumb.style.left = '2px';
    }
}
</script>
@endsection
