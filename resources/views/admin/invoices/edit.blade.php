@extends('layouts.admin')
@section('title','Edit Invoice ' . $invoice->invoice_number)
@section('page-title','Edit Invoice')
@section('page-subtitle',$invoice->invoice_number)
@section('content')
<div class="max-w-2xl">

    @if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 flex items-center gap-2 text-sm">
        <i class="fas fa-check-circle text-emerald-500"></i>{{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
        <div class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Please fix the following:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-5">
        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Invoice</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-yellow-50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                <i class="fas fa-edit text-amber-600"></i>
            </div>
            <div>
                <div class="font-bold text-gray-800">Edit Invoice</div>
                <div class="text-xs text-gray-500">{{ $invoice->invoice_number }} — {{ $invoice->customer?->name }}</div>
            </div>
        </div>

        <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" class="p-6 space-y-5">
            @csrf @method('PUT')

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-600">
                <div class="font-semibold text-slate-700 mb-2"><i class="fas fa-info-circle mr-1 text-slate-400"></i>Booking reference</div>
                <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs">
                    <div><span class="text-gray-400">Booking#</span> <span class="font-mono font-bold">{{ $invoice->booking->booking_number }}</span></div>
                    <div><span class="text-gray-400">Room</span> {{ $invoice->booking->is_whole_hotel ? 'Whole Hotel' : ('Room ' . ($invoice->booking->room?->room_number ?? '—')) }}</div>
                    <div><span class="text-gray-400">Check-In</span> {{ $invoice->booking->check_in_date->format('d M Y') }}</div>
                    <div><span class="text-gray-400">Check-Out</span> {{ $invoice->booking->check_out_date->format('d M Y') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date" name="issued_at" value="{{ old('issued_at', $invoice->issued_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="form-input @error('issued_at') border-red-400 @enderror" required>
                    @error('issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="form-input @error('status') border-red-400 @enderror" required>
                        <option value="paid"    {{ old('status', $invoice->status) == 'paid'    ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ old('status', $invoice->status) == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid"  {{ old('status', $invoice->status) == 'unpaid'  ? 'selected' : '' }}>Unpaid</option>
                    </select>
                    @error('status')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Total Amount (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_amount" step="0.01" min="0"
                        value="{{ old('total_amount', number_format($invoice->total_amount, 2, '.', '')) }}"
                        class="form-input @error('total_amount') border-red-400 @enderror" required>
                    @error('total_amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Amount Paid (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="paid_amount" step="0.01" min="0"
                        value="{{ old('paid_amount', number_format($invoice->paid_amount, 2, '.', '')) }}"
                        class="form-input @error('paid_amount') border-red-400 @enderror" required>
                    @error('paid_amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Balance (auto-calculated)</label>
                    <input type="text" id="balancePreview"
                        value="₹{{ number_format(max(0, $invoice->total_amount - $invoice->paid_amount), 2) }}"
                        class="form-input bg-slate-50 text-slate-500" readonly>
                    <p class="text-xs text-gray-400 mt-1">Updates as you type</p>
                </div>
            </div>

            <div>
                <label class="form-label">Adjustment Notes <span class="text-gray-400 font-normal text-xs">(reason for edit, discount details, etc.)</span></label>
                <textarea name="notes" rows="3" class="form-input" placeholder="e.g. 10% corporate discount applied, billing corrected per manager approval…">{{ old('notes', $invoice->notes) }}</textarea>
                @error('notes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @if($invoice->notes)
            <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-700">
                <span class="font-semibold">Previous notes:</span> {{ $invoice->notes }}
            </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('invoices.show', $invoice->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var total = document.querySelector('input[name="total_amount"]');
    var paid  = document.querySelector('input[name="paid_amount"]');
    var bal   = document.getElementById('balancePreview');
    function update() {
        var t = parseFloat(total.value) || 0;
        var p = parseFloat(paid.value)  || 0;
        var b = Math.max(0, t - p);
        bal.value = '₹' + b.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    total.addEventListener('input', update);
    paid.addEventListener('input', update);
})();
</script>
@endsection
