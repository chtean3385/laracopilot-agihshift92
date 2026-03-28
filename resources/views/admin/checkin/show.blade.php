@extends('layouts.admin')
@section('title','Process Check-In')
@section('page-title','Process Check-In')
@section('page-subtitle','Confirm arrival for ' . $booking->customer->name)
@section('content')
<div class="max-w-3xl space-y-5">
    <div class="flex items-center gap-3 flex-wrap">
    <a href="{{ route('checkin.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    @if(\App\Models\Module::isEnabled('pathik'))
    <button onclick="fillPathikCheckin()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
        <i class="fas fa-clipboard-list"></i> Fill Pathik Portal
    </button>
    @endif
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-user text-cyan-500 mr-2"></i>Guest Details</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Name</span><span class="font-semibold">{{ $booking->customer->name }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Phone</span><span class="font-semibold">{{ $booking->customer->phone }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">ID Type</span><span class="font-semibold">{{ ucwords(str_replace('_',' ',$booking->customer->id_type)) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">ID No</span><span class="font-semibold">{{ $booking->customer->id_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Guests</span><span class="font-semibold">{{ $booking->adults }} Adults @if($booking->children > 0), {{ $booking->children }} Children @endif</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-door-open text-cyan-500 mr-2"></i>Room & Booking</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Booking #</span><span class="font-mono font-bold text-cyan-600">{{ $booking->booking_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Room</span><span class="font-bold text-2xl">{{ $booking->room->room_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Type</span><span class="font-semibold">{{ ucfirst($booking->room->type) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Check-In</span><span class="font-semibold">{{ $booking->check_in_date->format('d M Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Check-Out</span><span class="font-semibold">{{ $booking->check_out_date->format('d M Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Nights</span><span class="font-semibold">{{ $booking->nights }}</span></div>
                <div class="flex justify-between text-sm border-t pt-2"><span class="text-gray-500">Total</span><span class="font-bold">₹{{ number_format($booking->total_amount) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Advance Paid</span><span class="text-emerald-600 font-bold">₹{{ number_format($booking->advance_payment) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Balance Due</span><span class="{{ $booking->balance_due > 0 ? 'text-red-500' : 'text-emerald-600' }} font-bold">₹{{ number_format($booking->balance_due) }}</span></div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-5"><i class="fas fa-sign-in-alt text-emerald-500 mr-2"></i>Complete Check-In</h3>
        <form action="{{ route('checkin.process', $booking->id) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Additional Payment (₹)</label>
                    <input type="number" name="additional_payment" value="0" min="0" step="0.01" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">Leave 0 if no payment at check-in</p>
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Check-In Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any notes about the check-in..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-5 border-t border-gray-100">
                <a href="{{ route('checkin.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt mr-2"></i>Confirm Check-In</button>
            </div>
        </form>
    </div>

    {{-- ── Additional Guests & Signatures ───────────────────────────────── --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
            <h3 style="font-size:15px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-users" style="color:#7c3aed;margin-right:7px;"></i>Additional Guests &amp; Signatures</h3>
            <button onclick="toggleCheckinGuestForm()" style="padding:7px 14px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-plus" style="margin-right:4px;"></i>Add Guest
            </button>
        </div>

        {{-- Quick Add Guest Form --}}
        <div id="checkinGuestForm" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:14px;">
            <form id="ciGuestForm" onsubmit="submitCheckinGuest(event)" enctype="multipart/form-data">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;max-width:100%;">
                <div style="grid-column:1/-1;">
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">Full Name *</label>
                    <input type="text" id="ci_name" placeholder="Guest full name" required style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">Relation</label>
                    <select id="ci_relation" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                        @foreach(\App\Models\BookingGuest::relations() as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">Age</label>
                    <input type="number" id="ci_age" min="0" max="120" placeholder="Age" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">Gender</label>
                    <select id="ci_gender" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">ID Type</label>
                    <select id="ci_id_type" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                        <option value="">None</option>
                        @foreach(\App\Models\BookingGuest::idTypes() as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:3px;">ID Number</label>
                    <input type="text" id="ci_id_number" placeholder="ID (optional)" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;box-sizing:border-box;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:10px;font-weight:700;color:#dc2626;text-transform:uppercase;display:block;margin-bottom:3px;">📸 ID Proof *</label>
                    <input type="file" id="ci_document" accept=".jpg,.jpeg,.png,.pdf" placeholder="Upload ID proof" required style="width:100%;padding:8px 10px;border:1.5px solid #fecaca;border-radius:7px;font-size:12px;box-sizing:border-box;background:#fff7f7;">
                    <small style="font-size:9px;color:#7f1d1d;margin-top:2px;display:block;">JPG/PNG/PDF, max 5MB</small>
                </div>
            </div>
            <div style="display:flex;gap:7px;margin-top:10px;">
                <button type="submit" style="flex:1;padding:8px 10px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;" id="btnCiSave">
                    <i class="fas fa-save" style="margin-right:4px;"></i>Save
                </button>
                <button type="button" onclick="toggleCheckinGuestForm()" style="padding:8px 10px;background:#f1f5f9;color:#475569;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">Cancel</button>
            </div>
            </form>
        </div>

        {{-- Existing guests with signature pads --}}
        <div id="ciGuestsList">
        @forelse($booking->bookingGuests as $guest)
            <div id="ciRow{{ $guest->id }}" style="border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;overflow:hidden;">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:10px 14px;background:#f8fafc;">
                    <div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-user" style="color:#7c3aed;font-size:12px;"></i>
                    </div>
                    <div style="flex:1;min-width:100px;">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $guest->name }}</div>
                        <div style="font-size:11px;color:#64748b;">{{ $guest->relation ?? '' }}{{ $guest->age ? ' · ' . $guest->age . ' yrs' : '' }}</div>
                    </div>
                    <button onclick="toggleSigPad({{ $guest->id }})" style="padding:5px 12px;background:{{ $guest->signature ? '#dcfce7' : '#fef3c7' }};color:{{ $guest->signature ? '#16a34a' : '#92400e' }};border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-{{ $guest->signature ? 'check' : 'signature' }}" style="margin-right:3px;"></i>
                        {{ $guest->signature ? 'Signed' : 'Capture Signature' }}
                    </button>
                    <button onclick="ciRemoveGuest({{ $guest->id }})" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;cursor:pointer;" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                {{-- Signature Pad --}}
                <div id="sigPad{{ $guest->id }}" style="display:none;padding:14px;border-top:1px solid #f1f5f9;">
                    <p style="font-size:11px;color:#64748b;margin:0 0 8px;">Sign in the box below (mouse or touch):</p>
                    <canvas id="canvas{{ $guest->id }}" width="480" height="140"
                        style="border:2px dashed #cbd5e1;border-radius:8px;cursor:crosshair;background:#fdfdfd;max-width:100%;touch-action:none;"></canvas>
                    <div style="display:flex;gap:7px;margin-top:8px;">
                        <button onclick="clearSig({{ $guest->id }})" style="padding:6px 12px;background:#f1f5f9;color:#475569;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-eraser" style="margin-right:4px;"></i>Clear
                        </button>
                        <button onclick="saveSig({{ $guest->id }})" style="padding:6px 14px;background:#16a34a;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-save" style="margin-right:4px;"></i>Save Signature
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div id="ciNoGuests" style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">
                <i class="fas fa-user-plus" style="font-size:24px;margin-bottom:7px;display:block;"></i>
                No additional guests. Click "Add Guest" to register family or group members.
            </div>
        @endforelse
        </div>
    </div>
</div>

<script>
var ciBookingId = {{ $booking->id }};
var ciCsrf = document.querySelector('meta[name="csrf-token"]').content;

function toggleCheckinGuestForm() {
    var f = document.getElementById('checkinGuestForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

function submitCheckinGuest(e) {
    e.preventDefault();
    var name = document.getElementById('ci_name').value.trim();
    var docInput = document.getElementById('ci_document');
    
    if (!name) { alert('Guest name is required.'); return; }
    if (!docInput.files || docInput.files.length === 0) {
        alert('ID Proof document is required. Please upload a JPG, PNG, or PDF file.');
        return;
    }
    
    var btn = document.getElementById('btnCiSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:4px;"></i>Saving...';
    
    var data = new FormData();
    data.append('_token', ciCsrf);
    data.append('name', name);
    data.append('relation', document.getElementById('ci_relation').value);
    data.append('age', document.getElementById('ci_age').value);
    data.append('gender', document.getElementById('ci_gender').value);
    data.append('id_type', document.getElementById('ci_id_type').value);
    data.append('id_number', document.getElementById('ci_id_number').value);
    data.append('document', docInput.files[0]);
    fetch('/bookings/' + ciBookingId + '/guests', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest'},
        body: data
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) {
            var g = res.guest;
            var noMsg = document.getElementById('ciNoGuests');
            if (noMsg) noMsg.remove();
            var html = '<div id="ciRow' + g.id + '" style="border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;overflow:hidden;">'
                + '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:10px 14px;background:#f8fafc;">'
                + '<div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-user" style="color:#7c3aed;font-size:12px;"></i></div>'
                + '<div style="flex:1;"><div style="font-size:13px;font-weight:700;color:#1e293b;">' + g.name + '</div><div style="font-size:11px;color:#64748b;">' + (g.relation || '') + '</div></div>'
                + '<button onclick="toggleSigPad(' + g.id + ')" id="sigBtn' + g.id + '" style="padding:5px 12px;background:#fef3c7;color:#92400e;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;"><i class="fas fa-signature" style="margin-right:3px;"></i>Capture Signature</button>'
                + '<button onclick="ciRemoveGuest(' + g.id + ')" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;cursor:pointer;"><i class="fas fa-times"></i></button>'
                + '</div>'
                + '<div id="sigPad' + g.id + '" style="display:none;padding:14px;border-top:1px solid #f1f5f9;">'
                + '<p style="font-size:11px;color:#64748b;margin:0 0 8px;">Sign in the box below:</p>'
                + '<canvas id="canvas' + g.id + '" width="480" height="140" style="border:2px dashed #cbd5e1;border-radius:8px;cursor:crosshair;background:#fdfdfd;max-width:100%;touch-action:none;"></canvas>'
                + '<div style="display:flex;gap:7px;margin-top:8px;">'
                + '<button onclick="clearSig(' + g.id + ')" style="padding:6px 12px;background:#f1f5f9;color:#475569;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-eraser" style="margin-right:4px;"></i>Clear</button>'
                + '<button onclick="saveSig(' + g.id + ')" style="padding:6px 14px;background:#16a34a;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-save" style="margin-right:4px;"></i>Save Signature</button>'
                + '</div></div></div>';
            document.getElementById('ciGuestsList').insertAdjacentHTML('beforeend', html);
            initCanvas(g.id);
            document.getElementById('ci_name').value = '';
            document.getElementById('ci_relation').value = 'Spouse';
            document.getElementById('ci_age').value = '';
            document.getElementById('ci_gender').value = '';
            document.getElementById('ci_id_type').value = '';
            document.getElementById('ci_id_number').value = '';
            document.getElementById('ci_document').value = '';
            toggleCheckinGuestForm();
        } else {
            alert('Error adding guest. Please try again.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save" style="margin-right:4px;"></i>Save';
    }).catch(function(err) {
        alert('Request failed: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save" style="margin-right:4px;"></i>Save';
    });
}

function ciRemoveGuest(guestId) {
    if (!confirm('Remove this guest?')) return;
    fetch('/bookings/' + ciBookingId + '/guests/' + guestId, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
        body: JSON.stringify({_token: ciCsrf})
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) {
            var row = document.getElementById('ciRow' + guestId);
            if (row) row.remove();
        }
    });
}

var sigPads = {};

function initCanvas(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var drawing = false;

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var scaleX = canvas.width / rect.width;
        var scaleY = canvas.height / rect.height;
        var src = e.touches ? e.touches[0] : e;
        return { x: (src.clientX - rect.left) * scaleX, y: (src.clientY - rect.top) * scaleY };
    }

    function start(e) { e.preventDefault(); drawing = true; var p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
    function draw(e)  { e.preventDefault(); if (!drawing) return; var p = getPos(e); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#1e293b'; ctx.lineTo(p.x, p.y); ctx.stroke(); }
    function stop(e)  { e.preventDefault(); drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, {passive:false});
    canvas.addEventListener('touchmove', draw, {passive:false});
    canvas.addEventListener('touchend', stop, {passive:false});

    sigPads[guestId] = canvas;
}

function toggleSigPad(guestId) {
    var pad = document.getElementById('sigPad' + guestId);
    if (!pad) return;
    var visible = pad.style.display !== 'none';
    pad.style.display = visible ? 'none' : 'block';
    if (!visible) initCanvas(guestId);
}

function clearSig(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
}

function saveSig(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (!canvas) return;
    var dataUrl = canvas.toDataURL('image/png');
    fetch('/bookings/' + ciBookingId + '/guests/' + guestId + '/signature', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
        body: JSON.stringify({ signature: dataUrl })
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) {
            document.getElementById('sigPad' + guestId).style.display = 'none';
            var btn = document.getElementById('sigBtn' + guestId);
            if (btn) { btn.style.background = '#dcfce7'; btn.style.color = '#16a34a'; btn.innerHTML = '<i class="fas fa-check" style="margin-right:3px;"></i>Signed'; }
        }
    });
}

// Init all existing signature pads
document.addEventListener('DOMContentLoaded', function() {
    @foreach($booking->bookingGuests as $guest)
    initCanvas({{ $guest->id }});
    @endforeach
});
</script>

@if(\App\Models\Module::isEnabled('pathik'))
<div id="pathikModalCheckin" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;max-width:420px;padding:16px;">
        <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
            <div style="background:linear-gradient(135deg,#f97316,#ea580c);padding:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:20px;">&#128203;</span>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;margin:0;">Pathik Portal Autofill</h3>
                        <p style="font-size:11px;opacity:.8;margin:2px 0 0;">{{ $booking->customer->name }}</p>
                    </div>
                </div>
                <button onclick="document.getElementById('pathikModalCheckin').style.display='none'" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:26px;height:26px;border-radius:8px;cursor:pointer;">&#10005;</button>
            </div>
            <div style="padding:18px;display:flex;flex-direction:column;gap:12px;">
                <div id="pathikCheckinStatus" style="padding:11px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;font-weight:600;display:none;">
                    <i class="fas fa-check-circle" style="margin-right:6px;"></i><span id="pathikCheckinStatusText"></span>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:12px;font-size:12px;color:#64748b;">
                    Guest data, ID details, and booking dates will be sent to the Chrome extension for autofill.
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="btnSendPathikCheckin" onclick="sendToPathikCheckin()" style="flex:1;padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension
                    </button>
                    <a id="btnOpenPortalCheckin" href="https://pathik.gujarat.gov.in" target="_blank" style="display:none;flex:1;padding:10px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;text-align:center;align-items:center;justify-content:center;">
                        <i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Open Pathik Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function fillPathikCheckin() {
    document.getElementById('pathikModalCheckin').style.display = 'block';
}
function sendToPathikCheckin() {
    var btn = document.getElementById('btnSendPathikCheckin');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Sending...';
    var form = new FormData();
    var d = {
        booking_id: '{{ $booking->id }}', booking_number: '{{ $booking->booking_number }}',
        name: '{{ addslashes($booking->customer->name) }}', phone: '{{ $booking->customer->phone }}',
        email: '{{ addslashes($booking->customer->email ?? '') }}',
        address: '{{ addslashes($booking->customer->address ?? '') }}',
        city: '{{ addslashes($booking->customer->city ?? '') }}',
        state: '{{ addslashes($booking->customer->state ?? '') }}',
        country: '{{ addslashes($booking->customer->country ?? 'India') }}',
        nationality: '{{ addslashes($booking->customer->nationality ?? 'Indian') }}',
        id_type: '{{ addslashes($booking->customer->id_type ?? '') }}',
        id_number: '{{ addslashes($booking->customer->id_number ?? '') }}',
        date_of_birth: '{{ $booking->customer->date_of_birth ? $booking->customer->date_of_birth->format('Y-m-d') : '' }}',
        check_in_date: '{{ $booking->check_in_date->format('Y-m-d') }}',
        check_out_date: '{{ $booking->check_out_date->format('Y-m-d') }}',
        nights: '{{ $booking->nights }}', adults: '{{ $booking->adults }}',
        children: '{{ $booking->children }}', room_number: '{{ $booking->room->room_number }}',
        room_type: '{{ $booking->room->type }}',
    };
    Object.keys(d).forEach(function(k) { form.append(k, d[k]); });
    fetch('{{ route('pathik.pending.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: form,
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok) {
            document.getElementById('pathikCheckinStatus').style.display = 'block';
            document.getElementById('pathikCheckinStatusText').textContent = 'Data ready! Open Pathik Portal and click Autofill Now.';
            document.getElementById('btnOpenPortalCheckin').style.display = 'flex';
            btn.style.display = 'none';
        } else {
            alert('Error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
        }
    }).catch(function(e) {
        alert('Failed: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
    });
}
document.getElementById('pathikModalCheckin').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endif
@endsection
