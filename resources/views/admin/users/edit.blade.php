@extends('layouts.admin')

@section('page-title', 'Edit User')
@section('page-subtitle', 'Update staff account details')

@section('content')
<div style="max-width:640px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('users.index') }}" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#f1f5f9;border-radius:10px;color:#64748b;text-decoration:none;">
            <i class="fas fa-arrow-left" style="font-size:14px;"></i>
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Edit: {{ $user->name }}</h2>
            <p style="font-size:13px;color:#64748b;margin:2px 0 0;">Update account details and permissions</p>
        </div>
    </div>

    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
        <form method="POST" action="{{ route('users.update', $user->id) }}">
            @csrf @method('PUT')

            <div style="display:grid;gap:20px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Full Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        style="width:100%;border:1.5px solid {{ $errors->has('name') ? '#ef4444' : '#e2e8f0' }};border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='{{ $errors->has('name') ? '#ef4444' : '#e2e8f0' }}'">
                    @error('name')<p style="color:#ef4444;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Email Address <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        {{ $user->is_super_admin ? 'readonly' : '' }}
                        style="width:100%;border:1.5px solid {{ $errors->has('email') ? '#ef4444' : '#e2e8f0' }};border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;{{ $user->is_super_admin ? 'background:#f8fafc;' : '' }}"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='{{ $errors->has('email') ? '#ef4444' : '#e2e8f0' }}'">
                    @error('email')<p style="color:#ef4444;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Role <span style="color:#ef4444;">*</span></label>
                        <select name="role" {{ $user->is_super_admin ? 'disabled' : '' }} style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;background:#fff;box-sizing:border-box;{{ $user->is_super_admin ? 'background:#f8fafc;' : '' }}">
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role', $user->role) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @if($user->is_super_admin)<input type="hidden" name="role" value="{{ $user->role }}">@endif
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Status</label>
                        <select name="status" {{ $user->is_super_admin ? 'disabled' : '' }} style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="active"   {{ old('status', $user->status) === 'active'   ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @if($user->is_super_admin)<input type="hidden" name="status" value="{{ $user->status }}">@endif
                    </div>
                </div>

                <div style="border-top:1px solid #f1f5f9;padding-top:20px;">
                    <p style="font-size:13px;font-weight:700;color:#374151;margin:0 0 4px;">Change Password</p>
                    <p style="font-size:12px;color:#94a3b8;margin:0 0 16px;">Leave blank to keep the current password.</p>
                    <div style="display:grid;gap:16px;">
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Password</label>
                            <input type="password" name="password" placeholder="Minimum 8 characters"
                                style="width:100%;border:1.5px solid {{ $errors->has('password') ? '#ef4444' : '#e2e8f0' }};border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='{{ $errors->has('password') ? '#ef4444' : '#e2e8f0' }}'">
                            @error('password')<p style="color:#ef4444;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Confirm New Password</label>
                            <input type="password" name="password_confirmation" placeholder="Re-enter new password"
                                style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:14px;color:#0f172a;outline:none;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:28px;">
                <button type="submit" style="flex:1;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(6,182,212,.3);">
                    <i class="fas fa-save" style="margin-right:6px;"></i> Save Changes
                </button>
                <a href="{{ route('users.index') }}" style="padding:12px 24px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-weight:600;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
