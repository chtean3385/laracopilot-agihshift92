<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Tax Invoice {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        @media print {
            @page { size: A4; margin: 0; }
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; margin: 0; padding: 0; background: #fff; }
            .page-wrap { box-shadow: none !important; margin: 0 !important; border: none !important; border-radius: 0 !important; }
            .logo-badge { display: none !important; }
            .outer-table { border-left: none !important; border-right: none !important; }
            .outer-table td:first-child, .outer-table th:first-child { border-left: none !important; }
            .outer-table td:last-child, .outer-table th:last-child { border-right: none !important; }
            .max-w-4xl { max-width: 100% !important; }
            .px-2 { padding-left: 0 !important; padding-right: 0 !important; }
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; background: #f4f5f7; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #222; padding: 4px 6px; }
        .no-border td, .no-border th { border: none; }
        .inner-table th, .inner-table td { border: 1px solid #bbb; padding: 3px 5px; }
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .bottom-flex { display: flex; gap: 16px; flex-wrap: wrap; }
        .bottom-left { flex: 1; min-width: 240px; }
        .bottom-right { width: 220px; flex-shrink: 0; }
        @media (max-width: 640px) {
            body { font-size: 11px; background: #fff; }
            .page-wrap { border-radius: 0 !important; border: none !important; }
            .inv-header-table td { display: block !important; width: 100% !important; text-align: left !important; }
            .bottom-right { width: 100%; }
            .no-print { padding: 8px 12px; }
            .no-print a, .no-print button { font-size: 12px !important; padding: 7px 12px !important; }
        }
    </style>
</head>
<body>

{{-- ── Action bar ── --}}
<div class="no-print sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
    <div class="max-w-4xl mx-auto flex items-center justify-between gap-3 flex-wrap">
        <a href="{{ route('invoices.show', $invoice->id) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f1f5f9;color:#475569;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">
            ← Back
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#1e293b;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">
                🖨️ Print
            </button>
            <a href="{{ route('invoices.download-pdf', $invoice->id) }}"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">
                ⬇️ Download PDF
            </a>
        </div>
    </div>
</div>

@php
    $b        = $invoice->booking;
    $customer = $invoice->customer;
    $room     = $b->room;
    $s        = $settings;

    $taxRate      = (float)($s->tax_rate ?? 12);
    $foodTaxRate  = (float)($s->food_tax_rate ?? 5);
    $cgstRate     = $taxRate / 2;
    $sgstRate     = $taxRate / 2;
    $cgstFoodRate = $foodTaxRate / 2;
    $sgstFoodRate = $foodTaxRate / 2;

    $nights     = (int)($b->nights ?? 1);
    $priceNight = (float)($room->price_per_night ?? 0);
    $roomBase   = $nights * $priceNight;

    // Extra charges: food category → food tax, others → room tax
    $extraCharges = $b->extraCharges ?? collect();
    $extraFood    = 0;
    $extraService = 0;
    foreach ($extraCharges as $ec) {
        if (in_array($ec->category, ['food','drink'])) {
            $extraFood += (float)$ec->total_price;
        } else {
            $extraService += (float)$ec->total_price;
        }
    }

    // Meal plan charges from booking
    $mealBase = 0;
    if ($b->meal_cost) $mealBase = (float)$b->meal_cost;
    $extraFood += $mealBase;

    $extraBedBase = $b->extra_beds ? (float)($b->extra_bed_cost ?? 0) : 0;
    $extraService += $extraBedBase;

    // Room line: base + service charges together taxed at room rate
    $roomTotal     = $roomBase + $extraService;
    $roomCgst      = round($roomTotal * $cgstRate / 100, 2);
    $roomSgst      = round($roomTotal * $sgstRate / 100, 2);
    $roomWithTax   = $roomTotal + $roomCgst + $roomSgst;

    // Food line: food charges taxed at food rate
    $foodCgst      = round($extraFood * $cgstFoodRate / 100, 2);
    $foodSgst      = round($extraFood * $sgstFoodRate / 100, 2);
    $foodWithTax   = $extraFood + $foodCgst + $foodSgst;

    $totalBeforeTax = $roomTotal + $extraFood;
    $totalCgst      = $roomCgst + $foodCgst;
    $totalSgst      = $roomSgst + $foodSgst;
    $totalWithTax   = $totalBeforeTax + $totalCgst + $totalSgst;

    $roundOff      = round($totalWithTax) - $totalWithTax;
    $grandTotal    = round($totalWithTax);

    // Advance payments from booking
    $payments      = $b->payments ?? collect();
    $advancePaid   = $payments->where('status','completed')->sum('amount');
    $balanceDue    = max(0, $grandTotal - $advancePaid);

    // Amount in words helper
    function numberToWords($n) {
        $n = (int)$n;
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                 'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                 'Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($n === 0) return 'Zero';
        $result = '';
        if ($n >= 10000000) { $result .= numberToWords(intdiv($n,10000000)) . ' Crore '; $n %= 10000000; }
        if ($n >= 100000)   { $result .= numberToWords(intdiv($n,100000)) . ' Lakh '; $n %= 100000; }
        if ($n >= 1000)     { $result .= numberToWords(intdiv($n,1000)) . ' Thousand '; $n %= 1000; }
        if ($n >= 100)      { $result .= $ones[intdiv($n,100)] . ' Hundred '; $n %= 100; }
        if ($n >= 20)       { $result .= $tens[intdiv($n,10)] . ' '; $n %= 10; }
        if ($n > 0)         { $result .= $ones[$n] . ' '; }
        return trim($result);
    }
    $amountWords = numberToWords($grandTotal) . ' Rupees Only';

    $hsnRoom = $s->hsn_room ?? '996311';
    $hsnFood = $s->hsn_food ?? '996331';
@endphp

{{-- ── Invoice ── --}}
<div class="max-w-4xl mx-auto px-2 py-6 sm:py-8">
<div class="page-wrap bg-white shadow border border-gray-300 rounded-lg overflow-hidden">

    {{-- ══ HEADER ══ --}}
    @php
        $hotelName     = $s->resort_name ?? 'Resort';
        $initials      = implode('', array_map(fn($w) => strtoupper($w[0]), array_filter(explode(' ', $hotelName))));
        $initials      = substr($initials, 0, 3);
        $logoUrl       = $s?->logo_url;
        $hasLogo       = !empty($logoUrl);
    @endphp
    <div>
        <table class="no-border inv-header-table" style="width:100%;">
            <tr>
                <td style="width:65%;vertical-align:top;padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:14px;">

                        {{-- Logo or initials badge (hidden in print) --}}
                        @if($hasLogo)
                        <div class="logo-badge" style="flex-shrink:0;width:72px;height:72px;border-radius:10px;border:1.5px solid #d1d5db;padding:4px;background:#fff;display:flex;align-items:center;justify-content:center;">
                            <img src="{{ $logoUrl }}" alt="{{ $hotelName }} Logo"
                                 style="max-width:100%;max-height:100%;object-fit:contain;">
                        </div>
                        @else
                        <div class="logo-badge" style="flex-shrink:0;width:72px;height:72px;border-radius:10px;border:2px solid #1e293b;background:#1e293b;display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:22px;font-weight:900;color:#fff;letter-spacing:1px;line-height:1;">{{ $initials }}</span>
                        </div>
                        @endif

                        {{-- Hotel name + address --}}
                        <div>
                            <div style="font-size:19px;font-weight:900;color:#0f172a;line-height:1.2;text-transform:uppercase;letter-spacing:0.3px;">{{ $hotelName }}</div>
                            @if($s && $s->tagline)
                            <div style="font-size:10.5px;color:#64748b;margin-top:1px;font-style:italic;">{{ $s->tagline }}</div>
                            @endif
                            <div style="font-size:10.5px;color:#374151;margin-top:5px;line-height:1.5;">
                                {{ $s->address ?? '' }}
                            </div>
                            <div style="font-size:10.5px;color:#374151;line-height:1.5;">
                                @if($s->phone) Phone No.: {{ $s->phone }} @endif
                                @if($s->contact_number), Contact No.: {{ $s->contact_number }} @endif
                            </div>
                            @if($s->email)
                            <div style="font-size:10.5px;color:#374151;">✉ {{ $s->email }}</div>
                            @endif
                            @if($s && $s->gst_number)
                            <div style="font-size:10.5px;font-weight:700;color:#1e293b;margin-top:4px;">
                                GST IN.: {{ $s->gst_number }}
                                @if($s->state_code) &nbsp;|&nbsp; State/Code: {{ $s->state_code }} @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width:35%;vertical-align:top;text-align:right;padding:14px 16px;">
                    <div style="font-size:22px;font-weight:900;letter-spacing:2px;color:#0f172a;text-transform:uppercase;">TAX INVOICE</div>
                    <div style="margin-top:10px;font-size:11px;color:#374151;line-height:2;">
                        <div><strong>Invoice No.:</strong> {{ $invoice->invoice_number }}</div>
                        <div><strong>Date:</strong> {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y') : now()->format('d/m/Y') }}</div>
                        <div><strong>Booking#:</strong> {{ $b->booking_number }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ GUEST + ROOM DETAILS ══ --}}
    <div style="padding:0 16px;" class="table-scroll">
        <table class="outer-table" style="width:100%;margin-top:0;border-left:none;border-right:none;min-width:480px;">
            <thead>
                <tr style="background:#1e293b;color:#fff;">
                    <th colspan="4" style="padding:5px 8px;font-size:11px;letter-spacing:.5px;border-color:#1e293b;">GUEST DETAILS</th>
                    <th colspan="4" style="padding:5px 8px;font-size:11px;letter-spacing:.5px;border-color:#1e293b;">ROOM / STAY DETAILS</th>
                </tr>
            </thead>
            <tbody>
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;white-space:nowrap;width:90px;">Name</td>
                    <td colspan="3">
                        {{ $customer->name ?? '—' }}
                        @if($customer->company_name)
                        <div style="font-size:10px;color:#7c3aed;font-weight:700;margin-top:1px;">{{ $customer->company_name }}</div>
                        @endif
                    </td>
                    <td style="background:#f8fafc;font-weight:600;white-space:nowrap;width:90px;">Arrival No.</td>
                    <td colspan="3">{{ $b->booking_number }}</td>
                </tr>
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;">Mobile</td>
                    <td colspan="3">{{ $customer->phone ?? '—' }}</td>
                    <td style="background:#f8fafc;font-weight:600;">Nationality</td>
                    <td colspan="3">{{ $customer->nationality ?? 'Indian' }}</td>
                </tr>
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;">Address</td>
                    <td colspan="3">{{ implode(', ', array_filter([$customer->address ?? null, $customer->city ?? null, $customer->state ?? null, $customer->country ?? null])) ?: '—' }}</td>
                    <td style="background:#f8fafc;font-weight:600;">Adults / Children</td>
                    <td colspan="3">{{ $b->adults ?? 1 }} / {{ $b->children ?? 0 }}</td>
                </tr>
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;">ID Proof</td>
                    <td colspan="3">{{ $customer->id_type ? strtoupper($customer->id_type).': '.$customer->id_number : '—' }}</td>
                    <td style="background:#f8fafc;font-weight:600;">Check-In</td>
                    <td colspan="3">{{ \Carbon\Carbon::parse($b->check_in_date)->format('d/m/Y') }} ({{ $s->check_in_time ?? '14:00' }})</td>
                </tr>
                @if($customer->gstin)
                <tr style="font-size:11px;background:#faf5ff;">
                    <td style="background:#ede9fe;font-weight:700;color:#6d28d9;">Buyer GSTIN</td>
                    <td colspan="3" style="font-weight:700;color:#5b21b6;letter-spacing:0.5px;">{{ $customer->gstin }}</td>
                    <td style="background:#ede9fe;font-weight:700;color:#6d28d9;">Company</td>
                    <td colspan="3" style="font-weight:700;color:#5b21b6;">{{ $customer->company_name ?? '—' }}</td>
                </tr>
                @endif
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;">Room No.</td>
                    <td colspan="3">{{ $room->room_number ?? '—' }}</td>
                    <td style="background:#f8fafc;font-weight:600;">Check-Out</td>
                    <td colspan="3">{{ \Carbon\Carbon::parse($b->check_out_date)->format('d/m/Y') }} ({{ $s->check_out_time ?? '11:00' }})</td>
                </tr>
                <tr style="font-size:11px;">
                    <td style="background:#f8fafc;font-weight:600;">Room Type</td>
                    <td colspan="3">{{ $room->type ?? '—' }}</td>
                    <td style="background:#f8fafc;font-weight:600;">Nights</td>
                    <td colspan="3">{{ $nights }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ══ PARTICULARS TABLE ══ --}}
    <div style="padding:0 16px;margin-top:12px;" class="table-scroll">
        <table style="font-size:11px;min-width:560px;">
            <thead>
                <tr style="background:#1e293b;color:#fff;text-align:center;">
                    <th style="width:28%;text-align:left;border-color:#1e293b;">Particular</th>
                    <th style="border-color:#1e293b;">HSN/SAC</th>
                    <th style="border-color:#1e293b;">Amount (₹)</th>
                    <th style="border-color:#1e293b;">Ser.Charge (₹)</th>
                    <th style="border-color:#1e293b;">CGST {{ $cgstRate }}%</th>
                    <th style="border-color:#1e293b;">SGST {{ $sgstRate }}%</th>
                    <th style="border-color:#1e293b;">Round Off</th>
                    <th style="border-color:#1e293b;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Room charges row --}}
                <tr style="text-align:right;">
                    <td style="text-align:left;">
                        Room Charges<br>
                        <span style="color:#64748b;font-size:10px;">{{ $room->type ?? '' }} × {{ $nights }} Night(s) @ ₹{{ number_format($priceNight,2) }}</span>
                    </td>
                    <td style="text-align:center;">{{ $hsnRoom }}</td>
                    <td>{{ number_format($roomBase,2) }}</td>
                    <td>{{ $extraService > 0 ? number_format($extraService,2) : '—' }}</td>
                    <td>{{ number_format($roomCgst,2) }}</td>
                    <td>{{ number_format($roomSgst,2) }}</td>
                    <td>—</td>
                    <td style="font-weight:700;">{{ number_format($roomWithTax,2) }}</td>
                </tr>

                @if($extraCharges->count())
                @foreach($extraCharges as $ec)
                @php
                    $ecIsFood = in_array($ec->category, ['food','drink']);
                    $ecCgstR  = $ecIsFood ? $cgstFoodRate : $cgstRate;
                    $ecSgstR  = $ecIsFood ? $sgstFoodRate : $sgstRate;
                    $ecBase   = (float)$ec->total_price;
                    $ecCgst   = round($ecBase * $ecCgstR / 100, 2);
                    $ecSgst   = round($ecBase * $ecSgstR / 100, 2);
                    $ecTotal  = $ecBase + $ecCgst + $ecSgst;
                    $ecHsn    = $ecIsFood ? $hsnFood : $hsnRoom;
                @endphp
                <tr style="text-align:right;">
                    <td style="text-align:left;">
                        {{ $ec->name }}<br>
                        <span style="color:#64748b;font-size:10px;">{{ $ec->categoryLabel }} × {{ $ec->quantity }}</span>
                    </td>
                    <td style="text-align:center;">{{ $ecHsn }}</td>
                    <td>{{ number_format($ecBase,2) }}</td>
                    <td>—</td>
                    <td>{{ number_format($ecCgst,2) }}</td>
                    <td>{{ number_format($ecSgst,2) }}</td>
                    <td>—</td>
                    <td style="font-weight:700;">{{ number_format($ecTotal,2) }}</td>
                </tr>
                @endforeach
                @endif

                @if($extraFood > 0 && !$extraCharges->count())
                {{-- Meal plan charges aggregated --}}
                <tr style="text-align:right;">
                    <td style="text-align:left;">
                        Meal Plan Charges<br>
                        <span style="color:#64748b;font-size:10px;">Food &amp; Beverage</span>
                    </td>
                    <td style="text-align:center;">{{ $hsnFood }}</td>
                    <td>{{ number_format($extraFood,2) }}</td>
                    <td>—</td>
                    <td>{{ number_format($foodCgst,2) }}</td>
                    <td>{{ number_format($foodSgst,2) }}</td>
                    <td>—</td>
                    <td style="font-weight:700;">{{ number_format($foodWithTax,2) }}</td>
                </tr>
                @endif

                {{-- Empty filler rows --}}
                <tr style="height:22px;"><td colspan="8">&nbsp;</td></tr>
                <tr style="height:22px;"><td colspan="8">&nbsp;</td></tr>

                {{-- Total row --}}
                <tr style="background:#f1f5f9;font-weight:700;text-align:right;">
                    <td style="text-align:left;">TOTAL</td>
                    <td></td>
                    <td>{{ number_format($totalBeforeTax,2) }}</td>
                    <td></td>
                    <td>{{ number_format($totalCgst,2) }}</td>
                    <td>{{ number_format($totalSgst,2) }}</td>
                    <td>{{ number_format($roundOff,2) }}</td>
                    <td>{{ number_format($grandTotal,2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ══ TAX SUMMARY (right) + AMOUNT WORDS / BANK / ADVANCE (left) ══ --}}
    <div style="padding:10px 16px 0;" class="bottom-flex">

        {{-- LEFT: Amount in words + bank + advances --}}
        <div class="bottom-left">
            <div style="border:1px solid #222;padding:7px 10px;margin-bottom:8px;">
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Amount in Words</div>
                <div style="font-weight:700;font-size:12px;">{{ $amountWords }}</div>
            </div>

            @if($s->bank_name || $s->bank_account_number || $s->bank_ifsc)
            <div style="border:1px solid #222;padding:7px 10px;margin-bottom:8px;">
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Bank Details (For Online Transfer)</div>
                @if($s->bank_name)<div style="font-size:11px;"><strong>Bank:</strong> {{ $s->bank_name }}</div>@endif
                @if($s->bank_account_number)<div style="font-size:11px;"><strong>A/C No.:</strong> {{ $s->bank_account_number }}</div>@endif
                @if($s->bank_ifsc)<div style="font-size:11px;"><strong>IFSC:</strong> {{ $s->bank_ifsc }}</div>@endif
            </div>
            @endif

            @if($payments->count())
            <div style="border:1px solid #222;">
                <table class="inner-table" style="font-size:10.5px;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="text-align:left;">Advance / Payment Details</th>
                            <th>Mode</th>
                            <th>Ref.</th>
                            <th style="text-align:right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments->where('status','completed') as $pay)
                        <tr>
                            <td>{{ $pay->payment_type ? ucwords(str_replace('_',' ',$pay->payment_type)) : 'Payment' }}</td>
                            <td style="text-align:center;">{{ strtoupper($pay->payment_method ?? '—') }}</td>
                            <td style="text-align:center;font-size:9px;">{{ $pay->transaction_id ? substr($pay->transaction_id,0,14) : '—' }}</td>
                            <td style="text-align:right;font-weight:600;">{{ number_format($pay->amount,2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- RIGHT: Summary box --}}
        <div class="bottom-right">
            <table class="inner-table" style="font-size:11px;width:100%;">
                <tbody>
                    <tr style="background:#f8fafc;">
                        <td colspan="2" style="font-weight:700;font-size:10px;letter-spacing:.5px;background:#1e293b;color:#fff;text-align:center;">TAX SUMMARY</td>
                    </tr>
                    <tr>
                        <td>Amount Without Tax</td>
                        <td style="text-align:right;">₹{{ number_format($totalBeforeTax,2) }}</td>
                    </tr>
                    <tr>
                        <td>CGST ({{ $cgstRate }}%)</td>
                        <td style="text-align:right;">₹{{ number_format($totalCgst,2) }}</td>
                    </tr>
                    <tr>
                        <td>SGST ({{ $sgstRate }}%)</td>
                        <td style="text-align:right;">₹{{ number_format($totalSgst,2) }}</td>
                    </tr>
                    <tr>
                        <td>Round Off</td>
                        <td style="text-align:right;">{{ $roundOff >= 0 ? '+' : '' }}{{ number_format($roundOff,2) }}</td>
                    </tr>
                    <tr style="font-weight:800;background:#f1f5f9;">
                        <td>Total Amount</td>
                        <td style="text-align:right;">₹{{ number_format($grandTotal,2) }}</td>
                    </tr>
                    <tr>
                        <td>Advance Paid</td>
                        <td style="text-align:right;color:#059669;">₹{{ number_format($advancePaid,2) }}</td>
                    </tr>
                    <tr style="font-weight:800;background:#fef3c7;color:#92400e;">
                        <td>Balance Due</td>
                        <td style="text-align:right;">₹{{ number_format($balanceDue,2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══ CERTIFICATION FOOTER ══ --}}
    <div style="padding:12px 16px;margin-top:14px;border-top:2px solid #111;">
        <div style="font-size:10px;color:#374151;margin-bottom:16px;font-style:italic;">
            I / We hereby certify that the services mentioned in this invoice have been rendered and the amount charged is correct as per our records.
            @if($s->cancellation_policy)
            <br><strong>Cancellation Policy:</strong> {{ $s->cancellation_policy }}
            @endif
        </div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:8px;flex-wrap:wrap;gap:16px;">
            <div style="text-align:center;">
                <div style="border-top:1px solid #555;margin-top:32px;padding-top:4px;font-size:11px;font-weight:600;min-width:160px;">
                    Signature of Guest
                </div>
                <div style="font-size:10px;color:#64748b;">{{ $customer->name ?? '' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:11px;color:#374151;margin-bottom:4px;">For <strong>{{ $s->resort_name ?? 'Resort' }}</strong></div>
                @if($s && $s->logo_url)
                <img src="{{ $s->logo_url }}" alt="Logo" style="height:36px;object-fit:contain;margin:0 auto;display:block;">
                @endif
                <div style="border-top:1px solid #555;margin-top:28px;padding-top:4px;font-size:11px;font-weight:600;min-width:160px;">
                    Authorised Signatory
                </div>
                <div style="font-size:10px;color:#64748b;">Front Office</div>
            </div>
        </div>
    </div>

    {{-- ══ FOOTER NOTE ══ --}}
    <div style="background:#1e293b;color:#94a3b8;padding:6px 16px;font-size:10px;text-align:center;">
        This is a computer generated Tax Invoice. {{ $s->website ? '| '.$s->website : '' }}
    </div>

</div>
</div>

<script>
// Detect WebView
(function(){
    var ua = navigator.userAgent||'';
    var isWV = /wv|WebView|Android.*Version\/\d/i.test(ua) && !/Chrome\/\d/.test(ua);
    if(isWV){ var tip=document.getElementById('webview-tip'); if(tip) tip.style.display='block'; }
})();

async function downloadPDF() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Generating…';
    try {
        const el = document.querySelector('.page-wrap');
        const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#fff' });
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const pW = pdf.internal.pageSize.getWidth();
        const pH = pdf.internal.pageSize.getHeight();
        const cW = canvas.width; const cH = canvas.height;
        const ratio = pW / cW;
        const imgH = cH * ratio;
        let y = 0;
        while (y < imgH) {
            if (y > 0) pdf.addPage();
            const sliceH = Math.min(pH, imgH - y);
            const sy = (y / ratio);
            const sh = Math.min(cH - sy, pH / ratio);
            const tmpC = document.createElement('canvas');
            tmpC.width = cW; tmpC.height = sh;
            tmpC.getContext('2d').drawImage(canvas, 0, sy, cW, sh, 0, 0, cW, sh);
            pdf.addImage(tmpC.toDataURL('image/jpeg',0.92), 'JPEG', 0, 0, pW, sh*ratio);
            y += pH;
        }
        pdf.save('GST-Invoice-{{ $invoice->invoice_number }}.pdf');
    } catch(e) { alert('PDF generation failed: ' + e.message); }
    btn.disabled = false; btn.textContent = '⬇️ Download PDF';
}
</script>
</body>
</html>
