<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #e8edf2; font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }

        /* A4 page container */
        .page {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
        }

        /* Hotel header strip */
        .hotel-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 32px;
            border-bottom: 1px solid #e2e8f0;
        }
        .hotel-header img { height: 48px; width: 48px; object-fit: contain; border-radius: 10px; }
        .hotel-logo-placeholder { width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center; }
        .hotel-name { font-weight: 900; font-size: 18px; color: #1e293b; }
        .hotel-sub { font-size: 12px; color: #94a3b8; margin-top: 1px; }
        .header-date { margin-left: auto; font-size: 11px; color: #94a3b8; text-align: right; }

        /* Two QR panels */
        .panels { display: flex; flex: 1; }

        .panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            gap: 0;
        }

        .panel-checkin { background: linear-gradient(160deg, #312e81 0%, #4338ca 50%, #6366f1 100%); }
        .panel-checkout { background: linear-gradient(160deg, #064e3b 0%, #065f46 50%, #10b981 100%); }

        /* Inner white card */
        .qr-card {
            background: #fff;
            border-radius: 24px;
            padding: 24px 20px;
            text-align: center;
            width: 100%;
            max-width: 260px;
            box-shadow: 0 8px 32px rgba(0,0,0,.25);
        }

        .qr-card img.qr-code {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            display: block;
            margin: 0 auto 14px;
        }

        .action-badge {
            border-radius: 12px;
            padding: 10px 16px;
            margin-bottom: 14px;
        }
        .badge-in { background: linear-gradient(135deg,#4338ca,#6366f1); }
        .badge-out { background: linear-gradient(135deg,#065f46,#10b981); }

        .action-badge .en { font-weight: 900; font-size: 15px; color: #fff; }
        .action-badge .hi { font-size: 11px; color: rgba(255,255,255,.75); margin-top: 2px; }

        .instructions { font-size: 11px; color: #64748b; line-height: 1.8; text-align: left; }
        .step { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px; }
        .step-num { width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; flex-shrink: 0; margin-top: 1px; }
        .num-in { background: #e0e7ff; color: #4338ca; }
        .num-out { background: #d1fae5; color: #065f46; }

        .qr-url { font-size: 8px; color: #cbd5e1; word-break: break-all; margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9; }

        /* No-print controls */
        .controls { display: flex; gap: 12px; justify-content: center; padding: 20px; background: #e8edf2; }
        .controls button, .controls a {
            padding: 11px 22px; border-radius: 10px; font-size: 14px; font-weight: 700;
            cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
        }
        .btn-print { background: #4338ca; color: #fff; }
        .btn-back  { background: #f1f5f9; color: #475569; }

        /* ── Print Styles ──────────────────────────────────────── */
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 0; }

            body { background: #fff; padding: 0; display: block; margin: 0; }
            .controls { display: none !important; }
            .page { width: 100%; min-height: 100vh; box-shadow: none; page-break-inside: avoid; }
            .panels { min-height: calc(100vh - 85px); }
        }
    </style>
</head>
<body>

{{-- Controls (hidden on print) --}}
<div style="position:fixed;top:0;left:0;right:0;z-index:100;background:#e8edf2;padding:12px 24px;display:flex;gap:12px;justify-content:center;" class="controls no-print">
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print A4</button>
    <a href="{{ route('qr-arrivals.index') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div style="margin-top:68px;">
<div class="page">

    {{-- Hotel header --}}
    <div class="hotel-header">
        @if($settings->logo_url ?? null)
        <img src="{{ $settings->logo_url }}" alt="Logo">
        @else
        <div class="hotel-logo-placeholder">
            <i class="fas fa-hotel" style="color:#fff;font-size:20px;"></i>
        </div>
        @endif
        <div>
            <div class="hotel-name">{{ $settings->resort_name ?? $hotel->name }}</div>
            <div class="hotel-sub">{{ $hotel->address ?? 'Guest QR Check-In & Check-Out' }}</div>
        </div>
        <div class="header-date">
            <div>Printed: {{ now()->format('d M Y') }}</div>
            <div style="margin-top:2px;">{{ now()->format('h:i A') }}</div>
        </div>
    </div>

    {{-- Two QR panels side by side --}}
    <div class="panels">

        {{-- LEFT: Check-In --}}
        <div class="panel panel-checkin">
            <div class="qr-card">
                {{-- Hotel mini-logo inside card --}}
                @if($settings->logo_url ?? null)
                <img src="{{ $settings->logo_url }}" alt="" style="height:36px;width:36px;object-fit:contain;border-radius:8px;margin:0 auto 10px;display:block;">
                @endif

                {{-- QR Code --}}
                <img class="qr-code"
                    src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($checkinUrl) }}&bgcolor=ffffff&color=1e1b4b&qzone=2&margin=4"
                    alt="Check-In QR">

                {{-- Action badge --}}
                <div class="action-badge badge-in">
                    <div class="en"><i class="fas fa-sign-in-alt" style="margin-right:6px;font-size:13px;"></i>Scan to Check In</div>
                    <div class="hi">चेक-इन के लिए स्कैन करें</div>
                </div>

                {{-- Steps --}}
                <div class="instructions">
                    <div class="step"><span class="step-num num-in">1</span><span>Open camera &amp; scan QR / कैमरा खोलें और स्कैन करें</span></div>
                    <div class="step"><span class="step-num num-in">2</span><span>Fill details &amp; upload ID / विवरण भरें</span></div>
                    <div class="step"><span class="step-num num-in">3</span><span>Sign digitally &amp; submit / हस्ताक्षर करें</span></div>
                    <div class="step"><span class="step-num num-in">4</span><span>Our team will assign your room / कमरा मिलेगा</span></div>
                </div>

                <div class="qr-url">{{ $checkinUrl }}</div>
            </div>
        </div>

        {{-- RIGHT: Check-Out --}}
        <div class="panel panel-checkout">
            <div class="qr-card">
                {{-- Hotel mini-logo inside card --}}
                @if($settings->logo_url ?? null)
                <img src="{{ $settings->logo_url }}" alt="" style="height:36px;width:36px;object-fit:contain;border-radius:8px;margin:0 auto 10px;display:block;">
                @endif

                {{-- QR Code --}}
                <img class="qr-code"
                    src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($checkoutUrl) }}&bgcolor=ffffff&color=064e3b&qzone=2&margin=4"
                    alt="Check-Out QR">

                {{-- Action badge --}}
                <div class="action-badge badge-out">
                    <div class="en"><i class="fas fa-sign-out-alt" style="margin-right:6px;font-size:13px;"></i>Scan to Check Out</div>
                    <div class="hi">चेक-आउट के लिए स्कैन करें</div>
                </div>

                {{-- Steps --}}
                <div class="instructions">
                    <div class="step"><span class="step-num num-out">1</span><span>Open camera &amp; scan QR / कैमरा खोलें और स्कैन करें</span></div>
                    <div class="step"><span class="step-num num-out">2</span><span>Enter phone number / फोन नंबर दर्ज करें</span></div>
                    <div class="step"><span class="step-num num-out">3</span><span>View bill &amp; pay via UPI / बिल देखें और भुगतान करें</span></div>
                    <div class="step"><span class="step-num num-out">4</span><span>Hand over the key &amp; you're done! / चाबी वापस करें</span></div>
                </div>

                <div class="qr-url">{{ $checkoutUrl }}</div>
            </div>
        </div>

    </div>{{-- /.panels --}}

</div>{{-- /.page --}}
</div>

<script>
// Auto-prompt print on load if ?print=1
if (window.location.search.includes('print=1')) {
    window.addEventListener('load', function() { setTimeout(window.print, 400); });
}
</script>
</body>
</html>
