<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 16mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { border-bottom: 2px solid #0891b2; padding-bottom: 8px; margin-bottom: 12px; }
    .hotel { font-size: 14pt; font-weight: bold; color: #0891b2; }
    .sub { font-size: 10pt; color: #475569; margin-top: 2px; }
    .meta { margin-top: 4px; font-size: 9pt; color: #64748b; }
    .kpis { display: table; width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 6px 0; }
    .kpi { display: table-cell; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 8px; background: #f8fafc; width: 1%; }
    .kpi-label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
    .kpi-val { font-size: 12pt; font-weight: bold; color: #0f172a; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f1f5f9; color: #475569; font-size: 8pt; text-transform: uppercase; }
    tr.total td { background: #fef3c7; font-weight: bold; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; padding-top: 4px; border-top: 1px solid #e2e8f0; }
    .right { text-align: right; }
</style>
</head>
<body>
<div class="header">
    <div class="hotel">{{ $hotel->name ?? 'Hotel' }}</div>
    <div class="sub">{{ $title }}</div>
    <div class="meta">
        @if(!empty($period))<strong>Period:</strong> {{ $period }} &nbsp;|&nbsp; @endif
        <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}
        @if(!empty($extraMeta))&nbsp;|&nbsp; {!! $extraMeta !!}@endif
    </div>
</div>

@if(!empty($kpis))
<div class="kpis">
    @foreach($kpis as $label => $val)
        <div class="kpi"><div class="kpi-label">{{ $label }}</div><div class="kpi-val">{{ $val }}</div></div>
    @endforeach
</div>
@endif

@if(empty($rows))
    <p style="text-align:center;color:#64748b;padding:40px 0;">No data found for the selected period.</p>
@else
<table>
    <thead>
        <tr>
            @foreach($headers as $h)
                <th @if(!empty($numeric[$loop->index])) class="right" @endif>{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            @foreach($row as $i => $cell)
                <td @if(!empty($numeric[$i])) class="right" @endif>{{ $cell }}</td>
            @endforeach
        </tr>
        @endforeach
        @if(!empty($totalsRow))
        <tr class="total">
            @foreach($totalsRow as $i => $cell)
                <td @if(!empty($numeric[$i])) class="right" @endif>{{ $cell }}</td>
            @endforeach
        </tr>
        @endif
    </tbody>
</table>
@endif

<div class="footer">{{ $hotel->name ?? 'Hotel' }} — {{ $title }}</div>
</body>
</html>
