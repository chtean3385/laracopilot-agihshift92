@extends('layouts.admin')
@section('title', 'OTA Booking Conflicts')
@section('page-title', 'OTA Booking Conflicts')
@section('page-subtitle', 'Bookings that need a room assigned manually.')

@section('content')
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    @if($items->isEmpty())
    <div style="text-align:center;padding:50px 20px;color:#94a3b8;">
        <i class="fas fa-circle-check" style="font-size:42px;display:block;margin-bottom:10px;color:#10b981;"></i>
        <div style="font-weight:700;color:#1e293b;">No unresolved conflicts. Great work!</div>
    </div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;text-align:left;">
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Guest</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Source</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Dates</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Requested Room</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Reason</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $c)
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px;font-weight:700;color:#1e293b;">{{ $c->booking?->customer?->name ?? 'Unknown' }}<div style="font-size:11px;color:#64748b;font-weight:500;">{{ $c->booking?->booking_number }}</div></td>
                <td style="padding:10px;color:#475569;">{{ $c->booking?->ota_name ?? $c->booking?->source ?? '—' }}</td>
                <td style="padding:10px;color:#475569;">{{ $c->check_in_date?->format('d M') }} → {{ $c->check_out_date?->format('d M Y') }}</td>
                <td style="padding:10px;color:#475569;">{{ $c->requested_room_type ?? '—' }}</td>
                <td style="padding:10px;"><span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">{{ $c->reason_label }}</span></td>
                <td style="padding:10px;display:flex;gap:6px;">
                    @if($c->booking_id)
                    <a href="{{ route('bookings.show', $c->booking_id) }}" style="background:#f8fafc;border:1.5px solid #e2e8f0;color:#475569;padding:7px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-pen"></i> Open Booking</a>
                    @endif
                    <form action="{{ route('email-parser.conflicts.resolve', $c->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" style="background:#dcfce7;border:none;color:#15803d;padding:7px 12px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-check"></i> Mark Resolved</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <div style="margin-top:14px;">{{ $items->links() }}</div>
    @endif
</div>
@endsection
