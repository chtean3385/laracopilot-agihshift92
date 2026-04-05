<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Admin Login — Hotel CRM SaaS Console</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/hotel-crm-logo.png">
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
        .login-box {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
        }
        .brand {
            text-align: center;
            margin-bottom: 36px;
        }
        .brand-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            margin: 0 auto 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(139,92,246,.4);
        }
        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }
        .brand p {
            font-size: 13px;
            color: #a78bfa;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #c4b5fd;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #7c3aed;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px 12px 40px;
            background: rgba(255,255,255,.07);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 12px;
            font-size: 14px;
            color: #fff;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control::placeholder { color: rgba(255,255,255,.3); }
        .form-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139,92,246,.25);
        }
        .form-error {
            font-size: 12px;
            color: #fca5a5;
            margin-top: 6px;
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg,#8b5cf6,#7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(139,92,246,.4);
            margin-top: 8px;
        }
        .btn-login:hover { opacity: .9; box-shadow: 0 6px 28px rgba(139,92,246,.5); }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #7c3aed;
            text-decoration: none;
        }
        .back-link:hover { color: #a78bfa; }
        .alert-error {
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #fca5a5;
        }
    </style>
</head>
<body>

<div class="login-box">

    {{-- Brand --}}
    <div class="brand">
        <div class="brand-icon">
            <img src="/hotel-crm-logo.png" alt="Hotel CRM">
        </div>
        <h1>Platform Admin</h1>
        <p>SaaS Management Console</p>
    </div>

    {{-- Error --}}
    @if($errors->any())
    <div class="alert-error">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
        {{ $errors->first('email') }}
    </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('platform.login.post') }}">
        @csrf

        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                    placeholder="superadmin@yourdomain.com" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control"
                    placeholder="••••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-shield-halved" style="margin-right:8px;"></i>
            Sign In to Platform
        </button>
    </form>

    <a href="{{ route('login') }}" class="back-link">
        <i class="fas fa-arrow-left" style="margin-right:4px;"></i>
        Back to Hotel CRM Login
    </a>

</div>

</body>
</html>
