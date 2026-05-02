<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Pricing Enquiry</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e1b4b,#312e81);padding:32px 40px;text-align:center;">
                        <img src="{{ url('/hotel-crm-logo.png') }}" alt="Resort CRM" width="70" style="display:block;margin:0 auto 16px;border-radius:12px;">
                        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-.02em;">New Pricing Enquiry</h1>
                        <p style="margin:8px 0 0;color:rgba(255,255,255,.55);font-size:13px;">Received on {{ $submittedAt }}</p>
                    </td>
                </tr>

                {{-- Plan badge --}}
                <tr>
                    <td style="padding:0 40px;">
                        <div style="margin-top:-1px;background:#4f46e5;padding:14px 24px;text-align:center;">
                            <span style="color:#fff;font-size:13px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;">
                                {{ $planLabel }} Plan &nbsp;·&nbsp; ₹{{ number_format($planPrice) }}/year
                            </span>
                        </div>
                    </td>
                </tr>

                {{-- Details --}}
                <tr>
                    <td style="padding:32px 40px 0;">
                        <h2 style="margin:0 0 20px;font-size:15px;font-weight:800;color:#1e1b4b;text-transform:uppercase;letter-spacing:.06em;">Enquiry Details</h2>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;width:35%;color:#64748b;font-size:13px;font-weight:600;">Contact Name</td>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:700;">{{ $enquiryName }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;font-weight:600;">Hotel / Resort</td>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:700;">{{ $hotelName }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;font-weight:600;">WhatsApp / Phone</td>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:700;">
                                    <a href="https://wa.me/91{{ preg_replace('/[^0-9]/','',$phone) }}" style="color:#4f46e5;text-decoration:none;">{{ $phone }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;font-weight:600;">Plan Selected</td>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:700;">
                                    <span style="background:#ede9fe;color:#4f46e5;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:800;">{{ $planLabel }}</span>
                                    &nbsp; ₹{{ number_format($planPrice) }}/yr
                                </td>
                            </tr>
                            @if($rooms)
                            <tr>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;font-weight:600;">No. of Rooms</td>
                                <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:700;">{{ $rooms }}</td>
                            </tr>
                            @endif
                            @if($message)
                            <tr>
                                <td style="padding:10px 0;color:#64748b;font-size:13px;font-weight:600;vertical-align:top;">Message</td>
                                <td style="padding:10px 0;color:#0f172a;font-size:14px;">{{ $message }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>

                {{-- CTA --}}
                <tr>
                    <td style="padding:28px 40px 36px;">
                        <p style="margin:0 0 16px;font-size:13px;color:#64748b;">Reply quickly to convert this enquiry:</p>
                        <a href="https://wa.me/91{{ preg_replace('/[^0-9]/','',$phone) }}?text={{ urlencode('Hello ' . $enquiryName . '! Thank you for your interest in our ' . $planLabel . ' plan. Let me help you get started with Resort CRM!') }}"
                           style="display:inline-block;background:#25d366;color:#fff;text-decoration:none;padding:13px 28px;border-radius:10px;font-size:14px;font-weight:800;margin-right:8px;">
                            💬 Reply on WhatsApp
                        </a>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;">
                        <p style="margin:0;font-size:11px;color:#94a3b8;">This enquiry was submitted via <strong>resort.dreamstechnology.in/pricing</strong><br>Dreams Technology CRM &nbsp;·&nbsp; +91 97252 25519</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
