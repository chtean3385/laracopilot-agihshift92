@extends('layouts.admin')

@section('title', 'OTA Import History')

@section('content')
<div style="padding:24px 20px;max-width:1100px;margin:0 auto;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 4px;display:flex;align-items:center;gap:10px;">
                <span style="width:34px;height:34px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-hotel" style="color:#fff;font-size:14px;"></i>
                </span>
                OTA Booking History
            </h1>
            <p style="font-size:12px;color:#64748b;margin:0;">Past confirmed and rejected OTA imports with match analytics.</p>
        </div>
    </div>

    {{-- Tab nav --}}
    @php
        $queueUrl   = $urlHotelId ? route('ota-bookings.hotel.index',   ['hotelId' => $urlHotelId]) : route('ota-bookings.index');
        $historyUrl = $urlHotelId ? route('ota-bookings.hotel.history', ['hotelId' => $urlHotelId]) : route('ota-bookings.history');
    @endphp
    <div style="display:flex;gap:4px;margin-bottom:24px;background:#f1f5f9;border-radius:12px;padding:4px;width:fit-content;">
        <a href="{{ $queueUrl }}"
           style="padding:8px 18px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;color:#64748b;background:transparent;">
            <i class="fas fa-inbox" style="margin-right:6px;"></i>Import Queue
        </a>
        <a href="{{ $historyUrl }}"
           style="padding:8px 18px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;color:#fff;background:linear-gradient(135deg,#6366f1,#4f46e5);box-shadow:0 2px 8px #6366f133;">
            <i class="fas fa-history" style="margin-right:6px;"></i>History &amp; Analytics
        </a>
    </div>

    {{-- Summary cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px;">
        <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:900;color:#1e293b;">{{ $summary['total'] }}</div>
            <div style="font-size:11px;font-weight:600;color:#64748b;margin-top:2px;">Total Imports</div>
        </div>
        <div style="background:#dcfce7;border:1.5px solid #10b98122;border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:900;color:#10b981;">{{ $summary['confirmed'] }}</div>
            <div style="font-size:11px;font-weight:600;color:#064e3b;margin-top:2px;">
                Confirmed
                @if($summary['total'] > 0)
                <span style="display:block;font-size:12px;font-weight:800;color:#059669;margin-top:2px;">
                    {{ round($summary['confirmed'] / $summary['total'] * 100, 1) }}%
                </span>
                @endif
            </div>
        </div>
        <div style="background:#fee2e2;border:1.5px solid #ef444422;border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:900;color:#ef4444;">{{ $summary['rejected'] }}</div>
            <div style="font-size:11px;font-weight:600;color:#7f1d1d;margin-top:2px;">
                Rejected
                @if($summary['total'] > 0)
                <span style="display:block;font-size:12px;font-weight:800;color:#dc2626;margin-top:2px;">
                    {{ round($summary['rejected'] / $summary['total'] * 100, 1) }}%
                </span>
                @endif
            </div>
        </div>
        <div style="background:#eff6ff;border:1.5px solid #3b82f622;border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:900;color:#3b82f6;">{{ $summary['matchedByName'] }}</div>
            <div style="font-size:11px;font-weight:600;color:#1e40af;margin-top:2px;">
                Matched by Name
                @if($summary['total'] > 0)
                <span style="display:block;font-size:12px;font-weight:800;color:#2563eb;margin-top:2px;">
                    {{ round($summary['matchedByName'] / $summary['total'] * 100, 1) }}%
                </span>
                @endif
            </div>
        </div>
        <div style="background:#faf5ff;border:1.5px solid #a855f722;border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:900;color:#a855f7;">{{ $summary['matchedByPhone'] }}</div>
            <div style="font-size:11px;font-weight:600;color:#581c87;margin-top:2px;">
                Matched by Phone
                @if($summary['total'] > 0)
                <span style="display:block;font-size:12px;font-weight:800;color:#7c3aed;margin-top:2px;">
                    {{ round($summary['matchedByPhone'] / $summary['total'] * 100, 1) }}%
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- OTA Source breakdown --}}
    @if($otaBreakdown->count())
    <div style="background:#fff;border-radius:16px;border:2px solid #e2e8f0;padding:20px;margin-bottom:24px;">
        <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-chart-bar" style="color:#6366f1;"></i> OTA Source Breakdown
        </h3>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @foreach($otaBreakdown as $row)
            @php
                $pct = $row->total > 0 ? round($row->confirmed_count / $row->total * 100) : 0;
            @endphp
            <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 16px;min-width:160px;flex:1;">
                <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:6px;">{{ $row->ota_name ?? 'Unknown' }}</div>
                <div style="font-size:12px;color:#64748b;margin-bottom:8px;">{{ $row->total }} import{{ $row->total !== 1 ? 's' : '' }}</div>
                <div style="background:#e2e8f0;border-radius:20px;height:6px;overflow:hidden;margin-bottom:4px;">
                    <div style="background:linear-gradient(90deg,#10b981,#059669);height:100%;width:{{ $pct }}%;border-radius:20px;transition:width .4s;"></div>
                </div>
                <div style="font-size:11px;color:#10b981;font-weight:700;">{{ $pct }}% confirmed</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ $historyUrl }}"
          style="background:#fff;border-radius:14px;border:2px solid #e2e8f0;padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">From Date</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
        </div>
        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">To Date</label>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
        </div>
        @if($otaSources->count())
        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">OTA Source</label>
            <select name="ota_source"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;background:#fff;">
                <option value="">All OTAs</option>
                @foreach($otaSources as $src)
                <option value="{{ $src }}" @selected($otaFilter === $src)>{{ $src }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div style="display:flex;gap:8px;">
            <button type="submit"
                style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:10px 18px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-filter" style="margin-right:5px;"></i>Filter
            </button>
            @if($dateFrom || $dateTo || $otaFilter)
            <a href="{{ $historyUrl }}"
               style="background:#f1f5f9;color:#475569;border:none;padding:10px 14px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </div>
    </form>

    {{-- History list --}}
    @forelse($imports as $imp)
    <div style="background:#fff;border-radius:16px;border:2px solid {{ $imp->status === 'confirmed' ? '#bbf7d0' : '#fecaca' }};padding:18px 20px;margin-bottom:12px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">

            {{-- Left: info --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <span style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                        {{ $imp->ota_name ?? 'OTA' }}
                    </span>
                    <span style="background:{{ $imp->status_color }}22;color:{{ $imp->status_color }};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid {{ $imp->status_color }}44;">
                        {{ $imp->status_label }}
                    </span>
                    @if(($imp->source_channel ?? 'whatsapp') === 'email')
                    <span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid #bfdbfe;">
                        <i class="fas fa-envelope" style="margin-right:3px;"></i>Email
                    </span>
                    @else
                    <span style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid #bbf7d0;">
                        <i class="fab fa-whatsapp" style="margin-right:3px;"></i>WhatsApp
                    </span>
                    @endif
                    @if($imp->booking_ref)
                    <span style="font-size:11px;color:#64748b;font-family:monospace;background:#f8fafc;padding:2px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                        Ref: {{ $imp->booking_ref }}
                    </span>
                    @endif
                    <span style="font-size:11px;color:#94a3b8;">{{ $imp->created_at->format('d M Y, h:i A') }}</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-bottom:8px;">
                    @if($imp->guest_name)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Guest</div>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $imp->guest_name }}</div>
                    </div>
                    @endif
                    @if($imp->guest_phone)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Phone</div>
                        <div style="font-size:13px;color:#1e293b;">{{ $imp->guest_phone }}</div>
                    </div>
                    @endif
                    @if($imp->checkin)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Check-in</div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($imp->checkin)->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($imp->checkout)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Check-out</div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($imp->checkout)->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($imp->room_type)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Room</div>
                        <div style="font-size:13px;color:#1e293b;">{{ $imp->room_type }}</div>
                    </div>
                    @endif
                    @if($imp->amount)
                    <div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Amount</div>
                        <div style="font-size:14px;font-weight:800;color:#10b981;">₹{{ number_format($imp->amount, 0) }}</div>
                    </div>
                    @endif
                </div>

                <div style="font-size:11px;color:#94a3b8;">
                    Matched by: <span style="font-weight:600;color:#64748b;">{{ $imp->matched_by ?: '—' }}</span>
                    @if($imp->property_name)
                    &nbsp;·&nbsp; Property: <span style="font-weight:600;color:#64748b;">{{ $imp->property_name }}</span>
                    @endif
                </div>
            </div>

            {{-- Right: booking link --}}
            @if($imp->booking_id)
            <div style="flex-shrink:0;">
                <a href="{{ route('bookings.show', $imp->booking_id) }}"
                   style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#059669;border:1.5px solid #bbf7d0;padding:8px 14px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-external-link-alt"></i> View Booking
                </a>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:16px;border:2px dashed #e2e8f0;">
        <i class="fas fa-history" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
        <p style="font-size:15px;font-weight:700;color:#94a3b8;margin:0 0 6px;">No history found</p>
        <p style="font-size:13px;color:#cbd5e1;margin:0;">Confirmed and rejected imports will appear here once they are processed.</p>
    </div>
    @endforelse

    {{ $imports->links() }}
</div>
@endsection
