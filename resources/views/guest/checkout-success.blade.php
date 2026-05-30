<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Request Received</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a, #134e4a); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; padding: 20px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; padding: 36px 28px; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(99,102,241,.35);">
        <i class="fas fa-clock" style="color:#fff;font-size:32px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:22px;color:#1e293b;margin-bottom:8px;">Checkout Request Received!</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;">
        Thank you, <strong>{{ $booking->customer?->name ?? 'Guest' }}</strong>! Our team has been notified and will complete your checkout shortly.
    </p>

    {{-- Show payment details guest submitted --}}
    <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin-bottom:16px;text-align:left;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Your Payment Details</div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
            <span style="color:#64748b;">Payment Method</span>
            <span style="font-weight:700;color:#1e293b;text-transform:uppercase;">{{ $booking->guest_payment_method ?? '—' }}</span>
        </div>
        @if($booking->guest_payment_ref)
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
            <span style="color:#64748b;">Reference / Transaction ID</span>
            <span style="font-weight:700;color:#1e293b;font-family:monospace;">{{ $booking->guest_payment_ref }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:13px;">
            <span style="color:#64748b;">Room</span>
            <span style="font-weight:700;color:#1e293b;">{{ $booking->room?->room_number ?? '—' }}</span>
        </div>
    </div>

    <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;font-size:13px;color:#92400e;font-weight:600;margin-bottom:20px;">
        <i class="fas fa-key" style="margin-right:6px;"></i> Please return your room key to the front desk.
    </div>

    <p style="font-size:13px;color:#94a3b8;">We hope you had a wonderful stay at <strong>{{ $settings->resort_name ?? '' }}</strong>. See you again! 🙏</p>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8;">
        धन्यवाद! हमें उम्मीद है कि आपका प्रवास सुखद रहा।
    </div>
</div>
</body>
</html>
