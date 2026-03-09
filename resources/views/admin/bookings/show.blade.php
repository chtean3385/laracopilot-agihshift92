@extends('layouts.admin')
@section('title', 'Booking ' . $booking->booking_number)
@section('page-title', 'Booking Details')
@section('page-subtitle', $booking->booking_number)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('bookings.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Bookings</a>
        <div class="flex gap-2">
            @if($booking->status == 'confirmed')
                <a href="{{ route('checkin.show', $booking->id) }}" class="btn-primary text-sm"><i class="fas fa-sign-in-alt mr-2"></i>Process Check-In</a>
            @endif
            @if($booking->status == 'checked_in')
                <a href="{{ route('checkout.show', $booking->id) }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-600 text-sm"><i class="fas fa-sign-out-alt mr-2"></i>Process Check-Out</a>
            @endif
            @if($booking->invoice)
                <a href="{{ route('invoices.show', $booking->invoice->id) }}" class="btn-secondary text-sm"><i class="fas fa-file-invoice mr-2"></i>View Invoice</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Booking Info -->
        <div class="space-y-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">Booking Info</h3>
                    <span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_',' ', $booking->status)) }}</span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Booking #</span><span class="text-sm font-mono font-bold text-cyan-600">{{ $booking->booking_number }}</span></div>
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Check-In</span><span class="text-sm font-semibold">{{ $booking->check_in_date->format('d M Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Check-Out</span><span class="text-sm font-semibold">{{ $booking->check_out_date->format('d M Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Nights</span><span class="text-sm font-semibold">{{ $booking->nights }}</span></div>
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Guests</span><span class="text-sm font-semibold">{{ $booking->adults }} Adults @if($booking->children > 0), {{ $booking->children }} Children @endif</span></div>
                </div>
                @if($booking->special_requests)
                <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-xs font-semibold text-amber-700"><i class="fas fa-star mr-1"></i>Special Requests</p>
                    <p class="text-sm text-amber-600 mt-1">{{ $booking->special_requests }}</p>
                </div>
                @endif
                @if($booking->checkin_notes)
                <div class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-xs font-semibold text-blue-700"><i class="fas fa-sign-in-alt mr-1"></i>Check-In Notes</p>
                    <p class="text-sm text-blue-600 mt-1">{{ $booking->checkin_notes }}</p>
                </div>
                @endif
                @if($booking->checkout_notes)
                <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <p class="text-xs font-semibold text-slate-600"><i class="fas fa-sign-out-alt mr-1"></i>Check-Out Notes</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $booking->checkout_notes }}</p>
                </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Guest</h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">{{ substr($booking->customer->name, 0, 1) }}</div>
                    <div>
                        <div class="font-semibold text-gray-800">{{ $booking->customer->name }}</div>
                        <div class="text-sm text-gray-400">{{ $booking->customer->phone }}</div>
                    </div>
                </div>
                <a href="{{ route('customers.show', $booking->customer_id) }}" class="text-cyan-600 hover:underline text-sm"><i class="fas fa-external-link-alt mr-1"></i>View Guest Profile</a>
            </div>
        </div>

        <!-- Room & Payment -->
        <div class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Room</h3>
                    <div class="text-4xl font-black text-gray-800 mb-1">{{ $booking->room->room_number }}</div>
                    <span class="badge-{{ $booking->room->type_color }}">{{ ucfirst($booking->room->type) }}</span>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Floor</span><span class="font-medium">{{ $booking->room->floor }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">View</span><span class="font-medium">{{ $booking->room->view }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Rate/Night</span><span class="font-bold text-emerald-600">₹{{ number_format($booking->room->price_per_night) }}</span></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Payment Summary</h3>
                    @php
                        $bSettings   = \App\Models\Setting::first();
                        $bTaxRate    = ($bSettings && $bSettings->gst_number && $bSettings->tax_rate > 0) ? (float) $bSettings->tax_rate : 0;
                        $bGst        = round($booking->total_amount * ($bTaxRate / 100), 2);
                        $bGrandTotal = $booking->total_amount + $bGst;
                        $bTotalPaid  = $booking->payments->where('status','completed')->sum('amount');
                        $bBalance    = max(0, $bGrandTotal - $bTotalPaid);
                        $bOverpaid   = max(0, $bTotalPaid - $bGrandTotal);
                    @endphp
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ $booking->nights }} nights × ₹{{ number_format($booking->room->price_per_night) }}</span><span class="font-medium">₹{{ number_format($booking->total_amount) }}</span></div>
                        @if($bTaxRate > 0)
                        <div class="flex justify-between text-sm"><span class="text-gray-500">GST ({{ $bTaxRate }}%)</span><span class="text-gray-600">₹{{ number_format($bGst) }}</span></div>
                        @endif
                        <div class="flex justify-between text-sm border-t pt-2"><span class="text-gray-500">Grand Total</span><span class="font-bold text-gray-800">₹{{ number_format($bGrandTotal) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total Paid</span><span class="font-semibold text-emerald-600">₹{{ number_format($bTotalPaid) }}</span></div>
                        @if($bOverpaid > 0)
                        <div class="flex justify-between text-sm"><span class="text-gray-500 text-violet-600">Overpayment/Credit</span><span class="font-semibold text-violet-600">₹{{ number_format($bOverpaid) }}</span></div>
                        @endif
                        <div class="flex justify-between text-sm border-t pt-2"><span class="font-semibold">Balance Due</span><span class="font-bold {{ $bBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($bBalance) }}</span></div>
                    </div>
                    <div class="mt-3"><span class="badge-{{ $booking->payment_status_color }}">{{ ucfirst($booking->payment_status) }}</span></div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Payment History</h3>
                    <a href="{{ route('payments.create') }}" class="text-cyan-600 hover:underline text-sm">+ Add Payment</a>
                </div>
                @if($booking->payments->count() > 0)
                <div class="divide-y divide-gray-50">
                    @foreach($booking->payments as $payment)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-700">{{ ucfirst($payment->payment_type) }} Payment</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($payment->payment_method) }} • {{ $payment->created_at->format('d M Y h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-emerald-600">₹{{ number_format($payment->amount) }}</div>
                            <div class="text-xs font-mono text-gray-400">{{ $payment->transaction_id }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-400 text-sm">No payments recorded</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
