<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #cbd5e1;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            gap: 32px;
            align-items: center;
        }

        /* ── Each page ───────────────────────────────────────── */
        .qr-page {
            width: 210mm;
            min-height: 297mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 48px rgba(0,0,0,.25);
        }

        /* background blobs for depth */
        .qr-page::before, .qr-page::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: .18;
        }
        .qr-page::before { width: 320px; height: 320px; top: -80px; right: -80px; }
        .qr-page::after  { width: 240px; height: 240px; bottom: -60px; left: -60px; }

        .page-checkin  { background: linear-gradient(160deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%); }
        .page-checkin::before  { background: #818cf8; }
        .page-checkin::after   { background: #6366f1; }

        .page-checkout { background: linear-gradient(160deg, #022c22 0%, #064e3b 40%, #065f46 100%); }
        .page-checkout::before { background: #34d399; }
        .page-checkout::after  { background: #10b981; }

        /* ── Inner white card ────────────────────────────────── */
        .inner {
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: 32px;
            padding: 36px 32px 28px;
            width: 76mm;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.12);
        }

        /* Hotel logo */
        .hotel-logo { height: 56px; width: 56px; object-fit: contain; border-radius: 14px; margin: 0 auto 10px; display: block; }
        .hotel-logo-placeholder {
            width: 56px; height: 56px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px;
        }
        .placeholder-in  { background: linear-gradient(135deg,#4338ca,#6366f1); }
        .placeholder-out { background: linear-gradient(135deg,#065f46,#10b981); }

        .hotel-name { font-weight: 900; font-size: 17px; color: #1e293b; margin-bottom: 2px; }
        .hotel-tagline { font-size: 11px; color: #94a3b8; margin-bottom: 20px; }

        /* QR image */
        .qr-img { width: 220px; height: 220px; border-radius: 16px; display: block; margin: 0 auto 20px; border: 1.5px solid #f1f5f9; }

        /* Action badge */
        .action-badge {
            border-radius: 14px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        .badge-in  { background: linear-gradient(135deg,#4338ca,#6366f1); }
        .badge-out { background: linear-gradient(135deg,#065f46,#10b981); }
        .badge-en { font-weight: 900; font-size: 16px; color: #fff; letter-spacing: .01em; }
        .badge-hi { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 3px; }

        /* Steps */
        .steps { text-align: left; }
        .step { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 8px; }
        .step-num {
            width: 20px; height: 20px; border-radius: 50%;
            font-size: 10px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px;
        }
        .num-in  { background: #e0e7ff; color: #3730a3; }
        .num-out { background: #d1fae5; color: #065f46; }
        .step-text { font-size: 11px; color: #475569; line-height: 1.5; }

        /* Bottom URL */
        .qr-url { font-size: 8px; color: #cbd5e1; word-break: break-all; margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e2e8f0; }

        /* Bottom label strip on page */
        .page-label {
            position: absolute; bottom: 20px; z-index: 2;
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,.45);
            letter-spacing: .08em; text-transform: uppercase;
        }

        /* ── Controls (screen only) ──────────────────────────── */
        .controls {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(15,23,42,.92); backdrop-filter: blur(6px);
            padding: 12px 24px; display: flex; gap: 12px; justify-content: center;
        }
        .controls button, .controls a {
            padding: 10px 22px; border-radius: 10px; font-size: 14px; font-weight: 700;
            cursor: pointer; border: none; text-decoration: none;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .btn-print { background: linear-gradient(135deg,#4338ca,#6366f1); color: #fff; }
        .btn-back  { background: rgba(255,255,255,.1); color: #cbd5e1; }
        .hint { color: #64748b; font-size: 12px; align-self: center; }

        /* ── Print styles ────────────────────────────────────── */
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 0; }

            body { background: none; padding: 0; gap: 0; display: block; }
            .controls { display: none !important; }

            .qr-page {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                page-break-after: always;
                box-shadow: none;
            }
            .qr-page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>

{{-- ── Screen controls (hidden on print) ── --}}
<div class="controls">
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Both Pages (A4)</button>
    <a href="{{ route('qr-arrivals.index') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    <span class="hint">Prints as 2 separate A4 pages — one per QR</span>
</div>

<div style="height:64px;"></div>{{-- spacer for fixed controls --}}

{{-- ════════════════════════════════════════════════════════ --}}
{{-- PAGE 1 — CHECK-IN                                       --}}
{{-- ════════════════════════════════════════════════════════ --}}
<div class="qr-page page-checkin">

    <div class="inner">
        {{-- Logo --}}
        @if($settings->logo_url ?? null)
        <img src="{{ $settings->logo_url }}" alt="Logo" class="hotel-logo">
        @else
        <div class="hotel-logo-placeholder placeholder-in">
            <i class="fas fa-hotel" style="color:#fff;font-size:22px;"></i>
        </div>
        @endif

        <div class="hotel-name">{{ $settings->resort_name ?? $hotel->name }}</div>
        <div class="hotel-tagline">{{ $hotel->address ?? 'Welcome — आपका स्वागत है' }}</div>

        {{-- QR --}}
        <img class="qr-img"
            src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data={{ urlencode($checkinUrl) }}&bgcolor=ffffff&color=1e1b4b&qzone=2&margin=4"
            alt="Check-In QR">

        {{-- Badge --}}
        <div class="action-badge badge-in">
            <div class="badge-en"><i class="fas fa-sign-in-alt" style="margin-right:7px;"></i>Scan to Check In</div>
            <div class="badge-hi">चेक-इन के लिए स्कैन करें</div>
        </div>

        {{-- Steps --}}
        <div class="steps">
            <div class="step"><span class="step-num num-in">1</span><span class="step-text">Open camera &amp; scan / कैमरा खोलें और स्कैन करें</span></div>
            <div class="step"><span class="step-num num-in">2</span><span class="step-text">Fill details &amp; upload ID / विवरण भरें और ID अपलोड करें</span></div>
            <div class="step"><span class="step-num num-in">3</span><span class="step-text">Sign &amp; submit / हस्ताक्षर करें और सबमिट करें</span></div>
            <div class="step"><span class="step-num num-in">4</span><span class="step-text">Our team assigns your room / कमरा आवंटित किया जाएगा</span></div>
        </div>

        <div class="qr-url">{{ $checkinUrl }}</div>
    </div>

    <div class="page-label">Check-In · {{ $settings->resort_name ?? $hotel->name }}</div>
</div>


{{-- ════════════════════════════════════════════════════════ --}}
{{-- PAGE 2 — CHECK-OUT                                      --}}
{{-- ════════════════════════════════════════════════════════ --}}
<div class="qr-page page-checkout">

    <div class="inner">
        {{-- Logo --}}
        @if($settings->logo_url ?? null)
        <img src="{{ $settings->logo_url }}" alt="Logo" class="hotel-logo">
        @else
        <div class="hotel-logo-placeholder placeholder-out">
            <i class="fas fa-hotel" style="color:#fff;font-size:22px;"></i>
        </div>
        @endif

        <div class="hotel-name">{{ $settings->resort_name ?? $hotel->name }}</div>
        <div class="hotel-tagline">{{ $hotel->address ?? 'Thank you for staying with us' }}</div>

        {{-- QR --}}
        <img class="qr-img"
            src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data={{ urlencode($checkoutUrl) }}&bgcolor=ffffff&color=022c22&qzone=2&margin=4"
            alt="Check-Out QR">

        {{-- Badge --}}
        <div class="action-badge badge-out">
            <div class="badge-en"><i class="fas fa-sign-out-alt" style="margin-right:7px;"></i>Scan to Check Out</div>
            <div class="badge-hi">चेक-आउट के लिए स्कैन करें</div>
        </div>

        {{-- Steps --}}
        <div class="steps">
            <div class="step"><span class="step-num num-out">1</span><span class="step-text">Open camera &amp; scan / कैमरा खोलें और स्कैन करें</span></div>
            <div class="step"><span class="step-num num-out">2</span><span class="step-text">Enter phone number / फोन नंबर दर्ज करें</span></div>
            <div class="step"><span class="step-num num-out">3</span><span class="step-text">View bill &amp; pay via UPI / बिल देखें और UPI से भुगतान करें</span></div>
            <div class="step"><span class="step-num num-out">4</span><span class="step-text">Hand over the key — you're done! / चाबी वापस करें</span></div>
        </div>

        <div class="qr-url">{{ $checkoutUrl }}</div>
    </div>

    <div class="page-label">Check-Out · {{ $settings->resort_name ?? $hotel->name }}</div>
</div>

</body>
</html>
