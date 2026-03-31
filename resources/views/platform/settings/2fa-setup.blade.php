@extends('layouts.platform')

@section('page-title', 'Two-Factor Authentication')

@section('page-subtitle')
Secure your platform admin account with TOTP-based two-factor authentication
@endsection

@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#15803d;font-size:14px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

@if(session('info'))
<div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#1d4ed8;font-size:14px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-info-circle"></i> {{ session('info') }}
</div>
@endif

@if(session('warning'))
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#b45309;font-size:14px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
</div>
@endif

<div style="max-width:680px;">

@if($totpEnabled)

{{-- New recovery codes display (shown once after enabling, via flash) --}}
@php $newCodes = session('platform_2fa_new_codes'); @endphp
@if($newCodes)
<div style="background:#fff;border:2px solid #f59e0b;border-radius:20px;padding:28px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-key" style="color:#fff;font-size:16px;"></i>
        </div>
        <div>
            <div style="font-size:16px;font-weight:800;color:#1e293b;">Save Your Recovery Codes</div>
            <div style="font-size:13px;color:#64748b;">These codes will not be shown again. Store them somewhere safe.</div>
        </div>
    </div>
    <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:16px;margin-bottom:14px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            @foreach($newCodes as $code)
            <code style="font-family:monospace;font-size:13px;font-weight:700;color:#92400e;background:#fff;border:1px solid #fde68a;border-radius:6px;padding:6px 12px;text-align:center;">{{ $code }}</code>
            @endforeach
        </div>
    </div>
    <p style="font-size:12px;color:#94a3b8;margin:0;">Each code can only be used once. If you lose access to your authenticator app, enter one of these codes at the 2FA prompt to log in. Then re-enable 2FA to generate fresh codes.</p>
</div>
@endif
<div style="background:#fff;border-radius:20px;padding:32px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <div style="width:48px;height:48px;background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-shield-halved" style="color:#fff;font-size:20px;"></i>
        </div>
        <div>
            <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin:0;">2FA is Active</h2>
            <p style="font-size:13px;color:#64748b;margin:2px 0 0;">Your account is protected with two-factor authentication.</p>
        </div>
        <span style="margin-left:auto;display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:6px 14px;border-radius:20px;background:#dcfce7;color:#15803d;">
            <i class="fas fa-circle" style="font-size:8px;"></i> Enabled
        </span>
    </div>

    <div style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:16px;font-size:13px;color:#475569;display:flex;gap:12px;align-items:flex-start;">
        <i class="fas fa-info-circle" style="color:#0284c7;margin-top:2px;flex-shrink:0;"></i>
        <span>Each time you log in you will be asked to enter the 6-digit code from your authenticator app (Microsoft Authenticator, Google Authenticator, or Authy) after entering your password.</span>
    </div>

    <div style="background:#f8fafc;border-radius:12px;padding:14px 16px;margin-bottom:24px;font-size:13px;color:#475569;display:flex;gap:12px;align-items:center;">
        <i class="fas fa-key" style="color:#7c3aed;flex-shrink:0;"></i>
        @php
            $remainingCodes = \Illuminate\Support\Facades\DB::table('platform_recovery_codes')
                ->where('user_id', session('crm_user_id'))
                ->where('used', false)
                ->count();
        @endphp
        <span>
            <strong>{{ $remainingCodes }}</strong> recovery code{{ $remainingCodes !== 1 ? 's' : '' }} remaining.
            @if($remainingCodes === 0)
            <span style="color:#b91c1c;font-weight:700;">You have no remaining recovery codes!</span>
            Disable and re-enable 2FA to generate new ones.
            @elseif($remainingCodes <= 2)
            <span style="color:#d97706;">Running low.</span> Consider re-enabling 2FA to regenerate codes.
            @endif
        </span>
    </div>

    {{-- Disable 2FA --}}
    <div style="border-top:1px solid #f1f5f9;padding-top:20px;">
        <h3 style="font-size:14px;font-weight:700;color:#1e293b;margin:0 0 10px;">Disable 2FA</h3>
        <p style="font-size:13px;color:#64748b;margin:0 0 14px;">Enter your current authenticator code to confirm you want to remove two-factor authentication from this account.</p>

        @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:13px;color:#b91c1c;">
            <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ $errors->first('one_time_password') }}
        </div>
        @endif

        <form method="POST" action="{{ route('platform.settings.2fa.disable') }}" style="display:flex;gap:10px;align-items:flex-end;">
            @csrf
            <div style="flex:1;">
                <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Authenticator Code</label>
                <input type="text"
                       name="one_time_password"
                       placeholder="000000"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:16px;font-weight:700;letter-spacing:.25em;text-align:center;outline:none;color:#1e293b;"
                       required>
            </div>
            <button type="submit"
                    onclick="return confirm('Are you sure you want to disable 2FA? Your account will be less secure.')"
                    style="padding:10px 20px;background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                <i class="fas fa-lock-open" style="margin-right:6px;"></i>Disable 2FA
            </button>
        </form>
    </div>
</div>

@else
{{-- ── 2FA NOT ENABLED ────────────────────────────────────────────────────── --}}

{{-- Status card --}}
<div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;">
    <i class="fas fa-exclamation-triangle" style="color:#d97706;font-size:18px;flex-shrink:0;margin-top:2px;"></i>
    <div>
        <div style="font-size:15px;font-weight:800;color:#92400e;margin-bottom:4px;">2FA is Not Enabled</div>
        <div style="font-size:13px;color:#a16207;">Enable two-factor authentication below to add an extra layer of security to your platform admin account.</div>
    </div>
    <span style="margin-left:auto;display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:6px 14px;border-radius:20px;background:#fef3c7;color:#b45309;white-space:nowrap;">
        <i class="fas fa-circle" style="font-size:8px;"></i> Disabled
    </span>
</div>

{{-- Setup steps --}}
<div style="background:#fff;border-radius:20px;padding:32px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
    <h2 style="font-size:17px;font-weight:800;color:#1e293b;margin:0 0 6px;">Set Up Two-Factor Authentication</h2>
    <p style="font-size:13px;color:#64748b;margin:0 0 28px;">Follow the steps below to connect your authenticator app.</p>

    @if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#b91c1c;">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ $errors->first('one_time_password') }}
    </div>
    @endif

    {{-- Step 1: Install app --}}
    <div style="display:flex;gap:14px;margin-bottom:24px;">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">1</div>
        <div>
            <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:4px;">Install an Authenticator App</div>
            <div style="font-size:13px;color:#64748b;">Download <strong>Microsoft Authenticator</strong>, <strong>Google Authenticator</strong>, or <strong>Authy</strong> on your phone.</div>
        </div>
    </div>

    {{-- Step 2: Scan QR --}}
    <div style="display:flex;gap:14px;margin-bottom:24px;">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">2</div>
        <div style="flex:1;">
            <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:10px;">Scan the QR Code</div>
            <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;">
                <div style="background:#fff;border:2px solid #e2e8f0;border-radius:12px;padding:10px;display:inline-flex;">
                    {!! $qrCodeSvg !!}
                </div>
                <div style="flex:1;min-width:200px;">
                    <div style="font-size:13px;color:#64748b;margin-bottom:10px;">Can't scan? Enter this secret key manually in your app:</div>
                    <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-family:monospace;font-size:13px;font-weight:700;color:#4c1d95;letter-spacing:.08em;word-break:break-all;">{{ $secretKey }}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:6px;">Keep this secret safe — it is the seed for all future codes.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Confirm --}}
    <div style="display:flex;gap:14px;">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">3</div>
        <div style="flex:1;">
            <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:8px;">Confirm with a Code</div>
            <div style="font-size:13px;color:#64748b;margin-bottom:12px;">Enter the 6-digit code shown in your authenticator app to confirm the setup is working correctly.</div>
            <form method="POST" action="{{ route('platform.settings.2fa.enable') }}" style="display:flex;gap:10px;align-items:flex-end;">
                @csrf
                <div style="flex:1;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">6-Digit Code</label>
                    <input type="text"
                           name="one_time_password"
                           placeholder="000000"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           autocomplete="one-time-code"
                           style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:18px;font-weight:700;letter-spacing:.25em;text-align:center;outline:none;color:#1e293b;"
                           required>
                </div>
                <button type="submit"
                        style="padding:10px 22px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;box-shadow:0 4px 12px rgba(139,92,246,.35);">
                    <i class="fas fa-check" style="margin-right:6px;"></i>Enable 2FA
                </button>
            </form>
        </div>
    </div>
</div>
@endif

</div>

@endsection
