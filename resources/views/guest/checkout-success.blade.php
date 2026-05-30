<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-Out Confirmed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; padding: 20px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; padding: 36px 28px; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(99,102,241,.35);">
        <i class="fas fa-check" style="color:#fff;font-size:32px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:22px;color:#1e293b;margin-bottom:8px;">Payment Confirmed!</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;">
        Thank you, <strong>{{ $booking->customer?->name ?? 'Guest' }}</strong>! Your payment has been recorded. Our team will finalise your check-out shortly.
    </p>
    <p style="font-size:13px;color:#94a3b8;">Thank you for staying at <strong>{{ $settings->resort_name ?? '' }}</strong>. We hope to see you again! 🙏</p>
    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8;">
        धन्यवाद! हमें उम्मीद है कि आपका प्रवास सुखद रहा।
    </div>
</div>
</body>
</html>
