@extends('layouts.admin')
@section('title','Record Payment')
@section('page-title','Record Payment')
@section('page-subtitle','Add a new payment transaction')
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
                <select name="booking_id" class="form-input" required>
                    <option value="">Select Booking</option>
                    @foreach($bookings as $booking)
                    <option value="{{ $booking->id }}">{{ $booking->booking_number }} - {{ $booking->customer->name }}</option>
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
@endsection
