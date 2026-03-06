@extends('layouts.admin')
@section('title','Invoice ' . $invoice->invoice_number)
@section('page-title','Invoice Details')
@section('page-subtitle',$invoice->invoice_number)
@section('content')
<div class="max-w-3xl space-y-5">
    <div class="flex items-center justify-between">
        <a href="{{ route('invoices.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back</a>
        <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn-primary text-sm"><i class="fas fa-print mr-2"></i>Print Invoice</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-6 text-white">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-2xl font-black">{{ $settings->resort_name ?? 'Azure Paradise Resort' }}</div>
                    <div class="text-slate-400 text-sm mt-1">{{ $settings->address ?? '' }}</div>
                    <div class="text-slate-400 text-sm">{{ $settings->phone ?? '' }} • {{ $settings->email ?? '' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black text-cyan-400">INVOICE</div>
                    <div class="text-slate-300 font-mono">{{ $invoice->invoice_number }}</div>
                    <div class="text-slate-400 text-sm mt-1">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Bill To</p>
                    <p class="font-bold text-gray-800 text-lg">{{ $invoice->customer->name }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->phone }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->email }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->city }}, {{ $invoice->customer->country }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Booking Details</p>
                    <p class="font-mono text-cyan-600 font-bold">{{ $invoice->booking->booking_number }}</p>
                    <p class="text-gray-600 text-sm">Room {{ $invoice->booking->room->room_number ?? '' }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->check_in_date->format('d M Y') }} → {{ $invoice->booking->check_out_date->format('d M Y') }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->nights }} night(s)</p>
                </div>
            </div>
            <table class="w-full mb-6">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Rate</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-sm">{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }} - {{ $invoice->booking->room->view ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights }} nights</td>
                        <td class="px-4 py-3 text-sm text-right">₹{{ number_format($invoice->booking->room->price_per_night ?? 0) }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($invoice->total_amount) }}</td>
                    </tr>
                </tbody>
            </table>
            @php
                $gstAmount    = ($settings && $settings->gst_number) ? $invoice->total_amount * ($settings->tax_rate / 100) : 0;
                $grandTotal   = $invoice->total_amount + $gstAmount;
                $displayBalance = max(0, $grandTotal - $invoice->paid_amount);
            @endphp
            <div class="flex justify-end">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span>₹{{ number_format($invoice->total_amount) }}</span></div>
                    @if($settings && $settings->gst_number)
                    <div class="flex justify-between text-sm"><span class="text-gray-500">GST ({{ $settings->tax_rate }}%)</span><span>₹{{ number_format($gstAmount) }}</span></div>
                    @endif
                    <div class="flex justify-between text-sm font-bold border-t pt-2"><span>Total</span><span>₹{{ number_format($grandTotal) }}</span></div>
                    <div class="flex justify-between text-sm text-emerald-600"><span>Amount Paid</span><span>₹{{ number_format($invoice->paid_amount) }}</span></div>
                    <div class="flex justify-between text-lg font-black border-t-2 border-gray-800 pt-2">
                        <span>Balance Due</span>
                        <span class="{{ $displayBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($displayBalance) }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                @php $displayStatus = $displayBalance <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'); @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold {{ $displayStatus == 'paid' ? 'bg-emerald-100 text-emerald-700' : ($displayStatus == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                    {{ strtoupper($displayStatus) }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
