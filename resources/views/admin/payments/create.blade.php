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
                    <option value="{{ $booking->id }}" {{ old('booking_id') == $booking->id ? 'selected' : '' }}>
                        {{ $booking->booking_number }} — {{ $booking->customer->name }} — Room {{ $booking->room->room_number ?? 'N/A' }}
                    </option>
                    @endforeach
                </select>
                @error('booking_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Amount (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="1" class="form-input" required>
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
                    <option value="advance">Advance</option>
                    <option value="partial">Partial</option>
                    <option value="final">Final</option>
                    <option value="refund">Refund</option>
                </select>
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..."></textarea>
            </div>
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
@endsection
