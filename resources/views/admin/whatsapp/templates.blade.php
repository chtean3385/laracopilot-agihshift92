@extends('layouts.admin')
@section('title', 'WhatsApp Templates')
@section('page-title', 'Message Templates & Automations')
@section('page-subtitle', 'Manage automated WhatsApp messages for every booking event')

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
$providerConnected = $config && $config->setup_completed && (
    ($config->mode === 'shared') ||
    ($config->mode === 'own') ||
    ($config->mode === 'managed' && $config->managed_otp_status === 'verified' && $config->is_active)
);
$providerName = $providerConnected
    ? match($config->mode) {
        'shared'  => 'CRM Shared Number',
        'own'     => 'Own Hotel Number',
        'managed' => 'Managed Number (+' . $config->phone_number . ')',
        default   => 'Connected',
    }
    : null;

$eventMeta = [
    'booking.created'    => ['fas fa-calendar-check', 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'Sent when a new booking is created or confirmed'],
    'checkin.tomorrow'   => ['fas fa-bell',            'linear-gradient(135deg,#f59e0b,#d97706)', 'Sent the day before check-in as a reminder'],
    'checkin.done'       => ['fas fa-door-open',       'linear-gradient(135deg,#10b981,#059669)', 'Sent when the guest checks in — welcome message'],
    'payment.received'   => ['fas fa-rupee-sign',      'linear-gradient(135deg,#7c3aed,#6d28d9)', 'Sent when a payment is recorded on the booking'],
    'checkout.done'      => ['fas fa-sign-out-alt',    'linear-gradient(135deg,#0891b2,#0e7490)', 'Sent after checkout — thank you + bill summary'],
    'feedback.request'      => ['fas fa-star',            'linear-gradient(135deg,#f97316,#ea580c)', 'Sent 2 days after checkout requesting a review'],
    'ota_booking_confirmed' => ['fas fa-envelope-open-text','linear-gradient(135deg,#0ea5e9,#0284c7)', 'Sent when an OTA booking email is parsed and confirmed automatically'],
    'ota_booking_conflict'  => ['fas fa-triangle-exclamation','linear-gradient(135deg,#ef4444,#b91c1c)', 'Sent when an OTA booking email conflicts with an existing booking and needs review'],
    'booking.alert.owner'   => ['fas fa-bell',               'linear-gradient(135deg,#7c3aed,#5b21b6)', 'Sent to owner/partner phones whenever a new booking is created'],
];
$eventMetaDefault = ['fas fa-bolt', 'linear-gradient(135deg,#64748b,#475569)', 'Automation'];

$readyCount = 0;
foreach($allEvents as $event => $label) {
    $t = $templates[$event] ?? null;
    if (!$t || !$providerConnected) continue;
    $isGF      = !empty($t->is_global_fallback);
    $isPlat    = in_array($t->template_name, $platformApprovedNames ?? []);
    $tmplApproved = $isGF || $isPlat || ($t->approval_status ?? 'pending') === 'approved';
    $tmplActive   = $t->is_active || ($isGF && $tmplApproved); // global fallbacks are always active when approved
    if ($tmplApproved && $tmplActive) {
        $readyCount++;
    }
}
@endphp

{{-- Plan restriction notice for basic plan hotel admins --}}
@if($isBasicPlan && !$isSaasAdmin)
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:13px;color:#1e40af;">
    <i class="fas fa-info-circle" style="font-size:16px;flex-shrink:0;"></i>
    <div>
        <strong>Basic Plan:</strong> Your templates are managed by the platform. You can toggle automations on or off, but template content is set by the CRM administrator.
        <a href="{{ route('upgrade') }}" style="color:#2563eb;font-weight:700;margin-left:6px;">Upgrade to Pro to customise your own templates →</a>
    </div>
</div>
@endif

{{-- Platform Templates Banner (managed hotels only) --}}
@if($config && $config->isManagedMode())
<div style="background:{{ $usePlatformTemplates ? '#eff6ff' : '#f8fafc' }};border:1px solid {{ $usePlatformTemplates ? '#bfdbfe' : '#e2e8f0' }};border-radius:16px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:14px;font-weight:800;color:{{ $usePlatformTemplates ? '#1d4ed8' : '#64748b' }};margin-bottom:2px;">
            <i class="fas fa-shield-alt" style="margin-right:6px;"></i>
            Use Platform Templates
        </div>
        <div style="font-size:12px;color:#64748b;max-width:500px;">
            @if($usePlatformTemplates)
                Your hotel uses the SaaS platform's pre-approved templates — no separate Meta submission needed.
                To customise a template for this hotel, click <strong>Edit for Hotel</strong> on any template below.
            @else
                Platform templates are OFF — this hotel manages its own templates independently.
                Turn ON to instantly use all pre-approved platform templates.
            @endif
        </div>
    </div>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;" title="Toggle platform template inheritance">
        <span style="font-size:12px;font-weight:700;color:{{ $usePlatformTemplates ? '#1d4ed8' : '#94a3b8' }};">
            {{ $usePlatformTemplates ? 'ON' : 'OFF' }}
        </span>
        <div id="platToggleTrack" onclick="togglePlatformTemplates(this)"
            style="position:relative;width:48px;height:26px;border-radius:26px;background:{{ $usePlatformTemplates ? '#2563eb' : '#e2e8f0' }};cursor:pointer;transition:background .2s;flex-shrink:0;">
            <div id="platToggleThumb"
                style="position:absolute;top:3px;left:{{ $usePlatformTemplates ? '24px' : '3px' }};width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></div>
        </div>
    </label>
</div>
@endif

{{-- Automation Status Dashboard --}}
<div style="background:#fff;border-radius:20px;padding:22px 26px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:18px;">
        <div>
            <div style="font-size:16px;font-weight:800;color:#1e293b;">Automation Status</div>
            <div style="font-size:13px;color:#94a3b8;margin-top:2px;">
                @if($providerConnected)
                    Connected via <strong style="color:#1e293b;">{{ $providerName }}</strong> ·
                    <span style="color:{{ $readyCount > 0 ? '#15803d' : '#92400e' }};font-weight:700;">{{ $readyCount }} of {{ count($allEvents) }} automations ready</span>
                @else
                    <span style="color:#92400e;">WhatsApp not connected — <a href="{{ route('whatsapp.setup') }}" style="color:#1877f2;text-decoration:none;">Set up WhatsApp →</a></span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            @if($providerConnected)
            <button onclick="document.getElementById('testMsgPanel').style.display=document.getElementById('testMsgPanel').style.display==='none'?'flex':'none'"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-paper-plane"></i> Send Test
            </button>
            @endif
            @if($canEdit)
            <button onclick="syncFromMeta(this)"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-sync-alt"></i> Sync from Meta
            </button>
            <a href="{{ route('whatsapp.template.create') }}"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;">
                <i class="fas fa-plus"></i> Add Template
            </a>
            @endif
            <a href="{{ route('whatsapp.setup') }}" style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
                <i class="fas fa-cog"></i> WhatsApp Setup
            </a>
        </div>
    </div>

    {{-- Send Test Message panel (hidden by default) --}}
    @if($providerConnected)
    <div id="testMsgPanel" style="display:none;align-items:flex-start;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px;margin-bottom:16px;flex-wrap:wrap;">
        <i class="fas fa-paper-plane" style="color:#15803d;margin-top:10px;font-size:14px;flex-shrink:0;"></i>
        <div style="flex:1;min-width:200px;">
            <div style="font-size:13px;font-weight:700;color:#14532d;margin-bottom:4px;">Send a test WhatsApp message to verify your number is working</div>
            <div style="font-size:11px;color:#166534;margin-bottom:8px;">Sends the Meta <strong>hello_world</strong> template — guaranteed delivery, no 24-hour window needed.</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="text" id="testPhoneInput" placeholder="+91 98765 43210" maxlength="15"
                    style="flex:1;min-width:160px;padding:9px 13px;border:1px solid #86efac;border-radius:8px;font-size:13px;outline:none;">
                <button id="testSendBtn" onclick="sendTestMessage()"
                    style="padding:9px 18px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
            <div id="testMsgResult" style="font-size:12px;margin-top:8px;display:none;"></div>
        </div>
    </div>
    @endif

    {{-- Quick readiness summary --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
        @foreach($allEvents as $event => $label)
        @php
            $t = $templates[$event] ?? null;
            $isPlatTmpl       = $t && in_array($t->template_name, $platformApprovedNames ?? []);
            $isGlobalFallback = $t && !empty($t->is_global_fallback);
            $approved   = $t && ($isPlatTmpl || $isGlobalFallback || ($t->approval_status ?? 'pending') === 'approved');
            // Platform/global fallback templates are considered active when approved — hotels can't disable them individually
            $active     = $t && ($t->is_active || ($isGlobalFallback && $approved));
            if ($t && $approved && $active && $providerConnected) {
                $dot = '#22c55e'; $dotLabel = 'Ready';
            } elseif ($t && $active && $providerConnected && !$approved) {
                $dot = '#f59e0b'; $dotLabel = 'Needs approval';
            } elseif (!$t) {
                $dot = '#e11d48'; $dotLabel = 'No template';
            } else {
                $dot = '#94a3b8'; $dotLabel = 'Inactive';
            }
            [$icon, $grad] = $eventMeta[$event] ?? $eventMetaDefault;
        @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
            <div style="width:32px;height:32px;background:{{ $grad }};border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $icon }}" style="color:#fff;font-size:13px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $label }}</div>
                <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $dot }};flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:11px;color:#64748b;">{{ $dotLabel }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Templates List --}}
<div style="display:grid;gap:14px;">
    @foreach($allEvents as $event => $label)
    @php
        $t               = $templates[$event] ?? null;
        [$icon, $grad, $desc] = $eventMeta[$event] ?? $eventMetaDefault;
        $isGlobalFallback   = $t && !empty($t->is_global_fallback);   // platform template shown as fallback
        $isPlatformTemplate = $isGlobalFallback || ($t && in_array($t->template_name, $platformApprovedNames ?? []));
        $approvalStatus  = $isPlatformTemplate ? 'approved' : ($t ? ($t->approval_status ?? 'pending') : null);
        $approvalColor   = match($approvalStatus) {
            'approved' => ['#dcfce7','#15803d'],
            'rejected' => ['#fee2e2','#b91c1c'],
            default    => ['#fef3c7','#92400e'],
        };
        $metaStatus = $t?->meta_status ?? 'not_submitted';
    @endphp
    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
            <div style="width:46px;height:46px;background:{{ $grad }};border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $icon }}" style="color:#fff;font-size:17px;"></i>
            </div>

            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">{{ $label }}</div>
                    @if($t)
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $approvalColor[0] }};color:{{ $approvalColor[1] }};">
                            {{ ucfirst($approvalStatus) }}
                        </span>
                        @if($isPlatformTemplate)
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                            <i class="fas fa-shield-alt" style="font-size:9px;"></i> Platform Template
                        </span>
                        @elseif($metaStatus === 'submitted')
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:#f0fdf4;color:#15803d;">
                            <i class="fas fa-paper-plane" style="font-size:9px;"></i> Submitted to Meta
                        </span>
                        @endif
                    @else
                        <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#f1f5f9;color:#64748b;">No template</span>
                    @endif
                </div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:8px;">{{ $desc }}</div>
                @if($t)
                    <div style="font-size:12px;color:#64748b;background:#f8fafc;padding:8px 12px;border-radius:10px;border-left:3px solid #e2e8f0;line-height:1.5;white-space:pre-line;max-height:60px;overflow:hidden;">{{ Str::limit($t->message_body, 160) }}</div>
                    @if($t->template_name)
                    <div style="margin-top:6px;font-size:11px;color:#94a3b8;">
                        <i class="fas fa-tag" style="margin-right:4px;"></i>Template name: <code style="color:#7c3aed;background:#f5f3ff;padding:1px 6px;border-radius:5px;">{{ $t->template_name }}</code>
                    </div>
                    @endif
                @else
                    <div style="font-size:13px;color:#94a3b8;font-style:italic;">No message configured for this event yet.</div>
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">
                @if($t)
                    @if($isGlobalFallback)
                    {{-- Platform fallback: always active when approved. Hotel cannot toggle globally managed templates. --}}
                    <span title="Managed by platform — always active when approved" style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:24px;border-radius:24px;background:#dcfce7;flex-shrink:0;">
                        <i class="fas fa-lock" style="font-size:11px;color:#15803d;"></i>
                    </span>
                    @else
                    {{-- Hotel-specific template: toggleable --}}
                    <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;" title="{{ $t->is_active ? 'Active — click to deactivate' : 'Inactive — click to activate' }}">
                        <input type="checkbox" {{ $t->is_active ? 'checked' : '' }}
                            onchange="toggleTemplate({{ $t->id }}, this)"
                            style="opacity:0;width:0;height:0;">
                        <span id="track-{{ $t->id }}" style="position:absolute;inset:0;border-radius:24px;background:{{ $t->is_active ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                        <span id="thumb-{{ $t->id }}" style="position:absolute;left:{{ $t->is_active ? '22px' : '2px' }};top:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></span>
                    </label>
                    @endif

                    @if($canEdit)
                    @if($isGlobalFallback)
                    {{-- Global platform fallback: offer to create hotel-specific copy --}}
                    <form action="{{ route('whatsapp.template.customize', $t) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit"
                            style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fef3c7;color:#92400e;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-edit"></i> Edit for Hotel
                        </button>
                    </form>
                    @else
                    {{-- Hotel-specific template: normal edit/delete --}}
                    <a href="{{ route('whatsapp.template.edit', $t) }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fef3c7;color:#92400e;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('whatsapp.template.destroy', $t) }}" method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete this template? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                    @endif

                    @if($isPlatformTemplate)
                    {{-- Platform/global template: already approved, ready to send --}}
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                        <i class="fas fa-check-circle"></i> Ready to use
                    </span>
                    @elseif($canEdit && $approvalStatus !== 'approved')
                    {{-- Custom hotel template: needs Meta submission --}}
                    <button onclick="submitToMeta({{ $t->id }}, this)"
                        id="submit-meta-{{ $t->id }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#1877F2,#0d65d9);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fab fa-meta"></i> Submit to Meta
                    </button>
                    @elseif($canEdit && $approvalStatus === 'approved')
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                        <i class="fas fa-check-circle"></i> Approved
                    </span>
                    @endif

                @else
                    @if($canEdit)
                    {{-- Set Up template: SaaS Admin or Pro+ plan only --}}
                    <a href="{{ route('whatsapp.template.create') }}?event={{ $event }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-plus"></i> Set Up
                    </a>
                    @else
                    <span style="font-size:12px;color:#94a3b8;font-style:italic;">Not configured</span>
                    @endif
                @endif
            </div>
        </div>

        {{-- Submit to Meta result message --}}
        <div id="meta-result-{{ $t?->id }}" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>

        {{-- PDF attachment variant(s) for this event --}}
        @foreach(($templatesByEvent[$event] ?? collect())->where('has_document_attachment', true) as $pdfT)
        @php
            $pdfStatus      = $pdfT->approval_status ?? 'pending';
            $pdfStatusColor = match($pdfStatus) {
                'approved' => ['#dcfce7','#15803d'],
                'rejected' => ['#fee2e2','#b91c1c'],
                default    => ['#fef3c7','#92400e'],
            };
        @endphp
        <div style="margin-top:14px;padding:14px 16px;background:#fdf4ff;border:1px solid #e9d5ff;border-radius:12px;">
            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:180px;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:800;color:#7c3aed;"><i class="fas fa-file-pdf" style="margin-right:4px;"></i>PDF Invoice Variant</span>
                        <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $pdfStatusColor[0] }};color:{{ $pdfStatusColor[1] }};">{{ ucfirst($pdfStatus) }}</span>
                    </div>
                    @if($pdfT->template_name)
                    <div style="font-size:11px;color:#9333ea;margin-bottom:5px;">
                        <i class="fas fa-tag" style="margin-right:4px;"></i>
                        <code style="background:#f5f3ff;padding:1px 5px;border-radius:4px;">{{ $pdfT->template_name }}</code>
                        <span style="margin-left:5px;font-style:italic;">Requires DOCUMENT header in Meta</span>
                    </div>
                    @endif
                    <div style="font-size:12px;color:#64748b;background:#fff;padding:8px 12px;border-radius:8px;border-left:3px solid #e9d5ff;line-height:1.5;white-space:pre-line;max-height:60px;overflow:hidden;">{{ Str::limit($pdfT->message_body, 160) }}</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">
                    <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;">
                        <input type="checkbox" {{ $pdfT->is_active ? 'checked' : '' }}
                            onchange="toggleTemplate({{ $pdfT->id }}, this)"
                            style="opacity:0;width:0;height:0;">
                        <span id="track-{{ $pdfT->id }}" style="position:absolute;inset:0;border-radius:24px;background:{{ $pdfT->is_active ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                        <span id="thumb-{{ $pdfT->id }}" style="position:absolute;left:{{ $pdfT->is_active ? '22px' : '2px' }};top:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></span>
                    </label>
                    @if($canEdit)
                    <a href="{{ route('whatsapp.template.edit', $pdfT) }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fef3c7;color:#92400e;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('whatsapp.template.destroy', $pdfT) }}" method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete PDF template? Hotels will fall back to the text-only checkout message.')">
                        @csrf @method('DELETE')
                        <button type="submit" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @if($pdfStatus !== 'approved')
                    <button onclick="submitToMeta({{ $pdfT->id }}, this)" id="submit-meta-{{ $pdfT->id }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fab fa-meta"></i> Submit PDF Template
                    </button>
                    @else
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                        <i class="fas fa-check-circle"></i> Approved
                    </span>
                    @endif
                    @endif
                </div>
            </div>
            <div id="meta-result-{{ $pdfT->id }}" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>

{{-- Variables reference --}}
<div style="margin-top:20px;background:#fff;border-radius:16px;padding:18px 22px;border:1px solid #f1f5f9;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:8px;">
        <i class="fas fa-code" style="color:#7c3aed;margin-right:6px;"></i>Available Template Variables
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach(['{{guest_name}}','{{hotel_name}}','{{room_number}}','{{room_type}}','{{check_in_date}}','{{check_out_date}}','{{booking_number}}','{{total_amount}}','{{balance_due}}','{{invoice_number}}'] as $var)
        <code style="background:#f1f5f9;color:#7c3aed;padding:4px 10px;border-radius:8px;font-size:12px;font-family:monospace;font-weight:700;">{{ $var }}</code>
        @endforeach
    </div>
</div>

<script>
function toggleTemplate(id, checkbox) {
    const track = document.getElementById('track-' + id);
    const thumb = document.getElementById('thumb-' + id);
    const active = checkbox.checked;
    track.style.background = active ? '#25d366' : '#e2e8f0';
    thumb.style.left = active ? '22px' : '2px';

    fetch('/whatsapp/automations/' + id + '/toggle', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).catch(() => {
        checkbox.checked = !active;
        track.style.background = !active ? '#25d366' : '#e2e8f0';
        thumb.style.left = !active ? '22px' : '2px';
    });
}

function submitToMeta(templateId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    fetch('/whatsapp/automations/' + templateId + '/submit-meta', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const resultEl = document.getElementById('meta-result-' + templateId);
        if (data.success) {
            resultEl.style.display = 'block';
            resultEl.style.background = '#dcfce7';
            resultEl.style.color = '#15803d';
            resultEl.style.border = '1px solid #86efac';
            resultEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + (data.meta_id ? ' (Meta ID: ' + data.meta_id + ')' : '');
            btn.innerHTML = '<i class="fas fa-check"></i> Submitted';
            btn.style.background = '#dcfce7';
            btn.style.color = '#15803d';
        } else {
            resultEl.style.display = 'block';
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#b91c1c';
            resultEl.style.border = '1px solid #fca5a5';
            resultEl.innerHTML = '<i class="fas fa-times-circle"></i> ' + (data.error || 'Submission failed.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-meta"></i> Submit to Meta';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-meta"></i> Submit to Meta';
    });
}

function togglePlatformTemplates(track) {
    var thumb   = document.getElementById('platToggleThumb');
    var isOn    = track.style.background.indexOf('2563eb') !== -1 || track.style.background === 'rgb(37, 99, 235)';
    var newVal  = !isOn;

    track.style.background  = newVal ? '#2563eb' : '#e2e8f0';
    thumb.style.left        = newVal ? '24px' : '3px';
    track.previousElementSibling && (track.previousElementSibling.textContent = newVal ? 'ON' : 'OFF');
    track.previousElementSibling && (track.previousElementSibling.style.color = newVal ? '#1d4ed8' : '#94a3b8');

    fetch('{{ route('whatsapp.toggle-platform-templates') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ enabled: newVal }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            setTimeout(function() { location.reload(); }, 400);
        } else {
            // revert on failure
            track.style.background = isOn ? '#2563eb' : '#e2e8f0';
            thumb.style.left = isOn ? '24px' : '3px';
            alert('Could not save setting: ' + (data.error || 'Unknown error'));
        }
    });
}

function syncFromMeta(btn) {
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

    fetch('{{ route('whatsapp.template.sync-meta') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Synced!';
            btn.style.background = '#dcfce7';
            btn.style.color = '#15803d';
            btn.style.borderColor = '#bbf7d0';
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            btn.innerHTML = orig;
            alert('Sync failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(function() {
        btn.innerHTML = orig;
        btn.disabled = false;
        alert('Network error during sync.');
    });
}

function sendTestMessage() {
    var phone = document.getElementById('testPhoneInput').value.trim();
    var btn   = document.getElementById('testSendBtn');
    var res   = document.getElementById('testMsgResult');
    if (!phone) { res.style.display='block'; res.style.background='#fee2e2'; res.style.color='#b91c1c'; res.style.border='1px solid #fca5a5'; res.style.padding='8px 12px'; res.style.borderRadius='8px'; res.innerHTML='<i class="fas fa-times-circle"></i> Enter a phone number first.'; return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    res.style.display = 'none';
    fetch('{{ route('whatsapp.test.json') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ phone: phone }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        res.style.display = 'block';
        res.style.padding = '8px 12px';
        res.style.borderRadius = '8px';
        if (data.success) {
            res.style.background = '#dcfce7'; res.style.color = '#15803d'; res.style.border = '1px solid #bbf7d0';
            res.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
        } else {
            res.style.background = '#fee2e2'; res.style.color = '#b91c1c'; res.style.border = '1px solid #fca5a5';
            res.innerHTML = '<i class="fas fa-times-circle"></i> ' + (data.error || 'Failed to send.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    })
    .catch(function() {
        res.style.display = 'block'; res.style.padding = '8px 12px'; res.style.borderRadius = '8px';
        res.style.background = '#fee2e2'; res.style.color = '#b91c1c'; res.style.border = '1px solid #fca5a5';
        res.innerHTML = '<i class="fas fa-times-circle"></i> Network error.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    });
}
</script>
@endsection
