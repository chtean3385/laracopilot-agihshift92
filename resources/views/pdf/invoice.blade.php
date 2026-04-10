<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }
.page { padding: 32px 36px; }

/* Header */
.header { background: #1e293b; color: #fff; padding: 22px 26px; border-radius: 10px 10px 0 0; }
.hotel-name { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
.hotel-tagline { color: #67e8f9; font-size: 11px; margin-bottom: 6px; }
.hotel-address { color: #94a3b8; font-size: 11px; }
.invoice-label { font-size: 22px; font-weight: 700; color: #67e8f9; text-align: right; }
.invoice-number { font-size: 12px; color: #94a3b8; text-align: right; font-family: monospace; }
.invoice-date { font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px; }
.header-inner { display: table; width: 100%; }
.header-left { display: table-cell; width: 60%; }
.header-right { display: table-cell; width: 40%; vertical-align: middle; text-align: right; }

/* Bill to section */
.bill-section { padding: 20px 26px; border-left: 3px solid #e2e8f0; margin: 18px 0; display: table; width: 100%; }
.bill-col { display: table-cell; width: 50%; vertical-align: top; }
.label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.guest-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.guest-info { font-size: 11px; color: #64748b; margin-top: 2px; }
.booking-ref { font-family: monospace; color: #0891b2; font-weight: 700; font-size: 12px; }
.booking-info { font-size: 11px; color: #64748b; margin-top: 2px; }

/* Table */
table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
thead tr { background: #f8fafc; }
th { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; padding: 9px 12px; border-bottom: 2px solid #e2e8f0; text-align: left; }
th.right, td.right { text-align: right; }
td { padding: 9px 12px; font-size: 12px; color: #374151; border-bottom: 1px solid #f1f5f9; }

/* Totals */
.totals-table { width: 220px; margin-left: auto; border-collapse: collapse; }
.totals-table td { padding: 5px 10px; font-size: 12px; }
.totals-table .total-label { color: #64748b; }
.totals-table .total-value { text-align: right; font-weight: 600; }
.totals-table .grand-total td { font-size: 14px; font-weight: 700; border-top: 2px solid #1e293b; padding-top: 8px; }
.totals-table .paid td { color: #16a34a; }
.totals-table .balance td { color: #dc2626; font-weight: 700; }

/* Status badge */
.status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-paid { background: #dcfce7; color: #15803d; }
.badge-partial { background: #fef3c7; color: #92400e; }
.badge-unpaid { background: #fee2e2; color: #b91c1c; }

/* Footer */
.footer { margin-top: 30px; text-align: center; font-size: 10px; color: #94a3b8; padding-top: 14px; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="hotel-name">{{ $settings->resort_name ?? 'Hotel' }}</div>
                @if($settings && $settings->tagline)
                <div class="hotel-tagline">{{ $settings->tagline }}</div>
                @endif
                <div class="hotel-address">{{ $settings->address ?? '' }}</div>
                <div class="hotel-address">{{ $settings->phone ?? '' }}</div>
                @if($settings && $settings->gst_number)
                <div class="hotel-address" style="margin-top:4px;">GST: {{ $settings->gst_number }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-label">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Bill To / Booking Details --}}
    <div class="bill-section">
        <div class="bill-col">
            <div class="label">Bill To</div>
            <div class="guest-name">{{ $invoice->customer?->name ?? '(Deleted Guest)' }}</div>
            @if($invoice->customer?->phone)
            <div class="guest-info">{{ $invoice->customer->phone }}</div>
            @endif
            @if($invoice->customer?->city)
            <div class="guest-info">{{ $invoice->customer->city }}{{ $invoice->customer->country ? ', ' . $invoice->customer->country : '' }}</div>
            @endif
        </div>
        <div class="bill-col" style="text-align:right;">
            <div class="label">Booking Details</div>
            <div class="booking-ref">{{ $invoice->booking->booking_number }}</div>
            @if($invoice->booking->room)
            <div class="booking-info">Room {{ $invoice->booking->room->room_number ?? '' }}</div>
            @endif
            <div class="booking-info">
                {{ $invoice->booking->check_in_date->format('d M Y') }} → {{ $invoice->booking->check_out_date->format('d M Y') }}
            </div>
            @php $nights = $invoice->booking->nights ?? 0; @endphp
            @if($nights > 0)
            <div class="booking-info">{{ $nights }} night(s)</div>
            @endif
        </div>
    </div>

    {{-- Line items table --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pricingType = $invoice->booking->room->pricing_type ?? 'per_night';
            @endphp
            @if($pricingType === 'per_night')
                <tr>
                    <td>{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }}</td>
                    <td class="right">{{ $invoice->booking->nights }}</td>
                    <td class="right">&#8377;{{ number_format($invoice->booking->room->price_per_night ?? 0) }}</td>
                    <td class="right">&#8377;{{ number_format(($invoice->booking->nights ?? 0) * ($invoice->booking->room->price_per_night ?? 0)) }}</td>
                </tr>
                @if($invoice->booking->meal_cost > 0)
                <tr>
                    <td>Meal Plan</td>
                    <td class="right">{{ $invoice->booking->nights }}</td>
                    <td class="right">—</td>
                    <td class="right">&#8377;{{ number_format($invoice->booking->meal_cost) }}</td>
                </tr>
                @endif
                @if($invoice->booking->extra_beds > 0)
                <tr>
                    <td>Extra Beds × {{ $invoice->booking->extra_beds }}</td>
                    <td class="right">{{ $invoice->booking->nights }}</td>
                    <td class="right">&#8377;{{ number_format($invoice->booking->room->extra_bed_price ?? 0) }}/bed</td>
                    <td class="right">&#8377;{{ number_format($invoice->booking->extra_bed_cost ?? 0) }}</td>
                </tr>
                @endif
            @elseif($pricingType === 'per_hour')
                <tr>
                    <td>{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }} (Hourly)</td>
                    <td class="right">{{ $invoice->booking->hours_booked ?? 1 }} hr(s)</td>
                    <td class="right">&#8377;{{ number_format($invoice->booking->room->hourly_rate ?? 0) }}/hr</td>
                    <td class="right">&#8377;{{ number_format($invoice->total_amount) }}</td>
                </tr>
            @else
                <tr>
                    <td>{{ ucfirst($invoice->booking->room->type ?? '') }} Room {{ $invoice->booking->room->room_number ?? '' }}</td>
                    <td class="right">1</td>
                    <td class="right">—</td>
                    <td class="right">&#8377;{{ number_format($invoice->total_amount) }}</td>
                </tr>
            @endif

            @foreach($invoice->booking->extraCharges as $charge)
            <tr>
                <td>{{ $charge->name }}@if($charge->notes) <span style="color:#94a3b8;font-size:10px;">({{ $charge->notes }})</span>@endif</td>
                <td class="right">{{ number_format($charge->quantity, 0) }}</td>
                <td class="right">&#8377;{{ number_format($charge->unit_price) }}</td>
                <td class="right">&#8377;{{ number_format($charge->total_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    @php
        $gstAmount    = ($settings && $settings->gst_number) ? ($invoice->total_amount * ($settings->tax_rate / 100)) : 0;
        $grandTotal   = $invoice->total_amount + $gstAmount;
        $displayBal   = max(0, $grandTotal - $invoice->paid_amount);
        $displayStatus = $displayBal <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid');
    @endphp
    <table class="totals-table">
        <tbody>
            <tr>
                <td class="total-label">Subtotal</td>
                <td class="total-value">&#8377;{{ number_format($invoice->total_amount) }}</td>
            </tr>
            @if($settings && $settings->gst_number)
            <tr>
                <td class="total-label">GST ({{ $settings->tax_rate }}%)</td>
                <td class="total-value">&#8377;{{ number_format($gstAmount) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Total</td>
                <td class="total-value">&#8377;{{ number_format($grandTotal) }}</td>
            </tr>
            <tr class="paid">
                <td>Amount Paid</td>
                <td class="total-value">&#8377;{{ number_format($invoice->paid_amount) }}</td>
            </tr>
            <tr class="balance">
                <td>Balance Due</td>
                <td class="total-value">&#8377;{{ number_format($displayBal) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Status --}}
    <div style="text-align:center;margin-top:16px;">
        <span class="status-badge {{ $displayStatus === 'paid' ? 'badge-paid' : ($displayStatus === 'partial' ? 'badge-partial' : 'badge-unpaid') }}">
            {{ strtoupper($displayStatus) }}
        </span>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Thank you for staying at {{ $settings->resort_name ?? 'our hotel' }}. We hope to welcome you again soon!
    </div>

</div>
</body>
</html>
