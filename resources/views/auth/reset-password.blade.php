<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — {{ $settings->resort_name ?? 'Hotel CRM' }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/hotel-crm-logo.png">
    <script src="/js/tailwind.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:32px;">
            @if($settings && $settings->logo)
            <img src="{{ asset('storage/' . $settings->logo) }}" alt="{{ $settings->resort_name }}"
                style="width:72px;height:72px;border-radius:20px;object-fit:cover;box-shadow:0 8px 24px rgba(0,0,0,.4);margin-bottom:16px;">
            @else
            <img src="/hotel-crm-logo.png" alt="Hotel CRM"
                 style="width:72px;height:72px;border-radius:20px;object-fit:cover;margin:0 auto 16px;display:block;box-shadow:0 8px 24px rgba(6,182,212,.35);">
            @endif
            <h1 style="color:#fff;font-size:24px;font-weight:800;margin:0;">{{ $settings->resort_name ?? 'Hotel CRM' }}</h1>
            @if($settings && $settings->tagline)
            <p style="color:#06b6d4;font-size:13px;margin:4px 0 0;">{{ $settings->tagline }} • Staff Portal</p>
            @endif
        </div>

        <div style="background:rgba(255,255,255,.07);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:36px;">
            <h2 style="color:#fff;font-size:20px;font-weight:700;text-align:center;margin:0 0 8px;">Set New Password</h2>
            <p style="color:#94a3b8;font-size:13px;text-align:center;margin:0 0 24px;">Choose a strong new password for your account.</p>

            @if($errors->any())
            <div style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5;padding:12px 16px;border-radius:12px;font-size:13px;margin-bottom:20px;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div style="margin-bottom:16px;">
                    <label style="display:block;color:#cbd5e1;font-size:13px;font-weight:600;margin-bottom:8px;">New Password</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:14px;"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" placeholder="Minimum 8 characters" required autofocus
                            style="width:100%;background:rgba(255,255,255,.08);border:1.5px solid {{ $errors->has('password') ? 'rgba(239,68,68,.6)' : 'rgba(255,255,255,.12)' }};border-radius:12px;padding:12px 14px 12px 42px;color:#fff;font-size:14px;outline:none;box-sizing:border-box;"
                            onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='rgba(255,255,255,.12)'">
                    </div>
                </div>

                <div style="margin-bottom:24px;">
                    <label style="display:block;color:#cbd5e1;font-size:13px;font-weight:600;margin-bottom:8px;">Confirm New Password</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:14px;"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password_confirmation" placeholder="Re-enter new password" required
                            style="width:100%;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);border-radius:12px;padding:12px 14px 12px 42px;color:#fff;font-size:14px;outline:none;box-sizing:border-box;"
                            onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='rgba(255,255,255,.12)'">
                    </div>
                </div>

                <button type="submit" style="width:100%;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border:none;padding:13px;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(6,182,212,.4);">
                    <i class="fas fa-key" style="margin-right:8px;"></i> Reset Password
                </button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:13px;color:#64748b;">
                <a href="{{ route('login') }}" style="color:#06b6d4;font-weight:600;text-decoration:none;"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px;"></i> Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
