@extends('layouts.platform')
@section('title', 'WhatsApp Global Templates')

@section('content')

@php
$eventMeta = [
    'booking.created'       => ['fas fa-calendar-check',     'linear-gradient(135deg,#06b6d4,#3b82f6)', 'Booking Confirmed'],
    'checkin.tomorrow'      => ['fas fa-bell',               'linear-gradient(135deg,#f59e0b,#d97706)', 'Check-In Reminder'],
    'checkin.done'          => ['fas fa-door-open',          'linear-gradient(135deg,#10b981,#059669)', 'Arrival Welcome'],
    'payment.received'      => ['fas fa-rupee-sign',         'linear-gradient(135deg,#7c3aed,#6d28d9)', 'Payment Received'],
    'checkout.done'         => ['fas fa-sign-out-alt',       'linear-gradient(135deg,#0891b2,#0e7490)', 'Check-Out Thank You'],
    'feedback.request'      => ['fas fa-star',               'linear-gradient(135deg,#f97316,#ea580c)', 'Feedback Request'],
    'ota_booking_confirmed' => ['fas fa-envelope-open-text', 'linear-gradient(135deg,#0ea5e9,#0284c7)', 'OTA Booking Confirmed'],
    'ota_booking_conflict'  => ['fas fa-triangle-exclamation','linear-gradient(135deg,#ef4444,#b91c1c)', 'OTA Booking Conflict'],
    'booking.alert.owner'   => ['fas fa-bell',               'linear-gradient(135deg,#7c3aed,#5b21b6)', 'New Booking — Owner Alert'],
];
$eventMetaDefault = ['fas fa-bolt', 'linear-gradient(135deg,#64748b,#475569)', 'Automation'];
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fab fa-whatsapp" style="color:#25D366;margin-right:8px;"></i>Global WhatsApp Templates
        </h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">These templates are used by all <strong>Basic plan</strong> hotels on the shared number. Pro+ hotels manage their own templates inside their CRM.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('platform.whatsapp.numbers') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-sim-card"></i> Hotel Numbers
        </a>
        <a href="{{ route('platform.whatsapp.settings') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-cog"></i> Platform Settings
        </a>
        <form method="POST" action="{{ route('platform.whatsapp.template.sync') }}" style="margin:0;">
            @csrf
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;"
                onclick="this.disabled=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Syncing…';this.form.submit();">
                <i class="fas fa-sync-alt"></i> Sync from Meta
            </button>
        </form>
        <button onclick="openCreateModal()"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#25D366,#1aad55);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-plus"></i> New Template
        </button>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {!! session('success') !!}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-times-circle"></i> {{ session('error') }}
</div>
@endif

{{-- Platform WABA status banner --}}
@if(!$platform || !$platform->saas_waba_id || !$platform->saas_token)
<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:13px;color:#92400e;">
    <i class="fas fa-exclamation-triangle" style="font-size:16px;flex-shrink:0;"></i>
    <div><strong>Submit to Meta unavailable:</strong> Platform WABA ID or access token not set. Configure them in
        <a href="{{ route('platform.whatsapp.settings') }}" style="color:#92400e;font-weight:700;">Platform Settings →</a>
    </div>
</div>
@endif

{{-- ── Legend ── --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 20px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
        <i class="fas fa-info-circle" style="color:#7c3aed;margin-right:6px;"></i>How it works
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;white-space:nowrap;flex-shrink:0;margin-top:1px;">Pending</div>
            <span style="font-size:12px;color:#6b7280;line-height:1.5;">Template is not yet submitted to Meta, or is under review. Cannot send to real users yet.</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#15803d;white-space:nowrap;flex-shrink:0;margin-top:1px;">Approved</div>
            <span style="font-size:12px;color:#6b7280;line-height:1.5;">Meta reviewed and approved this template. It can now send messages to any WhatsApp number.</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="display:inline-flex;align-items:center;gap:5px;width:44px;height:24px;border-radius:12px;background:#25D366;padding:0 4px;flex-shrink:0;margin-top:1px;">
                <div style="width:18px;height:18px;background:#fff;border-radius:50%;margin-left:auto;"></div>
            </div>
            <span style="font-size:12px;color:#6b7280;line-height:1.5;"><strong>Toggle ON:</strong> Template is active — will automatically send when the event fires (e.g., new booking). Requires Meta approval too.</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="display:inline-flex;align-items:center;gap:5px;width:44px;height:24px;border-radius:12px;background:#d1d5db;padding:0 4px;flex-shrink:0;margin-top:1px;">
                <div style="width:18px;height:18px;background:#fff;border-radius:50%;"></div>
            </div>
            <span style="font-size:12px;color:#6b7280;line-height:1.5;"><strong>Toggle OFF:</strong> Template is inactive — saved but will NOT send even if Meta approved it.</span>
        </div>
    </div>
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:12px;color:#6b7280;">
        <strong>Workflow:</strong> Create template → Click <em>Submit to Meta</em> → Meta reviews (minutes to hours) → Click <em>Sync from Meta</em> to pull latest approval status → Turn toggle ON to activate.
    </div>
</div>

{{-- Templates grid by event --}}
<div style="display:grid;gap:16px;">
@foreach($allEvents as $event => $eventLabel)
@php
    $t = $templates[$event] ?? null;  // primary text template
    // All templates for this event (may include text + PDF for checkout.done)
    $eventTemplates = $standardTemplates->where('trigger_event', $event)->values();
    [$icon, $grad, $shortLabel] = $eventMeta[$event] ?? $eventMetaDefault;
    $status      = $t ? ($t->approval_status ?? 'pending') : null;
    $metaStatus  = $t?->meta_status ?? 'not_submitted';
    $statusColor = match($status) {
        'approved' => ['#dcfce7','#15803d'],
        'rejected' => ['#fee2e2','#b91c1c'],
        default    => ['#fef3c7','#92400e'],
    };
@endphp

<div style="background:#fff;border-radius:18px;padding:20px 24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
    <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">

        {{-- Icon --}}
        <div style="width:48px;height:48px;background:{{ $grad }};border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="{{ $icon }}" style="color:#fff;font-size:18px;"></i>
        </div>

        {{-- Content --}}
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <span style="font-size:15px;font-weight:800;color:#1e293b;">{{ $eventLabel }}</span>
                @if($t)
                    <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};">
                        {{ ucfirst($status) }}
                    </span>
                    @if($metaStatus === 'submitted')
                    <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:#eff6ff;color:#1d4ed8;">
                        <i class="fas fa-paper-plane" style="font-size:9px;"></i> Submitted to Meta
                    </span>
                    @endif
                    @if($t->meta_template_id)
                    <span style="font-size:11px;color:#94a3b8;">ID: <code style="color:#7c3aed;background:#f5f3ff;padding:1px 5px;border-radius:4px;">{{ $t->meta_template_id }}</code></span>
                    @endif
                @else
                    <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#f1f5f9;color:#94a3b8;">Not configured</span>
                @endif
            </div>

            @if($t)
                @if($t->template_name)
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">
                    <i class="fas fa-tag" style="margin-right:4px;"></i>
                    <code style="color:#7c3aed;background:#f5f3ff;padding:1px 6px;border-radius:5px;">{{ $t->template_name }}</code>
                </div>
                @endif
                <div style="font-size:12px;color:#64748b;background:#f8fafc;padding:10px 14px;border-radius:10px;border-left:3px solid #e2e8f0;line-height:1.6;white-space:pre-line;max-height:80px;overflow:hidden;">{{ Str::limit($t->message_body, 200) }}</div>
            @else
                <div style="font-size:13px;color:#94a3b8;font-style:italic;">No template configured for this event. Click "Add" to create one for all Basic plan hotels.</div>
            @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">
            @if($t)
                {{-- Toggle --}}
                <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;" title="{{ $t->is_active ? 'Active' : 'Inactive' }}">
                    <input type="checkbox" {{ $t->is_active ? 'checked' : '' }}
                        onchange="toggleTemplate({{ $t->id }}, this)"
                        style="opacity:0;width:0;height:0;">
                    <span id="pt-track-{{ $t->id }}" style="position:absolute;inset:0;border-radius:24px;background:{{ $t->is_active ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                    <span id="pt-thumb-{{ $t->id }}" style="position:absolute;left:{{ $t->is_active ? '22px' : '2px' }};top:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></span>
                </label>

                {{-- Edit --}}
                <button onclick="openEditModal({{ $t->id }}, '{{ addslashes($t->trigger_event) }}', '{{ addslashes($t->template_name) }}', {!! json_encode($t->message_body) !!}, '{{ $t->approval_status }}', {{ $t->is_active ? 'true' : 'false' }}, {{ $t->has_document_attachment ? 'true' : 'false' }}, {!! json_encode($t->header_media_url) !!})"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fef3c7;color:#92400e;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </button>

                {{-- Delete --}}
                <form action="{{ route('platform.whatsapp.template.destroy', $t->id) }}" method="POST" style="display:inline;"
                    onsubmit="return confirm('Delete this global template? This will remove it for all Basic plan hotels.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>

                {{-- Submit to Meta --}}
                @if($status === 'approved')
                <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                    <i class="fas fa-check-circle"></i> Approved
                </span>
                @elseif($metaStatus === 'submitted')
                <button disabled id="pt-submit-{{ $t->id }}"
                    title="Already submitted — click 'Sync from Meta' to get the latest approval status"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#e2e8f0;color:#94a3b8;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:not-allowed;">
                    <i class="fas fa-clock"></i> Submitted · Sync to update
                </button>
                @else
                <button onclick="submitToMeta({{ $t->id }}, this)"
                    id="pt-submit-{{ $t->id }}"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#1877F2,#0d65d9);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fab fa-meta"></i> Submit to Meta
                </button>
                @endif
            @else
                {{-- No template yet: Add button --}}
                <button onclick="openCreateModal('{{ $event }}')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plus"></i> Add Template
                </button>
            @endif
        </div>
    </div>

    {{-- Inline result message area --}}
    @if($t)
    <div id="pt-result-{{ $t->id }}" style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>
    @endif

    {{-- Secondary PDF templates for this event (e.g. check_out_bill_with_pdf for checkout.done) --}}
    @foreach($eventTemplates->where('has_document_attachment', true) as $pdfT)
    @php
        $pdfStatus      = $pdfT->approval_status ?? 'pending';
        $pdfStatusColor = match($pdfStatus) {
            'approved' => ['#dcfce7','#15803d'],
            'rejected' => ['#fee2e2','#b91c1c'],
            default    => ['#fef3c7','#92400e'],
        };
    @endphp
    <div style="margin-top:14px;padding:14px 18px;background:#fdf4ff;border:1px solid #e9d5ff;border-radius:12px;">
        <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <span style="font-size:13px;font-weight:800;color:#7c3aed;"><i class="fas fa-file-pdf" style="margin-right:5px;"></i>PDF Invoice Variant</span>
                    <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $pdfStatusColor[0] }};color:{{ $pdfStatusColor[1] }};">{{ ucfirst($pdfStatus) }}</span>
                    @if($pdfT->meta_template_id)
                    <span style="font-size:11px;color:#94a3b8;">ID: <code style="color:#7c3aed;background:#f5f3ff;padding:1px 5px;border-radius:4px;">{{ $pdfT->meta_template_id }}</code></span>
                    @endif
                </div>
                @if($pdfT->template_name)
                <div style="font-size:11px;color:#9333ea;margin-bottom:6px;">
                    <i class="fas fa-tag" style="margin-right:4px;"></i>
                    <code style="background:#f5f3ff;padding:1px 6px;border-radius:5px;">{{ $pdfT->template_name }}</code>
                    <span style="margin-left:6px;font-style:italic;">Requires DOCUMENT header in Meta Business Manager</span>
                </div>
                @endif
                <div style="font-size:12px;color:#64748b;background:#fff;padding:10px 14px;border-radius:10px;border-left:3px solid #e9d5ff;line-height:1.6;white-space:pre-line;max-height:80px;overflow:hidden;">{{ Str::limit($pdfT->message_body, 200) }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">
                <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;">
                    <input type="checkbox" {{ $pdfT->is_active ? 'checked' : '' }}
                        onchange="toggleTemplate({{ $pdfT->id }}, this)"
                        style="opacity:0;width:0;height:0;">
                    <span id="pt-track-{{ $pdfT->id }}" style="position:absolute;inset:0;border-radius:24px;background:{{ $pdfT->is_active ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                    <span id="pt-thumb-{{ $pdfT->id }}" style="position:absolute;left:{{ $pdfT->is_active ? '22px' : '2px' }};top:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></span>
                </label>
                <button onclick="openEditModal({{ $pdfT->id }}, '{{ addslashes($pdfT->trigger_event) }}', '{{ addslashes($pdfT->template_name) }}', {!! json_encode($pdfT->message_body) !!}, '{{ $pdfT->approval_status }}', {{ $pdfT->is_active ? 'true' : 'false' }}, true, {!! json_encode($pdfT->header_media_url) !!})"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fef3c7;color:#92400e;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <form action="{{ route('platform.whatsapp.template.destroy', $pdfT->id) }}" method="POST" style="display:inline;"
                    onsubmit="return confirm('Delete the PDF invoice template? Hotels will fall back to the text-only checkout template.')">
                    @csrf @method('DELETE')
                    <button type="submit" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @if($pdfStatus === 'approved')
                <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                    <i class="fas fa-check-circle"></i> Approved
                </span>
                @elseif(($pdfT->meta_status ?? 'not_submitted') === 'submitted')
                <button disabled id="pt-submit-{{ $pdfT->id }}"
                    title="Already submitted — click 'Sync from Meta' to get the latest approval status"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#e2e8f0;color:#94a3b8;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:not-allowed;">
                    <i class="fas fa-clock"></i> Submitted · Sync to update
                </button>
                @else
                <button onclick="submitToMeta({{ $pdfT->id }}, this)" id="pt-submit-{{ $pdfT->id }}"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fab fa-meta"></i> Submit PDF Template
                </button>
                @endif
            </div>
        </div>
        <div id="pt-result-{{ $pdfT->id }}" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>
    </div>
    @endforeach
</div>
@endforeach
</div>

{{-- ── Custom Event Templates ── --}}
@if($customTemplates->count() > 0)
<div style="margin-top:24px;">
    <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-puzzle-piece" style="color:#7c3aed;"></i> Custom Event Templates
        <span style="font-size:11px;font-weight:600;color:#7c3aed;background:#f5f3ff;padding:2px 8px;border-radius:10px;">Manually triggered</span>
    </div>
    <div style="display:grid;gap:14px;">
    @foreach($customTemplates as $ct)
    @php
        $ctStatus      = $ct->approval_status ?? 'pending';
        $ctMetaStatus  = $ct->meta_status ?? 'not_submitted';
        $ctStatusColor = match($ctStatus) {
            'approved' => ['#dcfce7','#15803d'],
            'rejected' => ['#fee2e2','#b91c1c'],
            default    => ['#fef3c7','#92400e'],
        };
    @endphp
    <div style="background:#fff;border-radius:18px;padding:20px 24px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-puzzle-piece" style="color:#fff;font-size:18px;"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <span style="font-size:15px;font-weight:800;color:#1e293b;">{{ ucwords(str_replace(['.','_'], ' ', $ct->trigger_event)) }}</span>
                    <span style="padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $ctStatusColor[0] }};color:{{ $ctStatusColor[1] }};">{{ ucfirst($ctStatus) }}</span>
                    <code style="font-size:10px;background:#f1f5f9;color:#64748b;padding:1px 6px;border-radius:5px;">{{ $ct->trigger_event }}</code>
                </div>
                @if($ct->template_name)
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;"><i class="fas fa-tag" style="margin-right:4px;"></i><code style="color:#7c3aed;background:#f5f3ff;padding:1px 6px;border-radius:5px;">{{ $ct->template_name }}</code></div>
                @endif
                <div style="font-size:12px;color:#64748b;background:#f8fafc;padding:10px 14px;border-radius:10px;border-left:3px solid #e2e8f0;line-height:1.6;white-space:pre-line;max-height:80px;overflow:hidden;">{{ Str::limit($ct->message_body, 200) }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;">
                <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;" title="{{ $ct->is_active ? 'Active — toggle OFF to disable' : 'Inactive — toggle ON to enable' }}">
                    <input type="checkbox" {{ $ct->is_active ? 'checked' : '' }} onchange="toggleTemplate({{ $ct->id }}, this)" style="opacity:0;width:0;height:0;">
                    <span id="toggle-{{ $ct->id }}" style="position:absolute;inset:0;border-radius:12px;background:{{ $ct->is_active ? '#25D366' : '#d1d5db' }};transition:background .2s;"></span>
                    <span style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s;{{ $ct->is_active ? 'transform:translateX(20px)' : '' }}"></span>
                </label>
                <button onclick="openEditModal({{ $ct->id }}, '{{ addslashes($ct->trigger_event) }}', '{{ addslashes($ct->template_name) }}', {!! json_encode($ct->message_body) !!}, '{{ $ct->approval_status }}', {{ $ct->is_active ? 'true' : 'false' }}, false, {!! json_encode($ct->header_media_url) !!})"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#f1f5f9;color:#374151;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <form action="{{ route('platform.whatsapp.template.destroy', $ct->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this template?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fee2e2;color:#b91c1c;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-trash"></i></button>
                </form>
                @if($ctStatus === 'approved')
                <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;"><i class="fas fa-check-circle"></i> Approved</span>
                @elseif($ctMetaStatus === 'submitted')
                <button disabled id="pt-submit-{{ $ct->id }}"
                    title="Already submitted — click 'Sync from Meta' to get the latest approval status"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#e2e8f0;color:#94a3b8;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:not-allowed;">
                    <i class="fas fa-clock"></i> Submitted · Sync to update
                </button>
                @else
                <button onclick="submitToMeta({{ $ct->id }}, this)" id="pt-submit-{{ $ct->id }}"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#1877F2,#0d65d9);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fab fa-meta"></i> Submit to Meta
                </button>
                @endif
            </div>
        </div>
        <div id="pt-result-{{ $ct->id }}" style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>
    </div>
    @endforeach
    </div>
</div>
@endif

{{-- Variables reference --}}
<div style="margin-top:22px;background:#fff;border-radius:16px;padding:18px 24px;border:1px solid #f1f5f9;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:10px;">
        <i class="fas fa-code" style="color:#7c3aed;margin-right:6px;"></i>Available Template Variables
    </div>
    <div style="font-size:12px;color:#94a3b8;margin-bottom:10px;">Use these in your message body. They'll be replaced with real values when the message is sent, and converted to positional format ({{1}}, {{2}}...) when submitting to Meta.</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach(['{{guest_name}}','{{hotel_name}}','{{room_number}}','{{room_type}}','{{check_in_date}}','{{check_out_date}}','{{booking_number}}','{{total_amount}}','{{balance_due}}','{{invoice_number}}'] as $var)
        <code style="background:#f1f5f9;color:#7c3aed;padding:4px 10px;border-radius:8px;font-size:12px;font-family:monospace;font-weight:700;cursor:pointer;"
            onclick="insertVariable('{{ $var }}')" title="Click to copy">{{ $var }}</code>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════
     CREATE MODAL
══════════════════════════════════════════════════════════════════ --}}
<div id="createModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="padding:24px 28px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:17px;font-weight:800;color:#1e293b;"><i class="fas fa-plus" style="color:#25D366;margin-right:8px;"></i>New Global Template</div>
            <button onclick="closeCreateModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form id="createForm" action="{{ route('platform.whatsapp.template.store') }}" method="POST" style="padding:24px 28px;">
            @csrf
            <div style="display:grid;gap:18px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Trigger Event <span style="color:#e11d48;">*</span></label>
                    <select name="trigger_event" id="create-event" onchange="handleCustomEvent(this)"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#fff;">
                        <option value="">— Select event —</option>
                        @foreach($allEvents as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                        <option value="__custom__">✏️ Custom Event Name…</option>
                    </select>
                    <div id="custom-event-wrap" style="display:none;margin-top:8px;">
                        <input type="text" name="custom_trigger_event" id="custom-event-input"
                            placeholder="e.g. birthday_greeting, room_upgrade_offer"
                            style="width:100%;padding:10px 14px;border:1.5px solid #7c3aed;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;">
                        <div style="font-size:11px;color:#7c3aed;margin-top:4px;">
                            <i class="fas fa-info-circle"></i> This will be a unique event key (lowercase letters, numbers, dots, underscores). Use it to send this template manually via API or future custom triggers.
                        </div>
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Template Name <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="template_name" required
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;"
                        placeholder="e.g. booking_confirmed_crm">
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Lowercase letters, numbers, underscores only. Must match your Meta Business Manager template name.</div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Message Body <span style="color:#e11d48;">*</span></label>
                    <textarea name="message_body" id="create-body" required rows="9"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:monospace;resize:vertical;box-sizing:border-box;color:#1e293b;"
                        placeholder="Hi @{{guest_name}}, welcome to @{{hotel_name}}! ..."></textarea>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:13px;font-weight:700;color:#374151;">Active</label>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked style="width:18px;height:18px;cursor:pointer;">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
                <button type="button" onclick="closeCreateModal()"
                    style="padding:10px 20px;background:#f1f5f9;color:#64748b;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                    style="padding:10px 20px;background:linear-gradient(135deg,#25D366,#1aad55);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-save"></i> Create Template
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="padding:24px 28px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:17px;font-weight:800;color:#1e293b;"><i class="fas fa-edit" style="color:#7c3aed;margin-right:8px;"></i>Edit Template</div>
            <button onclick="closeEditModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form id="editForm" method="POST" enctype="multipart/form-data" style="padding:24px 28px;">
            @csrf
            @method('PUT')
            <div style="display:grid;gap:18px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Trigger Event</label>
                    <input type="text" id="edit-event-display" disabled
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#94a3b8;background:#f8fafc;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Template Name</label>
                    <input type="text" name="template_name" id="edit-name" required readonly
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#64748b;background:#f8fafc;box-sizing:border-box;cursor:default;">
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;"><i class="fas fa-lock" style="margin-right:3px;"></i>Auto-managed. If you change the body text, the name is automatically versioned (e.g. <code>login_reminder_v2</code>).</div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Message Body <span style="color:#e11d48;">*</span></label>
                    <textarea name="message_body" id="edit-body" required rows="9"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:monospace;resize:vertical;box-sizing:border-box;color:#1e293b;"></textarea>
                    <div id="edit-body-changed-notice" style="display:none;margin-top:8px;padding:10px 14px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;font-size:12px;color:#92400e;">
                        <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                        Body text changed — saving will auto-version the template name and reset status to <strong>Pending</strong> ready to submit to Meta.
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Approval Status</label>
                    <select name="approval_status" id="edit-status"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#fff;">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div id="edit-status-note" style="display:none;font-size:11px;color:#92400e;margin-top:4px;"><i class="fas fa-exclamation-triangle" style="margin-right:3px;"></i>Status will be forced to <strong>Pending</strong> because body text changed.</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:13px;font-weight:700;color:#374151;">Active</label>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-active" value="1" style="width:18px;height:18px;cursor:pointer;">
                </div>
                {{-- Header Image (for image-header templates used in blast) --}}
                <div style="border:1px solid #e0f2fe;border-radius:12px;padding:14px 16px;background:#f0f9ff;">
                    <div style="font-size:13px;font-weight:700;color:#0369a1;margin-bottom:10px;">
                        <i class="fas fa-image" style="margin-right:6px;"></i>Header Image
                        <span style="font-size:11px;font-weight:500;color:#64748b;margin-left:6px;">Optional — for templates with an image header in Meta</span>
                    </div>
                    {{-- Current image preview --}}
                    <div id="edit-header-wrap" style="display:none;margin-bottom:10px;">
                        <img id="edit-header-preview" src="" alt="Header image preview"
                            style="max-width:100%;max-height:160px;border-radius:10px;border:1px solid #bae6fd;object-fit:cover;display:block;">
                        <button type="button" onclick="clearHeaderImg()"
                            style="margin-top:6px;display:inline-flex;align-items:center;gap:5px;padding:4px 12px;background:#fee2e2;color:#b91c1c;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-times"></i> Remove image
                        </button>
                    </div>
                    {{-- Hidden field holds current/cleared URL --}}
                    <input type="hidden" name="header_media_url" id="edit-header-url">
                    {{-- File upload --}}
                    <label style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;background:#0ea5e9;color:#fff;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-upload"></i> Upload image
                        <input type="file" name="header_image" id="edit-header-file" accept="image/png,image/jpeg,image/jpg,image/webp"
                            style="display:none;" onchange="previewHeaderImg(this)">
                    </label>
                    <div style="font-size:11px;color:#64748b;margin-top:7px;">
                        <i class="fas fa-info-circle"></i> Upload the same image that is used in your Meta template header. Max 5 MB. PNG/JPG/WebP.
                        When blasting, this image URL will be sent automatically — no manual URL entry needed.
                    </div>
                </div>

                {{-- PDF attachment toggle (only shown for checkout.done templates) --}}
                <div id="edit-pdf-row" style="display:none;align-items:flex-start;gap:12px;padding:12px 14px;background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;">
                    <input type="hidden" name="has_document_attachment" value="0">
                    <input type="checkbox" name="has_document_attachment" id="edit-pdf-attach" value="1"
                        style="width:18px;height:18px;cursor:pointer;margin-top:2px;flex-shrink:0;">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#7c3aed;">Attach PDF invoice on send</div>
                        <div style="font-size:11px;color:#9333ea;margin-top:2px;">
                            When enabled, the invoice PDF is generated and uploaded to Meta, then sent as a DOCUMENT header with this template.
                            This template must have a <strong>DOCUMENT header</strong> in Meta Business Manager.
                        </div>
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
                <button type="button" onclick="closeEditModal()"
                    style="padding:10px 20px;background:#f1f5f9;color:#64748b;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                    style="padding:10px 20px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const allEvents = @json($allEvents);

function handleCustomEvent(sel) {
    const wrap  = document.getElementById('custom-event-wrap');
    const input = document.getElementById('custom-event-input');
    if (sel.value === '__custom__') {
        wrap.style.display = 'block';
        input.required = true;
        sel.removeAttribute('required');
    } else {
        wrap.style.display = 'none';
        input.required = false;
        sel.required = true;
    }
}

function openCreateModal(presetEvent) {
    const modal = document.getElementById('createModal');
    modal.style.display = 'flex';
    document.getElementById('custom-event-wrap').style.display = 'none';
    document.getElementById('custom-event-input').value = '';
    document.getElementById('custom-event-input').required = false;
    if (presetEvent) {
        document.getElementById('create-event').value = presetEvent;
    } else {
        document.getElementById('create-event').value = '';
    }
}
function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
    document.getElementById('custom-event-wrap').style.display = 'none';
}

var _editOriginalBody = '';

function openEditModal(id, event, name, body, status, active, hasDocAttachment, headerImgUrl) {
    document.getElementById('editForm').action = '/platform/whatsapp/templates/' + id;
    document.getElementById('edit-event-display').value = allEvents[event] || event;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-body').value = body;
    document.getElementById('edit-status').value = status;
    document.getElementById('edit-active').checked = active;

    // Reset body-change notices
    _editOriginalBody = body;
    document.getElementById('edit-body-changed-notice').style.display = 'none';
    document.getElementById('edit-status-note').style.display = 'none';

    const pdfRow = document.getElementById('edit-pdf-row');
    const pdfCheck = document.getElementById('edit-pdf-attach');
    if (event === 'checkout.done') {
        pdfRow.style.display = 'flex';
        pdfCheck.checked = hasDocAttachment || false;
    } else {
        pdfRow.style.display = 'none';
        pdfCheck.checked = false;
    }

    // Header image
    const headerWrap    = document.getElementById('edit-header-wrap');
    const headerPreview = document.getElementById('edit-header-preview');
    const headerUrl     = document.getElementById('edit-header-url');
    const headerFile    = document.getElementById('edit-header-file');
    headerFile.value = ''; // clear any stale file selection
    if (headerImgUrl) {
        headerPreview.src   = headerImgUrl;
        headerUrl.value     = headerImgUrl;
        headerWrap.style.display = 'block';
    } else {
        headerPreview.src   = '';
        headerUrl.value     = '';
        headerWrap.style.display = 'none';
    }

    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function previewHeaderImg(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('edit-header-preview');
        const wrap    = document.getElementById('edit-header-wrap');
        preview.src = e.target.result;
        wrap.style.display = 'block';
        // Clear stored URL so the controller knows to use the new file
        document.getElementById('edit-header-url').value = '';
    };
    reader.readAsDataURL(file);
}

function clearHeaderImg() {
    document.getElementById('edit-header-preview').src = '';
    document.getElementById('edit-header-url').value   = '';
    document.getElementById('edit-header-file').value  = '';
    document.getElementById('edit-header-wrap').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('edit-body').addEventListener('input', function () {
        var changed = this.value.trim() !== _editOriginalBody.trim();
        document.getElementById('edit-body-changed-notice').style.display = changed ? 'block' : 'none';
        document.getElementById('edit-status-note').style.display = changed ? 'block' : 'none';
    });
});

function toggleTemplate(id, checkbox) {
    const track = document.getElementById('pt-track-' + id);
    const thumb = document.getElementById('pt-thumb-' + id);
    const active = checkbox.checked;
    track.style.background = active ? '#25d366' : '#e2e8f0';
    thumb.style.left = active ? '22px' : '2px';

    fetch('/platform/whatsapp/templates/' + id + '/toggle', {
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

function submitToMeta(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    fetch('/platform/whatsapp/templates/' + id + '/submit-meta', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const resultEl = document.getElementById('pt-result-' + id);
        if (data.success) {
            resultEl.style.display = 'block';
            resultEl.style.background = '#dcfce7';
            resultEl.style.color = '#15803d';
            resultEl.style.border = '1px solid #86efac';
            resultEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + (data.meta_id ? ' — Meta Template ID: <strong>' + data.meta_id + '</strong>' : '') + ' Click <strong>Sync from Meta</strong> to check when it is approved.';
            // Grey out the button — re-enable only after Sync from Meta
            btn.disabled = true;
            btn.removeAttribute('onclick');
            btn.style.background = '#e2e8f0';
            btn.style.color = '#94a3b8';
            btn.style.cursor = 'not-allowed';
            btn.title = "Already submitted — click 'Sync from Meta' to get the latest approval status";
            btn.innerHTML = '<i class="fas fa-clock"></i> Submitted · Sync to update';
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

function insertVariable(variable) {
    const activeTextarea = document.activeElement;
    if (activeTextarea && (activeTextarea.id === 'create-body' || activeTextarea.id === 'edit-body')) {
        const start = activeTextarea.selectionStart;
        const end = activeTextarea.selectionEnd;
        activeTextarea.value = activeTextarea.value.substring(0, start) + variable + activeTextarea.value.substring(end);
        activeTextarea.selectionStart = activeTextarea.selectionEnd = start + variable.length;
        activeTextarea.focus();
    } else {
        navigator.clipboard.writeText(variable).then(() => {
            const el = event.target;
            const orig = el.innerText;
            el.style.background = '#dcfce7';
            el.innerText = 'Copied!';
            setTimeout(() => { el.style.background = '#f1f5f9'; el.innerText = orig; }, 1200);
        });
    }
}

document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

@endsection
