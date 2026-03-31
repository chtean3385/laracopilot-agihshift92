@extends('layouts.admin')

@section('page-title', 'User Management')
@section('page-subtitle', 'Create and manage staff accounts')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Staff Users</h2>
        <p style="font-size:13px;color:#64748b;margin:4px 0 0;">{{ $users->count() }} user{{ $users->count() !== 1 ? 's' : '' }} in the system</p>
    </div>
    @canDo('users.create')
    <a href="{{ route('users.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;box-shadow:0 4px 12px rgba(6,182,212,.3);">
        <i class="fas fa-user-plus"></i> Add User
    </a>
    @endCanDo
</div>

<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <th style="padding:14px 20px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">User</th>
                <th style="padding:14px 20px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Email</th>
                <th style="padding:14px 20px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Role</th>
                <th style="padding:14px 20px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                <th style="padding:14px 20px;text-align:left;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Joined</th>
                <th style="padding:14px 20px;text-align:right;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            @php
                $roleColors = ['Super Admin'=>'#7c3aed','Admin'=>'#dc2626','Manager'=>'#2563eb','Receptionist'=>'#16a34a'];
                $roleBg     = $roleColors[$user->role] ?? '#475569';
                $avatarBg   = $user->is_super_admin ? '#7c3aed' : ($roleColors[$user->role] ?? '#06b6d4');
                $isSelf     = $user->id === session('crm_user_id');
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                <td style="padding:14px 20px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:50%;background:{{ $avatarBg }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0;">
                            {{ $user->avatar }}
                        </div>
                        <div>
                            <div style="font-weight:700;color:#0f172a;font-size:14px;">
                                {{ $user->name }}
                                @if($isSelf)<span style="font-size:11px;color:#06b6d4;font-weight:600;margin-left:6px;">(You)</span>@endif
                                @if($user->is_super_admin)<span style="font-size:11px;background:#f3e8ff;color:#7c3aed;padding:2px 8px;border-radius:20px;font-weight:600;margin-left:6px;">Super Admin</span>@endif
                            </div>
                        </div>
                    </div>
                </td>
                <td style="padding:14px 20px;font-size:13px;color:#475569;">{{ $user->email }}</td>
                <td style="padding:14px 20px;">
                    <span style="background:{{ $roleBg }}1a;color:{{ $roleBg }};padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">{{ $user->role }}</span>
                </td>
                <td style="padding:14px 20px;">
                    @if($user->status === 'active')
                        <span style="background:#f0fdf4;color:#16a34a;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;"><span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;"></span> Active</span>
                    @else
                        <span style="background:#fef2f2;color:#dc2626;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;"><span style="width:6px;height:6px;border-radius:50%;background:#dc2626;display:inline-block;"></span> Inactive</span>
                    @endif
                </td>
                <td style="padding:14px 20px;font-size:12px;color:#94a3b8;">{{ $user->created_at->format('d M Y') }}</td>
                <td style="padding:14px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                        @canDo('users.edit')
                        <a href="{{ route('users.edit', $user->id) }}" style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endCanDo
                        @canDo('users.delete')
                        @if(!$user->is_super_admin && !$isSelf)
                        <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Delete user {{ addslashes($user->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" style="display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        @endif
                        @endCanDo
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:48px;text-align:center;color:#94a3b8;">
                    <i class="fas fa-users" style="font-size:40px;margin-bottom:12px;display:block;opacity:.3;"></i>
                    No users found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div style="margin-top:20px;display:flex;justify-content:center;">
    {{ $users->links() }}
</div>
@endif
@endsection
