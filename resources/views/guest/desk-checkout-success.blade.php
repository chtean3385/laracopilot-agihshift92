<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checked Out — {{ $settings->resort_name ?? $hotel->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#0f172a,#134e4a); min-height:100vh; font-family:'Inter',system-ui,sans-serif; display:flex; align-items:center; justify-content:center; padding:20px; }
        .card { background:#fff; border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:420px; padding:36px 28px; text-align:center; }
    </style>
</head>
<body>
<div class="card">
    <div style="width:72px;height:72px;background:linear-gradient(135deg,#10b981,#059669);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fas fa-check" style="color:#fff;font-size:30px;"></i>
    </div>
    <h2 style="font-weight:900;font-size:22px;color:#1e293b;margin:0 0 8px;">Payment Confirmed!</h2>
    <p style="font-size:14px;color:#64748b;margin:0 0 20px;line-height:1.6;">
        Thank you, <strong>{{ $name }}</strong>. Your checkout has been completed successfully.
        We hope you had a wonderful stay!
    </p>
    <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;font-size:13px;color:#92400e;font-weight:600;margin-bottom:20px;">
        Please return your room key to the front desk.
    </div>
    <div style="font-size:12px;color:#94a3b8;">{{ $settings->resort_name ?? $hotel->name }}</div>
</div>
</body>
</html>
