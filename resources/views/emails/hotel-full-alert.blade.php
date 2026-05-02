<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Full Alert</title>
</head>
<body style="margin:0;padding:0;background:#fff5f5;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff5f5;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                {{-- Header --}}
                <tr>
                    <td style="padding-bottom:24px;">
                        <div style="background:linear-gradient(135deg,#7f1d1d 0%,#991b1b 50%,#b91c1c 100%);border-radius:24px;padding:36px 40px;text-align:center;">
                            <div style="width:72px;height:72px;background:rgba(255,255,255,.15);border-radius:20px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                                <span style="font-size:36px;">🏨</span>
                            </div>
                            <h1 style="color:#fff;font-size:26px;font-weight:800;margin:0 0 6px;letter-spacing:-.5px;">Hotel CRM</h1>
                            <p style="color:#fca5a5;font-size:14px;margin:0;">by Dreams Technology</p>
                        </div>
                    </td>
                </tr>

                {{-- Alert Banner --}}
                <tr>
                    <td style="padding-bottom:16px;">
                        <div style="background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 4px 24px rgba(220,38,38,.1);border:1px solid #fecaca;">

                            <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:16px;padding:22px 28px;margin-bottom:28px;text-align:center;">
                                <div style="font-size:40px;margin-bottom:10px;">🚨</div>
                                <div style="font-size:20px;font-weight:900;color:#fff;margin-bottom:4px;letter-spacing:-.3px;">HOTEL FULLY BOOKED</div>
                                <div style="font-size:14px;color:rgba(255,255,255,.85);">All rooms are occupied for today</div>
                            </div>

                            <p style="color:#374151;font-size:16px;margin:0 0 8px;">Dear <strong>{{ $hotelName }}</strong> Admin,</p>
                            <p style="color:#6b7280;font-size:15px;line-height:1.7;margin:0 0 28px;">
                                This is an automated alert to inform you that <strong style="color:#991b1b;">{{ $hotelName }}</strong> has reached <strong style="color:#dc2626;">100% occupancy</strong> for <strong>{{ $date }}</strong>.
                                All available rooms are currently occupied.
                            </p>

                            {{-- Occupancy Stats --}}
                            <div style="background:#fef2f2;border-radius:16px;padding:24px 28px;margin-bottom:28px;border:1px solid #fecaca;">
                                <div style="font-size:13px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:18px;">Occupancy Summary</div>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:14px;">
                                            <div style="font-size:12px;color:#b91c1c;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Date</div>
                                            <div style="font-size:16px;font-weight:800;color:#1f2937;">{{ $date }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:14px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="50%">
                                                        <div style="font-size:12px;color:#b91c1c;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Total Rooms</div>
                                                        <div style="font-size:28px;font-weight:900;color:#1f2937;">{{ $totalRooms }}</div>
                                                    </td>
                                                    <td width="50%">
                                                        <div style="font-size:12px;color:#b91c1c;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Occupied</div>
                                                        <div style="font-size:28px;font-weight:900;color:#dc2626;">{{ $occupiedRooms }}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="background:#dc2626;border-radius:8px;padding:10px 16px;text-align:center;">
                                                <div style="font-size:15px;font-weight:800;color:#fff;">100% Occupancy Rate</div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            {{-- Actions --}}
                            <div style="margin-bottom:28px;">
                                <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Recommended Actions</div>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding-bottom:10px;">
                                            <div style="display:flex;align-items:flex-start;gap:10px;background:#f9fafb;border-radius:10px;padding:12px 16px;">
                                                <span style="font-size:16px;flex-shrink:0;">✅</span>
                                                <span style="font-size:14px;color:#374151;line-height:1.5;">Review today's check-outs and prepare rooms for upcoming arrivals.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:10px;">
                                            <div style="background:#f9fafb;border-radius:10px;padding:12px 16px;">
                                                <span style="font-size:16px;">🔔</span>
                                                <span style="font-size:14px;color:#374151;line-height:1.5;"> Update your online booking widget / OTA channels if needed.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="background:#f9fafb;border-radius:10px;padding:12px 16px;">
                                                <span style="font-size:16px;">📊</span>
                                                <span style="font-size:14px;color:#374151;line-height:1.5;"> Log into the dashboard to view a detailed room and booking status.</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            {{-- CTA Button --}}
                            <div style="text-align:center;margin-bottom:20px;">
                                <a href="{{ $dashboardUrl }}"
                                   style="display:inline-block;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;font-size:15px;font-weight:700;padding:14px 36px;border-radius:12px;text-decoration:none;letter-spacing:-.2px;box-shadow:0 4px 14px rgba(220,38,38,.35);">
                                    View Dashboard →
                                </a>
                            </div>

                            <p style="color:#9ca3af;font-size:12px;text-align:center;margin:0;">
                                This alert is sent once per day automatically when the hotel reaches full occupancy.<br>
                                © {{ date('Y') }} Dreams Technology · Hotel CRM
                            </p>
                        </div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
