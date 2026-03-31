@extends('layouts.platform')

@section('title', 'Users — Platform Admin')
@section('page-title', 'User Management')
@section('page-subtitle', 'All staff users across every hotel tenant')

@section('content')

{{-- ── Filters ─────────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('platform.users.index') }}" style="background:#fff;border-radius:16px;padding:18px 22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;margin-bottom:20px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">

    <div style="flex:1;min-width:180px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Hotel</label>
        <select name="hotel_id" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;outline:none;background:#fff;">
            <option value="">All Hotels</option>
            @foreach($hotels as $hotel)
            <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
            @endforeach
        </select>
    </div>

    <div style="min-width:140px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Role</label>
        <select name="role" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;outline:none;background:#fff;">
            <option value="">All Roles</option>
            @foreach($roles as $r)
            <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>

    <div style="min-width:130px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Status</label>
        <select name="status" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#1e293b;outline:none;background:#fff;">
            <option value="">All Statuses</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-primary" style="padding:9px 18px;font-size:13px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        @if(request()->hasAny(['hotel_id','role','status']))
        <a href="{{ route('platform.users.index') }}" class="btn-secondary" style="padding:9px 14px;font-size:13px;">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </div>

    <div style="margin-left:auto;font-size:12px;color:#94a3b8;white-space:nowrap;padding-top:18px;">
        {{ $assignments->total() }} assignment{{ $assignments->total() !== 1 ? 's' : '' }}
    </div>

</form>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">

    @if($assignments->isEmpty())
    <div style="padding:80px 24px;text-align:center;">
        <i class="fas fa-users" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
        <p style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 8px;">No users found</p>
        <p style="color:#94a3b8;margin:0;">
            @if(request()->hasAny(['hotel_id','role','status']))
                Try adjusting your filters, or <a href="{{ route('platform.users.index') }}" style="color:#6d28d9;font-weight:600;">clear all filters</a>.
            @else
                No staff users are assigned to any hotel yet.
            @endif
        </p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">User</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Hotel</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Role</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Status</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Joined</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignments as $row)
                @php
                    $isActive = $row->status === 'active';
                    $sBg = $isActive ? '#dcfce7' : '#fee2e2';
                    $sTx = $isActive ? '#15803d' : '#b91c1c';
                    $roleColors = ['Admin' => ['#ede9fe','#6d28d9'], 'Manager' => ['#cffafe','#0e7490'], 'Receptionist' => ['#fef9c3','#854d0e']];
                    $rc = $roleColors[$row->role] ?? ['#f1f5f9','#475569'];
                @endphp
                <tr style="border-bottom:1px solid #f8fafc;cursor:pointer;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background='transparent'" onclick="window.location='{{ route('platform.users.show', $row->user_id) }}'" title="View {{ addslashes($row->name) }}'s profile">

                    {{-- User --}}
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#4338ca);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:14px;font-weight:800;">{{ strtoupper(substr($row->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ $row->name }}</div>
                                <div style="font-size:11px;color:#94a3b8;">{{ $row->email }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Hotel --}}
                    <td style="padding:14px;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:24px;height:24px;background:linear-gradient(135deg,#8b5cf6,#4c1d95);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:10px;font-weight:800;">{{ strtoupper(substr($row->hotel_name, 0, 1)) }}</span>
                            </div>
                            <span style="font-size:13px;font-weight:600;color:#1e293b;">{{ $row->hotel_name }}</span>
                        </div>
                        @if($row->hotel_status === 'suspended')
                        <span style="font-size:9px;font-weight:700;background:#fee2e2;color:#b91c1c;padding:1px 6px;border-radius:10px;margin-top:3px;display:inline-block;">Hotel Suspended</span>
                        @endif
                    </td>

                    {{-- Role --}}
                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $rc[0] }};color:{{ $rc[1] }};">
                            {{ $row->role }}
                        </span>
                        @if($row->is_hotel_admin)
                        <span style="display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:700;color:#b45309;margin-top:3px;"><i class="fas fa-star" style="font-size:8px;"></i> Admin</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td style="padding:14px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $sBg }};color:{{ $sTx }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $sTx }};display:inline-block;"></span>
                            {{ ucfirst($row->status) }}
                        </span>
                    </td>

                    {{-- Joined --}}
                    <td style="padding:14px;">
                        <span style="font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($row->joined_at)->format('d M Y') }}</span>
                    </td>

                    {{-- Actions --}}
                    <td style="padding:14px 20px;text-align:center;" onclick="event.stopPropagation()">
                        <div style="display:flex;align-items:center;justify-content:center;gap:5px;">

                            <a href="{{ route('platform.users.show', $row->user_id) }}"
                               style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#f1f5f9;color:#475569;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;">
                                <i class="fas fa-user"></i> Profile
                            </a>

                            @if($isActive)
                            <form method="POST" action="{{ route('platform.users.suspend', [$row->user_id, $row->hotel_id]) }}" style="margin:0;" onsubmit="return confirm('Suspend {{ addslashes($row->name) }}\'s access to {{ addslashes($row->hotel_name) }}?')">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#fee2e2;color:#b91c1c;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-ban"></i> Suspend
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('platform.users.activate', [$row->user_id, $row->hotel_id]) }}" style="margin:0;">
                                @csrf
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#dcfce7;color:#15803d;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-check-circle"></i> Activate
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($assignments->hasPages())
    <div style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:12px;color:#94a3b8;">
            Showing {{ $assignments->firstItem() }}–{{ $assignments->lastItem() }} of {{ $assignments->total() }}
        </span>
        <div style="display:flex;gap:6px;">
            @if($assignments->onFirstPage())
            <span style="padding:6px 12px;background:#f1f5f9;color:#94a3b8;border-radius:8px;font-size:12px;font-weight:600;">← Prev</span>
            @else
            <a href="{{ $assignments->previousPageUrl() }}" style="padding:6px 12px;background:#f1f5f9;color:#475569;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">← Prev</a>
            @endif

            @if($assignments->hasMorePages())
            <a href="{{ $assignments->nextPageUrl() }}" style="padding:6px 12px;background:#7c3aed;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">Next →</a>
            @else
            <span style="padding:6px 12px;background:#f1f5f9;color:#94a3b8;border-radius:8px;font-size:12px;font-weight:600;">Next →</span>
            @endif
        </div>
    </div>
    @endif

    @endif

</div>

@endsection
