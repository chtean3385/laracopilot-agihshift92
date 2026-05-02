<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
.page { padding: 28px 32px; }

/* Header */
.header { background: #1e293b; color: #fff; padding: 18px 22px; border-radius: 8px 8px 0 0; }
.header-inner { display: table; width: 100%; }
.header-left  { display: table-cell; vertical-align: middle; width: 70%; }
.header-right { display: table-cell; vertical-align: top; text-align: right; width: 30%; }
.hotel-name   { font-size: 16px; font-weight: bold; color: #fff; }
.hotel-sub    { font-size: 10px; color: #94a3b8; margin-top: 3px; line-height: 1.5; }
.inv-label    { font-size: 20px; font-weight: bold; color: #22d3ee; }
.inv-num      { font-size: 11px; color: #94a3b8; font-family: monospace; margin-top: 3px; }
.inv-date     { font-size: 10px; color: #94a3b8; margin-top: 2px; }

/* Logo */
.logo-img { width: 48px; height: 48px; border-radius: 6px; background: #fff; margin-right: 12px; display: table-cell; vertical-align: middle; }
.logo-wrap { display: table-cell; vertical-align: middle; padding-right: 12px; }

/* Bill-to / Booking */
.meta-section { display: table; width: 100%; margin-top: 18px; margin-bottom: 14px; }
.meta-left    { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
.meta-right   { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
.meta-label   { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
.meta-name    { font-size: 13px; font-weight: bold; color: #1e293b; }
.meta-sub     { font-size: 10px; color: #64748b; margin-top: 2px; line-height: 1.5; }
.meta-mono    { font-size: 11px; font-weight: bold; color: #0891b2; font-family: monospace; }

/* Line items table */
.items-table  { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.items-table th { background: #f8fafc; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; padding: 7px 10px; border: 1px solid #e2e8f0; }
.items-table td { font-size: 11px; padding: 7px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
.items-table tr.alt td { background: #fefce8; }
.items-table tr.alt2 td { background: #eff6ff; }
.items-table tr.section-hdr td { background: #fefce8; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #92400e; }

/* Totals */
.totals-wrap  { text-align: right; margin-top: 4px; }
.totals-table { display: inline-table; width: 220px; }
.totals-table tr td { padding: 3px 0; font-size: 11px; border: none; }
.totals-table tr td:first-child { color: #64748b; text-align: left; padding-right: 18px; }
.totals-table tr td:last-child  { font-weight: bold; text-align: right; }
.total-divider td { border-top: 1px solid #e2e8f0 !important; padding-top: 6px !important; }
.grand-total td { font-size: 13px; font-weight: bold; border-top: 2px solid #1e293b !important; padding-top: 6px !important; }
.paid-row td   { color: #16a34a !important; }
.balance-due-pos td { color: #dc2626 !important; font-size: 13px; }
.balance-due-zero td { color: #16a34a !important; font-size: 13px; }
.overp-row td  { color: #7c3aed !important; }

/* Footer */
.footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 10px; color: #94a3b8; }
.status-badge { display: inline-block; padding: 4px 18px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-top: 16px; }
.badge-paid    { background: #dcfce7; color: #15803d; }
.badge-partial { background: #fef3c7; color: #92400e; }
.badge-unpaid  { background: #fee2e2; color: #b91c1c; }

.divider { height: 1px; background: #e2e8f0; margin: 12px 0; }
.notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 12px; margin-top: 12px; font-size: 10px; color: #78350f; }
</style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                <table><tr>
                    @if($logoBase64)
                    <td style="vertical-align:middle;padding-right:12px;">
                        <img src="{{ $logoBase64 }}" style="width:48px;height:48px;border-radius:6px;background:#fff;object-fit:contain;">
                    </td>
                    @endif
                    <td style="vertical-align:middle;">
                        <div class="hotel-name">{{ $settings->resort_name ?? 'Resort' }}</div>
                        @if($settings && $settings->tagline)
                        <div style="font-size:10px;color:#22d3ee;margin-top:2px;">{{ $settings->tagline }}</div>
                        @endif
                        <div class="hotel-sub">
                            @if($settings && $settings->address){{ $settings->address }}<br>@endif
                            @if($settings && $settings->phone){{ $settings->phone }}@endif
                            @if($settings && $settings->gst_number) &nbsp;·&nbsp; GST: {{ $settings->gst_number }}@endif
                        </div>
                    </td>
                </tr></table>
            </div>
            <div class="header-right">
                <div class="inv-label">INVOICE</div>
                <div class="inv-num">{{ $invoice->invoice_number }}</div>
                <div class="inv-date">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- BILL TO / BOOKING --}}
    <div class="meta-section">
        <div class="meta-left">
            <div class="meta-label">Bill To</div>
            <div class="meta-name">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</div>
            <div class="meta-sub">
                @if($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
                @if($invoice->customer?->email){{ $invoice->customer->email }}<br>@endif
                @if($invoice->customer?->city){{ implode(', ', array_filter([$invoice->customer->city, $invoice->customer->country])) }}@endif
            </div>
        </div>
        <div class="meta-right">
            <div class="meta-label">Booking Details</div>
            <div class="meta-mono">{{ $invoice->booking->booking_number }}</div>
            <div class="meta-sub">
                @if($isWH)Whole Hotel / Villa@else Room {{ $invoice->booking->room?->room_number ?? '' }}@endif<br>
                {{ $invoice->booking->check_in_date->format('d M Y') }} &rarr; {{ $invoice->booking->check_out_date->format('d M Y') }}<br>
                {{ $invoice->booking->nights }} night(s)
            </div>
        </div>
    </div>

    <div class="divider"></div>

    {{-- LINE ITEMS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align:left;width:50%;">Description</th>
                <th style="text-align:right;width:12%;">Qty</th>
                <th style="text-align:right;width:19%;">Rate</th>
                <th style="text-align:right;width:19%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            {{-- Room --}}
            <tr>
                <td>
                    @if($isWH)
                        Whole Hotel / Villa ({{ $invoice->booking->nights }} night(s))
                    @else
                        {{ ucfirst($invoice->booking->room?->type ?? '') }} Room {{ $invoice->booking->room?->room_number ?? '' }}
                        @if($invoice->booking->room?->view) — {{ $invoice->booking->room->view }}@endif
                    @endif
                </td>
                <td style="text-align:right;">{{ $invoice->booking->nights ?: 1 }}</td>
                <td style="text-align:right;">
                    @if($isWH || $invoice->booking->price_overridden)
                        Rs.{{ number_format($roomCost) }}
                    @else
                        Rs.{{ number_format($invoice->booking->room?->price_per_night ?? 0) }}
                    @endif
                </td>
                <td style="text-align:right;font-weight:bold;">Rs.{{ number_format($roomCost) }}</td>
            </tr>
            {{-- Meal plan --}}
            @if($mealCost > 0)
            <tr class="alt">
                <td>
                    Meal Plan —
                    @if($invoice->booking->meal_breakfast) Breakfast @endif
                    @if($invoice->booking->meal_lunch) Lunch @endif
                    @if($invoice->booking->meal_dinner) Dinner @endif
                </td>
                <td style="text-align:right;">{{ $invoice->booking->nights }} nights</td>
                <td style="text-align:right;">—</td>
                <td style="text-align:right;font-weight:bold;">Rs.{{ number_format($mealCost) }}</td>
            </tr>
            @endif
            {{-- Extra beds --}}
            @if($invoice->booking->extra_beds > 0)
            <tr class="alt2">
                <td>Extra Beds x {{ $invoice->booking->extra_beds }}</td>
                <td style="text-align:right;">{{ $invoice->booking->nights }} nights</td>
                <td style="text-align:right;">Rs.{{ number_format($invoice->booking->room?->extra_bed_price ?? 0) }}/bed</td>
                <td style="text-align:right;font-weight:bold;">Rs.{{ number_format($extraBedCost) }}</td>
            </tr>
            @endif
            {{-- Extra charges --}}
            @if($invoice->booking->extraCharges->count() > 0)
            <tr class="section-hdr">
                <td colspan="4">Extra Service Charges &amp; Food</td>
            </tr>
            @foreach($invoice->booking->extraCharges as $xc)
            <tr class="alt">
                <td>{{ $xc->name }}@if($xc->notes) <span style="color:#94a3b8;font-size:10px;">({{ $xc->notes }})</span>@endif</td>
                <td style="text-align:right;">{{ number_format($xc->quantity, ($xc->quantity == intval($xc->quantity) ? 0 : 2)) }}</td>
                <td style="text-align:right;">Rs.{{ number_format($xc->unit_price) }}</td>
                <td style="text-align:right;font-weight:bold;">Rs.{{ number_format($xc->total_price) }}</td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td>Rs.{{ number_format($subtotal) }}</td>
            </tr>
            @if($settings && $settings->gst_number)
            <tr>
                <td>Room GST ({{ $taxRate }}%)</td>
                <td>Rs.{{ number_format($roomGst) }}</td>
            </tr>
            @if($extraTotal > 0)
            <tr>
                <td>Food &amp; Service GST ({{ $foodTaxRate }}%)</td>
                <td>Rs.{{ number_format($foodGst) }}</td>
            </tr>
            @endif
            @endif
            <tr class="total-divider">
                <td>Total</td>
                <td>Rs.{{ number_format($grandTotal) }}</td>
            </tr>
            <tr class="paid-row">
                <td>Amount Paid</td>
                <td>Rs.{{ number_format($invoice->paid_amount) }}</td>
            </tr>
            @if($overpayment > 0)
            <tr class="overp-row">
                <td>Overpayment / Credit</td>
                <td>Rs.{{ number_format($overpayment) }}</td>
            </tr>
            @endif
            <tr class="grand-total {{ $balance > 0 ? 'balance-due-pos' : 'balance-due-zero' }}">
                <td>Balance Due</td>
                <td>Rs.{{ number_format($balance) }}</td>
            </tr>
        </table>
    </div>

    {{-- STATUS BADGE --}}
    <div style="text-align:center;margin-top:18px;">
        @php $displayStatus = $balance <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'); @endphp
        <span class="status-badge badge-{{ $displayStatus }}">{{ strtoupper($displayStatus) }}</span>
        @if($overpayment > 0)
        <div style="font-size:10px;color:#7c3aed;margin-top:4px;">Guest has a credit of Rs.{{ number_format($overpayment) }} — please process a refund.</div>
        @endif
    </div>

    {{-- NOTES / SPECIAL REQUESTS --}}
    @if($invoice->booking->special_requests)
    <div class="notes-box" style="margin-top:14px;">
        <strong>Special Requests:</strong> {{ $invoice->booking->special_requests }}
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        Thank you for staying at {{ $settings->resort_name ?? 'our resort' }}. We hope to see you again!
    </div>

</div>
</body>
</html>
