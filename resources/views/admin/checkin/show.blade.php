@extends('layouts.admin')
@section('title','Process Check-In')
@section('page-title','Process Check-In')
@section('page-subtitle','Confirm arrival for ' . $booking->customer->name)
@section('content')
<div class="max-w-3xl space-y-5">
    <a href="{{ route('checkin.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
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
@endsection
