@extends('layouts.admin')
@section('title','QR Arrivals')
@section('page-title','QR Arrivals')
@section('page-subtitle','Guests who checked in via QR scan')

@section('content')
<div class="space-y-5">

    {{-- Toolbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or phone…"
                style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;min-width:200px;">
            <select name="status" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
                <option value="">All Statuses</option>
                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" style="padding:9px 18px;background:#6366f1;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
            @if(request('search') || request('status'))
            <a href="{{ route('qr-arrivals.index') }}" style="padding:9px 14px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">Clear</a>
            @endif
        </form>
        <a href="{{ route('qr-arrivals.print-qr') }}" target="_blank"
            style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <i class="fas fa-qrcode"></i> Print Hotel Check-In QR
        </a>
    </div>

    @if(session('success'))
    <div style="background:#ecfdf5;border:1.5px solid #86efac;border-radius:12px;padding:12px 16px;color:#166534;font-weight:600;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle" style="color:#16a34a;"></i> {{ session('success') }}
    </div>
    @endif

    @if($pendingCount > 0)
    <div style="background:#eff6ff;border:1.5px solid #93c5fd;border-radius:12px;padding:12px 16px;color:#1e40af;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-clock" style="color:#3b82f6;"></i>
        {{ $pendingCount }} pending request{{ $pendingCount != 1 ? 's' : '' }} waiting for room assignment — click <strong>Assign Room</strong> to process quickly.
    </div>
    @endif

    {{-- Table --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">#</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Guest</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Phone</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Check-In</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Guests</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Status</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Received</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    @php
                        $additionalGuests = is_array($req->additional_guests) ? $req->additional_guests : (json_decode($req->additional_guests, true) ?? []);
                        $reqData = [
                            'id'            => $req->id,
                            'name'          => $req->name,
                            'phone'         => $req->phone,
                            'email'         => $req->email ?? '',
                            'id_type'       => $req->id_type ?? '',
                            'id_number'     => $req->id_number ?? '',
                            'address'       => $req->address ?? '',
                            'dob'           => $req->date_of_birth?->format('d M Y') ?? '',
                            'guests_count'  => $req->guests_count,
                            'checkin'       => $req->requested_check_in?->format('Y-m-d') ?? '',
                            'checkin_label' => $req->requested_check_in?->format('d M Y') ?? '',
                            'checkout'      => $req->requested_check_out?->format('Y-m-d') ?? '',
                            'checkout_label'=> $req->requested_check_out?->format('d M Y') ?? '',
                            'notes'         => $req->notes ?? '',
                            'status'        => $req->status,
                            'received'      => $req->created_at->diffForHumans(),
                            'signature'     => $req->signature_data ?? '',
                            'id_doc_url'    => $req->id_document_path ? asset('storage/' . $req->id_document_path) : '',
                            'additional'    => $additionalGuests,
                            'assign_url'    => route('qr-arrivals.assign', $req->id),
                            'cancel_url'    => route('qr-arrivals.cancel', $req->id),
                        ];
                    @endphp
                    <tr style="border-top:1px solid #f1f5f9;{{ $req->status === 'pending' ? 'background:#fafaf8;' : '' }}"
                        class="qr-row" data-req='{!! json_encode($reqData) !!}'>
                        <td style="padding:12px 16px;color:#94a3b8;font-size:12px;">{{ $req->id }}</td>
                        <td style="padding:12px 16px;">
                            <div style="font-weight:700;color:#1e293b;">{{ $req->name }}</div>
                            @if($req->email)
                            <div style="font-size:12px;color:#94a3b8;">{{ $req->email }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-weight:600;color:#1e293b;">{{ $req->phone }}</td>
                        <td style="padding:12px 16px;">
                            @if($req->requested_check_in)
                            <div style="font-weight:600;color:#1e293b;">{{ $req->requested_check_in->format('d M Y') }}</div>
                            @if($req->requested_check_out)
                            <div style="font-size:12px;color:#94a3b8;">→ {{ $req->requested_check_out->format('d M Y') }}</div>
                            @endif
                            @else
                            <span style="color:#94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-weight:600;color:#1e293b;">{{ $req->guests_count }}</td>
                        <td style="padding:12px 16px;">
                            @if($req->status === 'pending')
                            <span style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Pending</span>
                            @elseif($req->status === 'converted')
                            <span style="background:#ecfdf5;color:#166534;border:1px solid #86efac;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Converted</span>
                            @else
                            <span style="background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Cancelled</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-size:12px;color:#94a3b8;">{{ $req->created_at->diffForHumans() }}</td>
                        <td style="padding:12px 16px;">
                            <div style="display:flex;gap:6px;">
                                <button type="button" onclick="openQrModal(this.closest('tr'))"
                                    style="padding:6px 12px;background:#f0f9ff;color:#0284c7;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-eye" style="margin-right:4px;"></i>View
                                </button>
                                @if($req->status === 'pending')
                                <button type="button" onclick="openQrModal(this.closest('tr'), true)"
                                    style="padding:6px 12px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-door-open" style="margin-right:4px;"></i>Assign Room
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:48px;text-align:center;color:#94a3b8;">
                            <i class="fas fa-qrcode" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                            No QR check-in requests yet.<br>
                            <span style="font-size:12px;">Guests can scan the hotel QR code to submit their details.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div style="padding:12px 16px;border-top:1px solid #f1f5f9;">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     QUICK-VIEW / ASSIGN MODAL
═══════════════════════════════════════════════════════════ --}}
<div id="qrModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;" onclick="if(event.target===this)closeQrModal()">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:640px;margin:auto;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;" onclick="event.stopPropagation()">

        {{-- Header --}}
        <div id="qrModalHeader" style="padding:20px 24px 16px;background:linear-gradient(135deg,#1d4ed8,#6366f1);display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:17px;font-weight:900;color:#fff;" id="qrModalName">Guest Name</div>
                <div style="font-size:12px;color:rgba(255,255,255,.8);margin-top:2px;" id="qrModalSub">—</div>
            </div>
            <button onclick="closeQrModal()" style="width:32px;height:32px;background:rgba(255,255,255,.15);border:none;border-radius:8px;color:#fff;cursor:pointer;font-size:16px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Body --}}
        <div style="padding:20px 24px;max-height:70vh;overflow-y:auto;display:flex;flex-direction:column;gap:16px;">

            {{-- Guest Details grid --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="background:#f8fafc;border-radius:12px;padding:12px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Phone</div>
                    <div style="font-weight:700;color:#1e293b;" id="qrModalPhone">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:12px;padding:12px;" id="qrModalEmailBox">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Email</div>
                    <div style="font-weight:600;color:#1e293b;" id="qrModalEmail">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:12px;padding:12px;" id="qrModalDobBox">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Date of Birth</div>
                    <div style="font-weight:600;color:#1e293b;" id="qrModalDob">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:12px;padding:12px;" id="qrModalIdBox">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">ID</div>
                    <div style="font-weight:600;color:#1e293b;" id="qrModalId">—</div>
                </div>
                <div style="background:#f8fafc;border-radius:12px;padding:12px;grid-column:span 2;" id="qrModalAddrBox">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Address</div>
                    <div style="font-weight:600;color:#1e293b;" id="qrModalAddr">—</div>
                </div>
            </div>

            {{-- Check-in dates + guests --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                <div style="background:#eff6ff;border-radius:12px;padding:12px;text-align:center;">
                    <div style="font-size:11px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Check-In</div>
                    <div style="font-weight:800;color:#1e40af;" id="qrModalCheckin">—</div>
                </div>
                <div style="background:#fff7ed;border-radius:12px;padding:12px;text-align:center;">
                    <div style="font-size:11px;font-weight:700;color:#f97316;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Check-Out</div>
                    <div style="font-weight:800;color:#c2410c;" id="qrModalCheckout">—</div>
                </div>
                <div style="background:#f0fdf4;border-radius:12px;padding:12px;text-align:center;">
                    <div style="font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Guests</div>
                    <div style="font-weight:800;color:#166534;" id="qrModalGuests">—</div>
                </div>
            </div>

            {{-- Additional guests --}}
            <div id="qrModalAdditionalWrap" style="display:none;">
                <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Additional Guests</div>
                <div id="qrModalAdditional" style="display:flex;flex-direction:column;gap:6px;"></div>
            </div>

            {{-- Signature + ID doc side-by-side --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div id="qrModalSigWrap" style="display:none;">
                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Signature</div>
                    <img id="qrModalSig" src="" alt="Signature" style="width:100%;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;">
                </div>
                <div id="qrModalDocWrap" style="display:none;">
                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">ID Document</div>
                    <a id="qrModalDocLink" href="#" target="_blank"
                        style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;color:#0284c7;font-weight:700;font-size:13px;">
                        <i class="fas fa-file-alt" style="font-size:18px;"></i> View ID Document
                    </a>
                </div>
            </div>

            {{-- Assign Room form (shown for pending only) --}}
            <div id="qrModalAssignSection" style="display:none;border-top:1.5px solid #f1f5f9;padding-top:16px;">
                <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-door-open" style="color:#10b981;"></i> Assign Room &amp; Create Booking
                </div>
                <form id="qrAssignForm" method="POST" action="">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Room <span style="color:#ef4444;">*</span></label>
                            <select name="room_id" id="qrRoomSelect" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;"
                                onchange="qrRoomChanged(this)">
                                <option value="">Select a room…</option>
                                @foreach($availableRooms as $room)
                                <option value="{{ $room->id }}" data-price="{{ $room->price_per_night ?? 0 }}"
                                    data-label="{{ $room->room_number }} — {{ ucfirst($room->type) }} (₹{{ number_format($room->price_per_night ?? 0) }}/night)">
                                    {{ $room->room_number }} — {{ ucfirst($room->type) }} (₹{{ number_format($room->price_per_night ?? 0) }}/night)
                                </option>
                                @endforeach
                            </select>
                            @if($availableRooms->isEmpty())
                            <div style="font-size:11px;color:#f59e0b;margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> No available rooms right now.</div>
                            @endif
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Adults</label>
                            <input type="number" name="adults" id="qrAdults" min="1" value="1"
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Check-In Date <span style="color:#ef4444;">*</span></label>
                            <input type="date" name="check_in_date" id="qrCheckinDate" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;"
                                onchange="qrRecalcTotal()">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Check-Out Date <span style="color:#ef4444;">*</span></label>
                            <input type="date" name="check_out_date" id="qrCheckoutDate" required
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;"
                                onchange="qrRecalcTotal()">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Advance Payment (₹)</label>
                            <input type="number" name="advance_payment" id="qrAdvance" min="0" value="0" step="0.01"
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Payment Method</label>
                            <select name="payment_method"
                                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
                                <option value="">None</option>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div id="qrTotalPreview" style="display:none;background:#ecfdf5;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#166534;font-weight:700;"></div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit"
                            style="flex:1;padding:11px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:800;cursor:pointer;">
                            <i class="fas fa-check" style="margin-right:7px;"></i>Confirm &amp; Create Booking
                        </button>
                        <button type="button" id="qrCancelBtn" onclick="qrCancelRequest()"
                            style="padding:11px 16px;background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
                <form id="qrCancelForm" method="POST" action="" style="display:none;">
                    @csrf
                    @method('POST')
                </form>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
var qrAvailableRooms = {!! json_encode($availableRooms->map(fn($r) => ['id' => $r->id, 'price' => $r->price_per_night ?? 0])->keyBy('id')) !!};

function openQrModal(row, focusAssign) {
    var req = JSON.parse(row.dataset.req);

    // Header
    document.getElementById('qrModalName').textContent = req.name;
    var statusLabel = req.status === 'pending' ? '⏳ Pending — awaiting room assignment'
                    : (req.status === 'converted' ? '✅ Converted to Booking' : '❌ Cancelled');
    document.getElementById('qrModalSub').textContent = req.phone + ' · Received ' + req.received + ' · ' + statusLabel;

    // Basic fields
    document.getElementById('qrModalPhone').textContent = req.phone || '—';
    document.getElementById('qrModalEmail').textContent = req.email || '—';
    document.getElementById('qrModalDob').textContent = req.dob || '—';
    document.getElementById('qrModalId').textContent = (req.id_type && req.id_number) ? req.id_type + ': ' + req.id_number : (req.id_type || req.id_number || '—');
    document.getElementById('qrModalAddr').textContent = req.address || '—';
    document.getElementById('qrModalAddrBox').style.display = req.address ? '' : 'none';

    // Dates
    document.getElementById('qrModalCheckin').textContent = req.checkin_label || '—';
    document.getElementById('qrModalCheckout').textContent = req.checkout_label || '—';
    document.getElementById('qrModalGuests').textContent = req.guests_count + ' guest(s)';

    // Additional guests
    var addWrap = document.getElementById('qrModalAdditionalWrap');
    var addDiv  = document.getElementById('qrModalAdditional');
    addDiv.innerHTML = '';
    if (req.additional && req.additional.length) {
        req.additional.forEach(function(g) {
            var div = document.createElement('div');
            div.style.cssText = 'background:#f8fafc;border-radius:8px;padding:8px 12px;font-size:12px;';
            div.innerHTML = '<span style="font-weight:700;color:#1e293b;">' + g.name + '</span>'
                + (g.id_type ? ' <span style="color:#64748b;">· ' + g.id_type + ': ' + (g.id_number||'—') + '</span>' : '');
            addDiv.appendChild(div);
        });
        addWrap.style.display = '';
    } else {
        addWrap.style.display = 'none';
    }

    // Signature
    var sigWrap = document.getElementById('qrModalSigWrap');
    if (req.signature) {
        document.getElementById('qrModalSig').src = req.signature;
        sigWrap.style.display = '';
    } else {
        sigWrap.style.display = 'none';
    }

    // ID doc
    var docWrap = document.getElementById('qrModalDocWrap');
    if (req.id_doc_url) {
        document.getElementById('qrModalDocLink').href = req.id_doc_url;
        docWrap.style.display = '';
    } else {
        docWrap.style.display = 'none';
    }

    // Assign section
    var assignSection = document.getElementById('qrModalAssignSection');
    if (req.status === 'pending') {
        assignSection.style.display = '';
        document.getElementById('qrAssignForm').action = req.assign_url;
        document.getElementById('qrCancelForm').action  = req.cancel_url;
        document.getElementById('qrAdults').value = req.guests_count;
        document.getElementById('qrCheckinDate').value  = req.checkin || new Date().toISOString().slice(0,10);
        document.getElementById('qrCheckoutDate').value = req.checkout || '';
        document.getElementById('qrRoomSelect').value = '';
        document.getElementById('qrAdvance').value = 0;
        document.getElementById('qrTotalPreview').style.display = 'none';
    } else {
        assignSection.style.display = 'none';
    }

    var modal = document.getElementById('qrModal');
    modal.style.display = 'flex';

    if (focusAssign && req.status === 'pending') {
        setTimeout(function() {
            assignSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            document.getElementById('qrRoomSelect').focus();
        }, 100);
    }
}

function closeQrModal() {
    document.getElementById('qrModal').style.display = 'none';
}

function qrRoomChanged(sel) {
    qrRecalcTotal();
}

function qrRecalcTotal() {
    var roomId  = document.getElementById('qrRoomSelect').value;
    var checkin = document.getElementById('qrCheckinDate').value;
    var checkout= document.getElementById('qrCheckoutDate').value;
    var preview = document.getElementById('qrTotalPreview');

    if (!roomId || !checkin || !checkout) { preview.style.display = 'none'; return; }

    var d1 = new Date(checkin), d2 = new Date(checkout);
    var nights = Math.max(1, Math.round((d2 - d1) / 86400000));
    var price  = parseFloat((qrAvailableRooms[roomId] || {}).price || 0);
    var total  = nights * price;

    preview.style.display = '';
    preview.innerHTML = '<i class="fas fa-calculator" style="margin-right:6px;"></i>'
        + nights + ' night(s) × ₹' + price.toLocaleString('en-IN') + ' = <strong>₹' + total.toLocaleString('en-IN') + '</strong>';
}

function qrCancelRequest() {
    if (!confirm('Cancel this QR check-in request? This cannot be undone.')) return;
    document.getElementById('qrCancelForm').submit();
}
</script>
@endpush
@endsection
