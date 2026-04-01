@extends('layouts.admin')
@section('title','Guest Register')
@section('page-title','Guest Register')
@section('page-subtitle','Police / Government register — all guests by booking date')

@section('content')
<div class="space-y-6">

{{-- Filters --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:20px;">
    <form method="GET" action="{{ route('reports.guest_register') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px;">From</label>
            <input type="date" name="date_from" value="{{ $from->toDateString() }}" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px;">To</label>
            <input type="date" name="date_to" value="{{ $to->toDateString() }}" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <div style="flex:1;min-width:200px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px;">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Name, ID number, booking#..." style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;">
        </div>
        <button type="submit" style="padding:8px 18px;background:#0891b2;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-search" style="margin-right:5px;"></i>Search
        </button>
        <a href="{{ route('reports.guest_register', array_merge(request()->only('date_from','date_to','search'), ['export'=>'csv'])) }}"
           style="padding:8px 18px;background:#16a34a;color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="fas fa-download"></i>Download CSV
        </a>
    </form>
</div>

{{-- Stats --}}
@php
    $totalBookings = $bookings->count();
    $totalPrimary  = $totalBookings;
    $totalAdditional = $bookings->sum(fn($b) => $b->bookingGuests->count());
    $totalGuests   = $totalPrimary + $totalAdditional;
    $totalSigned   = $bookings->sum(fn($b) => $b->bookingGuests->filter(fn($g) => $g->signature)->count());
    $totalWithId   = $bookings->sum(fn($b) => $b->bookingGuests->filter(fn($g) => $g->id_document_path)->count());
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;">
    @foreach([
        ['Bookings','fas fa-calendar-check',$totalBookings,'#0891b2','#e0f2fe'],
        ['Total Guests','fas fa-users',$totalGuests,'#7c3aed','#ede9fe'],
        ['Additional Guests','fas fa-user-plus',$totalAdditional,'#d97706','#fef3c7'],
        ['Signatures Collected','fas fa-signature',$totalSigned,'#16a34a','#dcfce7'],
        ['ID Docs Uploaded','fas fa-id-card',$totalWithId,'#dc2626','#fee2e2'],
    ] as [$label,$icon,$val,$color,$bg])
    <div style="background:#fff;border-radius:14px;padding:16px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:34px;height:34px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;">
                <i class="{{ $icon }}" style="color:{{ $color }};font-size:14px;"></i>
            </div>
            <span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">{{ $label }}</span>
        </div>
        <div style="font-size:26px;font-weight:800;color:#1e293b;">{{ $val }}</div>
    </div>
    @endforeach
</div>

{{-- Register Table --}}
@if($bookings->isEmpty())
<div style="background:#fff;border-radius:16px;padding:48px;text-align:center;border:1px solid #f1f5f9;">
    <i class="fas fa-id-card" style="font-size:36px;color:#cbd5e1;margin-bottom:12px;"></i>
    <p style="color:#94a3b8;font-weight:600;">No bookings found for the selected period.</p>
</div>
@else
@foreach($bookings as $booking)
<div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
    {{-- Booking Header --}}
    <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:12px;font-weight:800;color:#0891b2;background:#e0f2fe;padding:4px 10px;border-radius:20px;">{{ $booking->booking_number }}</span>
            <span style="font-size:13px;font-weight:700;color:#1e293b;"><i class="fas fa-door-open" style="color:#64748b;margin-right:5px;"></i>Room {{ $booking->room->room_number ?? 'N/A' }}</span>
            <span style="font-size:12px;color:#64748b;"><i class="fas fa-calendar" style="margin-right:4px;"></i>{{ $booking->check_in_date?->format('d M Y') }} → {{ $booking->check_out_date?->format('d M Y') }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            @php $totalG = 1 + $booking->bookingGuests->count(); @endphp
            <span style="font-size:12px;font-weight:700;color:#7c3aed;background:#ede9fe;padding:3px 10px;border-radius:20px;">{{ $totalG }} {{ Str::plural('guest',$totalG) }}</span>
            <a href="{{ route('bookings.show', $booking->id) }}" style="font-size:11px;color:#0891b2;text-decoration:none;font-weight:700;">
                <i class="fas fa-external-link-alt" style="margin-right:3px;"></i>View Booking
            </a>
        </div>
    </div>
    {{-- Guests Table --}}
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">Name</th>
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">Relation</th>
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">Age/Gender</th>
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">Nationality</th>
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">ID Type</th>
                    <th style="padding:8px 14px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">ID Number</th>
                    <th style="padding:8px 14px;text-align:center;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">Sign</th>
                    <th style="padding:8px 14px;text-align:center;font-weight:700;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.05em;">ID Doc</th>
                </tr>
            </thead>
            <tbody>
                {{-- Primary Guest --}}
                <tr style="border-bottom:1px solid #f1f5f9;background:#fffbeb;">
                    <td style="padding:10px 14px;font-weight:700;color:#1e293b;">
                        {{ $booking->customer->name ?? '-' }}
                        <span style="display:inline-block;margin-left:6px;padding:1px 7px;background:#fde68a;color:#92400e;border-radius:20px;font-size:9px;font-weight:800;">PRIMARY</span>
                    </td>
                    <td style="padding:10px 14px;color:#64748b;">Self</td>
                    <td style="padding:10px 14px;color:#64748b;">
                        {{ $booking->customer->age ? $booking->customer->age . ' yrs' : '-' }}
                    </td>
                    <td style="padding:10px 14px;color:#64748b;">{{ $booking->customer->nationality ?? 'Indian' }}</td>
                    <td style="padding:10px 14px;color:#64748b;">{{ ucfirst($booking->customer->id_type ?? '-') }}</td>
                    <td style="padding:10px 14px;font-family:monospace;color:#1e293b;font-weight:600;">{{ $booking->customer->id_number ?? '-' }}</td>
                    <td style="padding:10px 14px;text-align:center;">
                        @if($booking->customer?->signature)
                            <i class="fas fa-check-circle" style="color:#16a34a;" title="Signature collected"></i>
                        @else
                            <i class="fas fa-times-circle" style="color:#e2e8f0;"></i>
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        @if($booking->customer && $booking->customer->documents->isNotEmpty())
                            <a href="{{ route('documents.index', $booking->customer->id) }}" style="color:#0891b2;" title="View uploaded documents">
                                <i class="fas fa-file-download"></i>
                            </a>
                        @else
                            <i class="fas fa-times-circle" style="color:#e2e8f0;"></i>
                        @endif
                    </td>
                </tr>
                {{-- Additional Guests --}}
                @foreach($booking->bookingGuests as $guest)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 14px;font-weight:600;color:#1e293b;">{{ $guest->name }}</td>
                    <td style="padding:10px 14px;color:#64748b;">{{ $guest->relation ?? '-' }}</td>
                    <td style="padding:10px 14px;color:#64748b;">
                        {{ $guest->age ? $guest->age . ' yrs' : '-' }}
                        {{ $guest->gender ? ' · ' . ucfirst($guest->gender) : '' }}
                    </td>
                    <td style="padding:10px 14px;color:#64748b;">{{ $guest->nationality ?? 'Indian' }}</td>
                    <td style="padding:10px 14px;color:#64748b;">{{ \App\Models\BookingGuest::idTypes()[$guest->id_type] ?? ($guest->id_type ?? '-') }}</td>
                    <td style="padding:10px 14px;font-family:monospace;color:#1e293b;font-weight:600;">{{ $guest->id_number ?? '-' }}</td>
                    <td style="padding:10px 14px;text-align:center;">
                        @if($guest->signature)
                            <i class="fas fa-check-circle" style="color:#16a34a;" title="Signature collected"></i>
                        @else
                            <i class="fas fa-times-circle" style="color:#e2e8f0;"></i>
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        @if($guest->id_document_path)
                            <a href="{{ route('booking.guests.document.download', [$booking->id, $guest->id]) }}" style="color:#0891b2;" title="{{ $guest->id_document_name }}">
                                <i class="fas fa-file-download"></i>
                            </a>
                        @else
                            <i class="fas fa-times-circle" style="color:#e2e8f0;"></i>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

</div>
@endsection
