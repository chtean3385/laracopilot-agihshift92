@extends('layouts.admin')
@section('title','Process Check-In')
@section('page-title','Process Check-In')
@section('page-subtitle','Confirm arrival · ' . $booking->customer->name)
@section('content')

{{-- ── Global modal styles ── --}}
<style>
.ci-modal-overlay {
    display:none;position:fixed;inset:0;z-index:9000;
    background:rgba(15,23,42,.55);backdrop-filter:blur(4px);
    align-items:flex-end;justify-content:center;
    padding:0;
}
.ci-modal-overlay.show { display:flex; }
.ci-modal-box {
    background:#fff;border-radius:20px 20px 0 0;
    width:100%;max-width:600px;
    max-height:92vh;overflow-y:auto;
    box-shadow:0 -8px 40px rgba(0,0,0,.18);
    padding:0 0 env(safe-area-inset-bottom,0);
}
@media(min-width:600px){
    .ci-modal-overlay { align-items:center; padding:16px; }
    .ci-modal-box { border-radius:20px; max-height:88vh; }
}
.ci-modal-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:18px 20px;border-bottom:1px solid #f1f5f9;
    position:sticky;top:0;background:#fff;z-index:1;border-radius:20px 20px 0 0;
}
.ci-modal-body { padding:18px 20px; }
.ci-field-label {
    font-size:11px;font-weight:700;color:#64748b;
    text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px;
}
.ci-field-input {
    width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;
    border-radius:10px;font-size:14px;color:#374151;
    outline:none;transition:border-color .15s;box-sizing:border-box;
}
.ci-field-input:focus { border-color:#7c3aed; }
.ci-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
@media(max-width:420px){ .ci-grid { grid-template-columns:1fr; } }
.sig-canvas-wrap {
    border:2px dashed #cbd5e1;border-radius:10px;
    background:#fdfdfd;overflow:hidden;
    position:relative;width:100%;
}
.sig-canvas-wrap canvas { display:block;width:100%;touch-action:none;cursor:crosshair; }
/* Prevent page scroll while signing on mobile */
#primarySigPad, [id^="sigPad"] { touch-action:none; }

/* Responsive grids */
.ci-info-grid  { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
.ci-form-grid  { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:540px) {
    .ci-info-grid  { grid-template-columns:1fr; }
    .ci-form-grid  { grid-template-columns:1fr; }
}
</style>

<div style="max-width:720px;">

    {{-- ── Top action buttons ── --}}
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
        <a href="{{ route('checkin.index') }}" class="btn-secondary" style="font-size:13px;padding:9px 16px;">
            <i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back
        </a>
        @if(\App\Models\Module::isEnabled('pathik'))
        <button onclick="document.getElementById('pathikModalCheckin').style.display='flex'" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-clipboard-list"></i> Fill Pathik Portal
        </button>
        @endif
    </div>

    {{-- ── Info cards grid ── --}}
    <div class="ci-info-grid">

        {{-- Guest Details --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;">
            <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 12px;"><i class="fas fa-user" style="color:#06b6d4;margin-right:7px;"></i>Guest</h3>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Name</span>
                    <span style="font-weight:700;color:#1e293b;text-align:right;">{{ $booking->customer->name }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Phone</span>
                    <span style="font-weight:600;text-align:right;">{{ $booking->customer->phone }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">ID</span>
                    <span style="font-weight:600;text-align:right;">{{ $booking->customer->id_number ?? '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Guests</span>
                    <span style="font-weight:600;">{{ $booking->adults }}A @if($booking->children > 0)+ {{ $booking->children }}C @endif</span>
                </div>
            </div>
            {{-- Primary Guest Signature --}}
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Primary Signature</span>
                    @if($booking->customer->signature)
                    <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:999px;"><i class="fas fa-check" style="margin-right:3px;"></i>Signed</span>
                    @endif
                </div>
                @if($booking->customer->signature)
                <img src="{{ $booking->customer->signature }}" alt="Signature" style="width:100%;max-height:60px;object-fit:contain;border:1px solid #e2e8f0;border-radius:8px;background:#fdfdfd;">
                <button onclick="togglePrimaryPad()" style="margin-top:6px;width:100%;padding:6px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-redo" style="margin-right:4px;"></i>Re-sign
                </button>
                @else
                <button onclick="togglePrimaryPad()" id="btnPrimaryPad" style="width:100%;padding:8px;background:#fef3c7;color:#92400e;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-signature" style="margin-right:5px;"></i>Capture Signature
                </button>
                @endif
                <div id="primarySigPad" style="display:none;margin-top:8px;">
                    <p style="font-size:11px;color:#64748b;margin:0 0 5px;">Sign below (finger or stylus):</p>
                    <div class="sig-canvas-wrap">
                        <canvas id="primaryCanvas" height="120"></canvas>
                    </div>
                    <div style="display:flex;gap:7px;margin-top:7px;">
                        <button onclick="clearPrimaryPad()" style="flex:1;padding:7px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-eraser" style="margin-right:4px;"></i>Clear</button>
                        <button onclick="savePrimarySig()" style="flex:2;padding:7px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-save" style="margin-right:4px;"></i>Save Signature</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Room & Booking --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;">
            <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 12px;"><i class="fas fa-door-open" style="color:#06b6d4;margin-right:7px;"></i>Room &amp; Booking</h3>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Booking#</span>
                    <span style="font-family:monospace;font-weight:700;color:#0891b2;font-size:12px;">{{ $booking->booking_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Room</span>
                    <span style="font-weight:900;font-size:22px;color:#0f172a;line-height:1;">{{ $booking->room->room_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Type</span>
                    <span style="font-weight:600;">{{ ucfirst($booking->room->type) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Check-In</span>
                    <span style="font-weight:600;">{{ $booking->check_in_date->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Check-Out</span>
                    <span style="font-weight:600;">{{ $booking->check_out_date->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Nights</span>
                    <span style="font-weight:700;">{{ $booking->nights }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;border-top:1px solid #f1f5f9;padding-top:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Total</span>
                    <span style="font-weight:800;">₹{{ number_format($booking->total_amount) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Paid</span>
                    <span style="font-weight:700;color:#16a34a;">₹{{ number_format($booking->advance_payment) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:#64748b;flex-shrink:0;">Balance</span>
                    <span style="font-weight:800;color:{{ $booking->balance_due > 0 ? '#ef4444' : '#16a34a' }};">₹{{ number_format($booking->balance_due) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Additional Guests & Signatures ── --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:18px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0;">
                <i class="fas fa-users" style="color:#7c3aed;margin-right:7px;"></i>
                Additional Guests
                <span id="guestCount" style="font-size:12px;font-weight:600;color:#94a3b8;margin-left:5px;">({{ $booking->bookingGuests->count() }})</span>
            </h3>
            <button onclick="openAddGuestModal()" style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;">
                <i class="fas fa-plus"></i>Add
            </button>
        </div>

        <div id="ciGuestsList">
        @forelse($booking->bookingGuests as $guest)
            <div id="ciRow{{ $guest->id }}" style="border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;overflow:hidden;">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:10px 14px;background:#f8fafc;">
                    <div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-user" style="color:#7c3aed;font-size:12px;"></i>
                    </div>
                    <div style="flex:1;min-width:80px;">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $guest->name }}</div>
                        <div style="font-size:11px;color:#64748b;">{{ $guest->relation ?? '' }}{{ $guest->age ? ' · '.$guest->age.' yrs' : '' }}</div>
                    </div>
                    @if($guest->id_document_path)
                    <span style="font-size:10px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 7px;border-radius:999px;"><i class="fas fa-id-card" style="margin-right:2px;"></i>Doc</span>
                    @endif
                    <button onclick="toggleSigPad({{ $guest->id }})" id="sigBtn{{ $guest->id }}" style="padding:5px 11px;background:{{ $guest->signature ? '#dcfce7' : '#fef3c7' }};color:{{ $guest->signature ? '#16a34a' : '#92400e' }};border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-{{ $guest->signature ? 'check' : 'signature' }}" style="margin-right:3px;"></i>{{ $guest->signature ? 'Signed' : 'Sign' }}
                    </button>
                    <button onclick="ciRemoveGuest({{ $guest->id }})" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;cursor:pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="sigPad{{ $guest->id }}" style="display:none;padding:14px;border-top:1px solid #f1f5f9;">
                    <p style="font-size:11px;color:#64748b;margin:0 0 8px;">Sign below (finger or stylus):</p>
                    <div class="sig-canvas-wrap">
                        <canvas id="canvas{{ $guest->id }}" height="120"></canvas>
                    </div>
                    <div style="display:flex;gap:7px;margin-top:8px;">
                        <button onclick="clearSig({{ $guest->id }})" style="flex:1;padding:7px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-eraser" style="margin-right:4px;"></i>Clear</button>
                        <button onclick="saveSig({{ $guest->id }})" style="flex:2;padding:7px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-save" style="margin-right:4px;"></i>Save Signature</button>
                    </div>
                </div>
            </div>
        @empty
            <div id="ciNoGuests" style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">
                <i class="fas fa-user-plus" style="font-size:28px;margin-bottom:8px;display:block;color:#cbd5e1;"></i>
                No additional guests yet.<br>
                <button onclick="openAddGuestModal()" style="margin-top:10px;padding:8px 18px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-plus" style="margin-right:5px;"></i>Add First Guest
                </button>
            </div>
        @endforelse
        </div>
    </div>

    {{-- ── Complete Check-In Form ── --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:20px;">
        <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 16px;"><i class="fas fa-sign-in-alt" style="color:#16a34a;margin-right:7px;"></i>Complete Check-In</h3>
        <form action="{{ route('checkin.process', $booking->id) }}" method="POST">
            @csrf
            <div class="ci-form-grid">
                <div>
                    <label class="form-label">Additional Payment (₹)</label>
                    <input type="number" name="additional_payment" value="0" min="0" step="0.01" class="form-input">
                    <p style="font-size:11px;color:#94a3b8;margin-top:4px;">Leave 0 if no payment now</p>
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
                <div style="grid-column:1/-1;">
                    <label class="form-label">Check-In Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any notes about the check-in..."></textarea>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('checkin.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt" style="margin-right:7px;"></i>Confirm Check-In</button>
            </div>
        </form>
    </div>

</div>

{{-- ══════════════════════════════════════════
     ADD GUEST MODAL (bottom sheet on mobile)
     ══════════════════════════════════════════ --}}
<div id="addGuestModal" class="ci-modal-overlay" onclick="if(event.target===this)closeAddGuestModal()">
    <div class="ci-modal-box">
        <div class="ci-modal-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user-plus" style="color:#7c3aed;font-size:13px;"></i>
                </div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Add Guest</div>
                    <div style="font-size:11px;color:#94a3b8;">Register additional guest for this booking</div>
                </div>
            </div>
            <button onclick="closeAddGuestModal()" style="width:32px;height:32px;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:#64748b;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ci-modal-body">
            <form id="ciGuestForm" onsubmit="submitCheckinGuest(event)" enctype="multipart/form-data">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label class="ci-field-label">Full Name <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="ci_name" class="ci-field-input" placeholder="Guest full name" required>
                    </div>
                    <div class="ci-grid">
                        <div>
                            <label class="ci-field-label">Relation</label>
                            <select id="ci_relation" class="ci-field-input">
                                @foreach(\App\Models\BookingGuest::relations() as $r)
                                <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="ci-field-label">Age</label>
                            <input type="number" id="ci_age" class="ci-field-input" min="0" max="120" placeholder="Age">
                        </div>
                        <div>
                            <label class="ci-field-label">Gender</label>
                            <select id="ci_gender" class="ci-field-input">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="ci-field-label">Nationality</label>
                            <input type="text" id="ci_nationality" class="ci-field-input" value="Indian" placeholder="Nationality">
                        </div>
                        <div>
                            <label class="ci-field-label">ID Type</label>
                            <select id="ci_id_type" class="ci-field-input">
                                <option value="">None</option>
                                @foreach(\App\Models\BookingGuest::idTypes() as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="ci-field-label">ID Number <span style="color:#94a3b8;font-size:9px;">(optional)</span></label>
                            <input type="text" id="ci_id_number" class="ci-field-input" placeholder="ID number">
                        </div>
                    </div>
                    <div>
                        <label class="ci-field-label" style="color:#dc2626;">
                            <i class="fas fa-id-card" style="margin-right:5px;"></i>ID Proof Document <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="file" id="ci_document" class="ci-field-input" accept=".jpg,.jpeg,.png,.pdf" required style="padding:8px 12px;border-color:#fecaca;background:#fff7f7;">
                        <p style="font-size:10px;color:#7f1d1d;margin-top:4px;">JPG / PNG / PDF, max 5 MB. Required for compliance.</p>
                    </div>
                    <div style="display:flex;gap:10px;padding-top:4px;">
                        <button type="button" onclick="closeAddGuestModal()" style="flex:1;padding:12px;background:#f1f5f9;color:#475569;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;">Cancel</button>
                        <button type="submit" id="btnCiSave" style="flex:2;padding:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-save" style="margin-right:6px;"></i>Save Guest
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═════════════════════════
     PATHIK MODAL
     ═════════════════════════ --}}
@if(\App\Models\Module::isEnabled('pathik'))
<div id="pathikModalCheckin" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:400px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#f97316,#ea580c);padding:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:20px;">&#128203;</span>
                <div>
                    <h3 style="font-size:14px;font-weight:800;margin:0;">Pathik Portal Autofill</h3>
                    <p style="font-size:11px;opacity:.8;margin:2px 0 0;">{{ $booking->customer->name }}</p>
                </div>
            </div>
            <button onclick="document.getElementById('pathikModalCheckin').style.display='none'" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:16px;">&#10005;</button>
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
                <a id="btnOpenPortalCheckin" href="https://pathik.gujarat.gov.in" target="_blank" style="display:none;flex:1;padding:10px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;text-align:center;">
                    <i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Open Portal
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<script>
var ciBookingId = {{ $booking->id }};
var ciCsrf     = document.querySelector('meta[name="csrf-token"]').content;

/* ── Add Guest Modal ── */
function openAddGuestModal() {
    document.getElementById('addGuestModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(function(){ document.getElementById('ci_name').focus(); }, 200);
}
function closeAddGuestModal() {
    document.getElementById('addGuestModal').classList.remove('show');
    document.body.style.overflow = '';
}

/* ── Submit Add Guest Form ── */
function submitCheckinGuest(e) {
    e.preventDefault();
    var name     = document.getElementById('ci_name').value.trim();
    var docInput = document.getElementById('ci_document');
    if (!name)   { alert('Guest name is required.'); return; }
    if (!docInput.files || docInput.files.length === 0) {
        alert('ID Proof document is required. Please upload a JPG, PNG, or PDF file.');
        return;
    }
    var btn = document.getElementById('btnCiSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Saving...';

    var data = new FormData();
    data.append('_token', ciCsrf);
    data.append('name',        name);
    data.append('relation',    document.getElementById('ci_relation').value);
    data.append('age',         document.getElementById('ci_age').value);
    data.append('gender',      document.getElementById('ci_gender').value);
    data.append('nationality', document.getElementById('ci_nationality').value);
    data.append('id_type',     document.getElementById('ci_id_type').value);
    data.append('id_number',   document.getElementById('ci_id_number').value);
    data.append('document',    docInput.files[0]);

    fetch('/bookings/' + ciBookingId + '/guests', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var g = res.guest;
            var noMsg = document.getElementById('ciNoGuests');
            if (noMsg) noMsg.remove();
            var docBadge = g.id_document_path ? '<span style="font-size:10px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 7px;border-radius:999px;"><i class="fas fa-id-card" style="margin-right:2px;"></i>Doc</span>' : '';
            var html = '<div id="ciRow' + g.id + '" style="border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;overflow:hidden;">'
                + '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:10px 14px;background:#f8fafc;">'
                + '<div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-user" style="color:#7c3aed;font-size:12px;"></i></div>'
                + '<div style="flex:1;min-width:80px;"><div style="font-size:13px;font-weight:700;color:#1e293b;">' + g.name + '</div><div style="font-size:11px;color:#64748b;">' + (g.relation || '') + '</div></div>'
                + docBadge
                + '<button onclick="toggleSigPad(' + g.id + ')" id="sigBtn' + g.id + '" style="padding:5px 11px;background:#fef3c7;color:#92400e;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;"><i class="fas fa-signature" style="margin-right:3px;"></i>Sign</button>'
                + '<button onclick="ciRemoveGuest(' + g.id + ')" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;cursor:pointer;"><i class="fas fa-times"></i></button>'
                + '</div>'
                + '<div id="sigPad' + g.id + '" style="display:none;padding:14px;border-top:1px solid #f1f5f9;">'
                + '<p style="font-size:11px;color:#64748b;margin:0 0 8px;">Sign below (finger or stylus):</p>'
                + '<div class="sig-canvas-wrap"><canvas id="canvas' + g.id + '" height="120"></canvas></div>'
                + '<div style="display:flex;gap:7px;margin-top:8px;">'
                + '<button onclick="clearSig(' + g.id + ')" style="flex:1;padding:7px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-eraser" style="margin-right:4px;"></i>Clear</button>'
                + '<button onclick="saveSig(' + g.id + ')" style="flex:2;padding:7px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-save" style="margin-right:4px;"></i>Save Sig</button>'
                + '</div></div></div>';
            document.getElementById('ciGuestsList').insertAdjacentHTML('beforeend', html);
            initCanvas(g.id);
            // Update counter
            var ctr = document.getElementById('guestCount');
            if (ctr) {
                var n = document.querySelectorAll('#ciGuestsList > div').length;
                ctr.textContent = '(' + n + ')';
            }
            // Reset form
            document.getElementById('ciGuestForm').reset();
            closeAddGuestModal();
        } else {
            alert(res.message || 'Error adding guest. Please check your input and try again.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save" style="margin-right:6px;"></i>Save Guest';
    })
    .catch(function(err) {
        alert('Request failed: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save" style="margin-right:6px;"></i>Save Guest';
    });
}

/* ── Remove Guest ── */
function ciRemoveGuest(guestId) {
    if (!confirm('Remove this guest?')) return;
    fetch('/bookings/' + ciBookingId + '/guests/' + guestId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
        body: JSON.stringify({ _token: ciCsrf })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var row = document.getElementById('ciRow' + guestId);
            if (row) row.remove();
            var remaining = document.querySelectorAll('#ciGuestsList > div').length;
            var ctr = document.getElementById('guestCount');
            if (ctr) ctr.textContent = '(' + remaining + ')';
            if (remaining === 0) {
                document.getElementById('ciGuestsList').innerHTML = '<div id="ciNoGuests" style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;"><i class="fas fa-user-plus" style="font-size:28px;margin-bottom:8px;display:block;color:#cbd5e1;"></i>No additional guests yet.<br><button onclick="openAddGuestModal()" style="margin-top:10px;padding:8px 18px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;"><i class="fas fa-plus" style="margin-right:5px;"></i>Add First Guest</button></div>';
            }
        }
    });
}

/* ══════════════════════════════
   SIGNATURE PADS (guests)
   ══════════════════════════════ */
var sigPads = {};

function initCanvas(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (!canvas) return;

    /* Resize to fill container every time the pad is opened */
    var wrap = canvas.parentElement;
    var w = (wrap ? wrap.clientWidth : 0) || canvas.offsetWidth || 350;
    canvas.width  = w;
    canvas.height = 130;

    /* Add listeners only once */
    if (canvas._ciReady) return;
    canvas._ciReady = true;

    var drawing = false;

    function getPos(e) {
        var rect   = canvas.getBoundingClientRect();
        var scaleX = canvas.width  / rect.width;
        var scaleY = canvas.height / rect.height;
        var src    = e.touches ? e.touches[0] : e;
        return { x: (src.clientX - rect.left) * scaleX, y: (src.clientY - rect.top) * scaleY };
    }
    function start(e) {
        e.preventDefault(); drawing = true;
        var ctx = canvas.getContext('2d'); var p = getPos(e);
        ctx.beginPath(); ctx.moveTo(p.x, p.y);
    }
    function move(e) {
        e.preventDefault(); if (!drawing) return;
        var ctx = canvas.getContext('2d'); var p = getPos(e);
        ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        ctx.strokeStyle = '#1e293b'; ctx.lineTo(p.x, p.y); ctx.stroke();
    }
    function stop() { drawing = false; }

    canvas.addEventListener('mousedown',  start);
    canvas.addEventListener('mousemove',  move);
    canvas.addEventListener('mouseup',    stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   stop,  { passive: false });
    sigPads[guestId] = canvas;
}

function toggleSigPad(guestId) {
    var pad = document.getElementById('sigPad' + guestId);
    if (!pad) return;
    var visible = pad.style.display !== 'none';
    pad.style.display = visible ? 'none' : 'block';
    if (!visible) {
        pad.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(function() { initCanvas(guestId); }, 150);
    }
}

function clearSig(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
}

function saveSig(guestId) {
    var canvas = document.getElementById('canvas' + guestId);
    if (!canvas) return;
    if (isCanvasBlank(canvas)) {
        alert('Please draw the signature first before saving.');
        return;
    }
    fetch('/bookings/' + ciBookingId + '/guests/' + guestId + '/signature', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
        body: JSON.stringify({ signature: canvas.toDataURL('image/png') })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            document.getElementById('sigPad' + guestId).style.display = 'none';
            var btn = document.getElementById('sigBtn' + guestId);
            if (btn) { btn.style.background = '#dcfce7'; btn.style.color = '#16a34a'; btn.innerHTML = '<i class="fas fa-check" style="margin-right:3px;"></i>Signed'; }
        }
    });
}

/* ══════════════════════════════
   PRIMARY GUEST SIGNATURE
   ══════════════════════════════ */

function togglePrimaryPad() {
    var pad = document.getElementById('primarySigPad');
    if (!pad) return;
    var showing = pad.style.display !== 'none';
    pad.style.display = showing ? 'none' : 'block';
    if (!showing) {
        /* scroll the pad into view on mobile, then init */
        pad.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(initPrimaryCanvas, 150);
    }
}

function initPrimaryCanvas() {
    var canvas = document.getElementById('primaryCanvas');
    if (!canvas) return;

    /* Resize to fill the container (must happen every time it becomes visible) */
    var wrap = canvas.parentElement;
    var w = (wrap ? wrap.clientWidth : 0) || canvas.offsetWidth || 300;
    canvas.width  = w;
    canvas.height = 130;

    /* Add listeners only once, using a flag on the element */
    if (canvas._ciReady) return;
    canvas._ciReady = true;

    var drawing = false;

    function getPos(e) {
        var rect   = canvas.getBoundingClientRect();
        /* canvas internal px vs CSS-display px ratio */
        var scaleX = canvas.width  / rect.width;
        var scaleY = canvas.height / rect.height;
        var src    = e.touches ? e.touches[0] : e;
        return {
            x: (src.clientX - rect.left) * scaleX,
            y: (src.clientY - rect.top)  * scaleY
        };
    }

    function start(e) {
        e.preventDefault();
        drawing = true;
        var ctx = canvas.getContext('2d');
        var p   = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }
    function move(e) {
        e.preventDefault();
        if (!drawing) return;
        var ctx = canvas.getContext('2d');
        var p   = getPos(e);
        ctx.lineWidth   = 2.5;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        ctx.strokeStyle = '#1e293b';
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }
    function stop() { drawing = false; }

    canvas.addEventListener('mousedown',  start);
    canvas.addEventListener('mousemove',  move);
    canvas.addEventListener('mouseup',    stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   stop,  { passive: false });
}

function clearPrimaryPad() {
    var canvas = document.getElementById('primaryCanvas');
    if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
}

function isCanvasBlank(canvas) {
    var ctx = canvas.getContext('2d');
    var buf = new Uint32Array(ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer);
    return !buf.some(function(px) { return px !== 0; });
}

function savePrimarySig() {
    var canvas = document.getElementById('primaryCanvas');
    if (!canvas) return;
    if (isCanvasBlank(canvas)) {
        alert('Please draw your signature first before saving.');
        return;
    }
    var dataUrl = canvas.toDataURL('image/png');
    fetch('/guests/{{ $booking->customer->id }}/signature', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': ciCsrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
        body: JSON.stringify({ signature: dataUrl })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            document.getElementById('primarySigPad').style.display = 'none';
            var btn = document.getElementById('btnPrimaryPad');
            if (btn) { btn.style.background = '#dcfce7'; btn.style.color = '#16a34a'; btn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i>Signed'; }
        } else {
            alert('Could not save signature. Please try again.');
        }
    })
    .catch(function(err) { alert('Error: ' + err.message); });
}

// Init existing guest signature pads on page load
document.addEventListener('DOMContentLoaded', function() {
    @foreach($booking->bookingGuests as $guest)
    initCanvas({{ $guest->id }});
    @endforeach
});

@if(\App\Models\Module::isEnabled('pathik'))
function sendToPathikCheckin() {
    var btn = document.getElementById('btnSendPathikCheckin');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Sending...';
    var form = new FormData();
    var d = {
        booking_id:   {{ $booking->id }},
        booking_number: {!! json_encode($booking->booking_number) !!},
        name:         {!! json_encode($booking->customer->name) !!},
        phone:        {!! json_encode($booking->customer->phone) !!},
        email:        {!! json_encode($booking->customer->email ?? '') !!},
        address:      {!! json_encode($booking->customer->address ?? '') !!},
        city:         {!! json_encode($booking->customer->city ?? '') !!},
        state:        {!! json_encode($booking->customer->state ?? '') !!},
        country:      {!! json_encode($booking->customer->country ?? 'India') !!},
        nationality:  {!! json_encode($booking->customer->nationality ?? 'Indian') !!},
        id_type:      {!! json_encode($booking->customer->id_type ?? '') !!},
        id_number:    {!! json_encode($booking->customer->id_number ?? '') !!},
        date_of_birth: {!! json_encode($booking->customer->date_of_birth ? $booking->customer->date_of_birth->format('Y-m-d') : '') !!},
        check_in_date:  {!! json_encode($booking->check_in_date->format('Y-m-d')) !!},
        check_out_date: {!! json_encode($booking->check_out_date->format('Y-m-d')) !!},
        nights:      {{ $booking->nights }},
        adults:      {{ $booking->adults }},
        children:    {{ $booking->children }},
        room_number: {!! json_encode((string)$booking->room->room_number) !!},
        room_type:   {!! json_encode($booking->room->type) !!},
    };
    Object.keys(d).forEach(function(k) { form.append(k, d[k]); });
    fetch('{{ route('pathik.pending.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': ciCsrf },
        body: form,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            document.getElementById('pathikCheckinStatus').style.display = 'block';
            document.getElementById('pathikCheckinStatusText').textContent = 'Data ready! Open Pathik Portal and click Autofill.';
            document.getElementById('btnOpenPortalCheckin').style.display = 'inline-flex';
            btn.style.display = 'none';
        } else {
            alert('Error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
        }
    })
    .catch(function(e) {
        alert('Failed: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
    });
}
@endif
</script>
@endsection
