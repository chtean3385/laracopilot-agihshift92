@extends('layouts.admin')
@section('title', 'Edit Template')
@section('page-title', 'Edit WhatsApp Template')
@section('page-subtitle', '{{ $template->template_name }}')

@section('content')

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

    <div>
        <form action="{{ route('whatsapp.template.save', $template) }}" method="POST">
            @csrf @method('PUT')

            <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
                <div style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px;">Template Details</div>
                <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">
                    Event: <strong style="color:#1e293b;">{{ \App\Models\WhatsAppTemplate::allEvents()[$template->trigger_event] ?? $template->trigger_event }}</strong>
                </div>

                <div style="display:grid;gap:16px;">
                    <div>
                        <label class="form-label">Template Name</label>
                        <input type="text" name="template_name" value="{{ old('template_name', $template->template_name) }}"
                            class="form-input" placeholder="e.g. Booking Confirmation">
                        @error('template_name') <div style="color:#e11d48;font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label">Message Body</label>
                        <div style="font-size:12px;color:#94a3b8;margin-bottom:6px;">
                            Click a variable on the right to insert it at cursor position.
                        </div>
                        <textarea name="message_body" id="message-body" class="form-input" rows="10"
                            style="font-family:monospace;font-size:13px;resize:vertical;"
                            placeholder="Write your WhatsApp message here...">{{ old('message_body', $template->message_body) }}</textarea>
                        @error('message_body') <div style="color:#e11d48;font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                        <div>
                            <div style="font-size:14px;font-weight:700;color:#1e293b;">Active</div>
                            <div style="font-size:12px;color:#94a3b8;">Send this message when the event fires</div>
                        </div>
                        <label style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                                style="opacity:0;width:0;height:0;" id="toggle-active" onchange="toggleSwitch(this)">
                            <span id="toggle-track" style="position:absolute;inset:0;border-radius:26px;background:{{ old('is_active', $template->is_active) ? '#25d366' : '#e2e8f0' }};transition:background .2s;"></span>
                            <span id="toggle-thumb" style="position:absolute;left:{{ old('is_active', $template->is_active) ? '24px' : '2px' }};top:2px;width:22px;height:22px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:left .2s;"></span>
                        </label>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;gap:12px;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save" style="margin-right:8px;"></i>Save Template
                    </button>
                    <a href="{{ route('whatsapp.templates') }}" class="btn-secondary">Cancel</a>
                </div>
            </div>
        </form>

        {{-- Live preview --}}
        <div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;">
            <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:14px;">
                <i class="fab fa-whatsapp" style="color:#25d366;margin-right:8px;"></i>Live Preview
            </div>
            <div style="background:#e5ddd5;border-radius:16px;padding:16px;min-height:120px;">
                <div style="max-width:320px;background:#fff;border-radius:14px 14px 14px 4px;padding:12px 14px;box-shadow:0 1px 2px rgba(0,0,0,.1);">
                    <div id="preview-text" style="font-size:13px;color:#111;white-space:pre-line;line-height:1.5;">{{ $template->message_body }}</div>
                    <div style="font-size:11px;color:#94a3b8;text-align:right;margin-top:6px;">{{ now()->format('h:i A') }} ✓✓</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Variables sidebar --}}
    <div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;position:sticky;top:20px;">
        <div style="font-size:15px;font-weight:800;color:#1e293b;margin-bottom:14px;">Insert Variables</div>
        <div style="font-size:12px;color:#94a3b8;margin-bottom:14px;">Click to insert at cursor position:</div>

        @php
        $variables = [
            '{{guest_name}}'    => 'Guest full name',
            '{{hotel_name}}'    => 'Resort/hotel name',
            '{{room_number}}'   => 'Room number',
            '{{room_type}}'     => 'Room type (e.g. Deluxe)',
            '{{check_in_date}}' => 'Check-in date',
            '{{check_out_date}}'=> 'Check-out date',
            '{{booking_number}}'=> 'Booking reference',
            '{{total_amount}}'  => 'Total bill amount (₹)',
            '{{balance_due}}'   => 'Remaining balance (₹)',
            '{{invoice_number}}'=> 'Invoice number',
        ];
        @endphp

        @foreach($variables as $var => $hint)
        <button type="button" onclick="insertVar('{{ $var }}')"
            style="width:100%;text-align:left;padding:9px 12px;margin-bottom:6px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .12s;display:flex;flex-direction:column;gap:2px;"
            onmouseenter="this.style.borderColor='#7c3aed';this.style.background='#faf5ff'" onmouseleave="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
            <code style="font-size:12px;font-weight:700;color:#7c3aed;font-family:monospace;">{{ $var }}</code>
            <span style="font-size:11px;color:#94a3b8;">{{ $hint }}</span>
        </button>
        @endforeach
    </div>
</div>

<script>
function insertVar(variable) {
    const ta = document.getElementById('message-body');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    const val   = ta.value;
    ta.value = val.substring(0, start) + variable + val.substring(end);
    ta.selectionStart = ta.selectionEnd = start + variable.length;
    ta.focus();
    updatePreview();
}

function updatePreview() {
    const text = document.getElementById('message-body').value;
    document.getElementById('preview-text').textContent = text;
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

document.getElementById('message-body').addEventListener('input', updatePreview);
</script>
@endsection
