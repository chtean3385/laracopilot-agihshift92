<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — Platform Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #2d1b69 50%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .box {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
        }
        .brand { text-align: center; margin-bottom: 32px; }
        .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg,#8b5cf6,#4c1d95);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 32px rgba(139,92,246,.4);
        }
        .brand h1 { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .brand p { font-size: 13px; color: #a78bfa; font-weight: 500; }
        .info-box {
            background: rgba(139,92,246,.12);
            border: 1px solid rgba(139,92,246,.25);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #c4b5fd;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .info-box i { flex-shrink: 0; margin-top: 1px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 11px; font-weight: 700;
            color: #c4b5fd; text-transform: uppercase;
            letter-spacing: .08em; margin-bottom: 8px;
        }
        .otp-input {
            width: 100%;
            padding: 16px 14px;
            background: rgba(255,255,255,.07);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 12px;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            letter-spacing: .35em;
            text-align: center;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .otp-input::placeholder { color: rgba(255,255,255,.2); font-size: 18px; letter-spacing: .1em; }
        .otp-input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.25); }
        .form-error { font-size: 12px; color: #fca5a5; margin-top: 6px; }
        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg,#8b5cf6,#7c3aed);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity .2s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(139,92,246,.4);
            margin-top: 4px;
        }
        .btn:hover { opacity: .9; box-shadow: 0 6px 28px rgba(139,92,246,.5); }
        .back-link {
            display: block; text-align: center; margin-top: 20px;
            font-size: 12px; color: #7c3aed; text-decoration: none;
        }
        .back-link:hover { color: #a78bfa; }
        .alert-error {
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 10px; padding: 12px 16px;
            margin-bottom: 20px; font-size: 13px; color: #fca5a5;
        }
    </style>
</head>
<body>

<div class="box">
    <div class="brand">
        <div class="brand-icon">
            <i class="fas fa-shield-halved" style="color:#fff;font-size:26px;"></i>
        </div>
        <h1>Two-Factor Auth</h1>
        <p>Enter the code from your authenticator app</p>
    </div>

    @if($errors->any())
    <div class="alert-error">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
        {{ $errors->first('one_time_password') }}
    </div>
    @endif

    <div class="info-box">
        <i class="fas fa-mobile-alt"></i>
        <span>Open Microsoft Authenticator (or any TOTP app) and enter the 6-digit code for your Platform Admin account.</span>
    </div>

    <form method="POST" action="{{ route('platform.login.2fa.post') }}">
        @csrf

        <div class="form-group">
            <label>6-Digit Code</label>
            <input type="text"
                   name="one_time_password"
                   class="otp-input"
                   placeholder="000000"
                   maxlength="6"
                   inputmode="numeric"
                   pattern="[0-9]{6}"
                   autocomplete="one-time-code"
                   autofocus
                   required>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-check-circle" style="margin-right:8px;"></i>
            Verify Code
        </button>
    </form>

    <a href="{{ route('platform.login') }}" class="back-link">
        <i class="fas fa-arrow-left" style="margin-right:4px;"></i>
        Back to Login
    </a>
</div>

</body>
</html>
