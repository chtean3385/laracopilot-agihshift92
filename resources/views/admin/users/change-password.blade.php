@extends('layouts.admin')

@section('page-title', 'Change Password')
@section('page-subtitle', 'Update your login password')

@section('content')
<div style="max-width:480px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#f1f5f9;border-radius:10px;color:#64748b;text-decoration:none;">
            <i class="fas fa-arrow-left" style="font-size:14px;"></i>
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Change Password</h2>
            <p style="font-size:13px;color:#64748b;margin:2px 0 0;">Logged in as {{ session('crm_user_email') }}</p>
        </div>
    </div>

    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
        <form method="POST" action="{{ route('password.change') }}">
            @csrf
            <div style="display:grid;gap:20px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Current Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="current_password" required
                        style="width:100%;border:1.5px solid {{ $errors->has('current_password') ? '#ef4444' : '#e2e8f0' }};border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='{{ $errors->has('current_password') ? '#ef4444' : '#e2e8f0' }}'">
                    @error('current_password')<p style="color:#ef4444;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="password" placeholder="Minimum 8 characters" required
                        style="width:100%;border:1.5px solid {{ $errors->has('password') ? '#ef4444' : '#e2e8f0' }};border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='{{ $errors->has('password') ? '#ef4444' : '#e2e8f0' }}'">
                    @error('password')<p style="color:#ef4444;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Confirm New Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="password_confirmation" placeholder="Re-enter new password" required
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>

            <button type="submit" style="width:100%;margin-top:28px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(6,182,212,.3);">
                <i class="fas fa-lock" style="margin-right:6px;"></i> Update Password
            </button>
        </form>
    </div>
</div>
@endsection
