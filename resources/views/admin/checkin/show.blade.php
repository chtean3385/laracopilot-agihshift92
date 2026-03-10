@extends('layouts.admin')
@section('title','Process Check-In')
@section('page-title','Process Check-In')
@section('page-subtitle','Confirm arrival for ' . $booking->customer->name)
@section('content')
<div class="max-w-3xl space-y-5">
    <div class="flex items-center gap-3 flex-wrap">
    <a href="{{ route('checkin.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    @if(\App\Models\Module::isEnabled('pathik'))
    <button onclick="fillPathikCheckin()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
        <i class="fas fa-clipboard-list"></i> Fill Pathik Portal
    </button>
    @endif
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-user text-cyan-500 mr-2"></i>Guest Details</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Name</span><span class="font-semibold">{{ $booking->customer->name }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Phone</span><span class="font-semibold">{{ $booking->customer->phone }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">ID Type</span><span class="font-semibold">{{ ucwords(str_replace('_',' ',$booking->customer->id_type)) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">ID No</span><span class="font-semibold">{{ $booking->customer->id_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Guests</span><span class="font-semibold">{{ $booking->adults }} Adults @if($booking->children > 0), {{ $booking->children }} Children @endif</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-door-open text-cyan-500 mr-2"></i>Room & Booking</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Booking #</span><span class="font-mono font-bold text-cyan-600">{{ $booking->booking_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Room</span><span class="font-bold text-2xl">{{ $booking->room->room_number }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Type</span><span class="font-semibold">{{ ucfirst($booking->room->type) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Check-In</span><span class="font-semibold">{{ $booking->check_in_date->format('d M Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Check-Out</span><span class="font-semibold">{{ $booking->check_out_date->format('d M Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Nights</span><span class="font-semibold">{{ $booking->nights }}</span></div>
                <div class="flex justify-between text-sm border-t pt-2"><span class="text-gray-500">Total</span><span class="font-bold">₹{{ number_format($booking->total_amount) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Advance Paid</span><span class="text-emerald-600 font-bold">₹{{ number_format($booking->advance_payment) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Balance Due</span><span class="{{ $booking->balance_due > 0 ? 'text-red-500' : 'text-emerald-600' }} font-bold">₹{{ number_format($booking->balance_due) }}</span></div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-5"><i class="fas fa-sign-in-alt text-emerald-500 mr-2"></i>Complete Check-In</h3>
        <form action="{{ route('checkin.process', $booking->id) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Additional Payment (₹)</label>
                    <input type="number" name="additional_payment" value="0" min="0" step="0.01" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">Leave 0 if no payment at check-in</p>
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Check-In Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any notes about the check-in..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-5 border-t border-gray-100">
                <a href="{{ route('checkin.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt mr-2"></i>Confirm Check-In</button>
            </div>
        </form>
    </div>
</div>

@if(\App\Models\Module::isEnabled('pathik'))
<div id="pathikModalCheckin" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;max-width:420px;padding:16px;">
        <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
            <div style="background:linear-gradient(135deg,#f97316,#ea580c);padding:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:20px;">&#128203;</span>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;margin:0;">Pathik Portal Autofill</h3>
                        <p style="font-size:11px;opacity:.8;margin:2px 0 0;">{{ $booking->customer->name }}</p>
                    </div>
                </div>
                <button onclick="document.getElementById('pathikModalCheckin').style.display='none'" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:26px;height:26px;border-radius:8px;cursor:pointer;">&#10005;</button>
            </div>
            <div style="padding:18px;display:flex;flex-direction:column;gap:12px;">
                <div id="pathikCheckinStatus" style="padding:11px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;font-weight:600;display:none;">
                    <i class="fas fa-check-circle" style="margin-right:6px;"></i><span id="pathikCheckinStatusText"></span>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:12px;font-size:12px;color:#64748b;">
                    Guest data, ID details, and booking dates will be sent to the Chrome extension for autofill.
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="btnSendPathikCheckin" onclick="sendToPathikCheckin()" style="flex:1;padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension
                    </button>
                    <a id="btnOpenPortalCheckin" href="https://pathik.gujarat.gov.in" target="_blank" style="display:none;flex:1;padding:10px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;text-align:center;align-items:center;justify-content:center;">
                        <i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Open Pathik Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function fillPathikCheckin() {
    document.getElementById('pathikModalCheckin').style.display = 'block';
}
function sendToPathikCheckin() {
    var btn = document.getElementById('btnSendPathikCheckin');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Sending...';
    var form = new FormData();
    var d = {
        booking_id: '{{ $booking->id }}', booking_number: '{{ $booking->booking_number }}',
        name: '{{ addslashes($booking->customer->name) }}', phone: '{{ $booking->customer->phone }}',
        email: '{{ addslashes($booking->customer->email ?? '') }}',
        address: '{{ addslashes($booking->customer->address ?? '') }}',
        city: '{{ addslashes($booking->customer->city ?? '') }}',
        state: '{{ addslashes($booking->customer->state ?? '') }}',
        country: '{{ addslashes($booking->customer->country ?? 'India') }}',
        nationality: '{{ addslashes($booking->customer->nationality ?? 'Indian') }}',
        id_type: '{{ addslashes($booking->customer->id_type ?? '') }}',
        id_number: '{{ addslashes($booking->customer->id_number ?? '') }}',
        date_of_birth: '{{ $booking->customer->date_of_birth ? $booking->customer->date_of_birth->format('Y-m-d') : '' }}',
        check_in_date: '{{ $booking->check_in_date->format('Y-m-d') }}',
        check_out_date: '{{ $booking->check_out_date->format('Y-m-d') }}',
        nights: '{{ $booking->nights }}', adults: '{{ $booking->adults }}',
        children: '{{ $booking->children }}', room_number: '{{ $booking->room->room_number }}',
        room_type: '{{ $booking->room->type }}',
    };
    Object.keys(d).forEach(function(k) { form.append(k, d[k]); });
    fetch('{{ route('pathik.pending.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: form,
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok) {
            document.getElementById('pathikCheckinStatus').style.display = 'block';
            document.getElementById('pathikCheckinStatusText').textContent = 'Data ready! Open Pathik Portal and click Autofill Now.';
            document.getElementById('btnOpenPortalCheckin').style.display = 'flex';
            btn.style.display = 'none';
        } else {
            alert('Error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
        }
    }).catch(function(e) {
        alert('Failed: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
    });
}
document.getElementById('pathikModalCheckin').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endif
@endsection
