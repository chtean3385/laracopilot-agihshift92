@extends('platform.layouts.app')

@section('title', 'OTA WhatsApp Sources')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:28px 20px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 4px;">OTA WhatsApp Sources</h1>
            <p style="font-size:13px;color:#64748b;margin:0;">Manage known OTA WhatsApp sender numbers. When a message arrives from a matched sender, it is automatically parsed as a booking confirmation.</p>
        </div>
        <button onclick="document.getElementById('addModal').style.display='flex'"
            style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-plus"></i> Add OTA Source
        </button>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Test message format tip --}}
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:18px 20px;margin-bottom:24px;">
        <div style="font-size:13px;font-weight:800;color:#92400e;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-flask"></i> Demo Testing Format
        </div>
        <p style="font-size:12px;color:#78350f;margin:0 0 10px;">Send the following message from any WhatsApp number to the SaaS Meta number to trigger a test OTA import (ensure the sender number is registered in the table below and the hotel has the module enabled):</p>
        <pre style="background:#fff;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:11.5px;color:#1e293b;white-space:pre-wrap;line-height:1.7;margin:0;">🏨 New Booking Confirmation
Property: Demo Hotel
OTA: Booking.com
Booking Ref: BDC-TEST-001
Guest Name: John Smith
Check-in: 10 Jun 2025
Check-out: 12 Jun 2025
Room: Deluxe Double Room
Guests: 2 Adults
Amount: ₹8,500
Guest Phone: +91 9876543210
Special Request: Early check-in if possible</pre>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">OTA Name</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Sender Number</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Pattern</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Notes</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Active</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sources as $src)
                <tr style="border-bottom:1px solid #f1f5f9;" id="row-{{ $src->id }}">
                    <td style="padding:12px 16px;">
                        <span style="font-size:13px;font-weight:700;color:#1e293b;">{{ $src->name }}</span>
                    </td>
                    <td style="padding:12px 16px;font-size:12px;color:#475569;font-family:monospace;">
                        {{ $src->sender_number ?? '—' }}
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="background:#ede9fe;color:#5b21b6;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;">{{ $src->message_pattern_key }}</span>
                    </td>
                    <td style="padding:12px 16px;font-size:12px;color:#64748b;max-width:200px;">{{ $src->notes ?? '—' }}</td>
                    <td style="padding:12px 16px;text-align:center;">
                        <button onclick="toggleSource({{ $src->id }}, this)"
                            data-active="{{ $src->is_active ? '1' : '0' }}"
                            style="background:{{ $src->is_active ? 'linear-gradient(135deg,#10b981,#059669)' : '#e2e8f0' }};color:{{ $src->is_active ? '#fff' : '#64748b' }};border:none;padding:5px 14px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;transition:all .2s;">
                            {{ $src->is_active ? 'Active' : 'Off' }}
                        </button>
                    </td>
                    <td style="padding:12px 16px;text-align:right;white-space:nowrap;">
                        <button onclick="openEdit({{ $src->id }}, {{ json_encode($src) }})"
                            style="background:#f1f5f9;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;color:#475569;cursor:pointer;margin-right:6px;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="{{ route('platform.ota-sources.destroy', $src) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Delete {{ $src->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#fee2e2;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;color:#dc2626;cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:32px;text-align:center;color:#94a3b8;font-size:13px;">No OTA sources configured yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 20px;">Add OTA Source</h3>
        <form action="{{ route('platform.ota-sources.store') }}" method="POST">
            @csrf
            @include('platform.ota-sources._form')
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Save</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'"
                    style="flex:1;background:#f1f5f9;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 20px;">Edit OTA Source</h3>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            @include('platform.ota-sources._form')
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Update</button>
                <button type="button" onclick="document.getElementById('editModal').style.display='none'"
                    style="flex:1;background:#f1f5f9;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, src) {
    var f = document.getElementById('editForm');
    f.action = '/platform/ota-sources/' + id;
    f.querySelector('[name="name"]').value             = src.name || '';
    f.querySelector('[name="sender_number"]').value    = src.sender_number || '';
    f.querySelector('[name="waba_id"]').value          = src.waba_id || '';
    f.querySelector('[name="message_pattern_key"]').value = src.message_pattern_key || 'generic';
    f.querySelector('[name="notes"]').value            = src.notes || '';
    f.querySelector('[name="is_active"]').checked      = src.is_active == true || src.is_active == 1;
    document.getElementById('editModal').style.display = 'flex';
}

function toggleSource(id, btn) {
    fetch('/platform/ota-sources/' + id + '/toggle', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            btn.dataset.active = d.active ? '1' : '0';
            btn.textContent    = d.active ? 'Active' : 'Off';
            btn.style.background   = d.active ? 'linear-gradient(135deg,#10b981,#059669)' : '#e2e8f0';
            btn.style.color        = d.active ? '#fff' : '#64748b';
        }
    });
}
</script>
@endsection
