<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Check-Out — {{ $settings->resort_name ?? $booking->customer?->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; font-family: 'Inter', system-ui, sans-serif; padding: 20px; display: flex; align-items: flex-start; justify-content: center; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 480px; padding: 24px; }
        .bill-row { display: flex; justify-content: space-between; font-size: 14px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .bill-row:last-child { border: none; }
        .input-field { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 15px; outline: none; transition: border-color .2s; background: #fafafa; box-sizing: border-box; }
        .input-field:focus { border-color: #6366f1; background: #fff; }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 14px; padding: 14px 28px; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; }
        .btn-primary:disabled { opacity: .5; }
        .lang-btn { padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 700; cursor: pointer; border: 2px solid #6366f1; transition: all .2s; }
        .lang-btn.active { background: #6366f1; color: #fff; }
        .lang-btn:not(.active) { background: transparent; color: #6366f1; }
        .pm-option { display: none; }
        .pm-label { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: border-color .2s; }
        .pm-option:checked + .pm-label { border-color: #6366f1; background: #f5f3ff; }
    </style>
</head>
<body>
<div class="card">
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if(($settings->logo_url ?? null))
            <img src="{{ $settings->logo_url }}" alt="Logo" style="height:42px;width:42px;object-fit:contain;border-radius:10px;">
            @else
            <div style="width:42px;height:42px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-hotel" style="color:#fff;font-size:16px;"></i>
            </div>
            @endif
            <div>
                <div style="font-weight:800;font-size:15px;color:#1e293b;">{{ $settings->resort_name ?? '' }}</div>
                <div style="font-size:12px;color:#94a3b8;" data-i18n="checkout_title">Guest Check-Out</div>
            </div>
        </div>
        <div style="display:flex;gap:6px;">
            <button class="lang-btn active" onclick="setLang('en')" id="btnEn">EN</button>
            <button class="lang-btn" onclick="setLang('hi')" id="btnHi">हि</button>
        </div>
    </div>

    {{-- Guest + Booking Info --}}
    <div style="background:#f8fafc;border-radius:14px;padding:14px;margin-bottom:18px;">
        <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;" data-i18n="booking_info">Booking Information</div>
        <div style="font-size:15px;font-weight:800;color:#1e293b;">{{ $booking->customer?->name ?? 'Guest' }}</div>
        <div style="font-size:13px;color:#64748b;margin-top:2px;">
            <span data-i18n="booking_ref">Booking</span>: <strong style="font-family:monospace;color:#0891b2;">{{ $booking->booking_number }}</strong>
        </div>
        <div style="font-size:13px;color:#64748b;margin-top:2px;">
            <span data-i18n="room">Room</span>: <strong>{{ $booking->is_whole_hotel ? 'Whole Hotel' : ($booking->room?->room_number ?? '—') }}</strong>
        </div>
    </div>

    {{-- Bill --}}
    <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:18px;">
        <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;" data-i18n="bill_summary">Bill Summary</div>

        <div class="bill-row">
            <span style="color:#64748b;" data-i18n="room_charge">Room Charge</span>
            <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
        </div>
        @if($mealCost > 0)
        <div class="bill-row">
            <span style="color:#64748b;" data-i18n="meal_plan">Meal Plan</span>
            <span style="font-weight:700;">₹{{ number_format($mealCost) }}</span>
        </div>
        @endif
        @if($extraBedCost > 0)
        <div class="bill-row">
            <span style="color:#64748b;" data-i18n="extra_bed">Extra Bed</span>
            <span style="font-weight:700;">₹{{ number_format($extraBedCost) }}</span>
        </div>
        @endif
        @foreach($booking->extraCharges as $ec)
        <div class="bill-row">
            <span style="color:#64748b;">{{ $ec->name }}@if($ec->quantity != 1) ×{{ $ec->quantity }}@endif</span>
            <span style="font-weight:700;">₹{{ number_format($ec->total_price) }}</span>
        </div>
        @endforeach
        @if($taxRate > 0)
        <div class="bill-row">
            <span style="color:#64748b;">GST ({{ $taxRate }}%)</span>
            <span style="font-weight:600;">₹{{ number_format($gstAmount) }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;padding-top:10px;margin-top:4px;border-top:2px solid #0f172a;">
            <span style="font-weight:800;font-size:15px;" data-i18n="grand_total">Grand Total</span>
            <span style="font-weight:900;font-size:20px;color:#0f172a;">₹{{ number_format($grandTotal) }}</span>
        </div>
        @if($totalPaid > 0)
        <div style="display:flex;justify-content:space-between;margin-top:6px;">
            <span style="color:#16a34a;font-size:13px;font-weight:600;" data-i18n="paid">Paid</span>
            <span style="color:#16a34a;font-weight:700;font-size:13px;">₹{{ number_format($totalPaid) }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;margin-top:6px;padding-top:8px;border-top:1.5px dashed #e2e8f0;">
            <span style="font-weight:800;font-size:14px;color:{{ $balanceDue > 0 ? '#ef4444' : '#16a34a' }};" data-i18n="balance_due">Balance Due</span>
            <span style="font-weight:900;font-size:22px;color:{{ $balanceDue > 0 ? '#ef4444' : '#16a34a' }};">₹{{ number_format($balanceDue) }}</span>
        </div>
    </div>

    {{-- UPI QR --}}
    @if($upiConfig && $upiConfig->upi_enabled && $upiConfig->upi_id)
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:16px;padding:18px;margin-bottom:18px;text-align:center;">
        <div style="font-size:12px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;" data-i18n="pay_upi">Pay via UPI</div>
        {{-- Generate QR via free public API --}}
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode('upi://pay?pa=' . $upiConfig->upi_id . '&pn=' . urlencode($upiConfig->upi_name ?? '') . '&am=' . $balanceDue . '&cu=INR') }}&bgcolor=f0fdf4&color=166534&qzone=1"
            alt="UPI QR" style="width:180px;height:180px;border-radius:14px;margin:0 auto 10px;display:block;">
        <div style="font-size:13px;font-weight:700;color:#166534;">{{ $upiConfig->upi_id }}</div>
        @if($upiConfig->upi_name)
        <div style="font-size:12px;color:#4ade80;margin-top:2px;">{{ $upiConfig->upi_name }}</div>
        @endif
        <div style="font-size:11px;color:#16a34a;margin-top:8px;" data-i18n="scan_to_pay">Scan with any UPI app (GPay, PhonePe, Paytm etc.)</div>
    </div>
    @endif

    {{-- Payment form --}}
    @if($booking->guest_checkout_submitted_at)
    <div style="background:#ecfdf5;border:1.5px solid #86efac;border-radius:14px;padding:16px;text-align:center;">
        <i class="fas fa-check-circle" style="color:#16a34a;font-size:24px;margin-bottom:8px;display:block;"></i>
        <div style="font-weight:700;color:#16a34a;" data-i18n="already_submitted">Your payment confirmation has already been submitted. Thank you!</div>
    </div>
    @else
    <form action="{{ route('guest.checkout.submit', $token) }}" method="POST">
        @csrf
        <div style="margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:#475569;margin-bottom:10px;" data-i18n="select_payment">Select Payment Method</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div>
                    <input type="radio" name="payment_method" value="upi" id="pm_upi" class="pm-option" checked>
                    <label for="pm_upi" class="pm-label">
                        <span style="width:36px;height:36px;background:#ecfdf5;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-qrcode" style="color:#16a34a;"></i>
                        </span>
                        <span style="font-weight:700;color:#1e293b;" data-i18n="upi">UPI</span>
                    </label>
                </div>
                <div>
                    <input type="radio" name="payment_method" value="cash" id="pm_cash" class="pm-option">
                    <label for="pm_cash" class="pm-label">
                        <span style="width:36px;height:36px;background:#fef9c3;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-money-bill" style="color:#ca8a04;"></i>
                        </span>
                        <span style="font-weight:700;color:#1e293b;" data-i18n="cash">Cash</span>
                    </label>
                </div>
                <div>
                    <input type="radio" name="payment_method" value="card" id="pm_card" class="pm-option">
                    <label for="pm_card" class="pm-label">
                        <span style="width:36px;height:36px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-credit-card" style="color:#2563eb;"></i>
                        </span>
                        <span style="font-weight:700;color:#1e293b;" data-i18n="card">Card</span>
                    </label>
                </div>
            </div>
        </div>
        <div style="margin-bottom:16px;">
            <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="ref_label">Transaction Reference (optional)</label>
            <input type="text" name="payment_ref" class="input-field" placeholder="UPI ref / transaction ID">
        </div>
        <button type="submit" class="btn-primary"><i class="fas fa-paper-plane" style="margin-right:8px;"></i><span data-i18n="confirm_payment">Confirm Payment</span></button>
    </form>
    @endif
</div>

<script>
var TRANS = {
    en: { checkout_title:'Guest Check-Out', booking_info:'Booking Information', booking_ref:'Booking', room:'Room', bill_summary:'Bill Summary', room_charge:'Room Charge', meal_plan:'Meal Plan', extra_bed:'Extra Bed', grand_total:'Grand Total', paid:'Paid', balance_due:'Balance Due', pay_upi:'Pay via UPI', scan_to_pay:'Scan with any UPI app (GPay, PhonePe, Paytm etc.)', select_payment:'Select Payment Method', upi:'UPI', cash:'Cash', card:'Card / Debit', ref_label:'Transaction Reference (optional)', confirm_payment:'Confirm Payment', already_submitted:'Your payment confirmation has already been submitted. Thank you!' },
    hi: { checkout_title:'अतिथि चेक-आउट', booking_info:'बुकिंग जानकारी', booking_ref:'बुकिंग', room:'कमरा', bill_summary:'बिल सारांश', room_charge:'कमरे का शुल्क', meal_plan:'भोजन योजना', extra_bed:'अतिरिक्त बिस्तर', grand_total:'कुल राशि', paid:'भुगतान किया', balance_due:'शेष राशि', pay_upi:'UPI से भुगतान करें', scan_to_pay:'किसी भी UPI ऐप से स्कैन करें (GPay, PhonePe, Paytm आदि)', select_payment:'भुगतान का तरीका चुनें', upi:'UPI', cash:'नकद', card:'कार्ड / डेबिट', ref_label:'ट्रांजेक्शन संदर्भ (वैकल्पिक)', confirm_payment:'भुगतान की पुष्टि करें', already_submitted:'आपकी भुगतान पुष्टि पहले ही सबमिट हो चुकी है। धन्यवाद!' }
};
var lang = (navigator.language || 'en').startsWith('hi') ? 'hi' : 'en';
function setLang(l) {
    lang = l;
    document.getElementById('btnEn').classList.toggle('active', l === 'en');
    document.getElementById('btnHi').classList.toggle('active', l === 'hi');
    document.querySelectorAll('[data-i18n]').forEach(function(el) { var k = el.getAttribute('data-i18n'); if (TRANS[l][k]) el.textContent = TRANS[l][k]; });
}
document.addEventListener('DOMContentLoaded', function() { setLang(lang); });
</script>
</body>
</html>
