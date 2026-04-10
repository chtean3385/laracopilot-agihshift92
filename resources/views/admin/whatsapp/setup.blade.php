@extends('layouts.admin')
@section('title', 'WhatsApp Setup')
@section('page-title', 'WhatsApp Setup')
@section('page-subtitle', 'Connect WhatsApp to send automated messages to your guests')

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

@php
$setupStep  = (int) ($config->setup_step ?? 0);
$setupMode  = $config->mode ?? 'shared';
$inProgress = !$config->setup_completed && $setupStep > 0 && $setupMode === 'own';
@endphp

@if(!$moduleActive)
{{-- Module disabled --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:48px;text-align:center;max-width:600px;margin:40px auto;">
    <div style="width:72px;height:72px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="fab fa-whatsapp" style="font-size:32px;color:#9ca3af;"></i>
    </div>
    <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:8px;">WhatsApp Module is Disabled</h2>
    <p style="color:#6b7280;font-size:15px;">The WhatsApp module is not enabled for your account. Please contact support or enable it from Modules settings.</p>
    <a href="{{ route('modules.index') }}" style="display:inline-block;margin-top:20px;padding:10px 24px;background:#25D366;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">Go to Modules</a>
</div>

@elseif($config->setup_completed)
{{-- Already connected --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;max-width:700px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#25D366,#128C7E);border-radius:14px;display:flex;align-items:center;justify-content:center;">
            <i class="fab fa-whatsapp" style="font-size:28px;color:#fff;"></i>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;">
                <h2 style="font-size:20px;font-weight:700;color:#111827;margin:0;">WhatsApp Connected</h2>
                <span style="background:#dcfce7;color:#15803d;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;">ACTIVE</span>
            </div>
            <p style="color:#6b7280;margin:4px 0 0;font-size:14px;">
                Mode: <strong>{{ ($config->mode ?? 'shared') === 'shared' ? 'CRM Shared Number' : 'Your Hotel\'s Own Number' }}</strong>
            </p>
        </div>
    </div>

    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-check-circle" style="color:#16a34a;font-size:18px;"></i>
            <div>
                @if(($config->mode ?? 'shared') === 'shared')
                <div style="font-weight:600;color:#15803d;font-size:14px;">Using CRM's Shared WhatsApp Number</div>
                <div style="color:#166534;font-size:13px;margin-top:2px;">Messages are sent from the CRM's verified business number. No extra setup needed.</div>
                @else
                <div style="font-weight:600;color:#15803d;font-size:14px;">Your Hotel's Own Number Connected</div>
                <div style="color:#166534;font-size:13px;margin-top:2px;">Guests will see your hotel's number on their WhatsApp messages.</div>
                @endif
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        @if($canUseOwn)
        <a href="{{ route('whatsapp.templates') }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#25D366;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;">
            <i class="fas fa-robot"></i> Manage Automations
        </a>
        @else
        <div style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;">
            <i class="fas fa-info-circle"></i>
            Message templates for your plan are managed by the CRM Administrator.
            <a href="{{ route('upgrade') }}" style="color:#0d9488;font-weight:700;margin-left:4px;white-space:nowrap;">Upgrade to Pro →</a>
        </div>
        @endif
        <form method="POST" action="{{ route('whatsapp.setup.reset') }}" onsubmit="return confirm('This will disconnect WhatsApp and delete all setup progress. Are you sure?')">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
                <i class="fas fa-redo"></i> Reconnect / Change Mode
            </button>
        </form>
    </div>

    @if(($config->mode ?? 'shared') === 'shared')
    {{-- ── Test Shared Number ── --}}
    <div style="margin-top:24px;border-top:1px solid #e5e7eb;padding-top:24px;">
        <div style="font-weight:700;color:#111827;font-size:15px;margin-bottom:6px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-flask" style="color:#7c3aed;"></i> Test Shared Number
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">
            Send the <strong>hello_world</strong> template to verify the shared number is working correctly.
        </p>

        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Phone Number (with country code)</label>
                <input type="tel" id="testPhone" placeholder="e.g. 9876543210 or +919876543210"
                    style="width:100%;border:1.5px solid #d1d5db;border-radius:10px;padding:10px 14px;font-size:14px;color:#111827;outline:none;transition:border-color .2s;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#d1d5db'">
            </div>
            <button onclick="sendTestMessage()"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;">
                <i class="fas fa-paper-plane"></i> Send Test
            </button>
        </div>

        {{-- Result banner --}}
        <div id="testResult" style="display:none;margin-top:14px;padding:12px 16px;border-radius:10px;font-size:14px;font-weight:600;align-items:center;gap:10px;"></div>

        {{-- Template preview --}}
        <div style="margin-top:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;">
            <div style="font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
                Template Preview — hello_world
            </div>
            <div style="background:#fff;border-radius:8px;padding:12px 16px;font-size:13px;color:#111827;line-height:1.6;box-shadow:0 1px 4px rgba(0,0,0,.06);max-width:380px;">
                <strong>Hello World</strong><br>
                Welcome and congratulations!! This message demonstrates your ability to send a WhatsApp message notification from the Cloud API, hosted by Meta. Thank you for taking the time to test with us.
            </div>
            <div style="font-size:11px;color:#6b7280;margin-top:6px;">
                <i class="fas fa-info-circle"></i> This is a Meta pre-approved template. No variables required.
            </div>
        </div>
    </div>

    <script>
    function sendTestMessage() {
        const phone  = document.getElementById('testPhone').value.trim();
        const result = document.getElementById('testResult');

        if (!phone || phone.replace(/[^0-9]/g, '').length < 10) {
            result.style.display = 'flex';
            result.style.background = '#fef2f2';
            result.style.border = '1px solid #fca5a5';
            result.style.color = '#b91c1c';
            result.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid phone number (at least 10 digits).';
            return;
        }

        result.style.display = 'flex';
        result.style.background = '#f8fafc';
        result.style.border = '1px solid #e2e8f0';
        result.style.color = '#475569';
        result.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';

        fetch('{{ route("whatsapp.setup.test-shared") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ phone }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                result.style.background = '#f0fdf4';
                result.style.border = '1px solid #86efac';
                result.style.color = '#15803d';
                result.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            } else {
                result.style.background = '#fef2f2';
                result.style.border = '1px solid #fca5a5';
                result.style.color = '#b91c1c';
                result.innerHTML = '<i class="fas fa-times-circle"></i> ' + (data.error || 'Something went wrong.');
            }
        })
        .catch(() => {
            result.style.background = '#fef2f2';
            result.style.border = '1px solid #fca5a5';
            result.style.color = '#b91c1c';
            result.innerHTML = '<i class="fas fa-times-circle"></i> Network error. Please try again.';
        });
    }

    document.getElementById('testPhone').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') sendTestMessage();
    });
    </script>
    @endif
</div>

@elseif($inProgress)
{{-- In-progress: own-mode setup interrupted mid-way — show resumable state --}}
<div style="max-width:700px;margin:0 auto;">

    <div style="background:#fffbeb;border:2px solid #fde68a;border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;">
        <i class="fas fa-info-circle" style="color:#d97706;font-size:18px;margin-top:2px;"></i>
        <div>
            <div style="font-weight:700;color:#92400e;font-size:14px;margin-bottom:4px;">Setup Was Interrupted</div>
            <div style="color:#78350f;font-size:13px;line-height:1.6;">Your WhatsApp setup started but wasn't completed. We've saved your progress — click <strong>Continue Setup</strong> to pick up where you left off.</div>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:20px;">
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">Setup Progress</h3>

        @php
        $steps = [
            1 => ['label' => 'Credentials verified', 'sublabel' => 'Access token obtained from Meta'],
            2 => ['label' => 'Webhook configured', 'sublabel' => 'Delivery notifications set up'],
            3 => ['label' => 'Message templates submitted', 'sublabel' => 'Templates sent to Meta for approval'],
        ];
        @endphp

        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px;">
            @foreach($steps as $n => $step)
            @php
            $done    = $setupStep >= $n;
            $current = $setupStep === $n - 1;
            @endphp
            <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:{{ $done ? '#f0fdf4' : ($current ? '#fefce8' : '#f9fafb') }};border-radius:10px;border:1px solid {{ $done ? '#86efac' : ($current ? '#fde68a' : '#e5e7eb') }};">
                <div style="width:36px;height:36px;border-radius:50%;background:{{ $done ? '#dcfce7' : ($current ? '#fef3c7' : '#e5e7eb') }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    @if($done)
                    <i class="fas fa-check" style="color:#16a34a;"></i>
                    @elseif($current)
                    <i class="fas fa-clock" style="color:#d97706;"></i>
                    @else
                    <i class="fas fa-circle" style="color:#d1d5db;font-size:8px;"></i>
                    @endif
                </div>
                <div>
                    <div style="font-weight:600;font-size:14px;color:{{ $done ? '#15803d' : ($current ? '#92400e' : '#9ca3af') }};">{{ $step['label'] }}</div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $step['sublabel'] }}</div>
                </div>
                @if($current)
                <span style="margin-left:auto;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">NEXT</span>
                @endif
            </div>
            @endforeach
        </div>

        <div style="display:flex;gap:12px;">
            <button onclick="continueSetup()" id="btnContinue"
                style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
                <i class="fas fa-play"></i> Continue Setup
            </button>
            <form method="POST" action="{{ route('whatsapp.setup.reset') }}" onsubmit="return confirm('This will delete all setup progress. Are you sure?')">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">
                    <i class="fas fa-times"></i> Start Over
                </button>
            </form>
        </div>
    </div>

    {{-- Progress display for continue action --}}
    <div id="progressSection" style="display:none;background:#fff;border:2px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:24px;">
        <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 20px;display:flex;align-items:center;gap:10px;">
            <span id="progressTitle">Continuing Setup...</span>
        </h3>
        <div style="display:flex;flex-direction:column;gap:12px;" id="resumeStepsList">
            @foreach($steps as $n => $step)
            @php $done = $setupStep >= $n; @endphp
            <div class="resume-step" id="res-step-{{ $n }}" style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:{{ $done ? '#f0fdf4' : '#f9fafb' }};border-radius:10px;">
                <div class="step-icon" style="width:36px;height:36px;border-radius:50%;background:{{ $done ? '#dcfce7' : '#e5e7eb' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    @if($done)
                    <i class="fas fa-check" style="color:#16a34a;"></i>
                    @else
                    <i class="fas fa-circle" style="color:#d1d5db;font-size:8px;"></i>
                    @endif
                </div>
                <div>
                    <div style="font-weight:600;font-size:14px;color:{{ $done ? '#15803d' : '#374151' }};">{{ $step['label'] }}</div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $step['sublabel'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Error panel for resume --}}
    <div id="errorPanel" style="display:none;background:#fff;border:2px solid #fca5a5;border-radius:16px;padding:24px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="width:40px;height:40px;background:#fee2e2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-times-circle" style="color:#dc2626;font-size:18px;"></i>
            </div>
            <div style="flex:1;">
                <h4 style="font-size:15px;font-weight:700;color:#b91c1c;margin:0 0 6px;">Step Failed</h4>
                <p id="errorMessage" style="color:#991b1b;font-size:14px;margin:0 0 14px;line-height:1.6;"></p>
                <button onclick="continueSetup()" style="padding:9px 18px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        </div>
    </div>
</div>

@else
{{-- Fresh start: mode selection --}}
<style>
@media (max-width: 640px) {
    .mode-cards-grid { grid-template-columns: 1fr !important; }
}
</style>
<div style="max-width:780px;margin:0 auto;">
    <p style="color:#6b7280;font-size:15px;margin-bottom:28px;">Choose how you want to use WhatsApp. You can change this later.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px;" id="modeCards" class="mode-cards-grid">

        {{-- Option 1: Shared --}}
        <div style="background:#fff;border:2px solid #e5e7eb;border-radius:16px;padding:28px;position:relative;transition:border-color .2s;" id="card-shared">
            <div style="position:absolute;top:16px;right:16px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">FREE</div>
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#25D366,#128C7E);border-radius:13px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fab fa-whatsapp" style="font-size:26px;color:#fff;"></i>
            </div>
            <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 8px;">Use CRM's WhatsApp Number</h3>
            <p style="color:#6b7280;font-size:13px;margin:0 0 16px;line-height:1.6;">Instant activation. Messages are sent from the CRM's verified business number. Perfect for getting started quickly.</p>
            <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:8px;">
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#25D366;"></i> Ready in one click</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#25D366;"></i> No credentials to enter</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#25D366;"></i> Pre-approved templates</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#25D366;"></i> Included in all plans</li>
            </ul>
            @if($saasReady)
            <button onclick="activateShared()" id="btn-shared"
                style="width:100%;padding:12px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
                Activate Now — One Click
            </button>
            @else
            <div style="width:100%;padding:12px;background:#f3f4f6;color:#9ca3af;border-radius:10px;font-weight:600;font-size:13px;text-align:center;">
                <i class="fas fa-clock"></i> Coming Soon — Contact Support
            </div>
            @endif
        </div>

        {{-- Option 2: Own Number --}}
        <div style="background:#fff;border:2px solid #e5e7eb;border-radius:16px;padding:28px;position:relative;" id="card-own">
            @if($canUseOwn)
            <div style="position:absolute;top:16px;right:16px;background:#ede9fe;color:#7c3aed;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">PRO</div>
            @else
            <div style="position:absolute;top:16px;right:16px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">UPGRADE</div>
            @endif
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:13px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fas fa-phone-alt" style="font-size:22px;color:#fff;"></i>
            </div>
            <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 8px;">Connect Your Hotel's Own Number</h3>
            <p style="color:#6b7280;font-size:13px;margin:0 0 16px;line-height:1.6;">Guests see your hotel's own WhatsApp number. Full brand identity. Setup takes 2–3 minutes entirely inside the CRM.</p>
            <ul style="list-style:none;padding:0;margin:0 0 20px;display:flex;flex-direction:column;gap:8px;">
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#7c3aed;"></i> Your number in guest messages</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#7c3aed;"></i> Full WhatsApp Business profile</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#7c3aed;"></i> Automated template approval</li>
                <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;"><i class="fas fa-check-circle" style="color:#7c3aed;"></i> No external dashboards</li>
            </ul>
            @if($canUseOwn && $embeddedSignupReady)
            <button onclick="showEmbeddedSignup()" id="btn-own"
                style="width:100%;padding:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
                Start Setup
            </button>
            @elseif($canUseOwn && !$embeddedSignupReady)
            <div style="width:100%;padding:12px;background:#f3f4f6;color:#9ca3af;border-radius:10px;font-weight:600;font-size:13px;text-align:center;">
                <i class="fas fa-clock"></i> Coming Soon — Contact Support
            </div>
            @else
            <div style="width:100%;padding:12px;background:#fef3c7;color:#92400e;border-radius:10px;font-weight:600;font-size:13px;text-align:center;">
                <i class="fas fa-lock"></i> Upgrade to Pro to unlock
            </div>
            @endif
        </div>
    </div>

    {{-- Embedded Signup Panel --}}
    <div id="embeddedSignupSection" style="display:none;background:#fff;border:2px solid #7c3aed;border-radius:16px;padding:28px;margin-bottom:24px;">
        <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 8px;display:flex;align-items:center;gap:10px;">
            <i class="fab fa-whatsapp" style="color:#25D366;"></i> Connect Your WhatsApp Business Account
        </h3>
        <p style="color:#6b7280;font-size:14px;margin:0 0 20px;line-height:1.7;">
            Click the button below. A small window will appear — log in with the Facebook account linked to your WhatsApp Business Manager. Select your business and phone number. The window will close automatically and we'll do the rest.
        </p>
        <div style="background:#f8f7ff;border:1px solid #ede9fe;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
            <div style="font-size:13px;color:#5b21b6;font-weight:600;margin-bottom:6px;"><i class="fas fa-info-circle"></i> Before you start</div>
            <ul style="margin:0;padding-left:18px;color:#6b7280;font-size:13px;line-height:1.8;">
                <li>You need a Facebook account connected to your WhatsApp Business Manager</li>
                <li>Your WhatsApp Business number should not be in use on a personal WhatsApp app</li>
                <li>Make sure popups are allowed in your browser for this page</li>
            </ul>
        </div>
        <button id="btn-embedded" onclick="launchEmbeddedSignup()"
            style="display:inline-flex;align-items:center;gap:12px;padding:14px 28px;background:#25D366;color:#fff;border:none;border-radius:12px;font-weight:700;font-size:15px;cursor:pointer;">
            <i class="fab fa-whatsapp" style="font-size:20px;"></i> Connect WhatsApp Business Account
        </button>
        <button onclick="hideEmbeddedSignup()" style="margin-left:12px;padding:14px 20px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;">Cancel</button>
    </div>

    {{-- Auto-processing progress --}}
    <div id="progressSection" style="display:none;background:#fff;border:2px solid #e5e7eb;border-radius:16px;padding:28px;margin-bottom:24px;">
        <h3 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 20px;display:flex;align-items:center;gap:10px;">
            <span id="progressTitle">Setting Up Your WhatsApp...</span>
        </h3>
        <div style="display:flex;flex-direction:column;gap:12px;" id="stepsList">
            @foreach([1 => 'Verifying your credentials', 2 => 'Configuring webhook', 3 => 'Submitting message templates'] as $n => $label)
            <div class="setup-step" id="step-{{ $n }}" style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:#f9fafb;border-radius:10px;opacity:{{ $n === 1 ? '1' : '0.4' }};">
                <div class="step-icon" style="width:36px;height:36px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    @if($n === 1)
                    <i class="fas fa-circle-notch fa-spin" style="color:#6b7280;"></i>
                    @else
                    <i class="fas fa-circle" style="color:#d1d5db;font-size:8px;"></i>
                    @endif
                </div>
                <div style="font-weight:600;font-size:14px;color:#374151;">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Number conflict panel --}}
    <div id="conflictPanel" style="display:none;background:#fff;border:2px solid #fbbf24;border-radius:16px;padding:28px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706;font-size:20px;"></i>
            </div>
            <div style="flex:1;">
                <h4 style="font-size:16px;font-weight:700;color:#92400e;margin:0 0 8px;">This Number Is Already on WhatsApp</h4>
                <p style="color:#78350f;font-size:14px;margin:0 0 16px;line-height:1.6;">The number you selected is currently linked to a WhatsApp personal or business account. To use it with the WhatsApp Business API, you need to remove it from WhatsApp first.</p>
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:16px;margin-bottom:16px;">
                    <div style="font-weight:700;color:#92400e;font-size:13px;margin-bottom:10px;">Steps to remove the number from WhatsApp:</div>
                    <ol style="margin:0;padding-left:18px;color:#78350f;font-size:13px;line-height:2;">
                        <li>Open <strong>WhatsApp</strong> on the phone that uses this number</li>
                        <li>Tap <strong>Settings → Account → Delete My Account</strong></li>
                        <li>Select your country code, enter the number, and confirm deletion</li>
                        <li>Wait <strong>at least 24 hours</strong> (Meta requires this)</li>
                        <li>Come back here and click <strong>Try Again</strong></li>
                    </ol>
                </div>
                <p style="color:#78350f;font-size:12px;margin:0 0 16px;"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Deleting your WhatsApp account does NOT delete your phone number. You keep the number — it just gets removed from WhatsApp.</p>
                <div style="display:flex;gap:12px;">
                    <button onclick="retryEmbeddedSignup()" style="padding:10px 20px;background:#25D366;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;"><i class="fas fa-redo"></i> Try Again</button>
                    <button onclick="document.getElementById('conflictPanel').style.display='none';document.getElementById('modeCards').style.display='grid';" style="padding:10px 20px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">Use a Different Number</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Error panel --}}
    <div id="errorPanel" style="display:none;background:#fff;border:2px solid #fca5a5;border-radius:16px;padding:24px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="width:40px;height:40px;background:#fee2e2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-times-circle" style="color:#dc2626;font-size:18px;"></i>
            </div>
            <div style="flex:1;">
                <h4 style="font-size:15px;font-weight:700;color:#b91c1c;margin:0 0 6px;">Something Went Wrong</h4>
                <p id="errorMessage" style="color:#991b1b;font-size:14px;margin:0 0 14px;line-height:1.6;"></p>
                <div style="display:flex;gap:10px;">
                    <button id="retryBtn" onclick="retryFromError()" style="padding:9px 18px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;"><i class="fas fa-redo"></i> Try Again</button>
                    <button onclick="resetAll()" style="padding:9px 18px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;">Start Over</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endif

<script>
const META_APP_ID    = '{{ $platform?->meta_app_id ?? '' }}';
const META_CONFIG_ID = '{{ $platform?->meta_config_id ?? '' }}';
const CSRF           = '{{ csrf_token() }}';
const SAVED_STEP     = {{ $setupStep }};

let embeddedCode    = null;
let embeddedWabaId  = null;
let embeddedPhoneId = null;
let failedStep      = null;

function activateShared() {
    const btn = document.getElementById('btn-shared');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Activating...';
    fetch('{{ route("whatsapp.setup.activate-shared") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { btn.disabled = false; btn.innerHTML = 'Activate Now — One Click'; showError(data.error, null); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = 'Activate Now — One Click'; showError('Could not reach the server. Check your internet connection and try again.', null); });
}

function showEmbeddedSignup() {
    document.getElementById('modeCards').style.display = 'none';
    document.getElementById('embeddedSignupSection').style.display = 'block';
}

function hideEmbeddedSignup() {
    document.getElementById('embeddedSignupSection').style.display = 'none';
    document.getElementById('modeCards').style.display = 'grid';
}

// Listen for WABA/phone IDs delivered via postMessage from Meta popup
(function() {
    window.addEventListener('message', function(event) {
        var origin = event.origin || '';
        if (origin !== 'https://www.facebook.com' && origin !== 'https://web.facebook.com') return;
        try {
            var data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            if (data && data.type === 'WA_EMBEDDED_SIGNUP') {
                if (data.event === 'FINISH' && data.data) {
                    embeddedWabaId  = data.data.waba_id || null;
                    embeddedPhoneId = data.data.phone_number_id || null;
                }
            }
        } catch (e) {}
    });
})();

function launchEmbeddedSignup() {
    if (!META_APP_ID || !META_CONFIG_ID) {
        showError('WhatsApp is not fully configured by the platform. Please contact support.', null);
        return;
    }
    const btn = document.getElementById('btn-embedded');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Opening...';

    if (typeof FB === 'undefined') {
        showError('The Facebook connection tool could not be loaded. Please disable any ad blockers for this page and try again.', null);
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp" style="font-size:20px;"></i> Connect WhatsApp Business Account';
        return;
    }

    embeddedCode    = null;
    embeddedWabaId  = null;
    embeddedPhoneId = null;

    FB.login(function(response) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp" style="font-size:20px;"></i> Connect WhatsApp Business Account';

        if (response.authResponse && response.authResponse.code) {
            embeddedCode = response.authResponse.code;

            if (!embeddedWabaId || !embeddedPhoneId) {
                showError('Your WhatsApp account details were not returned. Make sure you completed all steps in the popup, then try again.', null);
                return;
            }

            document.getElementById('embeddedSignupSection').style.display = 'none';
            runAutoSetup();
        } else if (response.status === 'not_authorized') {
            showError('You declined the required permissions. WhatsApp needs permission to manage your business account. Please try again and accept the permissions.', null);
        } else {
            showError('The connection was cancelled. Please click the button again to try connecting your WhatsApp account.', null);
        }
    }, {
        config_id: META_CONFIG_ID,
        response_type: 'code',
        override_default_response_type: true,
        extras: { sessionInfoVersion: 2, featureType: 'only_waba_sharing' }
    });
}

function runAutoSetup() {
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('errorPanel').style.display = 'none';
    document.getElementById('conflictPanel').style.display = 'none';
    setStepState(1, 'active'); setStepState(2, 'waiting'); setStepState(3, 'waiting');

    fetch('{{ route("whatsapp.setup.embedded-complete") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ code: embeddedCode, waba_id: embeddedWabaId, phone_number_id: embeddedPhoneId })
    })
    .then(r => r.json())
    .then(handleSetupResponse)
    .catch(() => {
        setStepState(1, 'error');
        showError('Could not reach the server. Check your internet connection and try again.', 1);
    });
}

function continueSetup() {
    const btn = document.getElementById('btnContinue');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Resuming...'; }
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('errorPanel').style.display = 'none';

    for (let s = 1; s <= SAVED_STEP; s++) setStepState(s, 'done');
    if (SAVED_STEP < 3) setStepState(SAVED_STEP + 1, 'active');

    fetch('{{ route("whatsapp.setup.resume") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(handleSetupResponse)
    .catch(() => {
        document.getElementById('errorMessage').textContent = 'Could not reach the server. Check your internet connection and try again.';
        document.getElementById('errorPanel').style.display = 'block';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-play"></i> Continue Setup'; }
    });
}

function handleSetupResponse(data) {
    if (data.success) {
        setStepState(1, 'done'); setStepState(2, 'done'); setStepState(3, 'done');
        const titleEl = document.getElementById('progressTitle');
        if (titleEl) titleEl.innerHTML = '<i class="fas fa-check-circle" style="color:#16a34a;"></i> WhatsApp Connected Successfully!';
        setTimeout(() => location.reload(), 2000);
    } else {
        const step = data.step || 1;
        for (let s = 1; s < step; s++) setStepState(s, 'done');
        setStepState(step, 'error');
        for (let s = step + 1; s <= 3; s++) setStepState(s, 'waiting');

        if (data.is_number_conflict) {
            document.getElementById('progressSection').style.display = 'none';
            const cp = document.getElementById('conflictPanel');
            if (cp) cp.style.display = 'block';
        } else {
            failedStep = step;
            showError(data.error, step);
        }
    }
}

function setStepState(n, state) {
    const el = document.getElementById('step-' + n) || document.getElementById('res-step-' + n);
    if (!el) return;
    const icon = el.querySelector('.step-icon');
    const defs = {
        waiting: { bg: '#f9fafb', ibg: '#e5e7eb', html: '<i class="fas fa-circle" style="color:#d1d5db;font-size:8px;"></i>', op: '0.4' },
        active:  { bg: '#f0fdf4', ibg: '#dcfce7', html: '<i class="fas fa-circle-notch fa-spin" style="color:#16a34a;"></i>', op: '1' },
        done:    { bg: '#f0fdf4', ibg: '#dcfce7', html: '<i class="fas fa-check" style="color:#16a34a;"></i>', op: '1' },
        error:   { bg: '#fef2f2', ibg: '#fee2e2', html: '<i class="fas fa-times" style="color:#dc2626;"></i>', op: '1' },
    };
    const s = defs[state] || defs.waiting;
    el.style.background = s.bg; el.style.opacity = s.op;
    if (icon) { icon.style.background = s.ibg; icon.innerHTML = s.html; }
}

function showError(msg, step) {
    document.getElementById('errorMessage').textContent = msg;
    document.getElementById('errorPanel').style.display = 'block';
    failedStep = step;
}

function retryFromError() {
    document.getElementById('errorPanel').style.display = 'none';
    if (failedStep === 1 && embeddedCode === null) { retryEmbeddedSignup(); }
    else { runAutoSetup(); }
}

function retryEmbeddedSignup() {
    document.getElementById('conflictPanel') && (document.getElementById('conflictPanel').style.display = 'none');
    document.getElementById('progressSection').style.display = 'none';
    document.getElementById('modeCards') && (document.getElementById('modeCards').style.display = 'none');
    showEmbeddedSignup();
    embeddedCode = null; embeddedWabaId = null; embeddedPhoneId = null;
}

function resetAll() {
    if (confirm('This will reset all WhatsApp setup progress. Continue?')) {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = '{{ route("whatsapp.setup.reset") }}';
        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
        document.body.appendChild(form); form.submit();
    }
}
</script>

@if($platform?->meta_app_id && !$config->setup_completed)
<div id="fb-root"></div>
<script>
window.fbAsyncInit = function() {
    FB.init({ appId: '{{ $platform->meta_app_id }}', xfbml: true, version: 'v19.0' });
};
(function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(d.getElementById(id))return;js=d.createElement(s);js.id=id;js.src="https://connect.facebook.net/en_US/sdk.js";fjs.parentNode.insertBefore(js,fjs);}(document,'script','facebook-jssdk'));
</script>
@endif

@endsection
