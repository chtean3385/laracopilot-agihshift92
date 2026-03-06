@extends('layouts.admin')
@section('title','Record Payment')
@section('page-title','Record Payment')
@section('page-subtitle','Add a new payment transaction')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-wrapper.form-control, .ts-wrapper.form-select { padding: 0; }
    .ts-control {
        border: 1.5px solid #e5e7eb !important;
        border-radius: 10px !important;
        padding: 10px 16px !important;
        font-size: 14px !important;
        color: #374151 !important;
        box-shadow: none !important;
        min-height: 44px !important;
    }
    .ts-wrapper.focus .ts-control { border-color: #06b6d4 !important; box-shadow: 0 0 0 3px rgba(6,182,212,.1) !important; }
    .ts-dropdown { border: 1.5px solid #e5e7eb !important; border-radius: 12px !important; box-shadow: 0 8px 24px rgba(0,0,0,.1) !important; margin-top: 4px !important; overflow: hidden; }
    .ts-dropdown .option { padding: 10px 16px; font-size: 13.5px; color: #374151; }
    .ts-dropdown .option:hover, .ts-dropdown .option.active { background: linear-gradient(90deg,rgba(6,182,212,.08),rgba(59,130,246,.06)) !important; color: #0f172a !important; }
    .ts-dropdown .option.selected { background: linear-gradient(90deg,rgba(6,182,212,.15),rgba(59,130,246,.1)) !important; }
    .ts-dropdown input { border: none !important; border-bottom: 1.5px solid #f1f5f9 !important; border-radius: 0 !important; padding: 10px 16px !important; font-size: 13px !important; }
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
                <select name="booking_id" id="bookingSelect" class="form-input" required placeholder="Search by booking number or guest name...">
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
        placeholder: 'Search by booking number or guest name...',
        allowEmptyOption: true,
        maxOptions: 200,
    });
</script>
@endpush
@endsection
