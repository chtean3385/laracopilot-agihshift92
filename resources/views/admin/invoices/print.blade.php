<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display:none; } body { -webkit-print-color-adjust: exact; } }</style>
</head>
<body class="bg-white p-8 max-w-3xl mx-auto">
    <div class="no-print mb-6">
        <button onclick="window.print()" class="bg-slate-800 text-white px-6 py-2 rounded-lg mr-3">Print</button>
        <a href="{{ route('invoices.show', $invoice->id) }}" class="text-gray-600 hover:underline">← Back</a>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-slate-800 text-white px-8 py-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-4">
                    @if($settings && $settings->logo && file_exists(public_path('storage/' . $settings->logo)))
                    <div class="w-16 h-16 bg-white rounded-xl flex items-center justify-center p-1 flex-shrink-0">
                        <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                    </div>
                    @endif
                    <div>
                        <div class="text-xl font-black">{{ $settings->resort_name ?? 'Azure Paradise Resort' }}</div>
                        @if($settings && $settings->tagline)<div class="text-cyan-400 text-xs font-semibold mb-1">{{ $settings->tagline }}</div>@endif
                        <div class="text-slate-400 text-sm">{{ $settings->address ?? '' }}</div>
                        <div class="text-slate-400 text-sm">{{ $settings->phone ?? '' }}</div>
                        @if($settings && $settings->gst_number)<div class="text-slate-400 text-xs mt-1">GST: {{ $settings->gst_number }}</div>@endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-cyan-400">INVOICE</div>
                    <div class="text-slate-300 font-mono text-sm">{{ $invoice->invoice_number }}</div>
                    <div class="text-slate-400 text-xs">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-2 gap-8 mb-6">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Bill To</p>
                    <p class="font-bold text-gray-800">{{ $invoice->customer->name }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->phone }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->city }}, {{ $invoice->customer->country }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Booking</p>
                    <p class="font-mono text-cyan-600 text-sm font-bold">{{ $invoice->booking->booking_number }}</p>
                    <p class="text-gray-600 text-sm">Room {{ $invoice->booking->room->room_number ?? '' }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->check_in_date->format('d M Y') }} → {{ $invoice->booking->check_out_date->format('d M Y') }}</p>
                </div>
            </div>
            <table class="w-full mb-6 border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Description</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Nights</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Rate</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t border-gray-200">
                        <td class="px-4 py-3 text-sm">{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights }}</td>
                        <td class="px-4 py-3 text-sm text-right">₹{{ number_format($invoice->booking->room->price_per_night ?? 0) }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($invoice->total_amount) }}</td>
                    </tr>
                </tbody>
            </table>
            @php
                $gstAmount    = ($settings && $settings->gst_number) ? $invoice->total_amount * ($settings->tax_rate / 100) : 0;
                $grandTotal   = $invoice->total_amount + $gstAmount;
                $displayBalance = max(0, $grandTotal - $invoice->paid_amount);
                $overpayment    = max(0, $invoice->paid_amount - $grandTotal);
            @endphp
            <div class="flex justify-end">
                <div class="w-56 text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>₹{{ number_format($invoice->total_amount) }}</span></div>
                    @if($settings && $settings->gst_number)
                    <div class="flex justify-between"><span class="text-gray-500">GST ({{ $settings->tax_rate }}%)</span><span>₹{{ number_format($gstAmount) }}</span></div>
                    @endif
                    <div class="flex justify-between border-t pt-1"><span class="font-bold">Total</span><span class="font-bold">₹{{ number_format($grandTotal) }}</span></div>
                    <div class="flex justify-between text-emerald-600"><span>Amount Paid</span><span class="font-bold">₹{{ number_format($invoice->paid_amount) }}</span></div>
                    @if($overpayment > 0)
                    <div class="flex justify-between text-purple-600"><span>Overpayment / Credit Due</span><span class="font-bold">₹{{ number_format($overpayment) }}</span></div>
                    @endif
                    <div class="flex justify-between border-t pt-1 font-black text-base"><span>Balance Due</span><span class="{{ $displayBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($displayBalance) }}</span></div>
                </div>
            </div>
            @if($invoice->booking->special_requests)
            <div class="mt-5 pt-4 border-t border-gray-100">
                <p class="text-xs font-bold text-amber-700 uppercase mb-1">Special Requests</p>
                <p class="text-sm text-gray-600">{{ $invoice->booking->special_requests }}</p>
            </div>
            @endif
            @if($invoice->booking->checkin_notes || $invoice->booking->checkout_notes)
            <div class="mt-3 grid grid-cols-2 gap-3">
                @if($invoice->booking->checkin_notes)
                <div><p class="text-xs font-bold text-blue-600 uppercase mb-1">Check-In Notes</p><p class="text-xs text-gray-600">{{ $invoice->booking->checkin_notes }}</p></div>
                @endif
                @if($invoice->booking->checkout_notes)
                <div><p class="text-xs font-bold text-slate-600 uppercase mb-1">Check-Out Notes</p><p class="text-xs text-gray-600">{{ $invoice->booking->checkout_notes }}</p></div>
                @endif
            </div>
            @endif
            <div class="mt-6 text-center">
                @php $displayStatus = $displayBalance <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'); @endphp
                <span class="px-4 py-1 rounded-full text-xs font-bold {{ $displayStatus == 'paid' ? 'bg-emerald-100 text-emerald-700' : ($displayStatus == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ strtoupper($displayStatus) }}</span>
                @if($overpayment > 0)<p class="text-xs text-purple-500 mt-1">Credit of ₹{{ number_format($overpayment) }} to be refunded.</p>@endif
            </div>
            <div class="mt-8 pt-4 border-t border-gray-100 text-center text-xs text-gray-400">
                Thank you for staying at {{ $settings->resort_name ?? 'Azure Paradise Resort' }}. We hope to see you again!
            </div>
        </div>
    </div>
</body>
</html>
