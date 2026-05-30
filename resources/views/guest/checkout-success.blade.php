<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-Out {{ isset($autoCheckedOut) && $autoCheckedOut ? 'Complete' : 'Confirmed' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a, #134e4a); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; padding: 20px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; padding: 36px 28px; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    @if(isset($autoCheckedOut) && $autoCheckedOut)
    {{-- ── AUTO CHECKED OUT (UPI + transaction ID) ── --}}
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(16,185,129,.4);">
        <i class="fas fa-check" style="color:#fff;font-size:32px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:24px;color:#1e293b;margin-bottom:8px;">You're Checked Out!</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;">
        Thank you, <strong>{{ $booking->customer?->name ?? 'Guest' }}</strong>! Your UPI payment has been recorded and your checkout is <strong style="color:#10b981;">complete</strong>. No further action needed.
    </p>
    @if($booking->guest_payment_ref)
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:14px 16px;margin-bottom:20px;">
        <div style="font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">UPI Transaction ID</div>
        <div style="font-family:monospace;font-size:16px;font-weight:800;color:#1e293b;">{{ $booking->guest_payment_ref }}</div>
    </div>
    @endif
    <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;font-size:13px;color:#92400e;font-weight:600;margin-bottom:20px;">
        Please return your room key to the front desk.
    </div>

    @else
    {{-- ── CASH / CARD — staff confirms ── --}}
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(99,102,241,.35);">
        <i class="fas fa-clock" style="color:#fff;font-size:32px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:22px;color:#1e293b;margin-bottom:8px;">Request Received!</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;">
        Thank you, <strong>{{ $booking->customer?->name ?? 'Guest' }}</strong>! Our team has been notified and will complete your checkout shortly. Please settle payment at the front desk.
    </p>
    <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;font-size:13px;color:#92400e;font-weight:600;margin-bottom:20px;">
        Please hand over your room key at the front desk.
    </div>
    @endif

    <p style="font-size:13px;color:#94a3b8;">We hope you had a wonderful stay at <strong>{{ $settings->resort_name ?? '' }}</strong>. See you again! 🙏</p>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8;">
        धन्यवाद! हमें उम्मीद है कि आपका प्रवास सुखद रहा।
    </div>
</div>
</body>
</html>
