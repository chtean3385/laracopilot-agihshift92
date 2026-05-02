<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Hotel CRM — Pricing Plans</title>
    <meta name="description" content="Simple cloud-based hotel management software. Bookings, check-in/out, WhatsApp automation, invoices & more. Plans starting ₹5,999/year.">
    <link rel="stylesheet" href="/css/font-awesome.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,sans-serif;background:#07101e;color:#fff;}
        a{text-decoration:none;color:inherit;}

        /* ── TOP NAV ── */
        .top-nav{background:rgba(7,16,30,.95);border-bottom:1px solid rgba(255,255,255,.07);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;backdrop-filter:blur(8px);}
        .nav-logo{display:flex;align-items:center;gap:12px;}
        .nav-logo img{width:38px;height:38px;object-fit:contain;border-radius:8px;}
        .nav-logo-text{font-size:15px;font-weight:800;line-height:1.2;}
        .nav-logo-text span{color:#38bdf8;}
        .nav-cta{background:#25d366;color:#fff;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:800;display:flex;align-items:center;gap:7px;}

        /* ── HERO ── */
        .hero{background:linear-gradient(135deg,#07101e 0%,#0d1f3c 50%,#07101e 100%);padding:56px 40px 48px;max-width:1280px;margin:0 auto;}
        .hero-inner{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;}
        .hero-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.3);border-radius:999px;padding:6px 16px;font-size:11px;font-weight:800;color:#38bdf8;letter-spacing:.08em;text-transform:uppercase;margin-bottom:18px;}
        .hero h1{font-size:46px;font-weight:900;line-height:1.05;letter-spacing:-.02em;margin-bottom:6px;}
        .hero h1 em{font-style:normal;color:#38bdf8;}
        .hero-sub{font-size:20px;font-weight:800;color:#fbbf24;margin-bottom:14px;letter-spacing:.01em;}
        .hero-tagline{font-size:15px;color:rgba(255,255,255,.55);line-height:1.65;margin-bottom:28px;max-width:460px;}
        .hero-pills{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:32px;}
        .hero-pill{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 16px;font-size:12px;font-weight:700;}
        .hero-pill i{font-size:14px;color:#38bdf8;}
        .hero-btns{display:flex;gap:12px;align-items:center;}
        .btn-primary{background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;padding:14px 28px;border-radius:10px;font-size:15px;font-weight:800;display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(3,105,161,.4);}
        .btn-wa-sm{background:#25d366;color:#fff;padding:14px 24px;border-radius:10px;font-size:15px;font-weight:800;display:inline-flex;align-items:center;gap:8px;}

        /* ── DASHBOARD MOCKUP ── */
        .mockup-wrap{background:#0f1f38;border-radius:16px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.08);}
        .mockup-bar{background:#060f1c;padding:10px 16px;display:flex;align-items:center;gap:8px;}
        .mockup-dot{width:10px;height:10px;border-radius:50%;}
        .mockup-title-row{background:#0f1f38;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.06);}
        .mockup-title-row span{font-size:13px;font-weight:700;color:rgba(255,255,255,.8);}
        .mockup-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:12px;}
        .mstat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:8px 6px;text-align:center;}
        .mstat-label{font-size:8px;color:rgba(255,255,255,.35);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
        .mstat-val{font-size:16px;font-weight:900;color:#fff;}
        .mstat-val.green{color:#4ade80;}
        .mstat-val.blue{color:#38bdf8;}
        .mockup-body{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 12px 12px;}
        .mbody-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:10px;}
        .mbody-title{font-size:9px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;margin-bottom:8px;letter-spacing:.06em;}
        .mbooking{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
        .mbooking-name{font-size:10px;color:rgba(255,255,255,.65);}
        .mbooking-room{font-size:9px;color:rgba(255,255,255,.35);}
        .mbadge{font-size:8px;font-weight:800;padding:2px 7px;border-radius:999px;}
        .mbadge.green{background:rgba(74,222,128,.15);color:#4ade80;}
        .mbadge.amber{background:rgba(251,191,36,.15);color:#fbbf24;}
        .occ-bar-wrap{margin-top:4px;}
        .occ-label{display:flex;justify-content:space-between;font-size:9px;color:rgba(255,255,255,.4);margin-bottom:4px;}
        .occ-bar{background:rgba(255,255,255,.08);border-radius:4px;height:6px;overflow:hidden;}
        .occ-fill{height:100%;border-radius:4px;}
        .mockup-footer{background:#060f1c;padding:8px 16px;display:flex;align-items:center;justify-content:space-between;}
        .mockup-footer span{font-size:9px;color:rgba(255,255,255,.3);}
        .mnew-btn{background:#0369a1;color:#fff;font-size:9px;font-weight:800;padding:4px 10px;border-radius:6px;}

        /* ── FEATURE STRIP ── */
        .feature-strip{background:#0d1f3c;border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06);}
        .feature-strip-inner{max-width:1280px;margin:0 auto;padding:20px 40px;display:flex;justify-content:space-around;flex-wrap:wrap;gap:16px;}
        .fstrip-item{display:flex;align-items:center;gap:10px;font-size:13px;}
        .fstrip-item i{width:36px;height:36px;background:rgba(56,189,248,.12);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#38bdf8;}
        .fstrip-item-text strong{display:block;font-size:13px;font-weight:800;color:#fff;margin-bottom:2px;}
        .fstrip-item-text span{font-size:11px;color:rgba(255,255,255,.4);}

        /* ── PLANS SECTION ── */
        .plans-section{padding:56px 40px;max-width:1280px;margin:0 auto;}
        .section-label{text-align:center;margin-bottom:36px;}
        .section-label h2{font-size:32px;font-weight:900;margin-bottom:8px;letter-spacing:-.02em;}
        .section-label p{font-size:15px;color:rgba(255,255,255,.45);}
        .plans-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}

        /* ── PLAN CARD ── */
        .plan-card{background:#fff;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.3);transition:transform .2s;}
        .plan-card:hover{transform:translateY(-4px);}
        .plan-card.popular{box-shadow:0 0 0 3px #fbbf24,0 16px 48px rgba(251,191,36,.25);}
        .popular-ribbon{position:absolute;top:-1px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:10px;font-weight:900;padding:5px 18px;border-radius:0 0 10px 10px;letter-spacing:.06em;text-transform:uppercase;z-index:2;white-space:nowrap;box-shadow:0 4px 12px rgba(245,158,11,.4);}
        .plan-header{padding:20px 20px 16px;color:#fff;text-align:center;}
        .plan-icon{width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px;}
        .plan-name{font-size:15px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;}
        .plan-subtitle{font-size:10px;color:rgba(255,255,255,.7);line-height:1.4;}
        .plan-price-box{background:#fff;padding:16px 20px;text-align:center;border-bottom:1px solid #f1f5f9;}
        .plan-price{font-size:34px;font-weight:900;color:#0f172a;line-height:1;}
        .plan-price sup{font-size:18px;vertical-align:top;margin-top:6px;}
        .plan-price-note{font-size:11px;color:#94a3b8;margin-top:4px;}
        .plan-include{padding:8px 20px;text-align:center;font-size:10px;font-weight:800;letter-spacing:.07em;color:#fff;}
        .plan-body{padding:16px 20px;flex:1;background:#fff;}
        .plan-features{list-style:none;margin-bottom:12px;}
        .plan-features li{display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#374151;margin-bottom:7px;line-height:1.4;}
        .plan-features li i{color:#16a34a;font-size:10px;flex-shrink:0;margin-top:3px;}
        .plan-limits{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;padding:10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;}
        .plan-limit{display:flex;justify-content:space-between;align-items:center;font-size:11px;}
        .plan-limit-label{color:#64748b;font-weight:600;}
        .plan-limit-val{font-weight:900;color:#0f172a;font-size:12px;}
        .plan-extra{margin-top:auto;background:#f1f5f9;border-top:1px solid #e2e8f0;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;}
        .plan-extra-label{font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;line-height:1.4;}
        .plan-extra-label span{display:block;font-size:9px;font-weight:500;color:#94a3b8;text-transform:none;}
        .plan-extra-price{text-align:right;}
        .plan-extra-price strong{display:block;font-size:18px;font-weight:900;color:#0f172a;}
        .plan-extra-price em{font-style:normal;font-size:9px;color:#94a3b8;}
        .plan-cta{padding:14px 20px;background:#fff;border-top:1px solid #f1f5f9;}
        .plan-btn{display:block;text-align:center;padding:11px;border-radius:9px;font-size:13px;font-weight:800;cursor:pointer;border:none;width:100%;}

        /* ── MODULES SECTION ── */
        .modules-section{background:#0d1f3c;padding:48px 40px;}
        .modules-inner{max-width:1280px;margin:0 auto;}
        .modules-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-top:32px;}
        .module-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px 12px;text-align:center;transition:all .2s;}
        .module-card:hover{background:rgba(56,189,248,.08);border-color:rgba(56,189,248,.3);transform:translateY(-2px);}
        .module-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px;}
        .module-name{font-size:12px;font-weight:800;color:#fff;margin-bottom:4px;line-height:1.3;}
        .module-desc{font-size:10px;color:rgba(255,255,255,.4);line-height:1.4;}

        /* ── BENEFITS ── */
        .benefits-section{background:#070f1e;padding:48px 40px;border-top:1px solid rgba(255,255,255,.05);}
        .benefits-inner{max-width:1280px;margin:0 auto;}
        .benefits-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:32px;}
        .benefit-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:24px 20px;text-align:center;}
        .benefit-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 14px;}
        .benefit-title{font-size:14px;font-weight:900;color:#fff;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
        .benefit-desc{font-size:12px;color:rgba(255,255,255,.45);line-height:1.6;}

        /* ── ENQUIRY FORM ── */
        .enquiry-section{padding:56px 40px;max-width:800px;margin:0 auto;}
        .form-wrap{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:36px 32px;}
        .form-title{font-size:22px;font-weight:900;margin-bottom:6px;}
        .form-sub{font-size:13px;color:rgba(255,255,255,.45);margin-bottom:24px;line-height:1.5;}
        .selected-plan-bar{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.25);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:700;color:#38bdf8;}
        .selected-plan-bar.empty{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);color:#fca5a5;}
        .field{margin-bottom:14px;}
        .field label{display:block;font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.07em;margin-bottom:7px;}
        .field input,.field select{width:100%;padding:11px 14px;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:9px;color:#fff;font-size:14px;outline:none;transition:border-color .2s;}
        .field input:focus,.field select:focus{border-color:rgba(56,189,248,.5);}
        .field input::placeholder{color:rgba(255,255,255,.2);}
        .field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .btn-submit{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;box-shadow:0 6px 24px rgba(3,105,161,.3);transition:all .2s;margin-top:6px;}
        .btn-submit:hover{transform:translateY(-2px);}
        .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}
        .form-note{font-size:11px;color:rgba(255,255,255,.25);text-align:center;margin-top:14px;line-height:1.6;}
        .form-note a{color:rgba(255,255,255,.4);}

        /* ── FOOTER CTA ── */
        .footer-cta{background:linear-gradient(135deg,#0d1f3c,#07101e);border-top:1px solid rgba(255,255,255,.06);padding:32px 40px;}
        .footer-cta-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
        .footer-left{display:flex;align-items:center;gap:16px;}
        .footer-wa-circle{width:56px;height:56px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;flex-shrink:0;}
        .footer-tagline{font-size:13px;color:rgba(255,255,255,.4);margin-bottom:4px;}
        .footer-number{font-size:28px;font-weight:900;color:#fff;letter-spacing:.02em;}
        .footer-benefits{display:flex;gap:24px;flex-wrap:wrap;}
        .fbenefit{display:flex;align-items:center;gap:8px;font-size:12px;color:rgba(255,255,255,.5);}
        .fbenefit i{color:#4ade80;font-size:12px;}
        .footer-copy{max-width:1280px;margin:0 auto;padding:16px 40px 0;border-top:1px solid rgba(255,255,255,.05);margin-top:24px;font-size:11px;color:rgba(255,255,255,.2);text-align:center;}

        /* ── SUCCESS / ERROR ── */
        .form-error{display:none;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:9px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#fca5a5;}
        .form-success{display:none;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:14px;padding:24px;text-align:center;margin-bottom:12px;}

        /* ── RESPONSIVE ── */
        @media(max-width:1100px){.plans-grid{grid-template-columns:repeat(2,1fr);}.modules-grid{grid-template-columns:repeat(4,1fr);}}
        @media(max-width:860px){.hero-inner{grid-template-columns:1fr;}.mockup-wrap{display:none;}.benefits-grid{grid-template-columns:repeat(2,1fr);}.hero h1{font-size:34px;}}
        @media(max-width:600px){.plans-grid{grid-template-columns:1fr;}.modules-grid{grid-template-columns:repeat(2,1fr);}.benefits-grid{grid-template-columns:1fr;}.field-row{grid-template-columns:1fr;}.hero{padding:32px 20px;}.plans-section,.modules-section,.benefits-section,.enquiry-section{padding-left:20px;padding-right:20px;}.section-label h2{font-size:24px;}}
    </style>
</head>
<body>

{{-- ── TOP NAV ── --}}
<nav class="top-nav">
    <div class="nav-logo">
        <img src="/hotel-crm-logo.png" alt="Hotel CRM">
        <div class="nav-logo-text">DREAM HOTEL<br><span>MANAGEMENT CRM</span></div>
    </div>
    <a href="https://wa.me/919725225519" target="_blank" class="nav-cta">
        <i class="fab fa-whatsapp"></i> +91 97252 25519
    </a>
</nav>

{{-- ── HERO ── --}}
<div style="background:linear-gradient(135deg,#07101e 0%,#0d1f3c 55%,#07101e 100%);padding:0;">
<div class="hero">
    <div class="hero-inner">
        <div>
            <div class="hero-badge"><i class="fas fa-cloud"></i> Cloud Based Software / CRM</div>
            <h1>DREAM HOTEL<br><em>MANAGEMENT</em></h1>
            <div class="hero-sub">Are you a Hotel Owner?<br>Need software that does <span style="color:#fbbf24;">all your staff work?</span></div>
            <p class="hero-tagline">Manage your hotel operations, automate tasks and grow your business with our all-in-one cloud based solution. Works on Desktop, Mobile & Tablet.</p>
            <div class="hero-pills">
                <div class="hero-pill"><i class="fas fa-cloud"></i><div><strong style="display:block;font-size:12px;">Cloud Based</strong><span style="font-size:10px;color:rgba(255,255,255,.4);">Access from anywhere</span></div></div>
                <div class="hero-pill"><i class="fas fa-shield-alt"></i><div><strong style="display:block;font-size:12px;">Secure & Reliable</strong><span style="font-size:10px;color:rgba(255,255,255,.4);">Enterprise grade security</span></div></div>
                <div class="hero-pill"><i class="fas fa-mobile-alt"></i><div><strong style="display:block;font-size:12px;">Access Anywhere</strong><span style="font-size:10px;color:rgba(255,255,255,.4);">Desktop, Mobile & Tablet</span></div></div>
            </div>
            <div class="hero-btns">
                <a href="#plans" class="btn-primary"><i class="fas fa-tags"></i> View Plans</a>
                <a href="https://wa.me/919725225519" target="_blank" class="btn-wa-sm"><i class="fab fa-whatsapp"></i> Chat Now</a>
            </div>
        </div>

        {{-- CSS Dashboard Mockup --}}
        <div class="mockup-wrap">
            <div class="mockup-bar">
                <div class="mockup-dot" style="background:#ef4444;"></div>
                <div class="mockup-dot" style="background:#fbbf24;"></div>
                <div class="mockup-dot" style="background:#4ade80;"></div>
                <span style="font-size:10px;color:rgba(255,255,255,.3);margin-left:8px;">resort.dreamstechnology.in</span>
            </div>
            <div class="mockup-title-row">
                <span><i class="fas fa-th-large" style="margin-right:7px;color:#38bdf8;"></i>Dashboard</span>
                <span style="font-size:9px;color:rgba(255,255,255,.3);">Today: {{ now()->format('d M Y') }}</span>
            </div>
            <div class="mockup-stats">
                <div class="mstat"><div class="mstat-label">Today's Check-in</div><div class="mstat-val green">12</div></div>
                <div class="mstat"><div class="mstat-label">Today's Check-out</div><div class="mstat-val">08</div></div>
                <div class="mstat"><div class="mstat-label">Total Bookings</div><div class="mstat-val blue">25</div></div>
                <div class="mstat"><div class="mstat-label">Revenue</div><div class="mstat-val" style="font-size:11px;">₹2.45L</div></div>
            </div>
            <div class="mockup-body">
                <div class="mbody-card">
                    <div class="mbody-title">Room Occupancy</div>
                    @foreach([['label'=>'Occupied','pct'=>72,'color'=>'#38bdf8'],['label'=>'Available','pct'=>20,'color'=>'#4ade80'],['label'=>'Maintenance','pct'=>8,'color'=>'#fbbf24']] as $occ)
                    <div class="occ-bar-wrap">
                        <div class="occ-label"><span>{{ $occ['label'] }}</span><span>{{ $occ['pct'] }}%</span></div>
                        <div class="occ-bar"><div class="occ-fill" style="width:{{ $occ['pct'] }}%;background:{{ $occ['color'] }};"></div></div>
                    </div>
                    @endforeach
                </div>
                <div class="mbody-card">
                    <div class="mbody-title">Recent Bookings</div>
                    @foreach([['name'=>'Amit Patel','room'=>'101','status'=>'green'],['name'=>'Neha Shah','room'=>'205','status'=>'green'],['name'=>'John Doe','room'=>'302','status'=>'amber']] as $b)
                    <div class="mbooking">
                        <div><div class="mbooking-name">{{ $b['name'] }}</div><div class="mbooking-room">Room {{ $b['room'] }}</div></div>
                        <div class="mbadge {{ $b['status'] }}">{{ $b['status']==='green' ? 'Check-in' : 'Pending' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="mockup-footer">
                <span>Hotel CRM — Active</span>
                <div class="mnew-btn">+ New Booking</div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- ── FEATURE STRIP ── --}}
<div class="feature-strip">
    <div class="feature-strip-inner">
        @foreach([['fas fa-chart-line','Increase Bookings','Get more direct bookings from your website'],['fas fa-clock','Save Staff Time','Automate tasks & reduce manual work'],['fas fa-check-double','Reduce Errors','Manage everything accurately in one place'],['fas fa-rocket','Grow Your Business','Data driven decisions & revenue growth']] as $f)
        <div class="fstrip-item">
            <div style="width:36px;height:36px;background:rgba(56,189,248,.12);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#38bdf8;flex-shrink:0;"><i class="{{ $f[0] }}"></i></div>
            <div class="fstrip-item-text"><strong>{{ $f[1] }}</strong><span>{{ $f[2] }}</span></div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── PLANS ── --}}
<div id="plans" class="plans-section">
    <div class="section-label">
        <h2>Choose Your Perfect Plan</h2>
        <p>All plans include core hotel management features. Add extra modules as you grow.</p>
    </div>
    <div class="plans-grid">
        @foreach($plans as $plan)
        <div class="plan-card {{ $plan->popular ? 'popular' : '' }}" id="card-{{ $plan->slug }}" onclick="selectPlan('{{ $plan->slug }}','{{ addslashes($plan->label) }}',{{ $plan->yearly_price }})">
            @if($plan->popular)
            <div class="popular-ribbon"><i class="fas fa-crown" style="margin-right:4px;"></i>MOST POPULAR</div>
            @endif

            {{-- Header --}}
            <div class="plan-header" style="background:{{ $plan->card_color }};padding-top:{{ $plan->popular ? '28px' : '20px' }};">
                <div class="plan-icon"><i class="fas {{ $plan->icon }}"></i></div>
                <div class="plan-name">{{ $plan->label }} Plan</div>
                <div class="plan-subtitle">{{ $plan->subtitle }}</div>
            </div>

            {{-- Price --}}
            <div class="plan-price-box">
                <div class="plan-price"><sup>₹</sup>{{ number_format($plan->yearly_price) }}</div>
                <div class="plan-price-note">+ GST / Year &nbsp;·&nbsp; ₹{{ number_format(round($plan->yearly_price/12)) }}/month</div>
            </div>

            {{-- Include label --}}
            @if($plan->include_text)
            <div class="plan-include" style="background:{{ $plan->card_color }};">
                <i class="fas fa-check-double" style="margin-right:6px;"></i>{{ $plan->include_text }}
            </div>
            @endif

            {{-- Features + Limits --}}
            <div class="plan-body">
                <ul class="plan-features">
                    @foreach($plan->features as $feat)
                    <li><i class="fas fa-check-circle"></i>{{ $feat }}</li>
                    @endforeach
                </ul>
                <div class="plan-limits">
                    <div class="plan-limit">
                        <span class="plan-limit-label"><i class="fas fa-bed" style="margin-right:5px;color:#94a3b8;"></i>Room Limit</span>
                        <span class="plan-limit-val" style="color:{{ $plan->card_color }};">{{ $plan->max_rooms >= 9999 ? 'UNLIMITED' : $plan->max_rooms . ' ROOMS' }}</span>
                    </div>
                    <div class="plan-limit">
                        <span class="plan-limit-label"><i class="fas fa-users" style="margin-right:5px;color:#94a3b8;"></i>User Limit</span>
                        <span class="plan-limit-val" style="color:{{ $plan->card_color }};">{{ $plan->max_users >= 9999 ? 'UNLIMITED' : $plan->max_users . ' USERS' }}</span>
                    </div>
                </div>
            </div>

            {{-- Extra Modules --}}
            <div class="plan-extra">
                <div class="plan-extra-label">
                    <i class="fas fa-puzzle-piece" style="margin-right:5px;color:{{ $plan->card_color }};"></i>EXTRA MODULES
                    <span>Add any extra module as per your need</span>
                </div>
                <div class="plan-extra-price">
                    <strong style="color:{{ $plan->card_color }};">₹{{ number_format($plan->extra_price) }}</strong>
                    <em>PER MODULE</em>
                </div>
            </div>

            {{-- CTA Button --}}
            <div class="plan-cta">
                <button class="plan-btn" id="planbtn-{{ $plan->slug }}"
                    style="background:{{ $plan->card_color }};color:#fff;"
                    onclick="event.stopPropagation();selectPlan('{{ $plan->slug }}','{{ addslashes($plan->label) }}',{{ $plan->yearly_price }});document.getElementById('enquiry-form').scrollIntoView({behavior:'smooth'});">
                    Get Started <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── MODULES ── --}}
<div class="modules-section">
    <div class="modules-inner">
        <div class="section-label">
            <h2 style="color:#fff;">Powerful Modules You Can Add</h2>
            <p>Pick any add-on module to supercharge your hotel operations</p>
        </div>
        <div class="modules-grid">
            @foreach($modules as $mod)
            @php
                $colors = ['#0369a1','#16a34a','#7c3aed','#d97706','#0891b2','#dc2626','#0369a1','#059669','#7c3aed','#d97706','#0891b2'];
                $ci = $loop->index % count($colors);
                $c  = $colors[$ci];
            @endphp
            <div class="module-card">
                <div class="module-icon" style="background:{{ $c }}22;"><i class="{{ $mod['brand'] ? 'fab' : 'fas' }} {{ $mod['icon'] }}" style="color:{{ $c }};"></i></div>
                <div class="module-name">{{ $mod['name'] }}</div>
                <div class="module-desc">{{ $mod['desc'] }}</div>
            </div>
            @endforeach
            <div class="module-card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;border-style:dashed;">
                <div style="font-size:28px;color:rgba(255,255,255,.2);margin-bottom:8px;"><i class="fas fa-ellipsis-h"></i></div>
                <div class="module-name" style="color:rgba(255,255,255,.35);">& More Modules</div>
                <div class="module-desc">More coming soon</div>
            </div>
        </div>
    </div>
</div>

{{-- ── BENEFITS ── --}}
<div class="benefits-section">
    <div class="benefits-inner">
        <div class="section-label">
            <h2>Why Hotel Owners Love Us</h2>
            <p>Thousands of hotels & resorts trust Dreams Technology CRM</p>
        </div>
        <div class="benefits-grid">
            @foreach([
                ['fas fa-chart-bar','#0369a1','INCREASE BOOKINGS','Get more direct bookings from your website. No commission, no middleman.'],
                ['fas fa-clock','#16a34a','SAVE STAFF TIME','Automate check-in messages, invoices & reports. Focus on guests, not paperwork.'],
                ['fas fa-shield-alt','#7c3aed','REDUCE ERRORS','Manage everything accurately in one place. No double bookings, no data loss.'],
                ['fas fa-rocket','#d97706','GROW YOUR BUSINESS','Make data driven decisions and grow revenue with powerful analytics.'],
            ] as $b)
            <div class="benefit-card">
                <div class="benefit-icon" style="background:{{ $b[1] }}22;"><i class="{{ $b[0] }}" style="color:{{ $b[1] }};"></i></div>
                <div class="benefit-title">{{ $b[2] }}</div>
                <div class="benefit-desc">{{ $b[3] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── ENQUIRY FORM ── --}}
<div id="enquiry-form" class="enquiry-section">
    <div class="section-label" style="margin-bottom:28px;">
        <h2>Get Started Today</h2>
        <p>Select a plan above, fill your details — we activate your account in minutes via WhatsApp.</p>
    </div>
    <div class="form-wrap">
        <div class="selected-plan-bar empty" id="selectedPlanBar">
            <i class="fas fa-hand-pointer"></i>
            <span id="selectedPlanText">Please select a plan above first</span>
        </div>

        <div class="field-row">
            <div class="field"><label>Your Name *</label><input type="text" id="f-name" placeholder="e.g. Rahul Sharma" autocomplete="name"></div>
            <div class="field"><label>Hotel / Resort Name *</label><input type="text" id="f-hotel" placeholder="e.g. Sunset Resort" autocomplete="organization"></div>
        </div>
        <div class="field-row">
            <div class="field"><label>WhatsApp / Phone *</label><input type="tel" id="f-phone" placeholder="e.g. 98765 43210" autocomplete="tel"></div>
            <div class="field"><label>Number of Rooms</label><input type="number" id="f-rooms" placeholder="e.g. 20" min="1" max="9999"></div>
        </div>
        <div class="field"><label>Message (optional)</label><input type="text" id="f-msg" placeholder="Any specific requirements or questions…"></div>

        <div class="form-error" id="formError"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i><span id="formErrorMsg">Please fill all required fields.</span></div>

        <div class="form-success" id="formSuccess">
            <div style="font-size:36px;margin-bottom:10px;">✅</div>
            <div style="font-size:16px;font-weight:800;color:#4ade80;margin-bottom:6px;">Enquiry Sent Successfully!</div>
            <div style="font-size:13px;color:rgba(255,255,255,.5);margin-bottom:16px;">We've received your details and will contact you shortly on WhatsApp.</div>
            <a href="https://wa.me/919725225519" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;padding:10px 20px;border-radius:9px;font-size:13px;font-weight:800;text-decoration:none;"><i class="fab fa-whatsapp"></i> Chat with us directly</a>
        </div>

        <button class="btn-submit" id="sendBtn" onclick="sendEnquiry()">
            <i class="fas fa-paper-plane" style="font-size:16px;"></i> Send Enquiry — Get Free Demo
        </button>
        <p class="form-note">
            <i class="fas fa-lock" style="margin-right:4px;"></i> We'll reach out on your WhatsApp. No payment taken online.<br>
            <a href="https://wa.me/919725225519" target="_blank"><i class="fab fa-whatsapp" style="margin-right:3px;"></i>Direct WhatsApp: +91 97252 25519</a>
        </p>
    </div>
</div>

{{-- ── FOOTER CTA ── --}}
<div class="footer-cta">
    <div class="footer-cta-inner">
        <div class="footer-left">
            <div class="footer-wa-circle"><i class="fab fa-whatsapp"></i></div>
            <div>
                <div class="footer-tagline">LET'S GROW TOGETHER!</div>
                <div class="footer-number">97252 25519</div>
            </div>
        </div>
        <div class="footer-benefits">
            @foreach(['No setup fee','Cancel anytime','Free onboarding','24/7 WhatsApp support'] as $fb)
            <div class="fbenefit"><i class="fas fa-check-circle"></i> {{ $fb }}</div>
            @endforeach
        </div>
        <a href="https://wa.me/919725225519" target="_blank" class="btn-wa-sm" style="font-size:14px;padding:13px 24px;border-radius:10px;">
            <i class="fab fa-whatsapp" style="font-size:18px;"></i> Chat on WhatsApp
        </a>
    </div>
    <div class="footer-copy">© {{ date('Y') }} Dreams Technology CRM &nbsp;·&nbsp; resort.dreamstechnology.in &nbsp;·&nbsp; +91 97252 25519</div>
</div>

<script>
var selectedSlug  = '';
var selectedLabel = '';
var selectedPrice = 0;
var csrfToken     = '{{ csrf_token() }}';

function selectPlan(slug, label, price) {
    document.querySelectorAll('.plan-card').forEach(function(c){ c.style.outline='none'; });
    document.querySelectorAll('[id^="planbtn-"]').forEach(function(b){
        var slug2 = b.id.replace('planbtn-','');
        var card  = document.getElementById('card-'+slug2);
        if(card) b.innerHTML = 'Get Started <i class="fas fa-arrow-right" style="margin-left:6px;"></i>';
    });

    selectedSlug  = slug;
    selectedLabel = label;
    selectedPrice = price;

    var card = document.getElementById('card-'+slug);
    var btn  = document.getElementById('planbtn-'+slug);
    if(card) card.style.outline = '3px solid #38bdf8';
    if(btn)  btn.innerHTML = '<i class="fas fa-check" style="margin-right:6px;"></i> Selected';

    var bar = document.getElementById('selectedPlanBar');
    bar.classList.remove('empty');
    bar.innerHTML = '<i class="fas fa-check-circle" style="color:#38bdf8;font-size:16px;"></i>'
        + '<span style="color:#fff;font-weight:800;">' + label + ' Plan</span>'
        + '<span style="color:rgba(255,255,255,.4);font-size:12px;margin-left:auto;">₹' + price.toLocaleString('en-IN') + '/yr</span>';

    document.getElementById('formError').style.display = 'none';
}

function sendEnquiry() {
    var name  = document.getElementById('f-name').value.trim();
    var hotel = document.getElementById('f-hotel').value.trim();
    var phone = document.getElementById('f-phone').value.trim();
    var rooms = document.getElementById('f-rooms').value.trim();
    var msg   = document.getElementById('f-msg').value.trim();

    var errEl  = document.getElementById('formError');
    var errMsg = document.getElementById('formErrorMsg');

    if(!selectedSlug){
        errEl.style.display='block'; errMsg.textContent='Please select a plan from the options above.';
        document.getElementById('plans').scrollIntoView({behavior:'smooth',block:'start'}); return;
    }
    if(!name||!hotel||!phone){
        errEl.style.display='block'; errMsg.textContent='Please fill in your name, hotel name, and phone number.'; return;
    }
    errEl.style.display='none';

    var btn=document.getElementById('sendBtn');
    btn.disabled=true;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Sending…';

    fetch('/pricing/enquire',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
        body:JSON.stringify({name,hotel,phone,plan_slug:selectedSlug,plan_label:selectedLabel,plan_price:selectedPrice,rooms,message:msg})
    })
    .then(r=>r.json())
    .then(function(d){
        if(d.success){
            document.getElementById('formSuccess').style.display='block';
            btn.style.display='none';
            document.querySelector('.form-note').style.display='none';
            document.getElementById('selectedPlanBar').style.display='none';
            document.querySelectorAll('.field,.field-row').forEach(function(f){f.style.display='none';});
        } else { throw new Error(); }
    })
    .catch(function(){
        btn.disabled=false;
        btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Enquiry — Get Free Demo';
        errEl.style.display='block';
        errMsg.textContent='Something went wrong. Please try WhatsApp directly or try again.';
    });
}
</script>
</body>
</html>
