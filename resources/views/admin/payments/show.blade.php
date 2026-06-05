@extends('layouts.admin')
@section('title','Payment Receipt')
@section('page-title','Payment Receipt')
@section('page-subtitle','Transaction details')
@section('content')
<div class="max-w-xl">
    <a href="{{ route('payments.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-8 text-white text-center" style="background: linear-gradient(135deg, #1a2332, #2a3545);">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background: rgba(201,169,110,.2);">
                <i class="fas fa-check text-2xl" style="color: #c9a96e;"></i>
            </div>
            <div class="text-3xl font-black">₹{{ number_format($payment->amount) }}</div>
            <div class="mt-1" style="color: rgba(201,169,110,.7);">{{ ucfirst($payment->payment_type) }} Payment</div>
        </div>
        <div class="p-6 space-y-3">
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Transaction ID</span>
                <span class="text-sm font-mono font-bold text-gray-700">{{ $payment->transaction_id }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Guest</span>
                <span class="text-sm font-semibold">{{ $payment->booking->customer->name ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Booking</span>
                <span class="text-sm font-mono" style="color: #c9a96e;">{{ $payment->booking->booking_number ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Room</span>
                <span class="text-sm font-semibold">{{ $payment->booking->room?->room_number ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Method</span>
                <span class="text-sm font-semibold">{{ ucfirst($payment->payment_method) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Date & Time</span>
                <span class="text-sm">{{ $payment->created_at->format('d M Y, h:i A') }}</span>
            </div>
            @if($payment->notes)
            <div class="flex justify-between py-2">
                <span class="text-sm text-gray-500">Notes</span>
                <span class="text-sm">{{ $payment->notes }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
