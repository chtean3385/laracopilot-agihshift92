@extends('layouts.admin')
@section('title','Process Check-Out')
@section('page-title','Process Check-Out')
@section('page-subtitle','Settle bill for ' . $booking->customer->name)
@section('content')
<div style="max-width:720px;" class="space-y-5">
    <a href="{{ route('checkout.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Guest Info --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
            <h3 style="font-weight:800;color:#1e293b;margin-bottom:16px;font-size:15px;"><i class="fas fa-user" style="color:#f59e0b;margin-right:8px;"></i>Guest Details</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Name</span>
                    <span style="font-weight:700;color:#1e293b;">{{ $booking->customer->name }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Phone</span>
                    <span style="font-weight:600;">{{ $booking->customer->phone }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room</span>
                    <span style="font-weight:800;font-size:22px;color:#0f172a;">{{ $booking->room->room_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room Type</span>
                    <span style="font-weight:600;">{{ ucfirst($booking->room->type) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Booking #</span>
                    <span style="font-family:monospace;font-weight:700;color:#0891b2;">{{ $booking->booking_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Check-In Date</span>
                    <span style="font-weight:700;">{{ \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Check-Out Date</span>
                    <span style="font-weight:700;">{{ $booking->check_out_date->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Booked Nights</span>
                    <span style="font-weight:800;font-size:20px;color:#0891b2;">{{ $actualNights }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Guests</span>
                    <span style="font-weight:600;">{{ $booking->adults }} Adults{{ $booking->children > 0 ? ', ' . $booking->children . ' Children' : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Bill Summary --}}
        @php
            $taxRate       = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float)$settings->tax_rate : 0;
            $gstAmount     = round($actualTotal * ($taxRate / 100), 2);
            $grandTotal    = $actualTotal + $gstAmount;
            $gstBalanceDue = max(0, $grandTotal - $totalPaid);
        @endphp
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
            <h3 style="font-weight:800;color:#1e293b;margin-bottom:16px;font-size:15px;"><i class="fas fa-receipt" style="color:#f59e0b;margin-right:8px;"></i>Bill Summary</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">{{ $actualNights }} nights × Rs{{ number_format($booking->room->price_per_night) }}</span>
                    <span style="font-weight:700;">Rs{{ number_format($roomCost) }}</span>
                </div>
                @if($mealCost > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">🍽️ Meal Plan</span>
                    <span style="font-weight:600;">Rs{{ number_format($mealCost) }}</span>
                </div>
                @endif
                @if($extraBedCost > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">🛏️ Extra Bed ({{ $booking->extra_beds }})</span>
                    <span style="font-weight:600;">Rs{{ number_format($extraBedCost) }}</span>
                </div>
                @endif
                @if($mealCost > 0 || $extraBedCost > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px dashed #e2e8f0;padding-top:8px;">
                    <span style="color:#64748b;">Subtotal</span>
                    <span style="font-weight:700;">Rs{{ number_format($actualTotal) }}</span>
                </div>
                @endif
                @if($taxRate > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">GST ({{ $taxRate }}%)</span>
                    <span style="font-weight:600;">Rs{{ number_format($gstAmount) }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Total Charges</span>
                    <span style="font-weight:800;">Rs{{ number_format($grandTotal) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Amount Paid</span>
                    <span style="font-weight:700;color:#16a34a;">Rs{{ number_format($totalPaid) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;border-top:2px solid #0f172a;padding-top:12px;margin-top:4px;">
                    <span>Balance Due</span>
                    <span style="color:{{ $gstBalanceDue > 0 ? '#ef4444' : '#16a34a' }};font-size:26px;">Rs{{ number_format($gstBalanceDue) }}</span>
                </div>
            </div>

            @if($booking->payments->where('status','completed')->count() > 0)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                <p style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Payment History</p>
                @foreach($booking->payments->where('status','completed') as $pmt)
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                    <span style="color:#64748b;">{{ ucfirst($pmt->payment_type) }} ({{ ucfirst($pmt->payment_method) }}) — {{ $pmt->created_at->format('d M Y') }}</span>
                    <span style="font-weight:700;color:#16a34a;">Rs{{ number_format($pmt->amount) }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Process Form --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;color:#1e293b;margin-bottom:20px;font-size:15px;"><i class="fas fa-sign-out-alt" style="color:#f59e0b;margin-right:8px;"></i>Complete Check-Out</h3>
        <form id="checkoutForm" action="{{ route('checkout.process', $booking->id) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Final Payment (Rs){{ $taxRate > 0 ? ' incl. GST' : '' }}</label>
                    <input type="number" name="final_payment" value="{{ $gstBalanceDue }}" min="0" step="0.01" class="form-input">
                    <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Pre-filled with balance due{{ $taxRate > 0 ? ' (incl. ' . $taxRate . '% GST)' : '' }}. Adjust if needed.</p>
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="coPaymentMethod" class="form-input" onchange="toggleCoUpiBtn(this.value)">
                        <option value="cash">Cash</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                    @if(\App\Models\Module::isEnabled('payment_links'))
                    <button type="button" id="coUpiQrBtn" onclick="showCoUpiQr()"
                        style="display:none;margin-top:8px;width:100%;padding:9px 0;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:none;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-qrcode"></i> Show UPI QR for Guest
                    </button>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Check-Out Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any remarks about the stay or departure..."></textarea>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('checkout.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" style="display:inline-flex;align-items:center;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;padding:11px 24px;border-radius:12px;font-weight:700;font-size:14px;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(245,158,11,.4);">
                    <i class="fas fa-sign-out-alt" style="margin-right:8px;"></i>Complete Check-Out & Generate Invoice
                </button>
            </div>
        </form>
    </div>
</div>

@if(\App\Models\Module::isEnabled('payment_links'))
<div id="coUpiModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:340px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <i class="fas fa-qrcode" style="color:#fff;font-size:20px;"></i>
                <div>
                    <div style="font-weight:800;color:#fff;font-size:15px;">UPI Payment</div>
                    <div style="color:#ddd6fe;font-size:12px;">Guest scans to pay instantly</div>
                </div>
            </div>
            <button onclick="closeCoUpiModal()" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:18px;cursor:pointer;">&times;</button>
        </div>
        <div id="coUpiQrBody" style="padding:24px;text-align:center;">
            <div style="display:flex;align-items:center;justify-content:center;height:120px;">
                <div style="width:40px;height:40px;border:3px solid #7c3aed;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
            </div>
        </div>
    </div>
</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
var _coUpiBalance = {{ $gstBalanceDue }};
var _coBookingNum = '{{ $booking->booking_number }}';
var _coGuestName  = '{{ addslashes($booking->customer->name) }}';
var _coGuestPhone = '{{ preg_replace('/[^0-9]/', '', $booking->customer->phone) }}';
var _coUpiId      = '';

function toggleCoUpiBtn(method) {
    var btn = document.getElementById('coUpiQrBtn');
    if (!btn) return;
    btn.style.display = (method === 'upi') ? 'flex' : 'none';
}

function showCoUpiQr() {
    var modal = document.getElementById('coUpiModal');
    modal.style.display = 'flex';
    document.getElementById('coUpiQrBody').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:120px;"><div style="width:40px;height:40px;border:3px solid #7c3aed;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div></div>';

    fetch('/payment-links/upi-config', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(cfg => {
            if (cfg.error) {
                document.getElementById('coUpiQrBody').innerHTML = '<p style="color:#ef4444;font-weight:600;padding:16px 0;">' + cfg.error + '</p>';
                return;
            }
            var amt    = parseFloat(_coUpiBalance).toFixed(2);
            var note   = 'Checkout ' + _coBookingNum;
            var upiUrl = 'upi://pay?pa=' + encodeURIComponent(cfg.upi_id)
                       + '&pn=' + encodeURIComponent(cfg.upi_name)
                       + '&am=' + amt
                       + '&cu=INR'
                       + '&tn=' + encodeURIComponent(note);
            _coUpiId = cfg.upi_id;

            document.getElementById('coUpiQrBody').innerHTML =
                '<div id="coQrCanvas" style="width:220px;height:220px;margin:0 auto;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;"></div>' +
                '<p style="margin-top:14px;font-size:22px;font-weight:900;color:#1e293b;">₹' + parseFloat(_coUpiBalance).toLocaleString('en-IN') + '</p>' +
                '<p style="font-size:13px;color:#64748b;margin-top:2px;">' + cfg.upi_name + '</p>' +
                '<p style="font-size:11px;color:#94a3b8;font-family:monospace;margin-top:2px;">' + cfg.upi_id + '</p>' +
                '<p style="font-size:11px;color:#94a3b8;margin-top:10px;">Scan with GPay · PhonePe · Paytm · any UPI app</p>' +
                '<div style="display:flex;gap:8px;margin-top:14px;">' +
                  '<button onclick="downloadCoQr()" style="flex:1;padding:9px 0;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;"><i class="fas fa-download"></i> Download QR</button>' +
                  '<button onclick="sendCoWhatsApp()" style="flex:1;padding:9px 0;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;"><i class="fab fa-whatsapp"></i> Send to Guest</button>' +
                '</div>' +
                '<button onclick="confirmCoUpiPaid()" style="margin-top:10px;width:100%;padding:11px;background:#0891b2;color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:7px;"><i class="fas fa-check-circle"></i> Payment Received — Record &amp; Complete Checkout</button>' +
                '<button onclick="closeCoUpiModal()" style="margin-top:6px;width:100%;padding:8px;background:#f1f5f9;border:none;border-radius:10px;font-weight:600;font-size:12px;color:#475569;cursor:pointer;">Cancel</button>';

            loadQrLib(function() {
                new QRCode(document.getElementById('coQrCanvas'), {
                    text: upiUrl, width: 220, height: 220,
                    colorDark: '#1e293b', colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        })
        .catch(() => {
            document.getElementById('coUpiQrBody').innerHTML = '<p style="color:#ef4444;padding:16px 0;">Failed to load. Please try again.</p>';
        });
}

function closeCoUpiModal() {
    document.getElementById('coUpiModal').style.display = 'none';
}

function confirmCoUpiPaid() {
    var sel = document.getElementById('coPaymentMethod');
    if (sel) sel.value = 'upi';
    var amtInput = document.querySelector('#checkoutForm input[name="final_payment"]');
    if (amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) {
        amtInput.value = parseFloat(_coUpiBalance).toFixed(2);
    }
    closeCoUpiModal();
    document.getElementById('checkoutForm').submit();
}

function downloadCoQr() {
    var canvas = document.querySelector('#coQrCanvas canvas');
    if (!canvas) { alert('QR not ready — please wait a moment and try again.'); return; }
    var link = document.createElement('a');
    link.download = 'UPI-QR-' + _coBookingNum + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function sendCoWhatsApp() {
    var phone = _coGuestPhone;
    if (phone.length === 10) phone = '91' + phone;
    var msg = 'Dear ' + _coGuestName + ','
            + '\nYour checkout bill for Booking #' + _coBookingNum + ' is ₹' + parseFloat(_coUpiBalance).toLocaleString('en-IN') + '.'
            + '\n\nPlease pay via UPI: *' + _coUpiId + '*'
            + '\n\nThank you for staying with us!';
    window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank');
}

function loadQrLib(cb) {
    if (window.QRCode) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    s.onload = cb;
    document.head.appendChild(s);
}

document.getElementById('coUpiModal').addEventListener('click', function(e) {
    if (e.target === this) closeCoUpiModal();
});
</script>
@endif
@endsection
