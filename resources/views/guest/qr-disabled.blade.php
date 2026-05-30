<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Not Available' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a, #1e3a5f); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; padding: 20px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 400px; padding: 40px 32px; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#64748b,#475569);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 8px 24px rgba(100,116,139,.35);">
        <i class="fas {{ $icon ?? 'fa-info-circle' }}" style="color:#fff;font-size:30px;"></i>
    </div>
    <h1 style="font-weight:900;font-size:20px;color:#1e293b;margin-bottom:12px;">{{ $title ?? 'Not Available' }}</h1>
    <p style="font-size:14px;color:#64748b;line-height:1.7;margin-bottom:0;">{{ $message ?? 'Please contact hotel staff for assistance.' }}</p>
    <div style="margin-top:28px;padding-top:20px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8;">
        धन्यवाद &nbsp;·&nbsp; Thank you
    </div>
</div>
</body>
</html>
