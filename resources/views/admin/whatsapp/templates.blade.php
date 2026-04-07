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
$providerConnected = $config && $config->is_active && $config->api_key;
$providerName      = $providerConnected ? strtoupper($config->provider) : null;

$eventMeta = [
    'booking.created'    => ['fas fa-calendar-check', 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'Sent when a new booking is created or confirmed'],
    'checkin.tomorrow'   => ['fas fa-bell',            'linear-gradient(135deg,#f59e0b,#d97706)', 'Sent the day before check-in as a reminder'],
    'checkin.done'       => ['fas fa-door-open',       'linear-gradient(135deg,#10b981,#059669)', 'Sent when the guest checks in — welcome message'],
    'payment.received'   => ['fas fa-rupee-sign',      'linear-gradient(135deg,#7c3aed,#6d28d9)', 'Sent when a payment is recorded on the booking'],
    'checkout.done'      => ['fas fa-sign-out-alt',    'linear-gradient(135deg,#0891b2,#0e7490)', 'Sent after checkout — thank you + bill summary'],
    'feedback.request'   => ['fas fa-star',            'linear-gradient(135deg,#f97316,#ea580c)', 'Sent 2 days after checkout requesting a review'],
];

$readyCount = 0;
foreach($allEvents as $event => $label) {
    $t = $templates[$event] ?? null;
    if ($t && $t->is_active && ($t->approval_status ?? 'pending') === 'approved' && $providerConnected) {
        $readyCount++;
    }
}
@endphp

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
            @if($config && $config->provider === 'wati')
            <form action="{{ route('whatsapp.templates.sync_wati') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-sync-alt"></i> Sync Approvals from WATI
                </button>
            </form>
            @endif
            <a href="{{ route('whatsapp.template.create') }}"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;">
                <i class="fas fa-plus"></i> Add Template
            </a>
            <a href="{{ route('whatsapp.setup') }}" style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
                <i class="fas fa-cog"></i> WhatsApp Setup
            </a>
        </div>
    </div>

    {{-- Quick readiness summary --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
        @foreach($allEvents as $event => $label)
        @php
            $t = $templates[$event] ?? null;
            $approved = $t && ($t->approval_status ?? 'pending') === 'approved';
            $active   = $t && $t->is_active;
            if ($t && $approved && $active && $providerConnected) {
                $dot = '#22c55e'; $dotLabel = 'Ready';
            } elseif ($t && $active && $providerConnected && !$approved) {
                $dot = '#f59e0b'; $dotLabel = 'Needs approval';
            } elseif (!$t) {
                $dot = '#e11d48'; $dotLabel = 'No template';
            } else {
                $dot = '#94a3b8'; $dotLabel = 'Inactive';
            }
            [$icon, $grad] = $eventMeta[$event];
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
        $t = $templates[$event] ?? null;
        [$icon, $grad, $desc] = $eventMeta[$event];
        $approvalStatus = $t ? ($t->approval_status ?? 'pending') : null;
        $approvalColor  = match($approvalStatus) {
            'approved' => ['#dcfce7','#15803d'],
            'rejected' => ['#fee2e2','#b91c1c'],
            default    => ['#fef3c7','#92400e'],
        };
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
                    {{-- Active toggle --}}
                    <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;" title="{{ $t->is_active ? 'Active — click to deactivate' : 'Inactive — click to activate' }}">
                        <input type="checkbox" {{ $t->is_active ? 'checked' : '' }}
                            onchange="toggleTemplate({{ $t->id }}, this)"
                            style="opacity:0;width:0;height:0;">
                        <span id="track-{{ $t->id }}" style="position:absolute;inset:0;border-radius:24px;background:{{ $t->is_active ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                        <span id="thumb-{{ $t->id }}" style="position:absolute;left:{{ $t->is_active ? '22px' : '2px' }};top:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></span>
                    </label>

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
                @else
                    <a href="{{ route('whatsapp.template.create') }}?event={{ $event }}"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-plus"></i> Set Up
                    </a>
                @endif
            </div>
        </div>
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

    fetch('/whatsapp/templates/' + id + '/toggle', {
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
</script>
@endsection
