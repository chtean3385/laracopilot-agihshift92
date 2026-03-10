@extends('layouts.admin')
@section('title','OTA Bookings')
@section('page-title','OTA Bookings')
@section('page-subtitle','Manage bookings received from OTA channels')

@section('content')
<div style="display:grid;gap:20px;">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#dc2626;font-weight:600;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Toolbar --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <h3 style="font-weight:800;font-size:15px;color:#1e293b;"><i class="fas fa-list-alt" style="color:#0891b2;margin-right:8px;"></i>OTA Bookings</h3>
        <button onclick="document.getElementById('addBookingModal').style.display='flex'" style="padding:9px 18px;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
            <i class="fas fa-plus" style="margin-right:6px;"></i>Add OTA Booking
        </button>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('channel_manager.bookings') }}" style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:16px 20px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Channel</label>
                <select name="channel" class="form-input" style="padding:7px 10px;font-size:12px;min-width:130px;">
                    <option value="">All Channels</option>
                    @foreach(['booking_com'=>'Booking.com','airbnb'=>'Airbnb','expedia'=>'Expedia','goibibo'=>'Goibibo','makemytrip'=>'MakeMyTrip','agoda'=>'Agoda','yatra'=>'Yatra','direct'=>'Direct','other'=>'Other'] as $v => $l)
                    <option value="{{ $v }}" {{ request('channel') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Status</label>
                <select name="status" class="form-input" style="padding:7px 10px;font-size:12px;min-width:120px;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Check-in From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" style="padding:7px 10px;font-size:12px;">
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Check-in To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" style="padding:7px 10px;font-size:12px;">
            </div>
            <button type="submit" style="padding:9px 16px;background:#0891b2;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:12px;cursor:pointer;"><i class="fas fa-filter"></i></button>
            @if(request()->anyFilled(['channel','status','date_from','date_to']))
            <a href="{{ route('channel_manager.bookings') }}" style="padding:9px 12px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">Clear</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        @if($bookings->isEmpty())
        <div style="padding:48px;text-align:center;color:#94a3b8;">
            <i class="fas fa-inbox" style="font-size:36px;margin-bottom:12px;display:block;"></i>
            <p style="font-size:13px;">No OTA bookings yet. Click "Add OTA Booking" to import one manually.</p>
        </div>
        @else
        <div class="lv-table-wrap">
            <table class="lv-table">
                <thead><tr>
                    <th>OTA Ref</th><th>Channel</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Nights</th><th>Total</th><th>Commission</th><th>Net</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                @foreach($bookings as $b)
                <tr>
                    <td style="font-family:monospace;font-size:11px;font-weight:700;color:#1e293b;">{{ $b->ota_booking_id }}</td>
                    <td><span style="background:{{ $b->channelColor() }};color:#fff;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">{{ $b->channelLabel() }}</span></td>
                    <td>
                        <div style="font-weight:700;font-size:13px;color:#1e293b;">{{ $b->guest_name }}</div>
                        @if($b->guest_phone)<div style="font-size:11px;color:#94a3b8;">{{ $b->guest_phone }}</div>@endif
                    </td>
                    <td style="font-weight:600;">{{ $b->room?->room_number ?? '<span style="color:#94a3b8;">—</span>' }}</td>
                    <td style="font-size:12px;white-space:nowrap;">
                        <div>{{ $b->check_in_date->format('d M Y') }}</div>
                        <div style="color:#94a3b8;font-size:11px;">to {{ $b->check_out_date->format('d M') }}</div>
                    </td>
                    <td style="text-align:center;font-weight:700;">{{ $b->nights }}</td>
                    <td style="font-weight:700;">Rs{{ number_format($b->total_amount) }}</td>
                    <td style="font-size:12px;color:#64748b;">{{ $b->commission_pct }}%<br><span style="font-size:11px;">Rs{{ number_format($b->total_amount * $b->commission_pct / 100) }}</span></td>
                    <td style="font-weight:800;color:#16a34a;">Rs{{ number_format($b->net_amount) }}</td>
                    <td>
                        @php $sc = $b->statusColor(); @endphp
                        <span style="background:{{ $sc }}22;color:{{ $sc }};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">{{ ucfirst($b->status) }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            @if(in_array($b->status, ['pending','confirmed']))
                            <form action="{{ route('channel_manager.booking.convert', $b->id) }}" method="POST" onsubmit="return confirm('Convert this OTA booking to a CRM booking?')">
                                @csrf
                                <button type="submit" style="padding:4px 10px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Convert</button>
                            </form>
                            <form action="{{ route('channel_manager.booking.cancel', $b->id) }}" method="POST" onsubmit="return confirm('Mark as cancelled?')">
                                @csrf
                                <button type="submit" style="padding:4px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Cancel</button>
                            </form>
                            @elseif($b->status === 'converted' && $b->converted_booking_id)
                            <a href="{{ route('bookings.show', $b->converted_booking_id) }}" style="padding:4px 10px;background:#f0fdf4;color:#16a34a;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">View Booking</a>
                            @endif
                            @if($b->raw_data)
                            <button onclick="showRaw({{ $b->id }})" style="padding:4px 8px;background:#f1f5f9;color:#64748b;border:none;border-radius:6px;font-size:11px;cursor:pointer;" title="View raw data"><i class="fas fa-code"></i></button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #f1f5f9;">
            {{ $bookings->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Add OTA Booking Modal --}}
<div id="addBookingModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-weight:800;font-size:16px;color:#1e293b;"><i class="fas fa-plus-circle" style="color:#0891b2;margin-right:8px;"></i>Add OTA Booking</h3>
            <button onclick="document.getElementById('addBookingModal').style.display='none'" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;color:#64748b;">✕</button>
        </div>
        <form action="{{ route('channel_manager.booking.store') }}" method="POST" style="padding:24px;display:grid;gap:14px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Channel <span style="color:#e11d48;">*</span></label>
                    <select name="channel" class="form-input" required>
                        @foreach(['booking_com'=>'Booking.com','airbnb'=>'Airbnb','expedia'=>'Expedia','goibibo'=>'Goibibo','makemytrip'=>'MakeMyTrip','agoda'=>'Agoda','yatra'=>'Yatra','direct'=>'Direct','other'=>'Other'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">OTA Booking ID <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="ota_booking_id" class="form-input" required placeholder="e.g. BK-8291847">
                </div>
            </div>
            <div>
                <label class="form-label">Guest Name <span style="color:#e11d48;">*</span></label>
                <input type="text" name="guest_name" class="form-input" required placeholder="Full name">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Guest Phone</label>
                    <input type="text" name="guest_phone" class="form-input" placeholder="Mobile number">
                </div>
                <div>
                    <label class="form-label">Guest Email</label>
                    <input type="email" name="guest_email" class="form-input" placeholder="email@example.com">
                </div>
            </div>
            <div>
                <label class="form-label">Room</label>
                <select name="room_id" class="form-input">
                    <option value="">— Select Room —</option>
                    @foreach($rooms as $r)
                    <option value="{{ $r->id }}">{{ $r->room_number }} ({{ ucfirst($r->type) }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Check-In Date <span style="color:#e11d48;">*</span></label>
                    <input type="date" name="check_in_date" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Check-Out Date <span style="color:#e11d48;">*</span></label>
                    <input type="date" name="check_out_date" class="form-input" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Rate Per Night (Rs) <span style="color:#e11d48;">*</span></label>
                    <input type="number" name="rate_per_night" class="form-input" required min="0" step="0.01" placeholder="0.00">
                </div>
                <div>
                    <label class="form-label">Commission %</label>
                    <input type="number" name="commission_pct" class="form-input" min="0" max="100" step="0.01" placeholder="0" value="0">
                </div>
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Any special notes..."></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="submit" style="flex:1;padding:11px;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:6px;"></i>Save OTA Booking
                </button>
                <button type="button" onclick="document.getElementById('addBookingModal').style.display='none'" style="padding:11px 18px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Raw Data Modals --}}
@foreach($bookings as $b)
@if($b->raw_data)
<div id="raw-{{ $b->id }}" style="display:none;position:fixed;inset:0;z-index:60;background:rgba(0,0,0,.6);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#1e293b;border-radius:16px;width:100%;max-width:600px;max-height:80vh;overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #334155;">
            <span style="color:#fff;font-weight:700;font-size:13px;"><i class="fas fa-code" style="margin-right:6px;color:#94a3b8;"></i>Raw Data — {{ $b->ota_booking_id }}</span>
            <button onclick="document.getElementById('raw-{{ $b->id }}').style.display='none'" style="background:#334155;border:none;color:#94a3b8;width:28px;height:28px;border-radius:6px;cursor:pointer;">✕</button>
        </div>
        <pre style="margin:0;padding:20px;color:#7dd3fc;font-size:11px;overflow-y:auto;line-height:1.6;">{{ json_encode($b->raw_data, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif
@endforeach

<script>
function showRaw(id) { document.getElementById('raw-' + id).style.display = 'flex'; }
document.getElementById('addBookingModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endsection
