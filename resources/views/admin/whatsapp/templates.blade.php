@extends('layouts.admin')
@section('title', 'WhatsApp Templates')
@section('page-title', 'Message Templates')
@section('page-subtitle', 'Manage automated WhatsApp message templates')

@section('content')

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;color:#15803d;padding:12px 18px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div style="font-size:13px;color:#64748b;">
        <i class="fas fa-info-circle" style="color:#0891b2;margin-right:5px;"></i>
        Each template is triggered automatically when the corresponding event occurs.
    </div>
    <a href="{{ route('whatsapp.config') }}" class="btn-secondary">
        <i class="fas fa-cog" style="margin-right:8px;"></i>Provider Settings
    </a>
</div>

<div style="display:grid;gap:16px;">
    @php
    $eventMeta = [
        'booking.created'  => ['fas fa-calendar-check', 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'Triggered when a new booking is created'],
        'checkin.tomorrow' => ['fas fa-bell',            'linear-gradient(135deg,#f59e0b,#d97706)', 'Sent daily at 9am to guests checking in tomorrow'],
        'checkout.done'    => ['fas fa-sign-out-alt',    'linear-gradient(135deg,#10b981,#059669)', 'Triggered when a guest completes check-out'],
    ];
    @endphp

    @foreach($allEvents as $event => $label)
    @php
        $template = $templates[$event] ?? null;
        [$icon, $grad, $desc] = $eventMeta[$event] ?? ['fas fa-comment', 'linear-gradient(135deg,#64748b,#334155)', ''];
    @endphp
    <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.05);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div style="width:48px;height:48px;background:{{ $grad }};border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="{{ $icon }}" style="color:#fff;font-size:18px;"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                    <div style="font-size:16px;font-weight:800;color:#1e293b;">{{ $label }}</div>
                    @if($template)
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $template->is_active ? '#dcfce7' : '#f1f5f9' }};color:{{ $template->is_active ? '#15803d' : '#94a3b8' }};">
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @else
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;">Not set up</span>
                    @endif
                </div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:10px;">{{ $desc }}</div>
                @if($template)
                <div style="font-size:13px;color:#374151;background:#f8fafc;padding:10px 14px;border-radius:10px;border-left:3px solid #e2e8f0;line-height:1.5;white-space:pre-line;max-height:80px;overflow:hidden;">{{ Str::limit($template->message_body, 180) }}</div>
                @else
                <div style="font-size:13px;color:#94a3b8;font-style:italic;">No template configured yet.</div>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                @if($template)
                <a href="{{ route('whatsapp.template.edit', $template) }}" class="lv-action-btn lv-action-btn-amber" title="Edit template" style="width:auto;padding:8px 16px;border-radius:12px;font-size:13px;font-weight:600;gap:6px;display:inline-flex;align-items:center;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @else
                <span style="font-size:13px;color:#94a3b8;">Create a template via seeder or database.</span>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div style="margin-top:20px;background:#fff;border-radius:16px;padding:18px 22px;border:1px solid #f1f5f9;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:8px;">
        <i class="fas fa-code" style="color:#7c3aed;margin-right:6px;"></i>Available Template Variables
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach(['{{guest_name}}','{{hotel_name}}','{{room_number}}','{{room_type}}','{{check_in_date}}','{{check_out_date}}','{{booking_number}}','{{total_amount}}','{{balance_due}}','{{invoice_number}}'] as $var)
        <code style="background:#f1f5f9;color:#7c3aed;padding:4px 10px;border-radius:8px;font-size:12px;font-family:monospace;font-weight:700;">{{ $var }}</code>
        @endforeach
    </div>
</div>

@endsection
