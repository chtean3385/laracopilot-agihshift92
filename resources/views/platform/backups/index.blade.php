@extends('layouts.platform')
@section('title','Hotel Backups — Platform Admin')
@section('page-title','Hotel Backups')
@section('page-subtitle','View and restore backups for any hotel')

@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:20px;font-weight:600;font-size:14px;">
    <i class="fas fa-check-circle" style="margin-right:8px;"></i>{{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;">
    <i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>{{ $errors->first() }}
</div>
@endif

{{-- Filter --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f1f5f9;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <form method="GET" action="{{ route('platform.backups.index') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <select name="hotel_id" class="form-input" style="min-width:220px;">
            <option value="">All Hotels</option>
            @foreach($hotels as $h)
            <option value="{{ $h->id }}" {{ request('hotel_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Filter</button>
        @if(request('hotel_id'))
        <a href="{{ route('platform.backups.index') }}" style="color:#64748b;font-size:13px;text-decoration:none;"><i class="fas fa-times"></i> Clear</a>
        @endif
    </form>
    <div style="margin-left:auto;font-size:13px;color:#64748b;">{{ $backups->total() }} backup{{ $backups->total() !== 1 ? 's' : '' }} total</div>
</div>

{{-- Backups table --}}
<div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;overflow:hidden;">
    @if($backups->isEmpty())
    <div style="padding:80px 24px;text-align:center;">
        <i class="fas fa-archive" style="font-size:48px;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
        <p style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 8px;">No backups found</p>
        <p style="color:#94a3b8;margin:0;">Hotels can create backups from their Settings → Backup & Recovery page.</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Hotel</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Backup Label</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Type</th>
                    <th style="text-align:right;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Size</th>
                    <th style="text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 14px;">Created</th>
                    <th style="text-align:center;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;padding:13px 20px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                <tr style="border-bottom:1px solid #f8fafc;">
                    <td style="padding:14px 20px;">
                        <div style="font-weight:700;color:#1e293b;font-size:13px;">{{ $backup->hotel->name ?? 'Unknown Hotel' }}</div>
                        <div style="font-size:11px;color:#94a3b8;">Hotel #{{ $backup->hotel_id }}</div>
                    </td>
                    <td style="padding:14px 14px;font-size:13px;color:#475569;max-width:200px;">
                        {{ $backup->label ?? 'Backup #' . $backup->id }}
                    </td>
                    <td style="padding:14px 14px;">
                        @if($backup->type === 'auto')
                        <span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">Auto</span>
                        @else
                        <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;">Manual</span>
                        @endif
                    </td>
                    <td style="padding:14px 14px;text-align:right;font-size:13px;color:#64748b;">{{ number_format($backup->size_kb) }} KB</td>
                    <td style="padding:14px 14px;font-size:13px;color:#64748b;">{{ $backup->created_at->format('d M Y, h:i A') }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        <form action="{{ route('platform.backups.restore', $backup->id) }}" method="POST"
                              onsubmit="return confirm('WARNING: This will replace all data for hotel \'{{ addslashes($backup->hotel->name ?? 'this hotel') }}\' with this backup snapshot.\n\nThis action cannot be undone. Continue?');">
                            @csrf
                            <button type="submit" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-undo" style="margin-right:4px;"></i>Restore
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
        {{ $backups->links() }}
    </div>
    @endif
</div>

<div style="background:#fff8ed;border:1px solid #fed7aa;border-radius:14px;padding:14px 18px;margin-top:20px;font-size:13px;color:#92400e;">
    <i class="fas fa-exclamation-triangle" style="margin-right:8px;color:#f59e0b;"></i>
    <strong>Restore replaces hotel data.</strong> Rooms, guests, bookings, payments, and invoices will all be replaced by the backup snapshot. Settings and branding will also revert. This action cannot be undone.
</div>

@endsection
