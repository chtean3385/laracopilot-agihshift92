<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

                    <tr>
                        <td align="center" style="padding-bottom:28px;">
                            <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:20px;padding:28px 32px;text-align:center;">
                                <div style="width:56px;height:56px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                                    <span style="font-size:24px;">🏨</span>
                                </div>
                                <h1 style="color:#fff;font-size:22px;font-weight:800;margin:0 0 4px;">{{ $resortName }}</h1>
                                @if($tagline)
                                <p style="color:#06b6d4;font-size:13px;margin:0;">{{ $tagline }}</p>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fff;border-radius:20px;padding:40px 36px;box-shadow:0 4px 24px rgba(0,0,0,.08);">
                            <h2 style="color:#0f172a;font-size:22px;font-weight:700;margin:0 0 8px;">Reset Your Password</h2>
                            <p style="color:#475569;font-size:15px;margin:0 0 24px;">Hi {{ $userName }},</p>
                            <p style="color:#475569;font-size:15px;line-height:1.6;margin:0 0 28px;">
                                We received a request to reset your password for the <strong>{{ $resortName }}</strong> staff portal. Click the button below to set a new password.
                            </p>

                            <div style="text-align:center;margin-bottom:32px;">
                                <a href="{{ $resetUrl }}"
                                    style="display:inline-block;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;text-decoration:none;padding:14px 36px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 16px rgba(6,182,212,.4);">
                                    🔑 Reset My Password
                                </a>
                            </div>

                            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-bottom:28px;">
                                <p style="color:#92400e;font-size:13px;margin:0;">
                                    ⏰ <strong>This link expires in 60 minutes.</strong> If it expires, you can request a new one from the login page.
                                </p>
                            </div>

                            <p style="color:#94a3b8;font-size:13px;line-height:1.6;margin:0 0 12px;">
                                If you didn't request a password reset, you can safely ignore this email. Your password will not change.
                            </p>

                            <p style="color:#94a3b8;font-size:12px;margin:0;word-break:break-all;">
                                Or copy this link: <a href="{{ $resetUrl }}" style="color:#06b6d4;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align:center;padding-top:24px;">
                            <p style="color:#94a3b8;font-size:12px;margin:0;">© {{ date('Y') }} {{ $resortName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
