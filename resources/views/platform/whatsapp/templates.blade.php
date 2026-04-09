@extends('layouts.platform')
@section('title', 'WhatsApp Global Templates')

@section('content')

@php
$eventMeta = [
    'booking.created'  => ['fas fa-calendar-check', 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'Booking Confirmed'],
    'checkin.tomorrow' => ['fas fa-bell',            'linear-gradient(135deg,#f59e0b,#d97706)', 'Check-In Reminder'],
    'checkin.done'     => ['fas fa-door-open',       'linear-gradient(135deg,#10b981,#059669)', 'Arrival Welcome'],
    'payment.received' => ['fas fa-rupee-sign',      'linear-gradient(135deg,#7c3aed,#6d28d9)', 'Payment Received'],
    'checkout.done'    => ['fas fa-sign-out-alt',    'linear-gradient(135deg,#0891b2,#0e7490)', 'Check-Out Thank You'],
    'feedback.request' => ['fas fa-star',            'linear-gradient(135deg,#f97316,#ea580c)', 'Feedback Request'],
];
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fab fa-whatsapp" style="color:#25D366;margin-right:8px;"></i>Global WhatsApp Templates
        </h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">These templates are used by all <strong>Basic plan</strong> hotels on the shared number. Pro+ hotels manage their own templates inside their CRM.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="{{ route('platform.whatsapp.settings') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-cog"></i> Platform Settings
        </a>
        <button onclick="openCreateModal()"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:linear-gradient(135deg,#25D366,#1aad55);color:#fff;border:none;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-plus"></i> New Template
        </button>
    </div>
</div>

{{-- Flash messages --}}
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

{{-- Platform WABA status banner --}}
@if(!$platform || !$platform->saas_waba_id || !$platform->saas_token)
<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:13px;color:#92400e;">
    <i class="fas fa-exclamation-triangle" style="font-size:16px;flex-shrink:0;"></i>
    <div><strong>Submit to Meta unavailable:</strong> Platform WABA ID or access token not set. Configure them in
        <a href="{{ route('platform.whatsapp.settings') }}" style="color:#92400e;font-weight:700;">Platform Settings →</a>
    </div>
</div>
@endif

{{-- Templates grid by event --}}
<div style="display:grid;gap:16px;">
@foreach($allEvents as $event => $eventLabel)
@php
    $t = $templates[$event] ?? null;
    [$icon, $grad, $shortLabel] = $eventMeta[$event];
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
                <button onclick="openEditModal({{ $t->id }}, '{{ addslashes($t->trigger_event) }}', '{{ addslashes($t->template_name) }}', {{ json_encode($t->message_body) }}, '{{ $t->approval_status }}', {{ $t->is_active ? 'true' : 'false' }})"
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
                @if($status !== 'approved')
                <button onclick="submitToMeta({{ $t->id }}, this)"
                    id="pt-submit-{{ $t->id }}"
                    style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#1877F2,#0d65d9);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fab fa-meta"></i> Submit to Meta
                </button>
                @else
                <span style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#dcfce7;color:#15803d;border-radius:10px;font-size:12px;font-weight:700;">
                    <i class="fas fa-check-circle"></i> Approved
                </span>
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
</div>
@endforeach
</div>

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
                    <select name="trigger_event" id="create-event" required
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#fff;">
                        <option value="">— Select event —</option>
                        @foreach($allEvents as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
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
                        placeholder="Hi {{guest_name}}, welcome to {{hotel_name}}! ..."></textarea>
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
        <form id="editForm" method="POST" style="padding:24px 28px;">
            @csrf
            @method('PUT')
            <input type="hidden" name="_method" value="PUT">
            <div style="display:grid;gap:18px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Trigger Event</label>
                    <input type="text" id="edit-event-display" disabled
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#94a3b8;background:#f8fafc;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Template Name <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="template_name" id="edit-name" required
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;">
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Lowercase, underscores only. Must match Meta Business Manager.</div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Message Body <span style="color:#e11d48;">*</span></label>
                    <textarea name="message_body" id="edit-body" required rows="9"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:monospace;resize:vertical;box-sizing:border-box;color:#1e293b;"></textarea>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Approval Status</label>
                    <select name="approval_status" id="edit-status"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#fff;">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:13px;font-weight:700;color:#374151;">Active</label>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-active" value="1" style="width:18px;height:18px;cursor:pointer;">
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

function openCreateModal(presetEvent) {
    const modal = document.getElementById('createModal');
    modal.style.display = 'flex';
    if (presetEvent) {
        document.getElementById('create-event').value = presetEvent;
    }
}
function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function openEditModal(id, event, name, body, status, active) {
    document.getElementById('editForm').action = '/platform/whatsapp/templates/' + id;
    document.getElementById('edit-event-display').value = allEvents[event] || event;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-body').value = body;
    document.getElementById('edit-status').value = status;
    document.getElementById('edit-active').checked = active;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

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
            resultEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + (data.meta_id ? ' — Meta Template ID: <strong>' + data.meta_id + '</strong>' : '');
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
