@extends('layouts.platform')

@section('title', 'OTA Email Inbound Sources')

@section('content')
<div style="max-width:960px;margin:0 auto;padding:28px 20px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 4px;">OTA Email Inbound Sources</h1>
            <p style="font-size:13px;color:#64748b;margin:0;">Configure a dedicated inbound email address per hotel. Hotel admins forward OTA booking confirmation emails to that address — the system parses them automatically and adds them to the same import queue as WhatsApp-sourced bookings.</p>
        </div>
        <button onclick="document.getElementById('addModal').style.display='flex'"
            style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-plus"></i> Add Hotel Email Source
        </button>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;">
        <i class="fas fa-check-circle" style="margin-right:6px;"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;">
        <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ session('error') }}
    </div>
    @endif

    {{-- How-it-works info box --}}
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:18px 20px;margin-bottom:24px;">
        <div style="font-size:13px;font-weight:800;color:#1e40af;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-info-circle"></i> How it works
        </div>
        <ol style="font-size:12px;color:#1e3a8a;margin:0;padding-left:18px;line-height:2;">
            <li>Add a unique inbound email address for each hotel below (e.g. <code style="background:#dbeafe;padding:1px 5px;border-radius:4px;">demo-hotel@inbound.yourdomain.com</code>).</li>
            <li>In Mailgun → Receiving → Routes, set the forward URL to: <code style="background:#dbeafe;padding:1px 5px;border-radius:4px;">{{ url('/webhook/ota-email') }}</code></li>
            <li>Set the <strong>MAILGUN_WEBHOOK_SIGNING_KEY</strong> Replit secret to your Mailgun webhook signing key (Mailgun → Settings → Webhooks). This secures the endpoint against forged requests.</li>
            <li>Hotel admins forward OTA booking emails (Booking.com, Airbnb, etc.) to that inbound address.</li>
            <li>The system auto-parses the email body and subject to detect the OTA, then adds the booking to the hotel's OTA Import Queue — same queue as WhatsApp imports, with an <i class="fas fa-envelope" style="color:#1d4ed8;"></i> Email badge.</li>
        </ol>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Hotel</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Inbound Email</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Notes</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Active</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sources as $src)
                <tr style="border-bottom:1px solid #f1f5f9;" id="erow-{{ $src->id }}">
                    <td style="padding:12px 16px;">
                        <span style="font-size:13px;font-weight:700;color:#1e293b;">{{ $src->hotel->name ?? '—' }}</span>
                        @if($src->hotel && $src->hotel->status !== 'active')
                        <span style="background:#fee2e2;color:#dc2626;padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700;margin-left:6px;">{{ ucfirst($src->hotel->status) }}</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;font-size:12px;color:#1e293b;font-family:monospace;">
                        {{ $src->inbound_email }}
                    </td>
                    <td style="padding:12px 16px;font-size:12px;color:#64748b;max-width:200px;">{{ $src->notes ?? '—' }}</td>
                    <td style="padding:12px 16px;text-align:center;">
                        <button onclick="toggleEmailSource({{ $src->id }}, this)"
                            data-active="{{ $src->is_active ? '1' : '0' }}"
                            style="background:{{ $src->is_active ? 'linear-gradient(135deg,#10b981,#059669)' : '#e2e8f0' }};color:{{ $src->is_active ? '#fff' : '#64748b' }};border:none;padding:5px 14px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;transition:all .2s;">
                            {{ $src->is_active ? 'Active' : 'Off' }}
                        </button>
                    </td>
                    <td style="padding:12px 16px;text-align:right;white-space:nowrap;">
                        <button onclick="openEdit({{ $src->id }}, {!! json_encode($src) !!})"
                            style="background:#f1f5f9;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;color:#475569;cursor:pointer;margin-right:6px;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form action="{{ route('platform.ota-email-sources.destroy', $src) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Remove inbound email for {{ $src->hotel->name ?? 'this hotel' }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#fee2e2;border:none;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;color:#dc2626;cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">
                        <i class="fas fa-envelope" style="font-size:28px;display:block;margin-bottom:10px;opacity:.4;"></i>
                        No hotel email sources configured yet. Add one to enable email-based OTA booking ingestion.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-envelope" style="color:#6366f1;"></i> Add Hotel Email Source
        </h3>
        <form action="{{ route('platform.ota-email-sources.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">Hotel</label>
                <select name="hotel_id" required style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    <option value="">— Select hotel —</option>
                    @foreach($hotels as $h)
                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">Inbound Email Address</label>
                <input type="email" name="inbound_email" required placeholder="hotel-slug@inbound.yourdomain.com"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                <p style="font-size:11px;color:#94a3b8;margin:5px 0 0;">This must match the route configured in Mailgun Inbound Parse.</p>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <input type="text" name="notes" placeholder="e.g. Booking.com + Airbnb forwards only"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <input type="checkbox" name="is_active" id="add_is_active" value="1" checked style="width:15px;height:15px;accent-color:#6366f1;">
                <label for="add_is_active" style="font-size:13px;color:#475569;font-weight:600;">Active</label>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Save</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'"
                    style="flex:1;background:#f1f5f9;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;margin:16px;">
        <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-edit" style="color:#6366f1;"></i> Edit Email Source
        </h3>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">Inbound Email Address</label>
                <input type="email" name="inbound_email" required
                    style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;">Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <input type="text" name="notes"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width:15px;height:15px;accent-color:#6366f1;">
                <label for="edit_is_active" style="font-size:13px;color:#475569;font-weight:600;">Active</label>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" style="flex:1;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Update</button>
                <button type="button" onclick="document.getElementById('editModal').style.display='none'"
                    style="flex:1;background:#f1f5f9;border:none;padding:11px;border-radius:10px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, src) {
    var f = document.getElementById('editForm');
    f.action = '/platform/ota-email-sources/' + id;
    f.querySelector('[name="inbound_email"]').value = src.inbound_email || '';
    f.querySelector('[name="notes"]').value         = src.notes || '';
    f.querySelector('[name="is_active"]').checked   = src.is_active == true || src.is_active == 1;
    document.getElementById('editModal').style.display = 'flex';
}

function toggleEmailSource(id, btn) {
    fetch('/platform/ota-email-sources/' + id + '/toggle', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            btn.dataset.active   = d.active ? '1' : '0';
            btn.textContent      = d.active ? 'Active' : 'Off';
            btn.style.background = d.active ? 'linear-gradient(135deg,#10b981,#059669)' : '#e2e8f0';
            btn.style.color      = d.active ? '#fff' : '#64748b';
        }
    });
}
</script>
@endsection
