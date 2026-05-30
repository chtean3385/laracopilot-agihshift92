<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Guest Register {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</title>
<style>
    @page { margin: 18mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { border-bottom: 2px solid #0891b2; padding-bottom: 8px; margin-bottom: 12px; }
    .hotel { font-size: 14pt; font-weight: bold; color: #0891b2; }
    .sub { font-size: 9pt; color: #64748b; }
    .meta { margin-top: 4px; font-size: 9pt; }
    .booking { border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 8px; page-break-inside: avoid; }
    .bk-head { background: #f1f5f9; padding: 5px 8px; font-size: 9pt; }
    .bk-no { font-weight: bold; color: #0891b2; }
    table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f8fafc; color: #475569; font-size: 8pt; text-transform: uppercase; }
    .primary { background: #fffbeb; }
    .badge { display: inline-block; padding: 1px 6px; background: #fde68a; color: #92400e; border-radius: 8px; font-size: 7pt; font-weight: bold; margin-left: 4px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; padding-top: 4px; border-top: 1px solid #e2e8f0; }
    .signbox { display: inline-block; border-bottom: 1px solid #94a3b8; min-width: 100px; height: 20px; }
</style>
</head>
<body>
<div class="header">
    <div class="hotel">{{ $hotel->name ?? 'Hotel' }}</div>
    <div class="sub">Guest Register (Police / Government Format)</div>
    <div class="meta">
        <strong>Period:</strong> {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} &nbsp;|&nbsp;
        <strong>Bookings:</strong> {{ $bookings->count() }} &nbsp;|&nbsp;
        <strong>Total guests:</strong> {{ $bookings->count() + $bookings->sum(fn($b) => $b->bookingGuests->count()) }}
        @if($search)&nbsp;|&nbsp;<strong>Search:</strong> {{ $search }}@endif
        &nbsp;|&nbsp;<strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}
    </div>
</div>

@forelse($bookings as $booking)
<div class="booking">
    <div class="bk-head">
        <span class="bk-no">{{ $booking->booking_number }}</span>
        &nbsp;|&nbsp; <strong>Room:</strong> {{ $booking->is_whole_hotel ? 'Whole Hotel' : ($booking->room?->room_number ?? 'N/A') }}
        &nbsp;|&nbsp; <strong>Stay:</strong> {{ $booking->check_in_date?->format('d M Y') }} → {{ $booking->check_out_date?->format('d M Y') }}
        &nbsp;|&nbsp; <strong>Guests:</strong> {{ 1 + $booking->bookingGuests->count() }}
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:16%;">Name</th>
                <th style="width:7%;">Relation</th>
                <th style="width:7%;">Age/Sex</th>
                <th style="width:9%;">Nationality</th>
                <th style="width:9%;">ID Type</th>
                <th style="width:12%;">ID Number</th>
                <th style="width:9%;">Arrived From</th>
                <th style="width:9%;">Purpose</th>
                <th style="width:8%;">Departing To</th>
                <th style="width:5%;">Sign</th>
                <th style="width:5%;">ID Doc</th>
                <th style="width:4%;">Sig.</th>
            </tr>
        </thead>
        <tbody>
            <tr class="primary">
                <td><strong>{{ $booking->customer->name ?? '-' }}</strong><span class="badge">PRIMARY</span></td>
                <td>Self</td>
                <td>{{ $booking->customer->age ? $booking->customer->age.' yrs' : '-' }}</td>
                <td>{{ $booking->customer->nationality ?? 'Indian' }}</td>
                <td>{{ ucfirst(str_replace('_',' ',$booking->customer->id_type ?? '-')) }}</td>
                <td>{{ $booking->customer->id_number ?? '-' }}</td>
                <td>{{ $booking->customer->arrival_city ?? '-' }}</td>
                <td>{{ $booking->customer->travel_reason ?? '-' }}</td>
                <td>{{ $booking->customer->dispatch_city ?? '-' }}</td>
                <td>{{ $booking->customer?->signature ? 'Yes' : 'No' }}</td>
                <td>{{ ($booking->customer && $booking->customer->documents->isNotEmpty()) ? 'Yes' : 'No' }}</td>
                <td><span class="signbox"></span></td>
            </tr>
            @foreach($booking->bookingGuests as $guest)
            <tr>
                <td>{{ $guest->name }}</td>
                <td>{{ $guest->relation ?? '-' }}</td>
                <td>{{ $guest->age ? $guest->age.' yrs' : '-' }}{{ $guest->gender ? ' / '.ucfirst(substr($guest->gender,0,1)) : '' }}</td>
                <td>{{ $guest->nationality ?? 'Indian' }}</td>
                <td>{{ \App\Models\BookingGuest::idTypes()[$guest->id_type] ?? ($guest->id_type ?? '-') }}</td>
                <td>{{ $guest->id_number ?? '-' }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>{{ $guest->signature ? 'Yes' : 'No' }}</td>
                <td>{{ $guest->id_document_path ? 'Yes' : 'No' }}</td>
                <td><span class="signbox"></span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@empty
<p style="text-align:center;color:#64748b;">No bookings found for the selected period.</p>
@endforelse

<div class="footer">
    {{ $hotel->name ?? 'Hotel' }} — Guest Register — Page <span class="pagenum"></span>
</div>
</body>
</html>
