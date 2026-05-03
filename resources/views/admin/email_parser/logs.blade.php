@extends('layouts.admin')
@section('title', 'Parsed Email Logs')
@section('page-title', 'Parsed Email Logs')
@section('page-subtitle', 'Every email fetched by the IMAP sync, with parsing status.')

@section('content')
<div style="background:#fff;border-radius:20px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @foreach(['all'=>'All','pending'=>'Pending','processed'=>'Processed','failed'=>'Failed','duplicate'=>'Duplicate'] as $k=>$lbl)
            @php $active = ($status === $k) || ($k === 'all' && !$status); @endphp
            <a href="{{ route('email-parser.logs', $k === 'all' ? [] : ['status'=>$k]) }}"
               style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;background:{{ $active ? '#0d9488' : '#f1f5f9' }};color:{{ $active ? '#fff' : '#475569' }};">
                {{ $lbl }}
            </a>
            @endforeach
        </div>
        <a href="{{ route('email-parser.config') }}" style="font-size:12px;color:#0d9488;text-decoration:none;font-weight:700;">← Back to Config</a>
    </div>

    @if($rows->isEmpty())
    <div style="text-align:center;padding:50px 20px;color:#94a3b8;">
        <i class="fas fa-inbox" style="font-size:36px;display:block;margin-bottom:10px;"></i>
        <div style="font-weight:700;">No emails to show.</div>
    </div>
    @else
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;text-align:left;">
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">When</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Subject</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Sender</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Status</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Booking</th>
                <th style="padding:10px;font-size:11px;text-transform:uppercase;color:#64748b;">Parsed Data</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px;color:#64748b;">{{ $row->created_at?->format('M d, H:i') }}</td>
                <td style="padding:10px;color:#1e293b;font-weight:600;max-width:300px;">{{ $row->subject }}</td>
                <td style="padding:10px;color:#64748b;">{{ $row->sender }}</td>
                <td style="padding:10px;">
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $row->status_color }}22;color:{{ $row->status_color }};">{{ $row->status }}</span>
                    @if($row->fail_reason)
                    <div style="font-size:11px;color:#b91c1c;margin-top:4px;">{{ \Illuminate\Support\Str::limit($row->fail_reason, 60) }}</div>
                    @endif
                </td>
                <td style="padding:10px;">
                    @if($row->booking_id)
                    <a href="{{ route('bookings.index') }}" style="color:#0d9488;font-weight:600;">#{{ $row->booking_id }}</a>
                    @else
                    <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="padding:10px;">
                    @if($row->parsed_data)
                    <details>
                        <summary style="cursor:pointer;color:#0d9488;font-weight:600;font-size:12px;">View JSON</summary>
                        <pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px;margin-top:6px;font-size:11px;color:#334155;white-space:pre-wrap;max-width:400px;">{{ json_encode($row->parsed_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                    @else
                    <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <div style="margin-top:14px;">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
