<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Check-Out — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { background: linear-gradient(135deg,#0f172a 0%,#134e4a 100%); min-height:100vh; font-family:'Inter',system-ui,sans-serif; display:flex; align-items:flex-start; justify-content:center; padding:20px; }
        .card { background:#fff; border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,.35); width:100%; max-width:480px; }
        .screen { display:none; }
        .screen.active { display:block; }
        .input-field { width:100%; padding:13px 15px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:16px; outline:none; transition:border-color .2s; background:#fafafa; }
        .input-field:focus { border-color:#10b981; background:#fff; }
        .btn-primary { background:linear-gradient(135deg,#10b981,#059669); color:#fff; border:none; border-radius:14px; padding:15px 28px; font-size:16px; font-weight:700; cursor:pointer; width:100%; transition:opacity .2s; }
        .btn-primary:disabled { opacity:.5; cursor:not-allowed; }
        .btn-secondary { background:#f1f5f9; color:#475569; border:none; border-radius:14px; padding:14px 28px; font-size:15px; font-weight:600; cursor:pointer; width:100%; }
        .bill-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:14px; }
        .bill-row:last-child { border:none; }
        .pm-option { display:none; }
        .pm-label { display:flex; align-items:center; gap:12px; padding:13px 15px; border:2px solid #e2e8f0; border-radius:13px; cursor:pointer; transition:border-color .2s,background .2s; }
        .pm-option:checked + .pm-label { border-color:#10b981; background:#f0fdf4; }
        .lang-btn { padding:6px 14px; border-radius:999px; font-size:13px; font-weight:700; cursor:pointer; border:2px solid #10b981; }
        .lang-btn.active { background:#10b981; color:#fff; }
        .lang-btn:not(.active) { background:transparent; color:#10b981; }
        .upi-tap-btn { display:flex; align-items:center; justify-content:center; gap:12px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; border-radius:16px; padding:18px 24px; font-size:17px; font-weight:800; cursor:pointer; width:100%; transition:transform .1s,opacity .2s; text-decoration:none; }
        .upi-tap-btn:active { transform:scale(.98); }
        .spinner { border:3px solid #e2e8f0; border-top:3px solid #10b981; border-radius:50%; width:32px; height:32px; animation:spin .8s linear infinite; margin:0 auto; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body>
<div class="card">

    {{-- ── Shared Header ── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:22px 22px 0;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if($settings->logo_url ?? null)
            <img src="{{ $settings->logo_url }}" alt="Logo" style="height:44px;width:44px;object-fit:contain;border-radius:10px;">
            @else
            <div style="width:44px;height:44px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-hotel" style="color:#fff;font-size:18px;"></i>
            </div>
            @endif
            <div>
                <div style="font-weight:800;font-size:15px;color:#1e293b;">{{ $settings->resort_name ?? $hotel->name }}</div>
                <div style="font-size:12px;color:#10b981;font-weight:600;letter-spacing:.04em;">GUEST CHECK-OUT</div>
            </div>
        </div>
        <div style="display:flex;gap:6px;">
            <button class="lang-btn active" onclick="setLang('en')" id="btnEn">EN</button>
            <button class="lang-btn" onclick="setLang('hi')" id="btnHi">हि</button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- Screen 1: Phone Entry --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="screen active" id="screenPhone" style="padding:24px;">
        <div style="text-align:center;margin-bottom:24px;">
            <div style="width:64px;height:64px;background:linear-gradient(135deg,#10b981,#059669);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-sign-out-alt" style="color:#fff;font-size:26px;"></i>
            </div>
            <h2 style="font-weight:900;font-size:22px;color:#1e293b;margin:0 0 6px;" data-i18n="title">Ready to Check Out?</h2>
            <p style="font-size:13px;color:#64748b;margin:0;" data-i18n="subtitle">Enter your phone number to find your booking</p>
        </div>

        <label style="font-size:13px;font-weight:700;color:#475569;display:block;margin-bottom:7px;" data-i18n="phone_label">Your Phone Number</label>
        <input type="tel" id="phoneInput" class="input-field" placeholder="9876543210" maxlength="15"
            style="font-size:20px;font-weight:700;letter-spacing:.05em;text-align:center;"
            oninput="onPhoneInput(this.value)">

        <div id="lookupError" style="display:none;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;margin-top:10px;font-weight:600;"></div>

        <div id="lookupSpinner" style="display:none;margin-top:20px;"><div class="spinner"></div></div>

        <div style="margin-top:16px;">
            <button type="button" class="btn-primary" id="findBtn" onclick="doLookup()" disabled data-i18n="find_btn">Find My Booking</button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- Screen 2: Bill + Payment --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="screen" id="screenBill" style="padding:22px;">

        {{-- Guest info bar --}}
        <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:12px 16px;margin-bottom:16px;">
            <div style="font-size:16px;font-weight:800;color:#1e293b;" id="billGuestName">—</div>
            <div style="font-size:13px;color:#64748b;margin-top:2px;">
                <span data-i18n="room_label">Room</span>: <strong id="billRoom">—</strong>
                &nbsp;·&nbsp; <span data-i18n="booking_label">Booking</span>: <strong id="billBookingNum" style="font-family:monospace;color:#0891b2;">—</strong>
            </div>
        </div>

        {{-- Bill summary --}}
        <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;" data-i18n="bill_summary">Bill Summary</div>
            <div id="billRows"></div>
            <div style="display:flex;justify-content:space-between;padding-top:10px;margin-top:4px;border-top:2px solid #0f172a;">
                <span style="font-weight:800;font-size:15px;" data-i18n="grand_total">Grand Total</span>
                <span style="font-weight:900;font-size:18px;" id="billGrandTotal">₹0</span>
            </div>
            <div id="billPaidRow" style="display:none;display:flex;justify-content:space-between;margin-top:6px;">
                <span style="color:#16a34a;font-size:13px;font-weight:600;" data-i18n="paid">Amount Paid</span>
                <span style="color:#16a34a;font-weight:700;font-size:13px;" id="billPaid">₹0</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1.5px dashed #e2e8f0;">
                <span style="font-weight:800;font-size:15px;color:#ef4444;" data-i18n="balance_due">Balance Due</span>
                <span style="font-weight:900;font-size:26px;color:#ef4444;" id="billBalanceDue">₹0</span>
            </div>
        </div>

        {{-- UPI deep-link tap-to-pay --}}
        <div id="upiSection" style="display:none;margin-bottom:16px;">
            <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1.5px solid #c4b5fd;border-radius:16px;padding:16px;">
                <div style="font-size:11px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;" data-i18n="pay_upi">Pay via UPI</div>

                {{-- Tap-to-pay button (Android UPI intent) --}}
                <a id="upiDeepLink" href="#" class="upi-tap-btn" style="margin-bottom:12px;" onclick="return handleUpiTap(this)">
                    <i class="fas fa-mobile-alt" style="font-size:22px;"></i>
                    <span data-i18n="tap_to_pay">Tap to Pay with UPI App</span>
                </a>
                <div style="font-size:11px;color:#6d28d9;text-align:center;margin-bottom:12px;" data-i18n="upi_hint">Opens GPay, PhonePe, Paytm & all UPI apps</div>

                {{-- QR for scanning --}}
                <div style="text-align:center;margin-bottom:12px;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:8px;" data-i18n="or_scan">— or scan QR with any UPI app —</div>
                    <img id="upiQrImg" src="" alt="UPI QR" style="width:160px;height:160px;border-radius:12px;border:1px solid #e2e8f0;margin:0 auto;display:block;">
                    <div style="font-size:13px;font-weight:700;color:#1e293b;margin-top:6px;" id="upiIdDisplay"></div>
                </div>

                <label style="font-size:12px;font-weight:700;color:#475569;display:block;margin-bottom:5px;" data-i18n="txn_ref_label">UPI Transaction ID (after payment)</label>
                <input type="text" id="upiRef" class="input-field" placeholder="e.g. 412312XXXXX" style="font-size:14px;">
                <div style="font-size:11px;color:#94a3b8;margin-top:4px;" data-i18n="txn_ref_hint">Enter the 12-digit UPI transaction ID from your UPI app</div>
            </div>
        </div>

        {{-- Razorpay online pay --}}
        <div id="razorpaySection" style="display:none;margin-bottom:16px;">
            <button type="button" onclick="initRazorpay()"
                style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border:none;border-radius:14px;padding:15px;font-size:16px;font-weight:800;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:10px;">
                <i class="fas fa-lock" style="font-size:16px;"></i>
                <span data-i18n="pay_online">Pay Online (Razorpay)</span>
            </button>
            <div style="font-size:11px;color:#64748b;text-align:center;margin-top:6px;" data-i18n="razorpay_hint">Secure payment — your account will be auto checked-out on success</div>
        </div>

        {{-- Payment method (cash / card) --}}
        <div style="margin-bottom:14px;">
            <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;" data-i18n="other_payment">Other Payment</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div>
                    <input type="radio" name="pm" value="upi" id="pmUpi" class="pm-option">
                    <label for="pmUpi" class="pm-label" style="display:none;"><!-- hidden UPI radio (handled by deep-link section) --></label>
                </div>
                <div>
                    <input type="radio" name="pm" value="cash" id="pmCash" class="pm-option" checked>
                    <label for="pmCash" class="pm-label">
                        <span style="width:36px;height:36px;background:#fef9c3;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-money-bill-wave" style="color:#ca8a04;"></i>
                        </span>
                        <div>
                            <div style="font-weight:700;color:#1e293b;font-size:14px;" data-i18n="cash">Cash</div>
                            <div style="font-size:11px;color:#94a3b8;" data-i18n="cash_hint">Pay at the counter — staff will confirm checkout</div>
                        </div>
                    </label>
                </div>
                <div>
                    <input type="radio" name="pm" value="card" id="pmCard" class="pm-option">
                    <label for="pmCard" class="pm-label">
                        <span style="width:36px;height:36px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-credit-card" style="color:#2563eb;"></i>
                        </span>
                        <div>
                            <div style="font-weight:700;color:#1e293b;font-size:14px;" data-i18n="card">Card / Debit</div>
                            <div style="font-size:11px;color:#94a3b8;" data-i18n="card_hint">Swipe at counter — staff will confirm checkout</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <input type="hidden" id="checkoutToken" value="">

        <button type="button" class="btn-primary" id="submitBtn" onclick="submitCheckout()">
            <i class="fas fa-paper-plane" style="margin-right:8px;"></i>
            <span data-i18n="submit_checkout">Submit Checkout Request</span>
        </button>

        <div id="submitError" style="display:none;margin-top:10px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;font-weight:600;"></div>

        <div style="margin-top:12px;">
            <button type="button" class="btn-secondary" onclick="goBack()" data-i18n="back">← Back</button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- Screen 3: Success --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="screen" id="screenSuccess" style="padding:36px 24px;text-align:center;">
        <div style="width:72px;height:72px;background:linear-gradient(135deg,#10b981,#059669);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <i class="fas fa-check" style="color:#fff;font-size:30px;"></i>
        </div>
        <h2 style="font-weight:900;font-size:22px;color:#1e293b;margin:0 0 10px;" data-i18n="success_title">Request Submitted!</h2>
        <p style="font-size:14px;color:#64748b;line-height:1.6;margin:0 0 20px;" data-i18n="success_msg">
            Our team has been notified. Please hand over the room key at the front desk and we'll complete your checkout shortly.
        </p>
        <div id="successUpiRef" style="display:none;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:12px 16px;margin-bottom:16px;">
            <div style="font-size:12px;font-weight:700;color:#16a34a;" data-i18n="txn_recorded">Transaction ID Recorded</div>
            <div id="successUpiRefVal" style="font-family:monospace;font-size:14px;font-weight:700;color:#1e293b;margin-top:4px;"></div>
        </div>
        <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;font-size:13px;color:#92400e;font-weight:600;" data-i18n="success_key_note">
            Please return your room key to the front desk.
        </div>
    </div>

</div>

<script>
var TRANS = {
    en: {
        title:'Ready to Check Out?', subtitle:'Enter your phone number to find your booking',
        phone_label:'Your Phone Number', find_btn:'Find My Booking',
        room_label:'Room', booking_label:'Booking',
        bill_summary:'Bill Summary', grand_total:'Grand Total', paid:'Amount Paid', balance_due:'Balance Due',
        pay_upi:'Pay via UPI', tap_to_pay:'Tap to Pay with UPI App', upi_hint:'Opens GPay, PhonePe, Paytm & all UPI apps',
        or_scan:'— or scan QR with any UPI app —', txn_ref_label:'UPI Transaction ID (after payment)',
        txn_ref_hint:'Enter the 12-digit UPI transaction ID from your UPI app',
        pay_online:'Pay Online (Razorpay)', razorpay_hint:'Secure payment — your account will be auto checked-out on success',
        other_payment:'Other Payment',
        cash:'Cash', cash_hint:'Pay at the counter — staff will confirm checkout',
        card:'Card / Debit', card_hint:'Swipe at counter — staff will confirm checkout',
        submit_checkout:'Submit Checkout Request', back:'← Back',
        success_title:'Request Submitted!',
        success_msg:'Our team has been notified. Please hand over the room key at the front desk and we\'ll complete your checkout shortly.',
        txn_recorded:'Transaction ID Recorded', success_key_note:'Please return your room key to the front desk.',
    },
    hi: {
        title:'चेक-आउट करना है?', subtitle:'अपना फोन नंबर दर्ज करें',
        phone_label:'आपका फोन नंबर', find_btn:'बुकिंग खोजें',
        room_label:'कमरा', booking_label:'बुकिंग',
        bill_summary:'बिल सारांश', grand_total:'कुल राशि', paid:'भुगतान किया', balance_due:'शेष राशि',
        pay_upi:'UPI से भुगतान करें', tap_to_pay:'UPI ऐप से भुगतान करें', upi_hint:'GPay, PhonePe, Paytm और सभी UPI ऐप खुलेंगे',
        or_scan:'— या QR स्कैन करें —', txn_ref_label:'UPI ट्रांजेक्शन ID (भुगतान के बाद)',
        txn_ref_hint:'अपने UPI ऐप से 12 अंक की ट्रांजेक्शन ID दर्ज करें',
        pay_online:'ऑनलाइन भुगतान करें (Razorpay)', razorpay_hint:'सुरक्षित भुगतान — सफल होने पर ऑटो चेक-आउट होगा',
        other_payment:'अन्य भुगतान',
        cash:'नकद', cash_hint:'काउंटर पर भुगतान करें — स्टाफ चेक-आउट की पुष्टि करेगा',
        card:'कार्ड / डेबिट', card_hint:'काउंटर पर स्वाइप करें',
        submit_checkout:'चेक-आउट अनुरोध भेजें', back:'← वापस',
        success_title:'अनुरोध सबमिट हो गया!',
        success_msg:'हमारी टीम को सूचित कर दिया गया है। कृपया फ्रंट डेस्क पर चाबी वापस करें।',
        txn_recorded:'ट्रांजेक्शन ID रिकॉर्ड की गई', success_key_note:'कृपया रूम की चाबी फ्रंट डेस्क पर वापस करें।',
    }
};

var currentLang = (navigator.language || 'en').startsWith('hi') ? 'hi' : 'en';
var currentBookingData = null;
var upiTapWorked = false;

function t(k) { return TRANS[currentLang][k] || TRANS.en[k] || k; }

function setLang(l) {
    currentLang = l;
    document.getElementById('btnEn').classList.toggle('active', l === 'en');
    document.getElementById('btnHi').classList.toggle('active', l === 'hi');
    document.querySelectorAll('[data-i18n]').forEach(function(el) {
        var k = el.getAttribute('data-i18n');
        if (TRANS[l][k]) el.textContent = TRANS[l][k];
    });
}

function showScreen(id) {
    document.querySelectorAll('.screen').forEach(function(s) { s.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Phone input ──────────────────────────────────────────────────────────────
var lookupTimer = null;
function onPhoneInput(val) {
    clearTimeout(lookupTimer);
    document.getElementById('findBtn').disabled = val.trim().length < 5;
    document.getElementById('lookupError').style.display = 'none';
}

function doLookup() {
    var phone = document.getElementById('phoneInput').value.trim();
    if (phone.length < 5) return;

    document.getElementById('lookupSpinner').style.display = 'block';
    document.getElementById('findBtn').disabled = true;
    document.getElementById('lookupError').style.display = 'none';

    fetch('{{ route('guest.desk-checkout.lookup', $slug) }}?phone=' + encodeURIComponent(phone), { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('lookupSpinner').style.display = 'none';
            document.getElementById('findBtn').disabled = false;

            if (!d.found) {
                var errEl = document.getElementById('lookupError');
                errEl.textContent = d.message || (currentLang === 'hi' ? 'कोई सक्रिय बुकिंग नहीं मिली।' : 'No active booking found for this number.');
                errEl.style.display = 'block';
                return;
            }

            currentBookingData = d;
            populateBillScreen(d);
            showScreen('screenBill');
        })
        .catch(function() {
            document.getElementById('lookupSpinner').style.display = 'none';
            document.getElementById('findBtn').disabled = false;
            var errEl = document.getElementById('lookupError');
            errEl.textContent = 'Connection error. Please try again.';
            errEl.style.display = 'block';
        });
}

// ── Populate bill screen ─────────────────────────────────────────────────────
function populateBillScreen(d) {
    document.getElementById('billGuestName').textContent = d.guest_name;
    document.getElementById('billRoom').textContent      = d.room;
    document.getElementById('billBookingNum').textContent = d.booking_number;
    document.getElementById('checkoutToken').value       = d.token;

    // Bill rows
    var rowsEl = document.getElementById('billRows');
    rowsEl.textContent = '';
    (d.bill_rows || []).forEach(function(row) {
        var div = document.createElement('div');
        div.className = 'bill-row';
        var labelSpan = document.createElement('span');
        labelSpan.style.cssText = 'color:#64748b;';
        labelSpan.textContent = row.label;
        var amtSpan = document.createElement('span');
        amtSpan.style.cssText = 'font-weight:700;';
        amtSpan.textContent = '₹' + Number(row.amount).toLocaleString('en-IN');
        div.appendChild(labelSpan);
        div.appendChild(amtSpan);
        rowsEl.appendChild(div);
    });

    document.getElementById('billGrandTotal').textContent = '₹' + Number(d.grand_total).toLocaleString('en-IN');

    var paidRow = document.getElementById('billPaidRow');
    if (d.total_paid > 0) {
        document.getElementById('billPaid').textContent = '₹' + Number(d.total_paid).toLocaleString('en-IN');
        paidRow.style.display = 'flex';
    } else {
        paidRow.style.display = 'none';
    }

    var due = Number(d.balance_due);
    document.getElementById('billBalanceDue').textContent = '₹' + due.toLocaleString('en-IN');
    document.getElementById('billBalanceDue').style.color = due > 0 ? '#ef4444' : '#16a34a';

    // UPI section
    if (d.upi_id && due > 0) {
        var upiLink = 'upi://pay?pa=' + encodeURIComponent(d.upi_id)
            + '&pn=' + encodeURIComponent(d.upi_name || '')
            + '&am=' + due.toFixed(2)
            + '&cu=INR'
            + '&tn=' + encodeURIComponent('Hotel Checkout ' + d.booking_number);

        document.getElementById('upiDeepLink').href = upiLink;
        document.getElementById('upiIdDisplay').textContent = d.upi_id;
        document.getElementById('upiQrImg').src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='
            + encodeURIComponent(upiLink) + '&bgcolor=f5f3ff&color=4c1d95&qzone=1';
        document.getElementById('upiSection').style.display = 'block';
    } else {
        document.getElementById('upiSection').style.display = 'none';
    }

    // Razorpay section
    if (d.razorpay_enabled && due > 0) {
        document.getElementById('razorpaySection').style.display = 'block';
    } else {
        document.getElementById('razorpaySection').style.display = 'none';
    }
}

// ── UPI tap handler — detect if deep link worked ─────────────────────────────
function handleUpiTap(el) {
    upiTapWorked = true;
    // Let the href=upi:// handle the OS intent. We just track it happened.
    return true;
}

// ── Razorpay payment link flow ───────────────────────────────────────────────
function initRazorpay() {
    if (!currentBookingData) return;

    var btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Creating payment link…';

    fetch('{{ route('guest.desk-checkout.razorpay-link', $slug) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ checkout_token: currentBookingData.token }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock" style="font-size:16px;margin-right:8px;"></i>' + t('pay_online');
            document.getElementById('submitError').textContent = d.error;
            document.getElementById('submitError').style.display = 'block';
            return;
        }
        // Redirect to Razorpay payment page
        window.location.href = d.payment_url;
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock" style="font-size:16px;margin-right:8px;"></i>' + t('pay_online');
    });
}

// ── Submit checkout request (cash / UPI manual ref) ──────────────────────────
function submitCheckout() {
    if (!currentBookingData) return;

    var method = 'cash';
    document.querySelectorAll('input[name="pm"]').forEach(function(r) {
        if (r.checked) method = r.value;
    });

    // If UPI deep link was tapped, capture UPI ref
    var upiRef = document.getElementById('upiRef').value.trim();
    if (document.getElementById('upiSection').style.display !== 'none' && upiRef) {
        method = 'upi';
    }

    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Submitting…';
    document.getElementById('submitError').style.display = 'none';

    fetch('{{ route('guest.desk-checkout.submit', $slug) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            checkout_token: currentBookingData.token,
            payment_method: method,
            payment_ref:    upiRef || null,
        }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            if (upiRef) {
                document.getElementById('successUpiRef').style.display = 'block';
                document.getElementById('successUpiRefVal').textContent = upiRef;
            }
            showScreen('screenSuccess');
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:8px;"></i>' + t('submit_checkout');
            document.getElementById('submitError').textContent = d.message || 'Error. Please try again.';
            document.getElementById('submitError').style.display = 'block';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:8px;"></i>' + t('submit_checkout');
    });
}

function goBack() {
    currentBookingData = null;
    document.getElementById('phoneInput').value = '';
    document.getElementById('findBtn').disabled = true;
    document.getElementById('lookupError').style.display = 'none';
    showScreen('screenPhone');
}

document.addEventListener('DOMContentLoaded', function() { setLang(currentLang); });

// Allow Enter key on phone input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('phoneInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') doLookup();
    });
});
</script>
</body>
</html>
