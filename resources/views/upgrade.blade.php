<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Your Plan — Resort CRM</title>
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
            max-width: 800px;
            width: 100%;
            margin-bottom: 24px;
        }
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .plan-card {
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all .2s;
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
        }
        .btn-wa:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(37,211,102,.5); }
        .logout-link {
            color: rgba(255,255,255,.4);
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
            transition: color .2s;
        }
        .logout-link:hover { color: rgba(255,255,255,.7); }
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

    {{-- Expired / locked banner --}}
    @if(session('trial_expired') || session('plan_expired'))
    <div class="card" style="border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08);margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                <div style="font-size:18px;font-weight:800;color:#fca5a5;margin-bottom:4px;">
                    @if(session('trial_expired')) Your free trial has expired @else Your plan has expired @endif
                </div>
                <div style="font-size:13px;color:rgba(252,165,165,.7);">
                    Your CRM access is temporarily locked. Contact us on WhatsApp to reactivate instantly.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main upgrade card --}}
    <div class="card">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(139,92,246,.2);border:1px solid rgba(139,92,246,.3);border-radius:999px;padding:6px 18px;margin-bottom:16px;">
                <i class="fas fa-star" style="color:#a78bfa;font-size:12px;"></i>
                <span style="font-size:12px;font-weight:700;color:#c4b5fd;letter-spacing:.06em;text-transform:uppercase;">Choose a Plan</span>
            </div>
            <h2 style="font-size:24px;font-weight:900;color:#fff;margin-bottom:8px;">Upgrade to keep going</h2>
            <p style="font-size:14px;color:rgba(255,255,255,.5);">Select a plan below and our team will contact you on WhatsApp to complete the upgrade in minutes.</p>
        </div>

        {{-- Plans --}}
        @if($plans && $plans->count())
        <div class="plan-grid" id="planGrid">
            @foreach($plans as $plan)
            <div class="plan-card" onclick="selectPlan('{{ $plan->slug }}', '{{ $plan->label }}', {{ $plan->monthly_price }}, {{ $plan->yearly_price }})" data-slug="{{ $plan->slug }}">
                <div style="font-size:14px;font-weight:800;color:#a78bfa;margin-bottom:6px;">{{ $plan->label }}</div>
                <div style="font-size:22px;font-weight:900;color:#fff;margin-bottom:4px;">
                    ₹{{ number_format($plan->monthly_price) }}<span style="font-size:12px;font-weight:500;color:rgba(255,255,255,.4);">/mo</span>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,.4);margin-bottom:12px;">₹{{ number_format($plan->yearly_price) }}/yr</div>
                @if($plan->features)
                @php $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features; @endphp
                @if(is_array($features))
                <ul style="list-style:none;padding:0;">
                    @foreach(array_slice($features, 0, 4) as $feat)
                    <li style="font-size:11px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:5px;margin-bottom:3px;">
                        <i class="fas fa-check" style="color:#4ade80;font-size:9px;"></i> {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                @endif
                @endif
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);">
                    <span class="selected-badge" style="display:none;font-size:10px;font-weight:700;color:#a78bfa;background:rgba(139,92,246,.2);padding:3px 10px;border-radius:999px;">
                        <i class="fas fa-check"></i> Selected
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        {{-- Fallback if no plans in DB --}}
        <div class="plan-grid" id="planGrid">
            @foreach([['slug'=>'basic','label'=>'Basic','monthly'=>999,'yearly'=>9999],['slug'=>'pro','label'=>'Pro','monthly'=>1999,'yearly'=>19999],['slug'=>'enterprise','label'=>'Enterprise','monthly'=>3999,'yearly'=>39999]] as $fp)
            <div class="plan-card" onclick="selectPlan('{{ $fp['slug'] }}', '{{ $fp['label'] }}', {{ $fp['monthly'] }}, {{ $fp['yearly'] }})" data-slug="{{ $fp['slug'] }}">
                <div style="font-size:14px;font-weight:800;color:#a78bfa;margin-bottom:6px;">{{ $fp['label'] }}</div>
                <div style="font-size:22px;font-weight:900;color:#fff;margin-bottom:4px;">₹{{ number_format($fp['monthly']) }}<span style="font-size:12px;color:rgba(255,255,255,.4);">/mo</span></div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Request via WhatsApp --}}
        <div style="margin-top:32px;padding-top:28px;border-top:1px solid rgba(255,255,255,.08);text-align:center;">
            <p style="font-size:13px;color:rgba(255,255,255,.5);margin-bottom:20px;">
                <i class="fas fa-shield-halved" style="color:#4ade80;margin-right:4px;"></i>
                No payment portal — just send us a WhatsApp message and we'll activate your plan within minutes.
            </p>

            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:20px;margin-bottom:24px;max-width:500px;margin-left:auto;margin-right:auto;">
                <label style="font-size:11px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:8px;">Optional Message</label>
                <textarea id="customMsg" rows="3"
                    placeholder="Any special requirements, number of rooms, etc..."
                    style="width:100%;background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;color:#fff;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>

            <a id="waBtn" href="#"
               class="btn-wa"
               onclick="return openWhatsApp(event)">
                <i class="fab fa-whatsapp" style="font-size:20px;"></i>
                Request Upgrade on WhatsApp
            </a>

            <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:14px;">
                <i class="fas fa-phone" style="margin-right:4px;"></i>
                <a href="tel:+919725225519" style="color:rgba(255,255,255,.4);text-decoration:none;">+91 97252 25519</a>
            </p>
        </div>
    </div>

    {{-- What's included note --}}
    <div style="text-align:center;max-width:600px;">
        <div style="display:flex;justify-content:center;gap:24px;flex-wrap:wrap;">
            @foreach(['Unlimited guests','All modules included','WhatsApp integration','Priority support','Data backup','Channel Manager'] as $feat)
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.4);">
                <i class="fas fa-check-circle" style="color:#4ade80;font-size:11px;"></i> {{ $feat }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Logout link --}}
    <div style="margin-top:24px;">
        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;">
                <i class="fas fa-arrow-left"></i> Sign out and return to login
            </button>
        </form>
    </div>

<script>
var selectedPlan  = '';
var selectedLabel = '';
var selectedPrice = 0;

function selectPlan(slug, label, monthly, yearly) {
    selectedPlan  = label;
    selectedLabel = label;
    selectedPrice = monthly;

    document.querySelectorAll('.plan-card').forEach(function(c) {
        c.classList.remove('selected');
        var badge = c.querySelector('.selected-badge');
        if (badge) badge.style.display = 'none';
    });

    var card = document.querySelector('.plan-card[data-slug="' + slug + '"]');
    if (card) {
        card.classList.add('selected');
        var badge = card.querySelector('.selected-badge');
        if (badge) badge.style.display = 'inline-block';
    }
}

function openWhatsApp(e) {
    e.preventDefault();
    var hotel   = @json(session('crm_hotel_name', 'Hotel'));
    var user    = @json(session('crm_user_name', 'Admin'));
    var plan    = selectedPlan || 'Not selected';
    var msg     = document.getElementById('customMsg').value;

    var text = 'Hello! I would like to upgrade my Resort CRM plan.\n\n'
             + '*Hotel:* ' + hotel + '\n'
             + '*Contact:* ' + user + '\n'
             + '*Interested Plan:* ' + plan + '\n'
             + (msg ? '*Message:* ' + msg : '');

    window.open('https://wa.me/919725225519?text=' + encodeURIComponent(text), '_blank');
    return false;
}

// Auto-select first plan
var firstCard = document.querySelector('.plan-card');
if (firstCard) firstCard.click();
</script>
</body>
</html>
