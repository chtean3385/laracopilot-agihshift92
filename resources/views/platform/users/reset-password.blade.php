@extends('layouts.platform')

@section('title', 'Reset Password — ' . $user->name)
@section('page-title', 'Reset Password')
@section('page-subtitle')
Set a new password for {{ $user->name }}
@endsection

@section('content')

<div style="max-width:480px;">

    {{-- Back link --}}
    <a href="{{ route('platform.users.show', $user->id) }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6d28d9;font-weight:600;text-decoration:none;margin-bottom:20px;">
        <i class="fas fa-arrow-left"></i> Back to {{ $user->name }}
    </a>

    {{-- User info strip --}}
    <div style="background:linear-gradient(135deg,#1e1b4b,#2d1b69);border-radius:16px;padding:18px 22px;margin-bottom:24px;display:flex;align-items:center;gap:14px;">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span style="color:#fff;font-size:20px;font-weight:800;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
        </div>
        <div>
            <div style="font-size:15px;font-weight:800;color:#fff;">{{ $user->name }}</div>
            <div style="font-size:12px;color:#a78bfa;">{{ $user->email }}</div>
        </div>
        <div style="margin-left:auto;">
            <span style="font-size:11px;font-weight:700;background:rgba(139,92,246,.3);color:#c4b5fd;padding:3px 10px;border-radius:20px;">
                <i class="fas fa-key" style="margin-right:4px;"></i> Password Reset
            </span>
        </div>
    </div>

    {{-- Form --}}
    <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <h2 style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="color:#fff;font-size:12px;"></i>
            </span>
            New Password
        </h2>

        <form method="POST" action="{{ route('platform.users.reset', $user->id) }}">
            @csrf

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">New Password <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password" required minlength="6"
                    style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('password')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                    placeholder="Minimum 6 characters" autocomplete="new-password">
                @if(isset($errors) && $errors->has('password')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('password') }}</p> @endif
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Confirm Password <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password_confirmation" required minlength="6"
                    style="width:100%;padding:10px 14px;border:1.5px solid {{ ($errors && $errors->has('password_confirmation')) ? '#ef4444' : '#e2e8f0' }};border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;outline:none;"
                    placeholder="Re-enter the new password" autocomplete="new-password">
                @if(isset($errors) && $errors->has('password_confirmation')) <p style="color:#ef4444;font-size:11px;margin:4px 0 0;">{{ $errors->first('password_confirmation') }}</p> @endif
            </div>

            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:12px;color:#92400e;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;color:#d97706;"></i>
                The user will need to use this new password the next time they log in. There is no email notification sent.
            </div>

            <div style="display:flex;gap:12px;align-items:center;">
                <button type="submit" class="btn-primary" style="padding:11px 24px;">
                    <i class="fas fa-save"></i> Save New Password
                </button>
                <a href="{{ route('platform.users.show', $user->id) }}" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</div>

@endsection
