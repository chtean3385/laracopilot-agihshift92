@extends('layouts.admin')
@section('title', 'WhatsApp Configuration')
@section('page-title', 'WhatsApp Automation')
@section('page-subtitle', 'Connect your WhatsApp provider and start sending automated messages')

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

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

    {{-- ─── Main config form ─── --}}
    <div>
        {{-- Provider selector --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
            <div style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px;">Select Provider</div>
            <div style="font-size:13px;color:#94a3b8;margin-bottom:18px;">Choose your WhatsApp messaging provider</div>

            @php
            $providers = [
                'meta'      => ['Meta (WhatsApp Business API)', 'fab fa-facebook', '#1877f2', 'Free · Requires Meta Business Verification'],
                'wati'      => ['WATI',                         'fas fa-comment-dots', '#25d366', 'Paid · Easiest setup · Popular in India ⚡'],
                'interakt'  => ['Interakt',                     'fas fa-bolt', '#7c3aed', 'Paid · Popular in India'],
                'gupshup'   => ['Gupshup',                      'fas fa-comments', '#f97316', 'Paid · Large enterprise provider'],
                'twilio'    => ['Twilio',                        'fas fa-phone-alt', '#e11d48', 'Paid · Global, developer-friendly'],
            ];
            $selectedProvider = old('provider', $config->provider ?? 'wati');
            @endphp

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;" id="provider-grid">
                @foreach($providers as $key => [$label, $icon, $color, $hint])
                <label style="cursor:pointer;position:relative;">
                    @if($key === 'wati')
                    <span style="position:absolute;top:-8px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;white-space:nowrap;z-index:1;">⚡ RECOMMENDED</span>
                    @endif
                    <input type="radio" name="provider_select" value="{{ $key }}" {{ $selectedProvider === $key ? 'checked' : '' }} onchange="selectProvider('{{ $key }}')" style="display:none;">
                    <div class="provider-card {{ $selectedProvider === $key ? 'provider-active' : '' }}" id="card-{{ $key }}"
                        style="border:2px solid {{ $selectedProvider === $key ? $color : '#e2e8f0' }};border-radius:14px;padding:14px;text-align:center;transition:all .15s;background:{{ $selectedProvider === $key ? $color.'10' : '#fff' }};margin-top:{{ $key === 'wati' ? '8px' : '0' }};">
                        <i class="{{ $icon }}" style="font-size:24px;color:{{ $color }};margin-bottom:7px;display:block;"></i>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $label }}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:3px;line-height:1.3;">{{ $hint }}</div>
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
                <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">
                    Enter the credentials from your provider. Follow the guide on the right if you need help finding them.
                </div>

                <div style="display:grid;gap:16px;">
                    <div>
                        <label class="form-label" id="label-api-key">API Key / Access Token <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="api_key" value="{{ old('api_key', $config->api_key) }}"
                            class="form-input" placeholder="Paste token only — do NOT include 'Bearer ' prefix">
                    </div>

                    <div id="field-phone-number-id" style="display:{{ in_array($selectedProvider, ['meta','wati','gupshup']) ? 'block' : 'none' }};">
                        <label class="form-label" id="label-phone-number-id">
                            @if($selectedProvider === 'wati') WATI Server ID
                            @elseif($selectedProvider === 'gupshup') Phone Number
                            @else Phone Number ID @endif
                        </label>
                        <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $config->phone_number_id) }}"
                            class="form-input" id="input-phone-number-id"
                            placeholder="{{ $selectedProvider === 'wati' ? 'e.g. 10109284 — number from WATI URL' : 'e.g. 15558143257 — numbers only' }}">
                    </div>

                    <div id="field-business-account-id" style="display:{{ in_array($selectedProvider, ['meta','twilio']) ? 'block' : 'none' }};">
                        <label class="form-label" id="label-business-account-id">
                            @if($selectedProvider === 'twilio') Account SID @else WhatsApp Business Account ID @endif
                        </label>
                        <input type="text" name="business_account_id" value="{{ old('business_account_id', $config->business_account_id) }}"
                            class="form-input" placeholder="e.g. WABA ID or Twilio Account SID">
                    </div>

                    <div id="field-webhook-token" style="display:{{ $selectedProvider === 'meta' ? 'block' : 'none' }};">
                        <label class="form-label">Webhook Verify Token <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
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
                        <i class="fas fa-save" style="margin-right:8px;"></i>Save & Activate
                    </button>
                    <a href="{{ route('whatsapp.templates') }}" class="btn-secondary">
                        <i class="fas fa-robot" style="margin-right:8px;"></i>Manage Automations
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

    {{-- ─── Setup Guides Sidebar ─── --}}
    <div>
        <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;position:sticky;top:20px;">

            {{-- ═══ WATI Guide — "Connect in 3 minutes" ═══ --}}
            <div class="setup-guide" id="guide-wati" style="display:{{ $selectedProvider === 'wati' ? 'block' : 'none' }};">
                <div style="background:linear-gradient(135deg,#25d366,#128c7e);border-radius:14px;padding:14px 16px;margin-bottom:18px;color:#fff;">
                    <div style="font-size:14px;font-weight:800;margin-bottom:2px;">⚡ Connect WATI in 3 Minutes</div>
                    <div style="font-size:12px;opacity:.9;">Easiest setup — recommended for India</div>
                </div>

                @php $watiSteps = [
                    ['Sign up at WATI', 'https://wati.io', 'Go to wati.io → Start Free Trial. You get 3 days free with no credit card.', 'fas fa-user-plus'],
                    ['Copy your API Key', 'https://app.wati.io/account/settings/api', 'In WATI: click <strong>Settings</strong> → <strong>API</strong>. Your API key (token) is shown there. Copy it and paste into the API Key field on the left.', 'fas fa-key'],
                    ['Copy your Server ID', 'https://app.wati.io', 'Open your WATI dashboard. Look at the URL bar — you\'ll see a number like <strong>app.wati.io/10109284/...</strong> — that number is your Server ID. Paste it into the Server ID field on the left.', 'fas fa-server'],
                ]; @endphp

                @foreach($watiSteps as $i => [$title, $link, $desc, $icon])
                <div style="display:flex;gap:12px;margin-bottom:16px;align-items:flex-start;">
                    <div style="width:28px;height:28px;background:linear-gradient(135deg,#25d366,#128c7e);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:3px;">
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                            @if($link !== '#')
                            <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#25d366;text-decoration:none;white-space:nowrap;">Open →</a>
                            @endif
                        </div>
                        <div style="font-size:12px;color:#64748b;line-height:1.5;">{!! $desc !!}</div>
                    </div>
                </div>
                @endforeach

                <div style="padding:12px 14px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
                    <div style="font-size:12px;color:#15803d;line-height:1.5;">
                        <i class="fas fa-check-circle" style="margin-right:5px;"></i>
                        <strong>That's it!</strong> Save your credentials above, then go to <a href="{{ route('whatsapp.templates') }}" style="color:#15803d;">Manage Automations</a> to activate message templates.
                    </div>
                </div>
            </div>

            {{-- ═══ META Guide — Two-path wizard ═══ --}}
            <div class="setup-guide" id="guide-meta" style="display:{{ $selectedProvider === 'meta' ? 'block' : 'none' }};">
                <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:14px;">
                    <i class="fab fa-whatsapp" style="color:#25d366;margin-right:6px;"></i>Meta WhatsApp Setup
                </div>

                {{-- Path toggle --}}
                <div style="display:flex;gap:6px;background:#f1f5f9;padding:4px;border-radius:12px;margin-bottom:18px;">
                    <button onclick="setMetaPath('fresh')" id="btn-fresh"
                        style="flex:1;padding:8px;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;background:#1877f2;color:#fff;transition:all .15s;">
                        Starting Fresh
                    </button>
                    <button onclick="setMetaPath('have')" id="btn-have"
                        style="flex:1;padding:8px;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;background:transparent;color:#64748b;transition:all .15s;">
                        I Have an Account
                    </button>
                </div>

                {{-- Path A: Starting Fresh --}}
                <div id="meta-path-fresh">
                    @php $freshSteps = [
                        ['Create a Facebook account', 'https://www.facebook.com/reg/', 'You need a personal Facebook account to access Meta\'s business tools. It\'s free.', 'fab fa-facebook'],
                        ['Create Meta Business Suite', 'https://business.facebook.com/', 'Go to business.facebook.com → click "Create account". Register your hotel as a business. Free.', 'fas fa-building'],
                        ['Register as a Developer', 'https://developers.facebook.com/', 'Go to developers.facebook.com → click "Get Started". Log in with your Facebook account. Free.', 'fas fa-code'],
                        ['Create a Meta App', 'https://developers.facebook.com/apps/', 'Click "Create App" → choose type <strong>Business</strong> → give it any name (e.g. "My Hotel CRM").', 'fas fa-mobile-alt'],
                        ['Add WhatsApp to your App', '#', 'On your app\'s dashboard → click "Add a Product" → find <strong>WhatsApp</strong> → click "Set Up".', 'fab fa-whatsapp'],
                        ['Verify your Phone Number', '#', 'Meta will ask for a phone number. Use your hotel\'s mobile or landline. They send a verification code via SMS or call.', 'fas fa-phone'],
                        ['Get your credentials', 'https://developers.facebook.com/apps/', 'Now switch to "I Have an Account" above — your Phone Number ID and token will be on the WhatsApp → API Setup page.', 'fas fa-key'],
                    ]; @endphp
                    @foreach($freshSteps as $i => [$title, $link, $desc, $icon])
                    <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
                        <div style="width:24px;height:24px;background:linear-gradient(135deg,#1877f2,#0d47a1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;margin-top:1px;">{{ $i+1 }}</div>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:2px;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                                @if($link !== '#')
                                <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#1877f2;text-decoration:none;white-space:nowrap;flex-shrink:0;">Open →</a>
                                @endif
                            </div>
                            <div style="font-size:11px;color:#64748b;line-height:1.5;">{!! $desc !!}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Path B: I have an account --}}
                <div id="meta-path-have" style="display:none;">
                    @php $haveSteps = [
                        ['Open your Meta App', 'https://developers.facebook.com/apps/', 'Go to developers.facebook.com/apps → click on your app name.', 'fas fa-external-link-alt'],
                        ['Go to WhatsApp → API Setup', '#', 'In the left sidebar → click <strong>WhatsApp</strong> → <strong>API Setup</strong>.', 'fas fa-list'],
                        ['Copy Phone Number ID', '#', 'Under the phone number dropdown, you\'ll see a number labeled <strong>"Phone number ID"</strong>. Copy it → paste in the <em>Phone Number ID</em> field on the left.', 'fas fa-hashtag'],
                        ['Copy Access Token', '#', 'Scroll down → <strong>"Temporary access token"</strong> → click <strong>Generate</strong> → copy the long string → paste in the <em>API Key</em> field.', 'fas fa-key'],
                        ['Copy Business Account ID', '#', 'At the top of the page: <strong>"WhatsApp Business Account ID"</strong> → copy it → paste in <em>Business Account ID</em> on the left.', 'fas fa-briefcase'],
                        ['Save & Test', '#', 'Click <strong>Save & Activate</strong> on the left, then use the <strong>Send Test</strong> section below to verify everything works.', 'fas fa-check-circle'],
                    ]; @endphp
                    @foreach($haveSteps as $i => [$title, $link, $desc, $icon])
                    <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
                        <div style="width:24px;height:24px;background:linear-gradient(135deg,#1877f2,#0d47a1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;margin-top:1px;">{{ $i+1 }}</div>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:2px;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                                @if($link !== '#')
                                <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#1877f2;text-decoration:none;white-space:nowrap;flex-shrink:0;">Open →</a>
                                @endif
                            </div>
                            <div style="font-size:11px;color:#64748b;line-height:1.5;">{!! $desc !!}</div>
                        </div>
                    </div>
                    @endforeach

                    <div style="padding:10px 12px;background:#eff6ff;border-radius:10px;border:1px solid #bfdbfe;margin-top:4px;">
                        <div style="font-size:11px;color:#1d4ed8;line-height:1.5;">
                            <i class="fas fa-lightbulb" style="margin-right:4px;"></i>
                            <strong>Permanent token tip:</strong> Temporary tokens expire in 24h. For a permanent token: Meta Business Manager → Settings → System Users → Add → Generate Token with <code>whatsapp_business_messaging</code> permission.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══ INTERAKT Guide ═══ --}}
            <div class="setup-guide" id="guide-interakt" style="display:{{ $selectedProvider === 'interakt' ? 'block' : 'none' }};">
                <div style="font-size:15px;font-weight:800;color:#7c3aed;margin-bottom:14px;"><i class="fas fa-bolt" style="margin-right:6px;"></i>Interakt Setup</div>
                @php $steps = [
                    ['Sign up at Interakt', 'https://app.interakt.ai', 'Create an Interakt account and connect your WhatsApp Business number.'],
                    ['Get your API Key', 'https://app.interakt.ai/settings', 'Settings → Developers → copy your API Key.'],
                    ['Paste here & Save', '#', 'Enter the API Key in the field on the left → click Save & Activate.'],
                    ['Create Templates', 'https://app.interakt.ai/templates', 'In Interakt, create and submit templates for Meta approval.'],
                ]; @endphp
                @foreach($steps as $i => [$title, $link, $desc])
                <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:#7c3aed;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:2px;">
                            <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                            @if($link !== '#')
                            <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#7c3aed;text-decoration:none;">Open →</a>
                            @endif
                        </div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ═══ GUPSHUP Guide ═══ --}}
            <div class="setup-guide" id="guide-gupshup" style="display:{{ $selectedProvider === 'gupshup' ? 'block' : 'none' }};">
                <div style="font-size:15px;font-weight:800;color:#f97316;margin-bottom:14px;"><i class="fas fa-comments" style="margin-right:6px;"></i>Gupshup Setup</div>
                @php $steps = [
                    ['Sign up at Gupshup', 'https://www.gupshup.io', 'Create a Gupshup account and set up your WhatsApp sender.'],
                    ['Create a WhatsApp App', 'https://www.gupshup.io', 'In the Gupshup dashboard, create a new app → choose WhatsApp channel.'],
                    ['Get API Key & Phone', 'https://www.gupshup.io', 'From your app settings, copy the API Key and your registered WhatsApp number.'],
                    ['Paste here & Save', '#', 'Enter API Key and phone number in the fields on the left → Save & Activate.'],
                ]; @endphp
                @foreach($steps as $i => [$title, $link, $desc])
                <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:#f97316;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:2px;">
                            <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                            @if($link !== '#')
                            <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#f97316;text-decoration:none;">Open →</a>
                            @endif
                        </div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ═══ TWILIO Guide ═══ --}}
            <div class="setup-guide" id="guide-twilio" style="display:{{ $selectedProvider === 'twilio' ? 'block' : 'none' }};">
                <div style="font-size:15px;font-weight:800;color:#e11d48;margin-bottom:14px;"><i class="fas fa-phone-alt" style="margin-right:6px;"></i>Twilio Setup</div>
                @php $steps = [
                    ['Sign up at Twilio', 'https://console.twilio.com', 'Create a Twilio account and add the WhatsApp Sandbox or a dedicated number.'],
                    ['Get Account SID', 'https://console.twilio.com', 'Dashboard → copy your Account SID → paste in Business Account ID field.'],
                    ['Get Auth Token', 'https://console.twilio.com', 'Dashboard → copy your Auth Token → paste in API Key field.'],
                    ['Enable WhatsApp Sender', 'https://console.twilio.com/us1/develop/sms/senders/whatsapp-senders', 'Set up a WhatsApp-enabled Twilio number.'],
                ]; @endphp
                @foreach($steps as $i => [$title, $link, $desc])
                <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
                    <div style="width:24px;height:24px;background:#e11d48;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;flex-shrink:0;">{{ $i+1 }}</div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:2px;">
                            <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $title }}</div>
                            @if($link !== '#')
                            <a href="{{ $link }}" target="_blank" style="font-size:11px;color:#e11d48;text-decoration:none;">Open →</a>
                            @endif
                        </div>
                        <div style="font-size:11px;color:#64748b;line-height:1.5;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            <div style="margin-top:14px;padding:11px 14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                <div style="font-size:11px;color:#64748b;line-height:1.5;">
                    <i class="fas fa-lightbulb" style="color:#f59e0b;margin-right:4px;"></i>
                    <strong>Tip:</strong> All providers require an approved WhatsApp Business account. After saving credentials, go to <a href="{{ route('whatsapp.templates') }}" style="color:#7c3aed;">Manage Automations</a> to activate message templates.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const providerColors = {
    meta:'#1877f2', wati:'#25d366', interakt:'#7c3aed', gupshup:'#f97316', twilio:'#e11d48'
};
const phoneFields    = ['meta','wati','gupshup'];
const businessFields = ['meta','twilio'];
const webhookFields  = ['meta'];
const fieldLabels    = {
    wati:     { phone: 'WATI Server ID', phonePlaceholder: 'e.g. 10109284 — number from WATI URL', apiKey: 'API Key / Access Token', biz: '' },
    meta:     { phone: 'Phone Number ID', phonePlaceholder: 'e.g. 15558143257 — numbers only', apiKey: 'API Key / Access Token', biz: 'WhatsApp Business Account ID' },
    gupshup:  { phone: 'Phone Number', phonePlaceholder: 'e.g. 919876543210', apiKey: 'API Key', biz: '' },
    twilio:   { phone: '', phonePlaceholder: '', apiKey: 'Auth Token', biz: 'Account SID' },
    interakt: { phone: '', phonePlaceholder: '', apiKey: 'API Key', biz: '' },
};

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

    const labels = fieldLabels[key] || {};
    const phoneLabel = document.getElementById('label-phone-number-id');
    const phoneInput = document.getElementById('input-phone-number-id');
    if (phoneLabel && labels.phone) { phoneLabel.textContent = labels.phone; }
    if (phoneInput && labels.phonePlaceholder) { phoneInput.placeholder = labels.phonePlaceholder; }

    document.getElementById('field-phone-number-id').style.display   = phoneFields.includes(key) ? 'block' : 'none';
    document.getElementById('field-business-account-id').style.display = businessFields.includes(key) ? 'block' : 'none';
    document.getElementById('field-webhook-token').style.display      = webhookFields.includes(key) ? 'block' : 'none';

    document.querySelectorAll('.setup-guide').forEach(g => g.style.display = 'none');
    const guide = document.getElementById('guide-' + key);
    if (guide) guide.style.display = 'block';
}

function setMetaPath(path) {
    const freshEl = document.getElementById('meta-path-fresh');
    const haveEl  = document.getElementById('meta-path-have');
    const btnF    = document.getElementById('btn-fresh');
    const btnH    = document.getElementById('btn-have');

    if (path === 'fresh') {
        freshEl.style.display = 'block';
        haveEl.style.display  = 'none';
        btnF.style.background = '#1877f2';
        btnF.style.color      = '#fff';
        btnH.style.background = 'transparent';
        btnH.style.color      = '#64748b';
    } else {
        freshEl.style.display = 'none';
        haveEl.style.display  = 'block';
        btnH.style.background = '#1877f2';
        btnH.style.color      = '#fff';
        btnF.style.background = 'transparent';
        btnF.style.color      = '#64748b';
    }
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
