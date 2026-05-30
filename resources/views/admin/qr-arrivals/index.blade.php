@extends('layouts.admin')
@section('title','QR Arrivals')
@section('page-title','QR Arrivals')
@section('page-subtitle','Guests who checked in via QR scan')

@section('content')
<div class="space-y-5">

    {{-- Toolbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or phone…"
                style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;min-width:200px;">
            <select name="status" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
                <option value="">All Statuses</option>
                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" style="padding:9px 18px;background:#6366f1;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Filter</button>
            @if(request('search') || request('status'))
            <a href="{{ route('qr-arrivals.index') }}" style="padding:9px 14px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">Clear</a>
            @endif
        </form>
        <a href="{{ route('qr-arrivals.print-qr') }}" target="_blank"
            style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <i class="fas fa-qrcode"></i> Print Hotel Check-In QR
        </a>
    </div>

    @if(session('success'))
    <div style="background:#ecfdf5;border:1.5px solid #86efac;border-radius:12px;padding:12px 16px;color:#166534;font-weight:600;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle" style="color:#16a34a;"></i> {{ session('success') }}
    </div>
    @endif

    @if($pendingCount > 0)
    <div style="background:#fffbeb;border:1.5px solid #fbbf24;border-radius:12px;padding:12px 16px;color:#92400e;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-clock" style="color:#f59e0b;"></i>
        {{ $pendingCount }} pending request{{ $pendingCount != 1 ? 's' : '' }} waiting for room assignment.
    </div>
    @endif

    {{-- Table --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">#</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Guest</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Phone</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Check-In</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Guests</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Status</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Received</th>
                        <th style="padding:12px 16px;text-align:left;font-weight:700;color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr style="border-top:1px solid #f1f5f9;{{ $req->status === 'pending' ? 'background:#fafaf8;' : '' }}">
                        <td style="padding:12px 16px;color:#94a3b8;font-size:12px;">{{ $req->id }}</td>
                        <td style="padding:12px 16px;">
                            <div style="font-weight:700;color:#1e293b;">{{ $req->name }}</div>
                            @if($req->email)
                            <div style="font-size:12px;color:#94a3b8;">{{ $req->email }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-weight:600;color:#1e293b;">{{ $req->phone }}</td>
                        <td style="padding:12px 16px;">
                            @if($req->requested_check_in)
                            <div style="font-weight:600;color:#1e293b;">{{ $req->requested_check_in->format('d M Y') }}</div>
                            @if($req->requested_check_out)
                            <div style="font-size:12px;color:#94a3b8;">→ {{ $req->requested_check_out->format('d M Y') }}</div>
                            @endif
                            @else
                            <span style="color:#94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-weight:600;color:#1e293b;">{{ $req->guests_count }}</td>
                        <td style="padding:12px 16px;">
                            @if($req->status === 'pending')
                            <span style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Pending</span>
                            @elseif($req->status === 'converted')
                            <span style="background:#ecfdf5;color:#166534;border:1px solid #86efac;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Converted</span>
                            @else
                            <span style="background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">Cancelled</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-size:12px;color:#94a3b8;">{{ $req->created_at->diffForHumans() }}</td>
                        <td style="padding:12px 16px;">
                            <div style="display:flex;gap:6px;">
                                <a href="{{ route('qr-arrivals.show', $req->id) }}"
                                    style="padding:6px 12px;background:#f0f9ff;color:#0284c7;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;">
                                    <i class="fas fa-eye" style="margin-right:4px;"></i>View
                                </a>
                                @if($req->status === 'pending')
                                <a href="{{ route('qr-arrivals.show', $req->id) }}"
                                    style="padding:6px 12px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;">
                                    <i class="fas fa-door-open" style="margin-right:4px;"></i>Assign Room
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:48px;text-align:center;color:#94a3b8;">
                            <i class="fas fa-qrcode" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                            No QR check-in requests yet.<br>
                            <span style="font-size:12px;">Guests can scan the hotel QR code to submit their details.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div style="padding:12px 16px;border-top:1px solid #f1f5f9;">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
