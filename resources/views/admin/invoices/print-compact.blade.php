<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <style>
        @media print {
            @page { size: A4; margin: 8mm; }
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; font-size: 10px; }
            .page-wrap { border: none !important; box-shadow: none !important; }
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; background: #f4f5f7; color: #111; }
        .page-wrap { background: #fff; max-width: 740px; margin: 0 auto; border: 1px solid #aaa; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #555; padding: 3px 5px; font-size: 10.5px; }
        .lbl { background: #f0f0f0; font-weight: 600; white-space: nowrap; }
        .section-title { font-weight: 700; font-size: 10px; background: #e0e0e0; text-align: center; letter-spacing: 0.5px; padding: 3px 5px; border: 1px solid #555; }
        .no-border td, .no-border th { border: none; padding: 2px 4px; }
    </style>
</head>
<body>

<div class="no-print" style="background:#1e293b;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
    <a href="{{ route('invoices.show', $invoice->id) }}"
       style="color:#94a3b8;text-decoration:none;font-size:13px;">← Back</a>
    <button onclick="window.print()"
        style="padding:7px 18px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">
        🖨️ Print
    </button>
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

    $extraCharges = $b->extraCharges ?? collect();
    $extraFood    = 0;
    $extraService = 0;
    foreach ($extraCharges as $ec) {
        if (in_array($ec->category, ['food','drink'])) $extraFood += (float)$ec->total_price;
        else $extraService += (float)$ec->total_price;
    }
    if ($b->meal_cost) $extraFood += (float)$b->meal_cost;
    $extraBedBase = $b->extra_beds ? (float)($b->extra_bed_cost ?? 0) : 0;
    $extraService += $extraBedBase;

    $roomTotal   = $roomBase + $extraService;
    $roomCgst    = round($roomTotal * $cgstRate / 100, 2);
    $roomSgst    = round($roomTotal * $sgstRate / 100, 2);
    $roomWithTax = $roomTotal + $roomCgst + $roomSgst;

    $foodCgst    = round($extraFood * $cgstFoodRate / 100, 2);
    $foodSgst    = round($extraFood * $sgstFoodRate / 100, 2);
    $foodWithTax = $extraFood + $foodCgst + $foodSgst;

    $totalBeforeTax = $roomTotal + $extraFood;
    $totalCgst      = $roomCgst + $foodCgst;
    $totalSgst      = $roomSgst + $foodSgst;
    $totalWithTax   = $totalBeforeTax + $totalCgst + $totalSgst;
    $roundOff       = round($totalWithTax) - $totalWithTax;
    $grandTotal     = round($totalWithTax);

    $payments    = $b->payments ?? collect();
    $advancePaid = $payments->where('status','completed')->sum('amount');
    $balanceDue  = max(0, $grandTotal - $advancePaid);

    if (!function_exists('cmpNumberToWords')) {
        function cmpNumberToWords($n) {
            $n = (int)$n;
            $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
            $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
            if ($n === 0) return 'Zero';
            $r = '';
            if ($n >= 10000000) { $r .= cmpNumberToWords(intdiv($n,10000000)) . ' Crore '; $n %= 10000000; }
            if ($n >= 100000)   { $r .= cmpNumberToWords(intdiv($n,100000)) . ' Lakh '; $n %= 100000; }
            if ($n >= 1000)     { $r .= cmpNumberToWords(intdiv($n,1000)) . ' Thousand '; $n %= 1000; }
            if ($n >= 100)      { $r .= $ones[intdiv($n,100)] . ' Hundred '; $n %= 100; }
            if ($n >= 20)       { $r .= $tens[intdiv($n,10)] . ' '; $n %= 10; }
            if ($n > 0)         { $r .= $ones[$n] . ' '; }
            return trim($r);
        }
    }
    $amountWords = cmpNumberToWords($grandTotal) . ' Rupees Only';
    $hsnRoom = $s->hsn_room ?? '996311';
    $hsnFood = $s->hsn_food ?? '996331';
    $hotelName = $s->resort_name ?? 'Hotel';
    $hasLogo   = $s && $s->logo && file_exists(public_path('storage/' . $s->logo));
    $logoUrl   = $hasLogo ? asset('storage/' . $s->logo) : null;
    $initials  = implode('', array_map(fn($w) => strtoupper($w[0]), array_filter(explode(' ', $hotelName))));
    $initials  = substr($initials, 0, 3);
@endphp

<div class="page-wrap" style="padding:10px 14px;">

    {{-- ══ COMPACT HEADER ══ --}}
    <table class="no-border" style="margin-bottom:6px;">
        <tr>
            <td style="width:60%;vertical-align:top;padding:0;">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    @if($hasLogo)
                    <img src="{{ $logoUrl }}" style="width:48px;height:48px;object-fit:contain;border:1px solid #aaa;">
                    @else
                    <div style="width:48px;height:48px;background:#1e293b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="color:#fff;font-weight:900;font-size:14px;letter-spacing:1px;">{{ $initials }}</span>
                    </div>
                    @endif
                    <div>
                        <div style="font-size:16px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px;">{{ $hotelName }}</div>
                        @if($s->tagline)<div style="font-size:9px;color:#555;font-style:italic;">{{ $s->tagline }}</div>@endif
                        @if($s->address)<div style="font-size:9.5px;margin-top:2px;">{{ $s->address }}</div>@endif
                        @if($s->phone)<div style="font-size:9.5px;">Ph: {{ $s->phone }}@if($s->contact_number), {{ $s->contact_number }}@endif</div>@endif
                        @if($s->email)<div style="font-size:9.5px;">{{ $s->email }}</div>@endif
                        @if($s->gst_number)<div style="font-size:9.5px;font-weight:700;margin-top:2px;">GST IN.: {{ $s->gst_number }}@if($s->state_code) &nbsp;|&nbsp; State/Code: {{ $s->state_code }}@endif</div>@endif
                    </div>
                </div>
            </td>
            <td style="width:40%;vertical-align:top;text-align:right;padding:0;">
                <div style="font-size:18px;font-weight:900;letter-spacing:2px;text-transform:uppercase;">TAX INVOICE</div>
                <div style="font-size:10px;margin-top:4px;line-height:1.7;">
                    <div><strong>Invoice No.:</strong> {{ $invoice->invoice_number }}</div>
                    <div><strong>Bill Date:</strong> {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y') : now()->format('d/m/Y') }}</div>
                    <div><strong>Arrival No.:</strong> {{ $b->booking_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ GUEST DETAILS ══ --}}
    <table style="margin-bottom:6px;">
        <thead>
            <tr>
                <td colspan="2" class="section-title">Guest Details of Receiver / Bill To</td>
                <td colspan="4" class="section-title">Room / Stay Details</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="lbl" style="width:12%;">Guest Name</td>
                <td style="width:24%;">
                    {{ $customer->name ?? '—' }}
                    @if($customer->company_name)<div style="font-size:9px;color:#555;">{{ $customer->company_name }}</div>@endif
                </td>
                <td class="lbl" style="width:12%;">Arrival No.</td>
                <td style="width:14%;">{{ $b->booking_number }}</td>
                <td class="lbl" style="width:12%;">Nationality</td>
                <td style="width:14%;">{{ $customer->nationality ?? 'Indian' }}</td>
            </tr>
            <tr>
                <td class="lbl">No. Adult</td>
                <td>{{ $b->adults ?? 1 }}</td>
                <td class="lbl">No. Child</td>
                <td>{{ $b->children ?? 0 }}</td>
                <td class="lbl">Check In Date</td>
                <td>{{ \Carbon\Carbon::parse($b->check_in_date)->format('d/m/Y') }} ({{ $s->check_in_time ?? '14:00' }})</td>
            </tr>
            <tr>
                <td class="lbl">Mobile No.</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td class="lbl">Check Out Date</td>
                <td>{{ \Carbon\Carbon::parse($b->check_out_date)->format('d/m/Y') }} ({{ $s->check_out_time ?? '11:00' }})</td>
                <td class="lbl">Nights</td>
                <td>{{ $nights }}</td>
            </tr>
            <tr>
                <td class="lbl">Bill To</td>
                <td>{{ $customer->company_name ?: ($customer->name ?? '—') }}</td>
                <td class="lbl">Plan Type</td>
                <td>{{ $b->meal_plan ?? 'EP' }}</td>
                <td class="lbl">Room Type</td>
                <td>{{ $room->type ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Address</td>
                <td>{{ implode(', ', array_filter([$customer->address ?? null, $customer->city ?? null, $customer->state ?? null])) ?: '—' }}</td>
                <td class="lbl">State/Code</td>
                <td>{{ $s->state_code ?? '—' }}</td>
                <td class="lbl">Room No.</td>
                <td>{{ $room->room_number ?? '—' }}</td>
            </tr>
            @if($customer->id_type)
            <tr>
                <td class="lbl">ID Proof</td>
                <td colspan="5">{{ strtoupper($customer->id_type) }}: {{ $customer->id_number }}</td>
            </tr>
            @endif
            @if($customer->gstin)
            <tr>
                <td class="lbl">Buyer GSTIN</td>
                <td colspan="5" style="font-weight:700;">{{ $customer->gstin }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- ══ PARTICULARS TABLE ══ --}}
    <table style="margin-bottom:6px;font-size:10px;">
        <thead>
            <tr>
                <th style="text-align:left;width:30%;">Particular</th>
                <th>HSN/SAC</th>
                <th>Amount</th>
                <th>Ser.Charge</th>
                <th>CGST {{ $cgstRate }}%</th>
                <th>SGST {{ $sgstRate }}%</th>
                <th>RoundOff</th>
                <th>TotalAmt</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Room Tariff ({{ $room->type ?? '' }})<br>
                    <span style="font-size:9px;color:#555;">{{ $nights }} night(s) @ ₹{{ number_format($priceNight,2) }}</span>
                </td>
                <td style="text-align:center;">{{ $hsnRoom }}</td>
                <td style="text-align:right;">{{ number_format($roomBase,2) }}</td>
                <td style="text-align:right;">{{ $extraService > 0 ? number_format($extraService,2) : '0.00' }}</td>
                <td style="text-align:right;">{{ number_format($roomCgst,2) }}</td>
                <td style="text-align:right;">{{ number_format($roomSgst,2) }}</td>
                <td style="text-align:right;">0.00</td>
                <td style="text-align:right;font-weight:700;">{{ number_format($roomWithTax,2) }}</td>
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
            <tr>
                <td>{{ $ec->name }} <span style="font-size:9px;color:#555;">× {{ $ec->quantity }}</span></td>
                <td style="text-align:center;">{{ $ecHsn }}</td>
                <td style="text-align:right;">{{ number_format($ecBase,2) }}</td>
                <td style="text-align:right;">0.00</td>
                <td style="text-align:right;">{{ number_format($ecCgst,2) }}</td>
                <td style="text-align:right;">{{ number_format($ecSgst,2) }}</td>
                <td style="text-align:right;">0.00</td>
                <td style="text-align:right;font-weight:700;">{{ number_format($ecTotal,2) }}</td>
            </tr>
            @endforeach
            @endif
            @if($extraFood > 0 && !$extraCharges->count())
            <tr>
                <td>Restaurant Bill (TC)<br><span style="font-size:9px;color:#555;">Food &amp; Beverage</span></td>
                <td style="text-align:center;">{{ $hsnFood }}</td>
                <td style="text-align:right;">{{ number_format($extraFood,2) }}</td>
                <td style="text-align:right;">0.00</td>
                <td style="text-align:right;">{{ number_format($foodCgst,2) }}</td>
                <td style="text-align:right;">{{ number_format($foodSgst,2) }}</td>
                <td style="text-align:right;">0.00</td>
                <td style="text-align:right;font-weight:700;">{{ number_format($foodWithTax,2) }}</td>
            </tr>
            @endif
            {{-- Totals --}}
            <tr style="font-weight:700;background:#f5f5f5;">
                <td style="text-align:right;" colspan="2">Total Invoice Amount</td>
                <td style="text-align:right;">{{ number_format($totalBeforeTax,2) }}</td>
                <td></td>
                <td style="text-align:right;">{{ number_format($totalCgst,2) }}</td>
                <td style="text-align:right;">{{ number_format($totalSgst,2) }}</td>
                <td style="text-align:right;">{{ number_format($roundOff,2) }}</td>
                <td style="text-align:right;">{{ number_format($grandTotal,2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ AMOUNT IN WORDS + TAX SUMMARY ══ --}}
    <table style="margin-bottom:6px;">
        <tr>
            <td style="width:60%;vertical-align:top;border:none;padding:0 6px 0 0;">
                <table>
                    <tr>
                        <td style="font-size:9px;color:#555;font-weight:600;text-transform:uppercase;padding-bottom:2px;border:none;" colspan="2">Total Invoice Amount In Words :</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight:700;border-top:1px solid #555;">IN WORDS : {{ strtoupper($amountWords) }}</td>
                    </tr>
                </table>

                @php
                    // Tax breakdown rows for compact display
                    $taxLines = [];
                    if ($roomTotal > 0) {
                        $taxLines[] = ['desc' => $hsnRoom.' @ CGST '.$cgstRate.'%', 'cgst' => $roomCgst, 'sgst' => $roomSgst, 'base' => $roomTotal];
                    }
                    if ($extraFood > 0) {
                        $taxLines[] = ['desc' => $hsnFood.' @ CGST '.$cgstFoodRate.'%', 'cgst' => $foodCgst, 'sgst' => $foodSgst, 'base' => $extraFood];
                    }
                @endphp
                @if(count($taxLines))
                <table style="margin-top:6px;font-size:10px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">HSN/SAC</th>
                            <th style="text-align:right;">Base</th>
                            <th style="text-align:right;">CGST @ {{ $cgstRate }}%</th>
                            <th style="text-align:right;">SGST @ {{ $sgstRate }}%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($taxLines as $tl)
                        <tr>
                            <td>{{ $tl['desc'] }}</td>
                            <td style="text-align:right;">{{ number_format($tl['base'],2) }}</td>
                            <td style="text-align:right;">{{ number_format($tl['cgst'],2) }}</td>
                            <td style="text-align:right;">{{ number_format($tl['sgst'],2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                @if($s->bank_name || $s->bank_account_number || $s->bank_ifsc)
                <table style="margin-top:6px;font-size:10px;">
                    <tr><td colspan="2" class="section-title">Bank Details</td></tr>
                    @if($s->bank_name)<tr><td class="lbl" style="width:35%;">Bank Name</td><td>{{ $s->bank_name }}</td></tr>@endif
                    @if($s->bank_account_number)<tr><td class="lbl">A/c No.</td><td>{{ $s->bank_account_number }}</td></tr>@endif
                    @if($s->bank_ifsc)<tr><td class="lbl">IFSC</td><td>{{ $s->bank_ifsc }}</td></tr>@endif
                </table>
                @endif
            </td>
            <td style="width:40%;vertical-align:top;border:none;padding:0;">
                <table style="font-size:10.5px;">
                    <tr><td colspan="2" class="section-title">Tax Summary</td></tr>
                    <tr><td class="lbl">Amount Without Tax</td><td style="text-align:right;">₹{{ number_format($totalBeforeTax,2) }}</td></tr>
                    <tr><td class="lbl">Total GBT Tax Amount</td><td style="text-align:right;">₹{{ number_format($totalCgst+$totalSgst,2) }}</td></tr>
                    <tr><td class="lbl">Round Off</td><td style="text-align:right;">{{ number_format($roundOff,2) }}</td></tr>
                    <tr style="font-weight:800;"><td class="lbl">Total Amount After Tax</td><td style="text-align:right;">₹{{ number_format($grandTotal,2) }}</td></tr>
                    <tr><td class="lbl">Advance Amount</td><td style="text-align:right;">₹{{ number_format($advancePaid,2) }}</td></tr>
                    <tr><td class="lbl">Refund Amount</td><td style="text-align:right;">₹0.00</td></tr>
                    <tr style="font-weight:800;background:#f5f5f5;"><td class="lbl">Bill Amount</td><td style="text-align:right;">₹{{ number_format($grandTotal,2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ ADVANCE / PAYMENT SUMMARY ══ --}}
    @if($payments->count())
    <table style="margin-bottom:6px;font-size:10px;">
        <thead>
            <tr><td colspan="4" class="section-title">Advance Summary</td></tr>
            <tr>
                <th style="text-align:left;">Paymode</th>
                <th style="text-align:right;">Amount</th>
                <th style="text-align:center;">Rec. No.</th>
                <th style="text-align:right;">Balance Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments->where('status','completed') as $pay)
            <tr>
                <td>{{ strtoupper($pay->payment_method ?? '—') }}</td>
                <td style="text-align:right;">{{ number_format($pay->amount,2) }}</td>
                <td style="text-align:center;font-size:9px;">{{ $pay->transaction_id ? substr($pay->transaction_id,0,16) : '—' }}</td>
                <td style="text-align:right;font-weight:700;">{{ number_format(max(0,$grandTotal - $payments->where('status','completed')->take($loop->index+1)->sum('amount')),2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ══ CERTIFICATION + SIGNATURE ══ --}}
    <div style="border:1px solid #555;padding:6px 8px;margin-bottom:8px;font-size:9.5px;font-style:italic;">
        Certified That The Particulars Given Above Are True &amp; Correct
        @if($s->cancellation_policy)<br><strong>Cancellation Policy:</strong> {{ $s->cancellation_policy }}@endif
    </div>

    <div style="display:flex;justify-content:space-between;align-items:flex-end;padding:0 4px;">
        <div style="text-align:center;">
            <div style="font-size:10px;font-weight:700;margin-bottom:28px;">{{ strtoupper($s->front_desk_label ?? 'FRONT OFFICE ASSISTANT') }}</div>
            <div style="border-top:1px solid #555;padding-top:3px;font-size:10px;min-width:140px;">Signature of the guest</div>
            <div style="font-size:9px;color:#555;">{{ $customer->name ?? '' }}</div>
        </div>
        <div style="text-align:center;">
            @if($hasLogo)
            <img src="{{ $logoUrl }}" style="height:32px;object-fit:contain;display:block;margin:0 auto 4px;">
            @endif
            <div style="font-size:10px;color:#555;margin-bottom:24px;">For <strong>{{ $hotelName }}</strong></div>
            <div style="border-top:1px solid #555;padding-top:3px;font-size:10px;min-width:140px;">Authorised Signatory</div>
            <div style="font-size:9px;color:#555;">Front Office</div>
        </div>
    </div>

</div>
</body>
</html>
