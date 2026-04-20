<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>अपग्रेड करें — Resort CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }
        .card {
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 820px;
            width: 100%;
            margin-bottom: 24px;
        }
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .plan-card {
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 16px;
            padding: 22px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .plan-card:hover { border-color: rgba(139,92,246,.6); background: rgba(139,92,246,.12); transform: translateY(-2px); }
        .plan-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,.18); }
        .btn-wa {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #25d366, #128c43);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(37,211,102,.4);
            transition: all .2s;
            width: 100%;
            justify-content: center;
        }
        .btn-wa:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(37,211,102,.5); }
        .btn-extend {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(245,158,11,.35);
            transition: all .2s;
            width: 100%;
            justify-content: center;
        }
        .btn-extend:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(245,158,11,.5); }
        input[type="text"], textarea {
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 10px;
            color: #fff;
            font-size: 13px;
            outline: none;
            padding: 10px 14px;
            width: 100%;
        }
        input[type="text"]::placeholder, textarea::placeholder { color: rgba(255,255,255,.3); }
        label.field-label {
            font-size: 11px;
            font-weight: 700;
            color: rgba(255,255,255,.4);
            text-transform: uppercase;
            letter-spacing: .08em;
            display: block;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

    {{-- Logo + Hotel name --}}
    <div style="text-align:center;margin-bottom:32px;">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 8px 24px rgba(139,92,246,.4);">
            <i class="fas fa-umbrella-beach" style="color:#fff;font-size:26px;"></i>
        </div>
        <h1 style="font-size:28px;font-weight:900;color:#fff;margin-bottom:6px;">Resort CRM</h1>
        @if(session('crm_hotel_name'))
        <p style="font-size:14px;color:rgba(255,255,255,.5);">{{ session('crm_hotel_name') }}</p>
        @endif
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);border-radius:14px;padding:14px 20px;margin-bottom:20px;max-width:820px;width:100%;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-check-circle" style="color:#4ade80;font-size:18px;"></i>
        <span style="color:#bbf7d0;font-size:14px;font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:14px;padding:14px 20px;margin-bottom:20px;max-width:820px;width:100%;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-exclamation-circle" style="color:#f87171;font-size:18px;"></i>
        <span style="color:#fca5a5;font-size:14px;font-weight:600;">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Expired / locked banner --}}
    @if(session('trial_expired') || session('plan_expired'))
    <div class="card" style="border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08);margin-bottom:20px;max-width:820px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                @if(session('trial_expired'))
                <div style="font-size:18px;font-weight:800;color:#fca5a5;margin-bottom:4px;">आपका ट्रायल समाप्त हो गया है</div>
                <div style="font-size:13px;color:rgba(252,165,165,.7);">Your free trial has expired. CRM access is temporarily locked. Contact us on WhatsApp to reactivate instantly.</div>
                @else
                <div style="font-size:18px;font-weight:800;color:#fca5a5;margin-bottom:4px;">आपका प्लान समाप्त हो गया है</div>
                <div style="font-size:13px;color:rgba(252,165,165,.7);">Your plan has expired. CRM access is temporarily locked. Renew your plan to resume operations.</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- "Give Me More Time" button — trial users only, one-time --}}
    @if($canExtendTrial)
    <div style="max-width:820px;width:100%;margin-bottom:20px;">
        <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:16px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-hourglass-half" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:#fcd34d;">मुझे थोड़ा और समय चाहिए</div>
                    <div style="font-size:12px;color:rgba(252,211,77,.65);margin-top:2px;">ट्रायल 3 दिन और बढ़ाएं — सिर्फ एक बार</div>
                </div>
            </div>
            <form method="POST" action="{{ route('upgrade.extend-trial') }}" onsubmit="return confirm('क्या आप अपना ट्रायल 3 दिन बढ़ाना चाहते हैं? यह विकल्प सिर्फ एक बार उपलब्ध है।')">
                @csrf
                <button type="submit" class="btn-extend" style="width:auto;padding:10px 24px;font-size:14px;">
                    <i class="fas fa-clock"></i> 3 दिन बढ़ाएं (मुफ्त)
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Main upgrade card --}}
    <div class="card">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(139,92,246,.2);border:1px solid rgba(139,92,246,.3);border-radius:999px;padding:6px 18px;margin-bottom:16px;">
                <i class="fas fa-star" style="color:#a78bfa;font-size:12px;"></i>
                <span style="font-size:12px;font-weight:700;color:#c4b5fd;letter-spacing:.06em;text-transform:uppercase;">अपग्रेड करें</span>
            </div>
            <h2 style="font-size:24px;font-weight:900;color:#fff;margin-bottom:8px;">अपना प्लान चुनें</h2>
            <p style="font-size:14px;color:rgba(255,255,255,.5);">नीचे से प्लान चुनें — हमारी टीम WhatsApp पर कुछ मिनटों में आपको सक्रिय कर देगी।</p>
        </div>

        {{-- Plan cards — yearly price as main --}}
        @if($plans && $plans->count())
        <div class="plan-grid" id="planGrid">
            @foreach($plans as $plan)
            @php
                $feats = is_string($plan->features) ? json_decode($plan->features, true) : [];
                $monthlyEquiv = round($plan->yearly_price / 12);
            @endphp
            <div class="plan-card" onclick="selectPlan('{{ $plan->slug }}', '{{ addslashes($plan->label) }}', {{ $plan->yearly_price }})" data-slug="{{ $plan->slug }}">
                <div style="font-size:13px;font-weight:800;color:#a78bfa;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;">{{ $plan->label }}</div>
                <div style="font-size:26px;font-weight:900;color:#fff;line-height:1;">
                    ₹{{ number_format($plan->yearly_price) }}
                    <span style="font-size:12px;font-weight:500;color:rgba(255,255,255,.4);">/वर्ष</span>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:4px;margin-bottom:14px;">
                    ≈ ₹{{ number_format($monthlyEquiv) }}/माह के बराबर
                </div>
                @if(is_array($feats) && count($feats))
                <ul style="list-style:none;padding:0;">
                    @foreach(array_slice($feats, 0, 4) as $feat)
                    <li style="font-size:11px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:5px;margin-bottom:4px;">
                        <i class="fas fa-check" style="color:#4ade80;font-size:9px;flex-shrink:0;"></i> {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                @endif
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.06);">
                    <span class="selected-badge" style="display:none;font-size:10px;font-weight:700;color:#a78bfa;background:rgba(139,92,246,.2);padding:3px 10px;border-radius:999px;">
                        <i class="fas fa-check"></i> चयनित
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:32px;color:rgba(255,255,255,.4);">
            <i class="fas fa-exclamation-circle" style="font-size:28px;margin-bottom:10px;"></i>
            <p>कोई प्लान उपलब्ध नहीं है। कृपया WhatsApp पर संपर्क करें।</p>
        </div>
        @endif

        {{-- Contact form --}}
        <div style="margin-top:32px;padding-top:28px;border-top:1px solid rgba(255,255,255,.08);">
            <p style="font-size:13px;color:rgba(255,255,255,.5);margin-bottom:20px;text-align:center;">
                <i class="fas fa-shield-halved" style="color:#4ade80;margin-right:4px;"></i>
                कोई पेमेंट पोर्टल नहीं — बस WhatsApp पर अनुरोध करें, हम तुरंत सक्रिय करेंगे।
            </p>

            <form method="POST" action="{{ route('upgrade.request') }}" id="upgradeForm">
                @csrf
                <input type="hidden" name="plan_slug" id="planSlugInput" value="">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                    <div>
                        <label class="field-label">आपका नाम</label>
                        <input type="text" name="contact_name" value="{{ session('crm_user_name','') }}" placeholder="e.g. Rahul Sharma">
                    </div>
                    <div>
                        <label class="field-label">होटल का नाम</label>
                        <input type="text" name="hotel_name_input" value="{{ session('crm_hotel_name','') }}" readonly style="opacity:.7;">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="field-label">अतिरिक्त संदेश (वैकल्पिक)</label>
                    <textarea name="message" rows="3" placeholder="कमरों की संख्या, विशेष आवश्यकताएं..."></textarea>
                </div>

                <div id="noPlanWarning" style="display:none;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#fca5a5;text-align:center;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                    कृपया पहले एक प्लान चुनें।
                </div>

                <button type="submit" class="btn-wa" onclick="return validateForm()">
                    <i class="fab fa-whatsapp" style="font-size:20px;"></i>
                    WhatsApp पर अपग्रेड का अनुरोध करें
                </button>
            </form>

            <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:14px;text-align:center;">
                <i class="fas fa-phone" style="margin-right:4px;"></i>
                <a href="tel:+919725225519" style="color:rgba(255,255,255,.4);text-decoration:none;">+91 97252 25519</a>
            </p>
        </div>
    </div>

    {{-- Feature highlights --}}
    <div style="text-align:center;max-width:620px;margin-bottom:16px;">
        <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;">
            @foreach(['अनलिमिटेड गेस्ट','सभी मॉड्यूल','WhatsApp ऑटोमेशन','प्राथमिकता सपोर्ट','डेटा बैकअप','OTA Channel Manager'] as $feat)
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.4);">
                <i class="fas fa-check-circle" style="color:#4ade80;font-size:11px;"></i> {{ $feat }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Logout --}}
    <div style="margin-top:8px;">
        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,.4);font-size:13px;display:inline-flex;align-items:center;gap:6px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,.7)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                <i class="fas fa-arrow-left"></i> लॉग आउट करें
            </button>
        </form>
    </div>

<script>
var selectedPlan = '';

function selectPlan(slug, label, yearly) {
    selectedPlan = slug;
    document.getElementById('planSlugInput').value = slug;
    document.getElementById('noPlanWarning').style.display = 'none';

    document.querySelectorAll('.plan-card').forEach(function(c) {
        c.classList.remove('selected');
        var b = c.querySelector('.selected-badge');
        if (b) b.style.display = 'none';
    });

    var card = document.querySelector('.plan-card[data-slug="' + slug + '"]');
    if (card) {
        card.classList.add('selected');
        var b = card.querySelector('.selected-badge');
        if (b) b.style.display = 'inline-block';
    }
}

function validateForm() {
    if (!document.getElementById('planSlugInput').value) {
        document.getElementById('noPlanWarning').style.display = 'block';
        return false;
    }
    return true;
}

// Auto-select first plan on page load
var firstCard = document.querySelector('.plan-card');
if (firstCard) firstCard.click();
</script>
</body>
</html>
