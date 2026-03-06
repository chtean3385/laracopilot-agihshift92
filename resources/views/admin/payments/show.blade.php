@extends('layouts.admin')
@section('title','Payment Receipt')
@section('page-title','Payment Receipt')
@section('page-subtitle','Transaction details')
@section('content')
<div class="max-w-xl">
    <a href="{{ route('payments.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-8 text-white text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <div class="text-3xl font-black">₹{{ number_format($payment->amount) }}</div>
            <div class="text-emerald-100 mt-1">{{ ucfirst($payment->payment_type) }} Payment</div>
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
                <span class="text-sm font-mono text-cyan-600">{{ $payment->booking->booking_number ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Room</span>
                <span class="text-sm font-semibold">{{ $payment->booking->room->room_number ?? 'N/A' }}</span>
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
