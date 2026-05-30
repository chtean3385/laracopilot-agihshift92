<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In Request Received</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; padding: 20px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; padding: 36px 28px; text-align: center; }
        .success-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 24px rgba(16,185,129,.35); }
        .lang-btn { padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 700; cursor: pointer; border: 2px solid #6366f1; background: transparent; color: #6366f1; margin: 0 4px; }
        .lang-btn.active { background: #6366f1; color: #fff; }
    </style>
</head>
<body>
<div class="card">
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button class="lang-btn active" onclick="setLang('en')" id="btnEn">EN</button>
        <button class="lang-btn" onclick="setLang('hi')" id="btnHi">हि</button>
    </div>
    <div class="success-icon">
        <i class="fas fa-check" style="color:#fff;font-size:32px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:22px;color:#1e293b;margin-bottom:8px;" data-i18n="title">Request Received!</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;" data-i18n="subtitle">
        Thank you, <strong>{{ $validated['name'] }}</strong>! Your check-in request has been submitted successfully. Our team will assign you a room and send a WhatsApp confirmation shortly.
    </p>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:16px;margin-bottom:20px;">
        <div style="font-size:12px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;" data-i18n="what_next">What happens next</div>
        <ul style="text-align:left;font-size:13px;color:#166534;line-height:2;list-style:none;padding:0;">
            <li>✅ <span data-i18n="next1">Your details have been recorded</span></li>
            <li>🏨 <span data-i18n="next2">Staff will assign your room</span></li>
            <li>📱 <span data-i18n="next3">You'll receive a WhatsApp confirmation</span></li>
        </ul>
    </div>
    <p style="font-size:12px;color:#94a3b8;" data-i18n="footer">Please wait at the front desk. Our team will be with you shortly.</p>
</div>
<script>
var TRANS = {
    en: {
        title: 'Request Received!',
        subtitle: 'Thank you, <strong>{{ $validated['name'] }}</strong>! Your check-in request has been submitted. Our team will assign you a room and send a WhatsApp confirmation shortly.',
        what_next: 'What happens next', next1: 'Your details have been recorded',
        next2: 'Staff will assign your room', next3: 'You\'ll receive a WhatsApp confirmation',
        footer: 'Please wait at the front desk. Our team will be with you shortly.',
    },
    hi: {
        title: 'अनुरोध प्राप्त हुआ!',
        subtitle: 'धन्यवाद, <strong>{{ $validated['name'] }}</strong>! आपका चेक-इन अनुरोध सफलतापूर्वक जमा हो गया है। हमारी टीम जल्द ही आपको एक कमरा आवंटित करेगी।',
        what_next: 'आगे क्या होगा', next1: 'आपका विवरण दर्ज कर लिया गया है',
        next2: 'स्टाफ आपका कमरा आवंटित करेगा', next3: 'आपको WhatsApp पर पुष्टि मिलेगी',
        footer: 'कृपया फ्रंट डेस्क पर प्रतीक्षा करें। हमारी टीम जल्द ही आपके पास आएगी।',
    }
};
var lang = (navigator.language || 'en').startsWith('hi') ? 'hi' : 'en';
function t(k) { return TRANS[lang][k] || TRANS.en[k] || k; }
function setLang(l) {
    lang = l;
    document.getElementById('btnEn').classList.toggle('active', l === 'en');
    document.getElementById('btnHi').classList.toggle('active', l === 'hi');
    document.querySelectorAll('[data-i18n]').forEach(function(el) {
        var k = el.getAttribute('data-i18n');
        if (TRANS[l][k]) el.innerHTML = TRANS[l][k];
    });
}
document.addEventListener('DOMContentLoaded', function() { setLang(lang); });
</script>
</body>
</html>
