@extends('layouts.admin')

@section('title', 'OTA Import Queue')

@section('content')
<div style="padding:24px 20px;max-width:1100px;margin:0 auto;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 4px;display:flex;align-items:center;gap:10px;">
                <span style="width:34px;height:34px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-hotel" style="color:#fff;font-size:14px;"></i>
                </span>
                OTA Import Queue
            </h1>
            <p style="font-size:12px;color:#64748b;margin:0;">Booking confirmations received via WhatsApp from OTAs. Review and confirm to create bookings.</p>
        </div>
        <button onclick="document.getElementById('simulateModal').style.display='flex'"
            style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-flask"></i> Simulate Import
        </button>
    </div>

    {{-- Status counts --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
        @foreach(['pending'=>['#f59e0b','#fffbeb','Pending Review'],'confirmed'=>['#10b981','#dcfce7','Confirmed'],'rejected'=>['#ef4444','#fee2e2','Rejected'],'duplicate'=>['#94a3b8','#f8fafc','Duplicates']] as $s=>[$col,$bg,$lbl])
        <div style="background:{{ $bg }};border:1.5px solid {{ $col }}22;border-radius:14px;padding:14px 16px;text-align:center;">
            <div style="font-size:22px;font-weight:900;color:{{ $col }};">{{ $counts[$s] }}</div>
            <div style="font-size:11px;font-weight:600;color:#64748b;margin-top:2px;">{{ $lbl }}</div>
        </div>
        @endforeach
    </div>

    {{-- Import list --}}
    @forelse($imports as $imp)
    <div id="imp-{{ $imp->id }}" style="background:#fff;border-radius:16px;border:2px solid {{ $imp->status === 'pending' ? '#fde68a' : ($imp->status === 'confirmed' ? '#bbf7d0' : ($imp->status === 'rejected' ? '#fecaca' : '#e2e8f0')) }};padding:20px;margin-bottom:14px;transition:border-color .2s;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">

            {{-- Left: info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <span style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                        {{ $imp->ota_name ?? 'OTA' }}
                    </span>
                    <span style="background:{{ $imp->status_color }}22;color:{{ $imp->status_color }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid {{ $imp->status_color }}44;">
                        {{ $imp->status_label }}
                    </span>
                    @if($imp->booking_ref)
                    <span style="font-size:11px;color:#64748b;font-family:monospace;background:#f8fafc;padding:2px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                        Ref: {{ $imp->booking_ref }}
                    </span>
                    @endif
                    <span style="font-size:11px;color:#94a3b8;">{{ $imp->created_at->diffForHumans() }}</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:10px;">
                    @if($imp->guest_name)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Guest</div>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $imp->guest_name }}</div>
                    </div>
                    @endif
                    @if($imp->guest_phone)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Phone</div>
                        <div style="font-size:13px;color:#1e293b;">{{ $imp->guest_phone }}</div>
                    </div>
                    @endif
                    @if($imp->checkin)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Check-in</div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($imp->checkin)->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($imp->checkout)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Check-out</div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($imp->checkout)->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($imp->room_type)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Room</div>
                        <div style="font-size:13px;color:#1e293b;">{{ $imp->room_type }}</div>
                    </div>
                    @endif
                    @if($imp->amount)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Amount</div>
                        <div style="font-size:14px;font-weight:800;color:#10b981;">₹{{ number_format($imp->amount, 0) }}</div>
                    </div>
                    @endif
                    @if($imp->guests_count)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Guests</div>
                        <div style="font-size:13px;color:#1e293b;">{{ $imp->guests_count }}</div>
                    </div>
                    @endif
                </div>

                @if($imp->special_request)
                <div style="background:#f8fafc;border-radius:8px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#475569;">
                    <i class="fas fa-comment-alt" style="color:#94a3b8;margin-right:6px;"></i>{{ $imp->special_request }}
                </div>
                @endif

                {{-- Matched by badge --}}
                <div style="font-size:11px;color:#94a3b8;">
                    Matched by: <span style="font-weight:600;color:#64748b;">{{ $imp->matched_by }}</span>
                    @if($imp->property_name)
                    &nbsp;·&nbsp; Property: <span style="font-weight:600;color:#64748b;">{{ $imp->property_name }}</span>
                    @endif
                </div>

                {{-- Raw message toggle --}}
                <div style="margin-top:10px;">
                    <button onclick="toggleRaw({{ $imp->id }})" style="background:none;border:none;font-size:11px;color:#6366f1;cursor:pointer;padding:0;font-weight:600;">
                        <i class="fas fa-eye" style="margin-right:4px;"></i>View raw message
                    </button>
                    <div id="raw-{{ $imp->id }}" style="display:none;margin-top:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:11.5px;font-family:monospace;white-space:pre-wrap;color:#334155;max-height:200px;overflow-y:auto;">{{ $imp->raw_message }}</div>
                </div>

                @if($imp->booking_id)
                <div style="margin-top:8px;">
                    <a href="{{ route('bookings.show', $imp->booking_id) }}" style="font-size:12px;color:#10b981;font-weight:600;text-decoration:none;">
                        <i class="fas fa-external-link-alt" style="margin-right:4px;"></i>View Created Booking
                    </a>
                </div>
                @endif
            </div>

            {{-- Right: actions --}}
            @if($imp->status === 'pending')
            <div style="display:flex;flex-direction:column;gap:8px;min-width:140px;">
                <button onclick="confirmImport({{ $imp->id }})"
                    style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <i class="fas fa-check"></i> Confirm & Book
                </button>
                <button onclick="openEditModal({{ $imp->id }}, {{ json_encode($imp->only(['guest_name','guest_phone','checkin','checkout','room_type','guests_count','amount','special_request'])) }})"
                    style="background:#f1f5f9;border:none;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;color:#475569;">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button onclick="rejectImport({{ $imp->id }})"
                    style="background:#fee2e2;border:none;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;color:#dc2626;">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:16px;border:2px dashed #e2e8f0;">
        <i class="fas fa-inbox" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
        <p style="font-size:15px;font-weight:700;color:#94a3b8;margin:0 0 6px;">No OTA imports yet</p>
        <p style="font-size:13px;color:#cbd5e1;margin:0;">When OTAs send WhatsApp booking confirmations to this hotel's number, they will appear here for review.</p>
    </div>
    @endforelse

    {{ $imports->links() }}
</div>

{{-- Simulate Import Modal --}}
<div id="simulateModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:520px;max-height:92vh;overflow-y:auto;margin:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-flask" style="color:#6366f1;"></i> Simulate OTA Message
            </h3>
            <button onclick="document.getElementById('simulateModal').style.display='none'" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;">&times;</button>
        </div>
        <p style="font-size:12px;color:#64748b;margin:0 0 14px;background:#f8fafc;border-radius:8px;padding:10px 12px;border-left:3px solid #6366f1;">
            Paste any OTA-format WhatsApp message below. This bypasses the live webhook and directly runs the parser — useful for testing when your webhook is pointed at production.
        </p>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:11.5px;font-family:monospace;color:#78350f;white-space:pre-wrap;line-height:1.6;">New Booking Confirmation
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
Special Request: Early check-in if possible</div>
        <textarea id="sim-message" rows="12"
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:monospace;box-sizing:border-box;resize:vertical;line-height:1.6;"
            placeholder="Paste OTA booking confirmation message here..."></textarea>
        <div id="sim-result" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;"></div>
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button onclick="runSimulate()" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-play" style="margin-right:6px;"></i>Run Parser
            </button>
            <button onclick="document.getElementById('simulateModal').style.display='none'" style="background:#f1f5f9;border:none;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editImpModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 20px;">Edit Import Details</h3>
        <div id="editImpContent"></div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button onclick="saveEditImport()" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Save Changes</button>
            <button onclick="document.getElementById('editImpModal').style.display='none'" style="flex:1;background:#f1f5f9;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

<script>
var editingImpId = null;

function runSimulate() {
    var msg = document.getElementById('sim-message').value.trim();
    if (!msg) { alert('Please paste a message first.'); return; }
    var res = document.getElementById('sim-result');
    res.style.display = 'none';
    fetch('{{ route("ota-bookings.simulate") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
    })
    .then(r => r.json())
    .then(d => {
        res.style.display = 'block';
        res.style.background = d.success ? '#dcfce7' : '#fee2e2';
        res.style.color = d.success ? '#065f46' : '#991b1b';
        res.style.border = '1px solid ' + (d.success ? '#bbf7d0' : '#fecaca');
        res.innerHTML = (d.success ? '<i class="fas fa-check-circle" style="margin-right:6px;"></i>' : '<i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>') + d.message;
        if (d.success) setTimeout(() => { document.getElementById('simulateModal').style.display='none'; location.reload(); }, 1800);
    })
    .catch(() => {
        res.style.display='block'; res.style.background='#fee2e2'; res.style.color='#991b1b';
        res.innerHTML = 'Network error. Please try again.';
    });
}

function toggleRaw(id) {
    var el = document.getElementById('raw-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function confirmImport(id) {
    if (!confirm('Confirm this OTA import and create a booking?')) return;
    fetch('/ota-bookings/' + id + '/confirm', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            var row = document.getElementById('imp-' + id);
            row.style.borderColor = '#bbf7d0';
            row.innerHTML = '<div style="padding:12px 0;color:#059669;font-weight:700;font-size:13px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i>' + d.message + ' Refresh to see booking link.</div>';
        } else {
            alert(d.message || 'Error confirming import.');
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}

function rejectImport(id) {
    if (!confirm('Reject this import?')) return;
    fetch('/ota-bookings/' + id + '/reject', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            var row = document.getElementById('imp-' + id);
            row.style.borderColor = '#fecaca';
            row.querySelector('[style*="pending"]') && (row.querySelector('[style*="pending"]').textContent = 'Rejected');
            row.querySelectorAll('button').forEach(b => b.remove());
        }
    });
}

function openEditModal(id, data) {
    editingImpId = id;
    var fs = 'width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;';
    var lbl = 'display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;';
    document.getElementById('editImpContent').innerHTML =
        '<div style="margin-bottom:12px;"><label style="' + lbl + '">Guest Name</label><input id="ei-guest_name" type="text" style="' + fs + '" value="' + (data.guest_name || '') + '"></div>' +
        '<div style="margin-bottom:12px;"><label style="' + lbl + '">Guest Phone</label><input id="ei-guest_phone" type="text" style="' + fs + '" value="' + (data.guest_phone || '') + '"></div>' +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">' +
        '<div><label style="' + lbl + '">Check-in</label><input id="ei-checkin" type="date" style="' + fs + '" value="' + (data.checkin || '') + '"></div>' +
        '<div><label style="' + lbl + '">Check-out</label><input id="ei-checkout" type="date" style="' + fs + '" value="' + (data.checkout || '') + '"></div>' +
        '</div>' +
        '<div style="margin-bottom:12px;"><label style="' + lbl + '">Room Type</label><input id="ei-room_type" type="text" style="' + fs + '" value="' + (data.room_type || '') + '"></div>' +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">' +
        '<div><label style="' + lbl + '">Guests</label><input id="ei-guests_count" type="text" style="' + fs + '" value="' + (data.guests_count || '') + '"></div>' +
        '<div><label style="' + lbl + '">Amount (₹)</label><input id="ei-amount" type="number" style="' + fs + '" value="' + (data.amount || '') + '"></div>' +
        '</div>' +
        '<div><label style="' + lbl + '">Special Request</label><textarea id="ei-special_request" rows="2" style="' + fs + 'resize:vertical;">' + (data.special_request || '') + '</textarea></div>';
    document.getElementById('editImpModal').style.display = 'flex';
}

function saveEditImport() {
    if (!editingImpId) return;
    var body = {
        guest_name:     document.getElementById('ei-guest_name').value,
        guest_phone:    document.getElementById('ei-guest_phone').value,
        checkin:        document.getElementById('ei-checkin').value,
        checkout:       document.getElementById('ei-checkout').value,
        room_type:      document.getElementById('ei-room_type').value,
        guests_count:   document.getElementById('ei-guests_count').value,
        amount:         document.getElementById('ei-amount').value,
        special_request:document.getElementById('ei-special_request').value,
    };
    fetch('/ota-bookings/' + editingImpId, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('editImpModal').style.display = 'none';
            location.reload();
        } else {
            alert(d.message || 'Error saving.');
        }
    });
}
</script>
@endsection
