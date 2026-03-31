<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Hotel CRM</title>
</head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                {{-- ── Header ── --}}
                <tr>
                    <td style="padding-bottom:24px;">
                        <div style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#4c1d95 100%);border-radius:24px;padding:36px 40px;text-align:center;">
                            <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:18px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                                <span style="font-size:30px;">🏨</span>
                            </div>
                            <h1 style="color:#fff;font-size:26px;font-weight:800;margin:0 0 6px;letter-spacing:-.5px;">Hotel CRM</h1>
                            <p style="color:#a5b4fc;font-size:14px;margin:0;">by Dreams Technology</p>
                        </div>
                    </td>
                </tr>

                {{-- ── Welcome Banner ── --}}
                <tr>
                    <td style="padding-bottom:16px;">
                        <div style="background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 4px 24px rgba(79,70,229,.08);border:1px solid #e0e7ff;">

                            <div style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:14px;padding:18px 22px;margin-bottom:28px;display:flex;align-items:center;gap:14px;">
                                <span style="font-size:28px;">🎉</span>
                                <div>
                                    <div style="font-size:16px;font-weight:800;color:#4c1d95;margin-bottom:2px;">Welcome Aboard!</div>
                                    <div style="font-size:13px;color:#6d28d9;">Your hotel CRM account is ready to use</div>
                                </div>
                            </div>

                            <p style="color:#374151;font-size:16px;margin:0 0 8px;">Hi <strong>{{ $adminName }}</strong>,</p>
                            <p style="color:#6b7280;font-size:15px;line-height:1.7;margin:0 0 28px;">
                                Your hotel <strong style="color:#1e1b4b;">{{ $hotelName }}</strong> has been successfully onboarded onto the Hotel CRM platform. You can now start managing your guests, rooms, bookings, payments, and much more — all from one powerful dashboard.
                            </p>

                            {{-- ── Login Credentials Box ── --}}
                            <div style="background:#1e1b4b;border-radius:16px;padding:28px 32px;margin-bottom:28px;">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                                    <span style="font-size:18px;">🔐</span>
                                    <span style="color:#a5b4fc;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Your Login Credentials</span>
                                </div>

                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:14px;">
                                            <div style="font-size:11px;color:#6d6aff;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Hotel Name</div>
                                            <div style="font-size:15px;font-weight:700;color:#fff;">{{ $hotelName }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:14px;border-top:1px solid rgba(255,255,255,.08);padding-top:14px;">
                                            <div style="font-size:11px;color:#6d6aff;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Email Address</div>
                                            <div style="font-size:15px;font-weight:700;color:#fff;">{{ $adminEmail }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-top:1px solid rgba(255,255,255,.08);padding-top:14px;">
                                            <div style="font-size:11px;color:#6d6aff;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Password</div>
                                            <div style="font-size:15px;font-weight:700;color:#fff;font-family:monospace;background:rgba(255,255,255,.08);padding:8px 14px;border-radius:8px;display:inline-block;letter-spacing:.05em;">{{ $adminPassword }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            {{-- ── Plan Badge ── --}}
                            <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;padding:14px 18px;margin-bottom:28px;display:flex;align-items:center;gap:12px;">
                                <span style="font-size:20px;">👑</span>
                                <div>
                                    <div style="font-size:12px;color:#7c3aed;font-weight:600;">Active Plan</div>
                                    <div style="font-size:15px;font-weight:700;color:#1e1b4b;">{{ ucfirst($plan) }} Plan</div>
                                </div>
                            </div>

                            {{-- ── CTA Button ── --}}
                            <div style="text-align:center;margin-bottom:32px;">
                                <a href="{{ $loginUrl }}"
                                   style="display:inline-block;background:linear-gradient(135deg,#6d28d9,#4c1d95);color:#fff;text-decoration:none;padding:16px 48px;border-radius:14px;font-size:16px;font-weight:800;letter-spacing:.02em;box-shadow:0 6px 24px rgba(109,40,217,.4);">
                                    🚀 &nbsp;Sign In to Your Dashboard
                                </a>
                            </div>

                            <div style="text-align:center;margin-bottom:28px;">
                                <span style="font-size:13px;color:#94a3b8;">Login URL: </span>
                                <a href="{{ $loginUrl }}" style="font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;">{{ $loginUrl }}</a>
                            </div>

                            {{-- ── Quick Start Tips ── --}}
                            <div style="border-top:1px solid #f1f5f9;padding-top:24px;">
                                <p style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">✅ &nbsp;Quick Start Guide</p>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    @foreach([
                                        ['🏠', 'Add your rooms and room types in the Rooms section'],
                                        ['👥', 'Invite your staff by creating user accounts under Users'],
                                        ['📅', 'Start creating bookings and managing check-ins'],
                                        ['💳', 'Set up payments and generate invoices for guests'],
                                    ] as $tip)
                                    <tr>
                                        <td style="padding:7px 0;">
                                            <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#4b5563;line-height:1.5;">
                                                <span style="flex-shrink:0;font-size:15px;">{{ $tip[0] }}</span>
                                                <span>{{ $tip[1] }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>

                        </div>
                    </td>
                </tr>

                {{-- ── Security Notice ── --}}
                <tr>
                    <td style="padding-bottom:16px;">
                        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:14px;padding:16px 20px;">
                            <p style="color:#92400e;font-size:13px;margin:0;line-height:1.6;">
                                🔒 <strong>Security tip:</strong> Please change your password after your first login. Keep your credentials private and do not share them with anyone.
                            </p>
                        </div>
                    </td>
                </tr>

                {{-- ── Footer ── --}}
                <tr>
                    <td style="text-align:center;padding-top:8px;padding-bottom:8px;">
                        <p style="color:#94a3b8;font-size:12px;margin:0 0 4px;">
                            This email was sent by <strong>Dreams Technology</strong> on behalf of Hotel CRM Platform.
                        </p>
                        <p style="color:#94a3b8;font-size:12px;margin:0;">
                            If you have any questions, please contact your platform administrator.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
