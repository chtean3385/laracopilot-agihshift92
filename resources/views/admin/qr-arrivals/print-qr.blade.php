<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In QR — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #fff; font-family: 'Inter', system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .qr-card { border: 3px solid #6366f1; border-radius: 24px; padding: 36px 32px; max-width: 380px; width: 100%; text-align: center; box-shadow: 0 8px 32px rgba(99,102,241,.15); }
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .no-print { display: none !important; }
            .qr-card { border: 2px solid #000; box-shadow: none; border-radius: 16px; max-width: 100%; page-break-inside: avoid; margin: 0 auto; }
            @page { size: A5 portrait; margin: 15mm; }
        }
    </style>
</head>
<body>
<div>
    {{-- Print / Back buttons --}}
    <div class="no-print" style="text-align:center;margin-bottom:20px;display:flex;gap:12px;justify-content:center;">
        <button onclick="window.print()" style="padding:10px 22px;background:#6366f1;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;"><i class="fas fa-print" style="margin-right:8px;"></i>Print</button>
        <a href="{{ route('qr-arrivals.index') }}" style="padding:10px 22px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;display:inline-block;">Back</a>
    </div>

    <div class="qr-card">
        {{-- Hotel logo + name --}}
        @if(($settings->logo_url ?? null))
        <img src="{{ $settings->logo_url }}" alt="Logo" style="height:60px;object-fit:contain;margin-bottom:12px;">
        @else
        <div style="width:60px;height:60px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-hotel" style="color:#fff;font-size:24px;"></i>
        </div>
        @endif
        <div style="font-weight:900;font-size:20px;color:#1e293b;margin-bottom:4px;">{{ $settings->resort_name ?? $hotel->name }}</div>
        <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">{{ $hotel->address ?? '' }}</div>

        {{-- QR Code --}}
        <div style="background:#f8fafc;border-radius:16px;padding:16px;display:inline-block;margin-bottom:18px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($url) }}&bgcolor=f8fafc&color=0f172a&qzone=1&margin=5"
                alt="Check-In QR" style="width:220px;height:220px;display:block;">
        </div>

        {{-- Instructions --}}
        <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;padding:14px;margin-bottom:16px;">
            <div style="font-weight:900;font-size:16px;color:#fff;margin-bottom:4px;">Scan to Check In</div>
            <div style="font-size:13px;color:#c7d2fe;">चेक-इन के लिए स्कैन करें</div>
        </div>

        <div style="font-size:12px;color:#64748b;line-height:1.8;margin-bottom:12px;">
            1. Open your camera app &amp; scan the QR<br>
            2. Fill in your details &amp; upload ID<br>
            3. Sign digitally &amp; submit<br>
            4. Our team will assign your room
        </div>
        <div style="font-size:11px;color:#94a3b8;line-height:1.8;">
            1. कैमरा खोलें और QR स्कैन करें<br>
            2. विवरण भरें और ID अपलोड करें<br>
            3. हस्ताक्षर करें और सबमिट करें<br>
            4. हमारी टीम कमरा आवंटित करेगी
        </div>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;word-break:break-all;">
            {{ $url }}
        </div>
    </div>
</div>
</body>
</html>
