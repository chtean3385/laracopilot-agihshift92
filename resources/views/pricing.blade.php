<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Plans — Resort CRM</title>
    <meta name="description" content="Simple, transparent pricing for your hotel or resort. Choose a plan and get started today.">
    <link rel="stylesheet" href="/css/font-awesome.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
            min-height: 100vh;
            color: #fff;
        }

        /* ── Hero ── */
        .hero {
            text-align: center;
            padding: 60px 24px 40px;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(139,92,246,.18); border: 1px solid rgba(139,92,246,.35);
            border-radius: 999px; padding: 7px 20px; margin-bottom: 20px;
            font-size: 12px; font-weight: 700; color: #c4b5fd;
            letter-spacing: .08em; text-transform: uppercase;
        }
        .hero h1 { font-size: 40px; font-weight: 900; letter-spacing: -.02em; margin-bottom: 12px; }
        .hero h1 span { background: linear-gradient(135deg,#a78bfa,#38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 16px; color: rgba(255,255,255,.55); max-width: 520px; margin: 0 auto 12px; line-height: 1.6; }
        .hero-sub { font-size: 13px; color: rgba(255,255,255,.35); }

        /* ── Plan grid ── */
        .plans-wrap { max-width: 1120px; margin: 0 auto; padding: 0 24px 60px; }
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 48px;
        }
        @media (max-width: 900px) { .plan-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 520px) { .plan-grid { grid-template-columns: 1fr; } }

        .plan-card {
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 24px 20px;
            cursor: pointer;
            transition: all .22s;
            position: relative;
            display: flex; flex-direction: column;
        }
        .plan-card:hover { border-color: rgba(139,92,246,.5); background: rgba(139,92,246,.1); transform: translateY(-3px); box-shadow: 0 12px 40px rgba(139,92,246,.2); }
        .plan-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,.15); box-shadow: 0 0 0 3px rgba(139,92,246,.25), 0 12px 40px rgba(139,92,246,.25); }
        .plan-card.popular { border-color: rgba(59,130,246,.5); }
        .popular-badge {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg,#3b82f6,#1d4ed8);
            color: #fff; font-size: 10px; font-weight: 800; padding: 4px 14px;
            border-radius: 999px; white-space: nowrap; letter-spacing: .05em;
            box-shadow: 0 4px 12px rgba(59,130,246,.4);
        }
        .plan-name { font-size: 11px; font-weight: 800; color: #a78bfa; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 10px; }
        .plan-price { font-size: 32px; font-weight: 900; color: #fff; line-height: 1; }
        .plan-price-unit { font-size: 13px; font-weight: 500; color: rgba(255,255,255,.4); }
        .plan-monthly { font-size: 11px; color: rgba(255,255,255,.35); margin-top: 4px; margin-bottom: 16px; }
        .plan-features { list-style: none; flex: 1; }
        .plan-features li { font-size: 12px; color: rgba(255,255,255,.6); display: flex; align-items: flex-start; gap: 7px; margin-bottom: 7px; line-height: 1.4; }
        .plan-features li i { color: #4ade80; font-size: 9px; flex-shrink: 0; margin-top: 3px; }
        .plan-select-btn {
            margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.07);
            text-align: center;
        }
        .plan-select-btn span {
            display: inline-block; font-size: 12px; font-weight: 700;
            color: rgba(255,255,255,.35); padding: 6px 16px;
            border: 1.5px solid rgba(255,255,255,.12); border-radius: 999px;
            transition: all .2s;
        }
        .plan-card.selected .plan-select-btn span,
        .plan-card:hover .plan-select-btn span {
            background: linear-gradient(135deg,#8b5cf6,#6d28d9);
            border-color: transparent; color: #fff;
        }

        /* ── Enquiry form ── */
        .form-wrap {
            max-width: 640px; margin: 0 auto;
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px; padding: 36px 32px;
        }
        .form-title { font-size: 20px; font-weight: 900; margin-bottom: 6px; }
        .form-sub { font-size: 13px; color: rgba(255,255,255,.45); margin-bottom: 28px; line-height: 1.5; }
        .selected-plan-bar {
            background: rgba(139,92,246,.15); border: 1px solid rgba(139,92,246,.3);
            border-radius: 12px; padding: 12px 16px; margin-bottom: 22px;
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 700; color: #c4b5fd;
        }
        .selected-plan-bar.empty { background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.25); color: #fca5a5; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 11px; font-weight: 700; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 7px; }
        .field input, .field select {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,.06); border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 10px; color: #fff; font-size: 14px; outline: none;
            transition: border-color .2s;
        }
        .field input:focus, .field select:focus { border-color: rgba(139,92,246,.6); }
        .field input::placeholder { color: rgba(255,255,255,.25); }
        .field select option { background: #1e1b4b; color: #fff; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 500px) { .field-row { grid-template-columns: 1fr; } }

        .btn-wa {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #25d366, #128c43);
            color: #fff; border: none; border-radius: 14px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            box-shadow: 0 6px 24px rgba(37,211,102,.35);
            transition: all .2s; margin-top: 8px;
        }
        .btn-wa:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(37,211,102,.5); }
        .btn-wa:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .form-note { font-size: 11px; color: rgba(255,255,255,.3); text-align: center; margin-top: 14px; line-height: 1.5; }

        /* ── Feature highlights strip ── */
        .features-strip {
            display: flex; justify-content: center; flex-wrap: wrap; gap: 18px;
            padding: 0 24px 40px; max-width: 900px; margin: 0 auto;
        }
        .features-strip span { display: flex; align-items: center; gap: 7px; font-size: 12px; color: rgba(255,255,255,.4); }
        .features-strip i { color: #4ade80; font-size: 11px; }

        /* ── Footer ── */
        .footer { text-align: center; padding: 24px; border-top: 1px solid rgba(255,255,255,.06); }
        .footer p { font-size: 12px; color: rgba(255,255,255,.25); }
        .footer a { color: rgba(255,255,255,.45); text-decoration: none; }
        .footer a:hover { color: #fff; }
    </style>
</head>
<body>

    {{-- Hero --}}
    <div class="hero">
        <div style="width:60px;height:60px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(139,92,246,.4);">
            <i class="fas fa-umbrella-beach" style="color:#fff;font-size:24px;"></i>
        </div>
        <div class="hero-badge"><i class="fas fa-star"></i> Resort CRM — Hotel Management Platform</div>
        <h1>Simple, Transparent <span>Pricing</span></h1>
        <p>Everything your hotel needs — bookings, check-in/out, WhatsApp automation, invoices, reports and more.</p>
        <p class="hero-sub">No hidden fees &nbsp;·&nbsp; Activated within minutes via WhatsApp</p>
    </div>

    {{-- Plans --}}
    <div class="plans-wrap">
        @if($plans && $plans->count())
        <div class="plan-grid" id="planGrid">
            @foreach($plans as $i => $plan)
            @php
                $feats        = is_string($plan->features) ? (json_decode($plan->features, true) ?? []) : [];
                $monthlyEquiv = round($plan->yearly_price / 12);
                $isPopular    = $i === 1; // second plan highlighted as "Most Popular"
            @endphp
            <div class="plan-card {{ $isPopular ? 'popular' : '' }}"
                 id="card-{{ $plan->slug }}"
                 onclick="selectPlan('{{ $plan->slug }}','{{ addslashes($plan->label) }}',{{ $plan->yearly_price }})">
                @if($isPopular)
                <div class="popular-badge"><i class="fas fa-fire" style="margin-right:4px;"></i> Most Popular</div>
                @endif
                <div class="plan-name">{{ $plan->label }}</div>
                <div class="plan-price">
                    ₹{{ number_format($plan->yearly_price) }}
                    <span class="plan-price-unit">/yr</span>
                </div>
                <div class="plan-monthly">≈ ₹{{ number_format($monthlyEquiv) }}/month</div>
                @if(count($feats))
                <ul class="plan-features">
                    @foreach($feats as $feat)
                    <li><i class="fas fa-check"></i> {{ $feat }}</li>
                    @endforeach
                </ul>
                @endif
                <div class="plan-select-btn">
                    <span id="btn-{{ $plan->slug }}"><i class="fas fa-hand-pointer" style="margin-right:5px;"></i>Select Plan</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:60px 24px;color:rgba(255,255,255,.4);">
            <i class="fas fa-exclamation-circle" style="font-size:32px;margin-bottom:12px;display:block;"></i>
            No plans available right now. Please contact us on WhatsApp.
        </div>
        @endif

        {{-- Features highlights --}}
        <div class="features-strip">
            @foreach(['Unlimited guests','All modules included','WhatsApp automation','OTA Channel Manager','Priority support','Secure data backup'] as $f)
            <span><i class="fas fa-check-circle"></i> {{ $f }}</span>
            @endforeach
        </div>

        {{-- Enquiry form --}}
        <div class="form-wrap">
            <div class="form-title">Get started today</div>
            <div class="form-sub">Select a plan above, fill your details, and we'll activate your account via WhatsApp — usually within a few minutes.</div>

            <div class="selected-plan-bar empty" id="selectedPlanBar">
                <i class="fas fa-hand-pointer"></i>
                <span id="selectedPlanText">Please select a plan above first</span>
            </div>

            <div class="field-row">
                <div class="field">
                    <label>Your Name *</label>
                    <input type="text" id="f-name" placeholder="e.g. Rahul Sharma" autocomplete="name">
                </div>
                <div class="field">
                    <label>Hotel / Resort Name *</label>
                    <input type="text" id="f-hotel" placeholder="e.g. Sunset Resort" autocomplete="organization">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label>WhatsApp / Phone *</label>
                    <input type="tel" id="f-phone" placeholder="e.g. 98765 43210" autocomplete="tel">
                </div>
                <div class="field">
                    <label>Number of Rooms</label>
                    <input type="number" id="f-rooms" placeholder="e.g. 20" min="1" max="9999">
                </div>
            </div>
            <div class="field">
                <label>Message (optional)</label>
                <input type="text" id="f-msg" placeholder="Any specific requirements or questions…">
            </div>

            <div id="formError" style="display:none;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#fca5a5;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                <span id="formErrorMsg">Please fill all required fields.</span>
            </div>

            <button class="btn-wa" id="sendBtn" onclick="sendEnquiry()">
                <i class="fab fa-whatsapp" style="font-size:22px;"></i>
                Send Enquiry on WhatsApp
            </button>
            <p class="form-note">
                <i class="fas fa-lock" style="margin-right:4px;color:rgba(255,255,255,.4);"></i>
                Your details are only used to contact you. No payment is taken online.<br>
                <a href="https://wa.me/919725225519" target="_blank" style="color:rgba(255,255,255,.45);">
                    <i class="fab fa-whatsapp" style="margin-right:3px;"></i>Direct WhatsApp: +91 97252 25519
                </a>
            </p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} Resort CRM &nbsp;·&nbsp; Powered by Dreams Technology &nbsp;·&nbsp;
            <a href="https://wa.me/919725225519" target="_blank">
                <i class="fab fa-whatsapp" style="margin-right:3px;"></i>+91 97252 25519
            </a>
        </p>
    </div>

<script>
var selectedSlug  = '';
var selectedLabel = '';
var selectedPrice = 0;

function selectPlan(slug, label, price) {
    // Deselect all
    document.querySelectorAll('.plan-card').forEach(function(c) { c.classList.remove('selected'); });
    document.querySelectorAll('[id^="btn-"]').forEach(function(b) {
        b.innerHTML = '<i class="fas fa-hand-pointer" style="margin-right:5px;"></i>Select Plan';
    });

    // Select chosen
    selectedSlug  = slug;
    selectedLabel = label;
    selectedPrice = price;

    var card = document.getElementById('card-' + slug);
    var btn  = document.getElementById('btn-' + slug);
    if (card) card.classList.add('selected');
    if (btn)  btn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i>Selected';

    // Update bar
    var bar = document.getElementById('selectedPlanBar');
    bar.classList.remove('empty');
    bar.innerHTML = '<i class="fas fa-check-circle" style="color:#a78bfa;font-size:16px;"></i>'
        + '<span style="color:#c4b5fd;font-weight:800;">' + label + '</span>'
        + '<span style="color:rgba(255,255,255,.4);font-size:12px;margin-left:auto;">₹' + price.toLocaleString('en-IN') + '/yr</span>';

    // Hide error if shown
    document.getElementById('formError').style.display = 'none';

    // Scroll to form
    document.querySelector('.form-wrap').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function sendEnquiry() {
    var name  = document.getElementById('f-name').value.trim();
    var hotel = document.getElementById('f-hotel').value.trim();
    var phone = document.getElementById('f-phone').value.trim();
    var rooms = document.getElementById('f-rooms').value.trim();
    var msg   = document.getElementById('f-msg').value.trim();

    var errEl  = document.getElementById('formError');
    var errMsg = document.getElementById('formErrorMsg');

    if (!selectedSlug) {
        errEl.style.display = 'block';
        errMsg.textContent  = 'Please select a plan from the options above.';
        document.getElementById('planGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }
    if (!name || !hotel || !phone) {
        errEl.style.display = 'block';
        errMsg.textContent  = 'Please fill in your name, hotel name, and phone number.';
        return;
    }
    errEl.style.display = 'none';

    var text = 'Hello! I am interested in the Resort CRM.\n\n'
        + '*Plan Selected:* ' + selectedLabel + ' (₹' + selectedPrice.toLocaleString('en-IN') + '/year)\n'
        + '*Name:* ' + name + '\n'
        + '*Hotel / Resort:* ' + hotel + '\n'
        + '*Phone:* ' + phone + '\n'
        + (rooms ? '*Rooms:* ' + rooms + '\n' : '')
        + (msg   ? '*Message:* ' + msg  + '\n' : '')
        + '\nPlease activate my account.';

    var btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:18px;"></i> Opening WhatsApp…';

    window.open('https://wa.me/919725225519?text=' + encodeURIComponent(text), '_blank');

    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp" style="font-size:22px;"></i> Send Enquiry on WhatsApp';
    }, 3000);
}

// Auto-select first plan on load
var firstCard = document.querySelector('.plan-card');
if (firstCard) firstCard.click();
</script>
</body>
</html>
