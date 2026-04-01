@extends('layouts.admin')
@section('title','Record Payment')
@section('page-title','Record Payment')
@section('page-subtitle','Add a new payment transaction')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" rel="stylesheet">
<style>
    .ts-wrapper { position: relative; }
    .ts-control {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0 40px 0 14px;
        min-height: 44px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        box-shadow: none;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #06b6d4;
        box-shadow: 0 0 0 3px rgba(6,182,212,.12);
        outline: none;
    }
    .ts-control input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        flex: 1;
        font-size: 14px;
        color: #374151;
        padding: 0 !important;
        margin: 0 !important;
        min-width: 60px;
        height: auto;
    }
    .ts-control .item {
        font-size: 14px;
        color: #374151;
        line-height: 1;
    }
    .ts-control::after {
        content: '';
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #9ca3af;
        pointer-events: none;
    }
    .ts-wrapper.open .ts-control::after { border-top: none; border-bottom: 5px solid #06b6d4; }
    .ts-wrapper.open .ts-control { border-color: #06b6d4; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    .ts-dropdown {
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        z-index: 9999;
        background: #fff;
        border: 1.5px solid #06b6d4;
        border-top: none;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1);
        overflow: hidden;
    }
    .ts-dropdown .ts-dropdown-content { max-height: 220px; overflow-y: auto; }
    .ts-dropdown-content::-webkit-scrollbar { width: 5px; }
    .ts-dropdown-content::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 13.5px;
        color: #374151;
        cursor: pointer;
        border-bottom: 1px solid #f9fafb;
        transition: background .1s;
    }
    .ts-dropdown .option:last-child { border-bottom: none; }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active { background: #f0fdfe; color: #0891b2; }
    .ts-dropdown .option.selected { background: #cffafe; color: #0e7490; font-weight: 500; }
    .ts-dropdown .no-results { padding: 12px 14px; font-size: 13px; color: #9ca3af; text-align: center; }
</style>
@endpush

@section('content')
<div class="max-w-xl">
    <a href="{{ route('payments.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800"><i class="fas fa-credit-card text-emerald-500 mr-2"></i>Payment Details</h3>
        </div>
        <form action="{{ route('payments.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="form-label">Booking <span class="text-red-500">*</span></label>
                <select name="booking_id" id="bookingSelect" required>
                    <option value="">Search by booking number or guest name...</option>
                    @foreach($bookings as $booking)
                    <option value="{{ $booking->id }}" {{ (old('booking_id', $prefillBookingId) == $booking->id) ? 'selected' : '' }}>
                        {{ $booking->booking_number }} — {{ $booking->customer?->name ?? '(Deleted Guest)' }} — Room {{ $booking->room->room_number ?? 'N/A' }} ({{ ucfirst($booking->status) }})
                    </option>
                    @endforeach
                </select>
                @error('booking_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Amount (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" value="{{ old('amount', $prefillAmount) }}" step="0.01" min="1" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_method" class="form-input" required>
                    <option value="cash">Cash</option>
                    <option value="card">Credit/Debit Card</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div>
                <label class="form-label">Payment Type <span class="text-red-500">*</span></label>
                <select name="payment_type" class="form-input" required>
                    <option value="advance" {{ old('payment_type', $prefillBookingId ? 'final' : 'advance') == 'advance' ? 'selected' : '' }}>Advance</option>
                    <option value="partial" {{ old('payment_type') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="final" {{ old('payment_type', $prefillBookingId ? 'final' : '') == 'final' ? 'selected' : '' }}>Final</option>
                    <option value="refund" {{ old('payment_type') == 'refund' ? 'selected' : '' }}>Refund</option>
                </select>
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..."></textarea>
            </div>
            @if(\App\Models\Module::isEnabled('payment_links'))
            <div class="border border-violet-100 bg-violet-50 rounded-xl p-4 space-y-3">
                <p class="text-xs font-bold text-violet-600 uppercase tracking-wide"><i class="fas fa-bolt mr-1"></i>Digital Payment Options</p>
                <div class="flex gap-3 flex-wrap">
                    <button type="button" onclick="pmShowUpiQr()"
                        class="inline-flex items-center gap-2 bg-violet-500 hover:bg-violet-600 text-white px-4 py-2 rounded-xl font-semibold text-sm transition-all">
                        <i class="fas fa-qrcode"></i>Show UPI QR
                    </button>
                    <button type="button" id="pmRzpBtn" onclick="pmCreateRzpLink()"
                        class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold text-sm transition-all">
                        <i class="fas fa-link"></i>Send Razorpay Link
                    </button>
                </div>
                <p class="text-xs text-violet-500">UPI QR: guest scans and pays in person · Razorpay: send a link remotely</p>
            </div>
            @endif

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('payments.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Record Payment</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    new TomSelect('#bookingSelect', {
        allowEmptyOption: false,
        placeholder: 'Search by booking number or guest name...',
        maxOptions: 300,
    });
</script>
@endpush

@if(\App\Models\Module::isEnabled('payment_links'))
{{-- UPI QR Modal --}}
<div id="pmUpiModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4" style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-violet-500 to-purple-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-qrcode text-white text-xl"></i>
                <div>
                    <h3 class="font-bold text-white">UPI Payment</h3>
                    <p class="text-violet-200 text-xs">Guest scans to pay instantly</p>
                </div>
            </div>
            <button onclick="pmCloseUpi()" class="text-white/70 hover:text-white text-xl font-bold">&times;</button>
        </div>
        <div id="pmUpiBody" class="p-6 text-center">
            <div class="flex items-center justify-center h-32">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-violet-500 border-t-transparent"></div>
            </div>
        </div>
    </div>
</div>

{{-- Razorpay Modal --}}
<div id="pmRzpModal" class="fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4" style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-link text-white text-xl"></i>
                <div>
                    <h3 class="font-bold text-white">Razorpay Payment Link</h3>
                    <p class="text-blue-200 text-xs">Send link to guest for remote payment</p>
                </div>
            </div>
            <button onclick="pmCloseRzp()" class="text-white/70 hover:text-white text-xl font-bold">&times;</button>
        </div>
        <div id="pmRzpBody" class="p-6">
            <div class="flex items-center justify-center h-20">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-blue-500 border-t-transparent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function pmShowUpiQr() {
    var amtInput = document.querySelector('input[name="amount"]');
    var amt      = parseFloat(amtInput ? amtInput.value : 0) || 0;
    document.getElementById('pmUpiModal').style.display = 'flex';
    document.getElementById('pmUpiBody').innerHTML = '<div class="flex items-center justify-center h-32"><div class="animate-spin rounded-full h-10 w-10 border-2 border-violet-500 border-t-transparent"></div></div>';

    fetch('/payment-links/upi-config', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(cfg => {
            if (cfg.error) {
                document.getElementById('pmUpiBody').innerHTML = '<p class="text-red-500 font-semibold py-6">' + cfg.error + '<br><a href="/payment-links/config" class="text-violet-500 underline text-sm mt-1 block">Configure Payment Links</a></p>';
                return;
            }
            var note   = 'Advance Payment';
            var upiUrl = 'upi://pay?pa=' + encodeURIComponent(cfg.upi_id)
                       + '&pn=' + encodeURIComponent(cfg.upi_name)
                       + '&am=' + amt.toFixed(2)
                       + '&cu=INR'
                       + '&tn=' + encodeURIComponent(note);
            document.getElementById('pmUpiBody').innerHTML =
                '<div id="pmQrCanvas" style="width:208px;height:208px;margin:0 auto;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;"></div>' +
                '<p class="mt-4 text-xl font-black text-gray-800">₹' + amt.toLocaleString('en-IN') + '</p>' +
                '<p class="text-sm text-gray-500 mt-1">' + cfg.upi_name + '</p>' +
                '<p class="text-xs text-gray-400 font-mono mt-0.5">' + cfg.upi_id + '</p>' +
                '<p class="text-xs text-gray-400 mt-3">GPay · PhonePe · Paytm · any UPI app</p>' +
                '<button onclick="pmCloseUpi()" class="mt-3 w-full py-2 text-sm font-semibold text-gray-500 bg-gray-50 rounded-xl hover:bg-gray-100 transition">Close</button>';
            pmLoadQrLib(function() {
                new QRCode(document.getElementById('pmQrCanvas'), {
                    text: upiUrl, width: 208, height: 208,
                    colorDark: '#1e293b', colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        })
        .catch(() => {
            document.getElementById('pmUpiBody').innerHTML = '<p class="text-red-500 py-6">Failed to load. Please try again.</p>';
        });
}

function pmLoadQrLib(cb) {
    if (window.QRCode) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    s.onload = cb;
    document.head.appendChild(s);
}

function pmCloseUpi() {
    document.getElementById('pmUpiModal').style.display = 'none';
}

function pmCreateRzpLink() {
    var bookingSelect = document.getElementById('bookingSelect');
    var amtInput      = document.querySelector('input[name="amount"]');
    var bookingId     = bookingSelect ? bookingSelect.value : '';
    var amt           = parseFloat(amtInput ? amtInput.value : 0) || 0;

    if (!bookingId) { alert('Please select a booking first.'); return; }
    if (amt <= 0)   { alert('Please enter a valid amount first.'); return; }

    document.getElementById('pmRzpModal').style.display = 'flex';
    document.getElementById('pmRzpBody').innerHTML = '<div class="flex flex-col items-center justify-center py-6 gap-3"><div class="animate-spin rounded-full h-10 w-10 border-2 border-blue-500 border-t-transparent"></div><p class="text-gray-500 text-sm">Creating payment link…</p></div>';

    fetch('/payment-links/booking/' + bookingId + '/razorpay', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ amount: amt, note: 'Advance Payment' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            document.getElementById('pmRzpBody').innerHTML = '<p class="text-red-500 font-semibold py-4 text-center">' + data.error + '<br><a href="/payment-links/config" class="text-blue-500 underline text-sm mt-1 block">Configure Razorpay</a></p>';
            return;
        }
        pmShowRzpLink(data.link);
    })
    .catch(() => {
        document.getElementById('pmRzpBody').innerHTML = '<p class="text-red-500 text-center py-4">Failed to create link. Please try again.</p>';
    });
}

function pmShowRzpLink(url) {
    document.getElementById('pmRzpBody').innerHTML =
        '<div class="space-y-4">' +
        '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center"><i class="fas fa-check-circle text-emerald-500 text-xl mb-1"></i><p class="font-bold text-emerald-700 text-sm">Payment Link Created!</p></div>' +
        '<div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-center gap-2">' +
        '<input type="text" value="' + url + '" id="pmRzpLinkInput" readonly class="flex-1 bg-transparent text-xs font-mono text-gray-700 outline-none truncate">' +
        '<button onclick="pmCopyRzpLink()" class="flex-shrink-0 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold"><i class="fas fa-copy mr-1"></i>Copy</button>' +
        '</div>' +
        '<a href="https://wa.me/?text=' + encodeURIComponent('Please complete your payment: ' + url) + '" target="_blank" class="flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white w-full py-2.5 rounded-xl font-semibold text-sm">' +
        '<i class="fab fa-whatsapp"></i>Share via WhatsApp</a>' +
        '<button onclick="pmCloseRzp()" class="w-full py-2 text-sm text-gray-500 hover:text-gray-700">Close</button>' +
        '</div>';
}

function pmCopyRzpLink() {
    var input = document.getElementById('pmRzpLinkInput');
    navigator.clipboard.writeText(input.value).then(() => {
        var btn = input.nextElementSibling;
        btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
        btn.classList.replace('bg-blue-500','bg-emerald-500');
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy'; btn.classList.replace('bg-emerald-500','bg-blue-500'); }, 2000);
    });
}

function pmCloseRzp() {
    document.getElementById('pmRzpModal').style.display = 'none';
}

document.getElementById('pmUpiModal').addEventListener('click', function(e) { if(e.target===this) pmCloseUpi(); });
document.getElementById('pmRzpModal').addEventListener('click', function(e) { if(e.target===this) pmCloseRzp(); });
</script>
@endif
@endsection
