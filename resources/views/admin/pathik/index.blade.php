@extends('layouts.admin')
@section('title','Pathik Autofill')
@section('page-title','Pathik Portal Autofill')
@section('page-subtitle','Auto-fill Gujarat Pathik portal with guest data from CRM')

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle" style="color:#16a34a;font-size:16px;"></i>
        <span style="font-size:13px;color:#15803d;font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Status + Token --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-clipboard-list" style="color:#fff;font-size:20px;"></i>
                </div>
                <div>
                    <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;">Pathik Autofill Module</h2>
                    <p style="font-size:12px;color:#64748b;margin:2px 0 0;">Auto-fill Gujarat Pathik tourist registration portal with CRM guest data</p>
                </div>
            </div>
            <span style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:#dcfce7;color:#16a34a;">
                <i class="fas fa-check-circle" style="margin-right:5px;"></i>Module Active
            </span>
        </div>
        <div style="margin-top:20px;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div>
                    <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 4px;">Extension API Token</p>
                    <code style="font-size:14px;font-weight:800;color:#1e293b;font-family:monospace;letter-spacing:.05em;">{{ $masked }}</code>
                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Paste this into the Chrome extension settings</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button id="btnCopyToken" onclick="copyToken()" style="padding:8px 14px;background:#e0f2fe;color:#0369a1;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-copy" style="margin-right:5px;"></i>Copy Token
                    </button>
                    <form action="{{ route('pathik.token.regenerate') }}" method="POST" style="margin:0;" onsubmit="return confirm('Regenerate token? The old token will stop working.')">
                        @csrf
                        <button type="submit" style="padding:8px 14px;background:#fff7ed;color:#c2410c;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-sync-alt" style="margin-right:5px;"></i>Regenerate
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div style="margin-top:12px;padding:14px;background:#fffbeb;border-radius:10px;border:1px solid #fde68a;">
            <p style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px;"><i class="fas fa-link" style="margin-right:5px;color:#d97706;"></i>CRM URL for Extension</p>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <code id="crmUrlDisplay" style="font-size:13px;font-weight:800;color:#78350f;font-family:monospace;background:rgba(0,0,0,.05);padding:5px 10px;border-radius:6px;word-break:break-all;">{{ url('/') }}</code>
                <button onclick="copyCrmUrl()" style="padding:6px 12px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;" id="btnCopyCrmUrl">
                    <i class="fas fa-copy" style="margin-right:4px;"></i>Copy URL
                </button>
            </div>
        </div>
    </div>

    {{-- How It Works --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:20px;"><i class="fas fa-route" style="color:#f97316;margin-right:8px;"></i>How It Works</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            @foreach([
                ['1','fas fa-download','Install Extension','Download the ZIP below, unzip it, then go to Chrome → Extensions → Developer Mode → Load Unpacked','#e0f2fe','#0369a1'],
                ['2','fas fa-plug','Connect to CRM','Open the extension, enter your CRM URL and the API token shown above','#f0fdf4','#16a34a'],
                ['3','fas fa-user-check','Select a Guest','Open any booking in the CRM and click the "Fill Pathik Portal" button','#fef3c7','#d97706'],
                ['4','fas fa-magic','Autofill','Click "Open Pathik Portal" in the popup, then click "Autofill Now" in the extension','#fdf4ff','#9333ea'],
            ] as [$num,$icon,$title,$desc,$bg,$color])
            <div style="background:{{ $bg }};border-radius:14px;padding:18px;position:relative;">
                <div style="width:32px;height:32px;border-radius:50%;background:{{ $color }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;margin-bottom:10px;">{{ $num }}</div>
                <i class="{{ $icon }}" style="color:{{ $color }};font-size:18px;margin-bottom:8px;display:block;"></i>
                <h4 style="font-size:13px;font-weight:800;color:#1e293b;margin:0 0 6px;">{{ $title }}</h4>
                <p style="font-size:12px;color:#475569;margin:0;line-height:1.5;">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Download Extension --}}
    <div style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:16px;padding:28px;color:#fff;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h3 style="font-size:18px;font-weight:800;margin:0 0 6px;">Download Chrome Extension</h3>
                <p style="font-size:13px;color:#94a3b8;margin:0;">Free, open source. Works on Chrome, Edge, Brave, and other Chromium browsers.</p>
            </div>
            <a href="{{ asset('pathik-extension.zip') }}" download style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border-radius:12px;font-weight:800;font-size:14px;text-decoration:none;">
                <i class="fas fa-download"></i> Download Extension ZIP
            </a>
        </div>
        <div style="margin-top:20px;border-top:1px solid rgba(255,255,255,.1);padding-top:16px;">
            <p style="font-size:12px;font-weight:700;color:#94a3b8;margin-bottom:10px;">Manual Install Steps:</p>
            <ol style="margin:0;padding-left:18px;display:grid;gap:6px;">
                @foreach([
                    'Download and unzip <strong>pathik-extension.zip</strong>',
                    'Open Chrome → go to <code style="background:rgba(255,255,255,.1);padding:1px 5px;border-radius:4px;">chrome://extensions</code>',
                    'Enable <strong>Developer mode</strong> toggle (top right)',
                    'Click <strong>Load unpacked</strong> and select the unzipped folder',
                    'The Pathik CRM Autofill extension icon will appear in your toolbar',
                ] as $step)
                <li style="font-size:12px;color:#cbd5e1;">{!! $step !!}</li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- Field Mapping --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:16px;"><i class="fas fa-table" style="color:#0891b2;margin-right:8px;"></i>Field Mapping Reference</h3>
        <p style="font-size:12px;color:#64748b;margin-bottom:14px;">The extension maps these CRM fields to the Pathik portal form fields:</p>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 14px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;">CRM Field</th>
                        <th style="padding:10px 14px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;">Pathik Portal Field</th>
                        <th style="padding:10px 14px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Guest Name','Tourist Name / Full Name','Maps to name fields'],
                        ['Phone','Mobile Number','10-digit Indian mobile'],
                        ['Email','Email Address','Optional on portal'],
                        ['Address','Residential Address','Full address text'],
                        ['City','City','City of residence'],
                        ['State','State','State of residence'],
                        ['Country','Country','Default: India'],
                        ['ID Type','ID Proof Type','Aadhaar / Passport / PAN etc.'],
                        ['ID Number','ID Proof Number','Document number'],
                        ['Date of Birth','Date of Birth','DD/MM/YYYY format'],
                        ['Nationality','Nationality','Default: Indian'],
                        ['Check-In Date','Arrival Date','Booking check-in date'],
                        ['Check-Out Date','Departure Date','Booking check-out date'],
                        ['Adults','Number of Adults','Guest count'],
                        ['Room Number','Room / Accommodation No.','CRM room number'],
                    ] as [$crm,$pathik,$note])
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:9px 14px;font-weight:600;color:#1e293b;">{{ $crm }}</td>
                        <td style="padding:9px 14px;color:#0891b2;font-weight:600;">{{ $pathik }}</td>
                        <td style="padding:9px 14px;color:#64748b;">{{ $note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="font-size:11px;color:#94a3b8;margin-top:12px;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>The extension uses a flexible field-matching strategy: it tries label text, name attribute, placeholder text, and field IDs to find the best match on the portal page.</p>
    </div>

</div>

<input type="hidden" id="fullToken" value="{{ $fullToken }}">
<script>
function doCopy(text, btnId, label) {
    var btn = document.getElementById(btnId);
    var copy = function() {
        if (btn) { btn.innerHTML = '<i class="fas fa-check" style="margin-right:4px;"></i>Copied!'; btn.style.background='#dcfce7'; btn.style.color='#15803d'; btn.style.borderColor='#bbf7d0'; }
        setTimeout(function() { if (btn) { btn.innerHTML = '<i class="fas fa-copy" style="margin-right:4px;"></i>' + label; btn.style.background=''; btn.style.color=''; btn.style.borderColor=''; } }, 2000);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(copy);
    } else {
        var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); copy();
    }
}
function copyToken() { doCopy(document.getElementById('fullToken').value, 'btnCopyToken', 'Copy Token'); }
function copyCrmUrl() { doCopy(document.getElementById('crmUrlDisplay').textContent.trim(), 'btnCopyCrmUrl', 'Copy URL'); }
</script>
@endsection
