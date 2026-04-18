<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .invoice-wrap { box-shadow: none !important; border: none !important; }
        }
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- ── Action bar (hidden when printing) ── --}}
    <div class="no-print sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
        <div class="max-w-3xl mx-auto flex items-center justify-between gap-3 flex-wrap">
            <a id="btn-back" href="{{ route('invoices.show', $invoice->id) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">
                ← Back
            </a>
            <div class="flex items-center gap-3 flex-wrap">
                <button id="btn-print" onclick="window.print()"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#1e293b;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                    🖨️ Print
                </button>
                <button id="btn-download" onclick="downloadPDF()" title="Download invoice as a PDF file"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                    ⬇️ Download PDF
                </button>
            </div>
        </div>
        {{-- WebView tip (shown only in WebView) --}}
        <div id="webview-tip" style="display:none;" class="max-w-3xl mx-auto mt-2 px-3 py-2 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-700">
            📱 Tap <strong>Download PDF</strong> to save the invoice to your device.
        </div>
    </div>

    {{-- ── Invoice ── --}}
    <div class="max-w-3xl mx-auto px-3 py-6 sm:px-6 sm:py-8">
    <div class="invoice-wrap bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Dark header --}}
        <div class="bg-slate-800 text-white px-4 py-5 sm:px-8 sm:py-6">
            <div class="flex justify-between items-start flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    @if($settings && $settings->logo && file_exists(public_path('storage/' . $settings->logo)))
                    <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center p-1 flex-shrink-0">
                        <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                    </div>
                    @endif
                    <div>
                        <div class="text-lg sm:text-xl font-black">{{ $settings->resort_name ?? 'Azure Paradise Resort' }}</div>
                        @if($settings && $settings->tagline)<div class="text-cyan-400 text-xs font-semibold mb-1">{{ $settings->tagline }}</div>@endif
                        <div class="text-slate-400 text-xs sm:text-sm">{{ $settings->address ?? '' }}</div>
                        <div class="text-slate-400 text-xs sm:text-sm">{{ $settings->phone ?? '' }}</div>
                        @if($settings && $settings->gst_number)<div class="text-slate-400 text-xs mt-1">GST: {{ $settings->gst_number }}</div>@endif
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-xl sm:text-2xl font-black text-cyan-400">INVOICE</div>
                    <div class="text-slate-300 font-mono text-sm">{{ $invoice->invoice_number }}</div>
                    <div class="text-slate-400 text-xs mt-1">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-4 sm:p-8">

            {{-- Bill To / Booking Details --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 mb-6">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Bill To</p>
                    <p class="font-bold text-gray-800">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->phone }}</p>
                    <p class="text-gray-500 text-sm">{{ $invoice->customer->city }}, {{ $invoice->customer->country }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Booking</p>
                    <p class="font-mono text-cyan-600 text-sm font-bold">{{ $invoice->booking->booking_number }}</p>
                    <p class="text-gray-600 text-sm">Room {{ $invoice->booking->room->room_number ?? '' }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->check_in_date->format('d M Y') }} → {{ $invoice->booking->check_out_date->format('d M Y') }}</p>
                    <p class="text-gray-600 text-sm">{{ $invoice->booking->nights }} night(s)</p>
                </div>
            </div>

            {{-- Line items table --}}
            <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full mb-6 border border-gray-200 min-w-[420px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2 text-xs text-gray-500 uppercase">Description</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Qty</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Rate</th>
                        <th class="text-right px-4 py-2 text-xs text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $prtExtraTotal = $invoice->booking->extraCharges->sum('total_price');
                        if ($invoice->booking->price_overridden) {
                            $prtRoomCost = max(0, (float)$invoice->booking->total_amount - $prtExtraTotal);
                        } else {
                            $prtRoomCost = ($invoice->booking->nights ?? 0) * ($invoice->booking->room->price_per_night ?? 0);
                        }
                    @endphp
                    <tr class="border-t border-gray-200">
                        <td class="px-4 py-3 text-sm">{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($invoice->booking->price_overridden)₹{{ number_format($prtRoomCost) }}@else₹{{ number_format($invoice->booking->room->price_per_night ?? 0) }}@endif
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($prtRoomCost) }}</td>
                    </tr>
                    @if($invoice->booking->meal_cost > 0)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 text-sm">
                            Meal Plan —
                            @if($invoice->booking->meal_breakfast) Breakfast @endif
                            @if($invoice->booking->meal_lunch) Lunch @endif
                            @if($invoice->booking->meal_dinner) Dinner @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights }}</td>
                        <td class="px-4 py-3 text-sm text-right">—</td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($invoice->booking->meal_cost) }}</td>
                    </tr>
                    @endif
                    @if($invoice->booking->extra_beds > 0)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 text-sm">Extra Beds × {{ $invoice->booking->extra_beds }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $invoice->booking->nights }}</td>
                        <td class="px-4 py-3 text-sm text-right">₹{{ number_format($invoice->booking->room->extra_bed_price ?? 0) }}/bed</td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($invoice->booking->extra_bed_cost) }}</td>
                    </tr>
                    @endif
                    @if($invoice->booking->extraCharges->count() > 0)
                    <tr class="border-t border-gray-100">
                        <td colspan="4" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-700">
                            Food Bill
                        </td>
                    </tr>
                    @foreach($invoice->booking->extraCharges as $xCharge)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 text-sm">
                            {{ $xCharge->name }}
                            @if($xCharge->notes)<span class="text-gray-400 text-xs ml-1">({{ $xCharge->notes }})</span>@endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($xCharge->quantity, ($xCharge->quantity == intval($xCharge->quantity) ? 0 : 2)) }}</td>
                        <td class="px-4 py-3 text-sm text-right">₹{{ number_format($xCharge->unit_price) }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-right">₹{{ number_format($xCharge->total_price) }}</td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
            </div>{{-- /overflow-x:auto --}}

            @php
                $prtSubtotal  = $invoice->booking->price_overridden
                    ? (float) $invoice->booking->total_amount
                    : (float) $invoice->total_amount;
                $gstAmount    = ($settings && $settings->gst_number) ? $prtSubtotal * ($settings->tax_rate / 100) : 0;
                $grandTotal   = $prtSubtotal + $gstAmount;
                $displayBalance = max(0, $grandTotal - $invoice->paid_amount);
                $overpayment    = max(0, $invoice->paid_amount - $grandTotal);
            @endphp

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-full sm:w-56 text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>₹{{ number_format($prtSubtotal) }}</span></div>
                    @if($settings && $settings->gst_number)
                    <div class="flex justify-between"><span class="text-gray-500">GST ({{ $settings->tax_rate }}%)</span><span>₹{{ number_format($gstAmount) }}</span></div>
                    @endif
                    <div class="flex justify-between border-t pt-1"><span class="font-bold">Total</span><span class="font-bold">₹{{ number_format($grandTotal) }}</span></div>
                    <div class="flex justify-between text-emerald-600"><span>Amount Paid</span><span class="font-bold">₹{{ number_format($invoice->paid_amount) }}</span></div>
                    @if($overpayment > 0)
                    <div class="flex justify-between text-purple-600"><span>Overpayment / Credit Due</span><span class="font-bold">₹{{ number_format($overpayment) }}</span></div>
                    @endif
                    <div class="flex justify-between border-t pt-1 font-black text-base">
                        <span>Balance Due</span>
                        <span class="{{ $displayBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($displayBalance) }}</span>
                    </div>
                </div>
            </div>

            @if($invoice->booking->special_requests)
            <div class="mt-5 pt-4 border-t border-gray-100">
                <p class="text-xs font-bold text-amber-700 uppercase mb-1">Special Requests</p>
                <p class="text-sm text-gray-600">{{ $invoice->booking->special_requests }}</p>
            </div>
            @endif

            @if($invoice->booking->checkin_notes || $invoice->booking->checkout_notes)
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <span class="px-4 py-1 rounded-full text-xs font-bold {{ $displayStatus == 'paid' ? 'bg-emerald-100 text-emerald-700' : ($displayStatus == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                    {{ strtoupper($displayStatus) }}
                </span>
                @if($overpayment > 0)<p class="text-xs text-purple-500 mt-1">Credit of ₹{{ number_format($overpayment) }} to be refunded.</p>@endif
            </div>

            <div class="mt-8 pt-4 border-t border-gray-100 text-center text-xs text-gray-400">
                Thank you for staying at {{ $settings->resort_name ?? 'Azure Paradise Resort' }}. We hope to see you again!
            </div>
        </div>
    </div>
    </div>

<script>
function isNativeWebView() {
    if (window.flutter_inappwebview) return true;
    if (window.ReactNativeWebView) return true;
    var ua = navigator.userAgent || '';
    if (/Android/.test(ua) && /wv\)/.test(ua)) return true;
    if (/Android/.test(ua) && !/Chrome\//.test(ua)) return true;
    return false;
}

async function downloadPDF() {
    const btn = document.getElementById('btn-download');
    const actionBar = document.querySelector('.no-print');
    btn.disabled = true;
    btn.innerHTML = '⏳ Generating…';
    if (actionBar) actionBar.style.visibility = 'hidden';
    try {
        const invoice = document.querySelector('.invoice-wrap');
        const canvas = await html2canvas(invoice, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false
        });
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const pdfW  = pdf.internal.pageSize.getWidth();
        const pdfH  = pdf.internal.pageSize.getHeight();
        const imgH  = (canvas.height * pdfW) / canvas.width;
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        if (imgH <= pdfH) {
            pdf.addImage(imgData, 'JPEG', 0, 0, pdfW, imgH);
        } else {
            let yRemain = imgH;
            let yPos    = 0;
            pdf.addImage(imgData, 'JPEG', 0, 0, pdfW, imgH);
            yRemain -= pdfH;
            while (yRemain > 0) {
                yPos -= pdfH;
                pdf.addPage();
                pdf.addImage(imgData, 'JPEG', 0, yPos, pdfW, imgH);
                yRemain -= pdfH;
            }
        }
        pdf.save('Invoice-{{ $invoice->invoice_number }}.pdf');
    } catch (e) {
        alert('PDF generation failed. Please use Print → Save as PDF instead.');
    } finally {
        if (actionBar) actionBar.style.visibility = '';
        btn.disabled = false;
        btn.innerHTML = '⬇️ Save as PDF';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (isNativeWebView()) {
        var tip = document.getElementById('webview-tip');
        if (tip) tip.style.display = 'block';
        setTimeout(function () {
            window.print();
        }, 800);
    }
});
</script>
</body>
</html>
