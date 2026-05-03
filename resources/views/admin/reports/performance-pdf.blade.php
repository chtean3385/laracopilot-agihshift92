<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Performance Analysis</title>
<style>
    @page { margin: 14mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { border-bottom: 2px solid #6366f1; padding-bottom: 8px; margin-bottom: 12px; }
    .hotel { font-size: 14pt; font-weight: bold; color: #4f46e5; }
    .sub { font-size: 10pt; color: #475569; margin-top: 2px; }
    .meta { margin-top: 4px; font-size: 9pt; color: #64748b; }
    .kpis { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 6px 0; }
    .kpi { display: table-cell; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 8px; background: #f8fafc; width: 1%; }
    .kpi-label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
    .kpi-val { font-size: 12pt; font-weight: bold; color: #0f172a; margin-top: 2px; }
    h2 { font-size: 11pt; color: #1e293b; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f1f5f9; color: #475569; font-size: 8pt; text-transform: uppercase; }
    .right { text-align: right; }
    .two-col { width: 100%; }
    .two-col td { vertical-align: top; padding: 0 4px; border: none; }
    .insight { border: 1px solid #fde68a; background: #fefce8; border-radius: 4px; padding: 6px 8px; margin-bottom: 4px; }
    .insight .t { font-weight: bold; font-size: 9pt; color: #78350f; }
    .insight .m { font-size: 8.5pt; color: #92400e; margin-top: 2px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; padding-top: 4px; border-top: 1px solid #e2e8f0; }
    .empty { color: #94a3b8; font-size: 8.5pt; padding: 6px; }
</style>
</head>
<body>
<div class="header">
    <div class="hotel">{{ $hotel->name ?? 'Hotel' }}</div>
    <div class="sub">Performance Analysis</div>
    <div class="meta"><strong>Period:</strong> {{ $periodLabel }} &nbsp;|&nbsp; <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}</div>
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">Revenue</div><div class="kpi-val">₹{{ number_format($totalRevenue) }}</div></div>
    <div class="kpi"><div class="kpi-label">Avg Occupancy</div><div class="kpi-val">{{ $avgOccupancy }}%</div></div>
    <div class="kpi"><div class="kpi-label">ADR</div><div class="kpi-val">₹{{ number_format($adr) }}</div></div>
    <div class="kpi"><div class="kpi-label">RevPAR</div><div class="kpi-val">₹{{ number_format($revpar) }}</div></div>
    <div class="kpi"><div class="kpi-label">Room Nights</div><div class="kpi-val">{{ $totalRoomNights }}</div></div>
</div>

<h2>Monthly Trend</h2>
@if(empty($months))
    <div class="empty">No data for the selected period.</div>
@else
<table>
    <thead><tr><th>Month</th><th class="right">Revenue</th><th class="right">Occupancy %</th><th class="right">Bookings</th></tr></thead>
    <tbody>
        @foreach($months as $i => $m)
        <tr>
            <td>{{ $m }}</td>
            <td class="right">₹{{ number_format($monthRevenue[$i] ?? 0) }}</td>
            <td class="right">{{ $monthOccupancy[$i] ?? 0 }}%</td>
            <td class="right">{{ $monthBookings[$i] ?? 0 }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="two-col"><tr>
    <td style="width:50%;">
        <h2>Bookings by Room Type</h2>
        @if(empty($roomTypeLabels))
            <div class="empty">No bookings in this period.</div>
        @else
        <table>
            <thead><tr><th>Room Type</th><th class="right">Bookings</th></tr></thead>
            <tbody>
                @foreach($roomTypeLabels as $i => $l)
                    <tr><td>{{ $l }}</td><td class="right">{{ $roomTypeData[$i] ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </td>
    <td style="width:50%;">
        <h2>Booking Source Mix</h2>
        @if(empty($sourceLabels))
            <div class="empty">No source data.</div>
        @else
        <table>
            <thead><tr><th>Source</th><th class="right">Bookings</th></tr></thead>
            <tbody>
                @foreach($sourceLabels as $i => $l)
                    <tr><td>{{ $l }}</td><td class="right">{{ $sourceCounts[$i] ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </td>
</tr></table>

<table class="two-col"><tr>
    <td style="width:50%;">
        <h2>Revenue by Day of Week</h2>
        <table>
            <thead><tr><th>Day</th><th class="right">Revenue</th></tr></thead>
            <tbody>
                @foreach($dowLabels as $i => $l)
                    <tr><td>{{ $l }}</td><td class="right">₹{{ number_format($dowTotals[$i] ?? 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </td>
    <td style="width:50%;">
        <h2>Revenue by Payment Method</h2>
        @if(empty($pmLabels))
            <div class="empty">No payments recorded.</div>
        @else
        <table>
            <thead><tr><th>Method</th><th class="right">Revenue</th></tr></thead>
            <tbody>
                @foreach($pmLabels as $i => $l)
                    <tr><td>{{ $l }}</td><td class="right">₹{{ number_format($pmAmounts[$i] ?? 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </td>
</tr></table>

<h2>Insights</h2>
@foreach($insights as $ins)
    <div class="insight">
        <div class="t">{{ $ins['title'] }}</div>
        <div class="m">{{ $ins['msg'] }}</div>
    </div>
@endforeach

<div class="footer">{{ $hotel->name ?? 'Hotel' }} — Performance Analysis</div>
</body>
</html>
