@extends('layouts.platform')
@section('title', 'WhatsApp Webhook Logs')

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0 0 4px;">
            <i class="fab fa-whatsapp" style="color:#25D366;margin-right:8px;"></i>WhatsApp Webhook Logs
        </h1>
        <p style="color:#6b7280;font-size:14px;margin:0;">Real-time events received from Meta — messages, delivery status, template approvals.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route('platform.whatsapp.templates') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-robot"></i> Templates
        </a>
        <a href="{{ route('platform.whatsapp.numbers') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-sim-card"></i> Hotel Numbers
        </a>
        <a href="{{ route('platform.whatsapp.settings') }}"
            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#f1f5f9;color:#64748b;border-radius:11px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e8f0;">
            <i class="fas fa-cog"></i> Settings
        </a>
        <form method="POST" action="{{ route('platform.whatsapp.logs.clear') }}" style="margin:0;"
            onsubmit="return confirm('Delete all logs older than 30 days?')">
            @csrf
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#fee2e2;color:#b91c1c;border:none;border-radius:11px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid #fca5a5;">
                <i class="fas fa-trash"></i> Clear Old Logs
            </button>
        </form>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Webhook URL info card --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 20px;margin-bottom:18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:4px;text-transform:uppercase;letter-spacing:.06em;">Webhook URL (register this in Meta)</div>
        <code style="font-size:13px;color:#7c3aed;background:#f5f3ff;padding:4px 10px;border-radius:8px;word-break:break-all;">{{ $webhookUrl }}</code>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;text-align:right;">
        <div style="font-size:12px;color:#6b7280;">Verify Token</div>
        <div style="font-weight:700;font-size:13px;color:{{ $platform?->webhook_verify_token ? '#15803d' : '#b91c1c' }};">
            {{ $platform?->webhook_verify_token ? '✅ Configured' : '❌ Not set — go to Settings' }}
        </div>
    </div>
    <a href="{{ $webhookUrl }}" target="_blank"
        style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f5f3ff;color:#7c3aed;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #ddd6fe;white-space:nowrap;">
        <i class="fas fa-external-link-alt"></i> Open URL
    </a>
</div>

{{-- Filters --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px;margin-bottom:18px;">
    <form method="GET" action="{{ route('platform.whatsapp.logs') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;text-transform:uppercase;">Event Type</label>
            <select name="type" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;">
                <option value="">All types</option>
                @foreach(['verification','message_received','delivery_status','template_status_update','signature_check','error'] as $t)
                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$t)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;text-transform:uppercase;">Direction</label>
            <select name="direction" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;">
                <option value="">All</option>
                <option value="incoming" {{ request('direction') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                <option value="outgoing" {{ request('direction') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;text-transform:uppercase;">Status</label>
            <select name="status" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;">
                <option value="">All</option>
                <option value="ok" {{ request('status') === 'ok' ? 'selected' : '' }}>OK</option>
                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
            </select>
        </div>
        <button type="submit"
            style="padding:8px 18px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="{{ route('platform.whatsapp.logs') }}"
            style="padding:8px 14px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
            Reset
        </a>
    </form>
</div>

{{-- Logs table --}}
@if($logs->isEmpty())
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:48px 24px;text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">📭</div>
    <div style="font-size:17px;font-weight:700;color:#374151;margin-bottom:8px;">No logs yet</div>
    <div style="font-size:14px;color:#6b7280;">Once you register the webhook URL in Meta Business Manager and events start flowing, they'll appear here in real time.</div>
</div>
@else
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb;">
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;white-space:nowrap;">Time</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Direction</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Event</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Phone</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Status</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Notes</th>
                <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
        @php
            $isError = $log->status === 'error';
            $rowBg   = $isError ? '#fff5f5' : '#fff';
            $dirColor = $log->direction === 'incoming' ? '#1d4ed8' : '#15803d';
            $dirBg    = $log->direction === 'incoming' ? '#eff6ff' : '#f0fdf4';
            $eventColors = [
                'message_received'       => ['#f0fdf4','#15803d'],
                'delivery_status'        => ['#eff6ff','#1d4ed8'],
                'template_status_update' => ['#f5f3ff','#7c3aed'],
                'verification'           => ['#fef9c3','#713f12'],
                'signature_check'        => ['#fef2f2','#b91c1c'],
                'error'                  => ['#fef2f2','#b91c1c'],
            ];
            [$evBg, $evColor] = $eventColors[$log->event_type] ?? ['#f1f5f9','#64748b'];
        @endphp
        <tr style="border-bottom:1px solid #f1f5f9;background:{{ $rowBg }};">
            <td style="padding:11px 16px;white-space:nowrap;color:#6b7280;font-size:12px;">
                {{ $log->created_at->format('d M H:i:s') }}
            </td>
            <td style="padding:11px 16px;">
                <span style="background:{{ $dirBg }};color:{{ $dirColor }};padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;">
                    {{ $log->direction }}
                </span>
            </td>
            <td style="padding:11px 16px;">
                <span style="background:{{ $evBg }};color:{{ $evColor }};padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700;">
                    {{ str_replace('_',' ', $log->event_type ?? '—') }}
                </span>
            </td>
            <td style="padding:11px 16px;font-family:monospace;color:#374151;font-size:12px;">
                {{ $log->phone ? '+' . $log->phone : '—' }}
            </td>
            <td style="padding:11px 16px;">
                @if($isError)
                <span style="color:#b91c1c;font-weight:700;font-size:12px;"><i class="fas fa-times-circle"></i> Error</span>
                @else
                <span style="color:#15803d;font-weight:700;font-size:12px;"><i class="fas fa-check-circle"></i> OK</span>
                @endif
            </td>
            <td style="padding:11px 16px;color:#6b7280;max-width:280px;font-size:12px;">
                {{ $log->notes ?? '—' }}
            </td>
            <td style="padding:11px 16px;">
                @if($log->payload)
                <button onclick="togglePayload({{ $log->id }})"
                    style="padding:4px 10px;background:#f1f5f9;color:#64748b;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-code"></i> JSON
                </button>
                @endif
            </td>
        </tr>
        @if($log->payload)
        <tr id="payload-{{ $log->id }}" style="display:none;background:#f8fafc;">
            <td colspan="7" style="padding:0 16px 14px;">
                <pre style="background:#1e293b;color:#e2e8f0;padding:14px 18px;border-radius:10px;font-size:12px;overflow-x:auto;margin:0;line-height:1.5;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </td>
        </tr>
        @endif
        @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($logs->hasPages())
<div style="margin-top:20px;display:flex;justify-content:center;">
    {{ $logs->links() }}
</div>
@endif
@endif

<script>
function togglePayload(id) {
    const row = document.getElementById('payload-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>

@endsection
