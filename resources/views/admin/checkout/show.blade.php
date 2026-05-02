@extends('layouts.admin')
@section('title','Process Check-Out')
@section('page-title','Process Check-Out')
@section('page-subtitle','Settle bill for ' . $booking->customer?->name ?? '(Deleted Guest)')
@section('content')
<div style="max-width:720px;" class="space-y-5">
    <a href="{{ route('checkout.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back</a>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Guest Info --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
            <h3 style="font-weight:800;color:#1e293b;margin-bottom:16px;font-size:15px;"><i class="fas fa-user" style="color:#f59e0b;margin-right:8px;"></i>Guest Details</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Name</span>
                    <span style="font-weight:700;color:#1e293b;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Phone</span>
                    <span style="font-weight:600;">{{ $booking->customer?->phone ?? '' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room</span>
                    <span style="font-weight:800;font-size:22px;color:#0f172a;">{{ $booking->is_whole_hotel ? 'Whole Hotel' : ($booking->room?->room_number ?? '—') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room Type</span>
                    <span style="font-weight:600;">{{ $booking->is_whole_hotel ? 'All Rooms' : ucfirst($booking->room?->type ?? '—') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Booking #</span>
                    <span style="font-family:monospace;font-weight:700;color:#0891b2;">{{ $booking->booking_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Check-In</span>
                    <span style="font-weight:700;">
                        {{ \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->format('d M Y') }}
                        @if($pricingType === 'per_hour' && $booking->actual_checkin_at)
                        <span style="color:#7c3aed;font-size:12px;margin-left:4px;">{{ \Carbon\Carbon::parse($booking->actual_checkin_at)->format('h:i A') }}</span>
                        @endif
                    </span>
                </div>
                @if($pricingType === 'per_hour' && $booking->actual_checkin_at)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Current Time</span>
                    <span style="font-weight:700;color:#7c3aed;" id="liveClockCheckout">—</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Check-Out Date</span>
                    <span style="font-weight:700;">{{ $booking->check_out_date->format('d M Y') }}</span>
                </div>
                @if($pricingType === 'per_night')
                <div style="display:flex;justify-content:space-between;font-size:12px;background:#f8fafc;border-radius:8px;padding:7px 10px;margin-top:2px;">
                    <span style="color:#64748b;display:flex;align-items:center;gap:5px;"><i class="fas fa-hotel" style="color:#94a3b8;font-size:10px;"></i>Hotel times</span>
                    <span style="font-weight:600;color:#475569;">
                        <i class="fas fa-sign-in-alt" style="color:#16a34a;font-size:10px;margin-right:3px;"></i>{{ $hotelCheckInTime }}
                        &nbsp;·&nbsp;
                        <i class="fas fa-sign-out-alt" style="color:#ef4444;font-size:10px;margin-right:3px;"></i>{{ $hotelCheckOutTime }}
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Nights Charged</span>
                    <span style="font-weight:800;font-size:20px;color:#0891b2;" id="nightsDisplay">{{ $actualNights }}</span>
                </div>
                @elseif($pricingType === 'per_hour')
                @if($booking->price_overridden)
                <div style="display:flex;justify-content:space-between;font-size:13px;align-items:center;">
                    <span style="color:#64748b;">Pricing</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#fef3c7;color:#92400e;border:1px solid #fbbf24;border-radius:8px;padding:3px 10px;font-size:12px;font-weight:700;">
                        <i class="fas fa-lock" style="font-size:10px;"></i> Fixed rate agreed at booking
                    </span>
                </div>
                @else
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Actual Hours Billed</span>
                    <span style="font-weight:800;font-size:20px;color:#7c3aed;">{{ $hoursBooked ?? '—' }}</span>
                </div>
                @endif
                @else
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Time Slot</span>
                    <span style="font-weight:700;font-size:14px;color:#0891b2;">{{ $booking->timeSlot?->name ?? 'Slot booking' }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Guests</span>
                    <span style="font-weight:600;">{{ $booking->adults }} Adults{{ $booking->children > 0 ? ', ' . $booking->children . ' Children' : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Bill Summary --}}
        @php
            $taxRate       = ($settings && $settings->gst_number && $settings->tax_rate > 0) ? (float)$settings->tax_rate : 0;
            $gstAmount     = round($actualTotal * ($taxRate / 100), 2);
            $grandTotal    = $actualTotal + $gstAmount;
            $gstBalanceDue = max(0, $grandTotal - $totalPaid);
        @endphp
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
            <h3 style="font-weight:800;color:#1e293b;margin-bottom:16px;font-size:15px;"><i class="fas fa-receipt" style="color:#f59e0b;margin-right:8px;"></i>Bill Summary</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @if($pricingType === 'per_night')
                @if($booking->price_overridden)
                <div style="display:flex;justify-content:space-between;font-size:13px;align-items:center;">
                    <span style="color:#64748b;display:inline-flex;align-items:center;gap:5px;">
                        <i class="fas fa-pen" style="color:#f59e0b;font-size:10px;"></i>
                        Room charge <span style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;border-radius:6px;padding:1px 6px;font-size:10px;font-weight:700;margin-left:4px;">custom price</span>
                    </span>
                    <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
                </div>
                @else
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">{{ $actualNights }} night{{ $actualNights != 1 ? 's' : '' }} × ₹{{ number_format($booking->room?->price_per_night ?? 0) }}/night</span>
                    <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
                </div>
                @endif
                @elseif($pricingType === 'per_hour')
                @if($booking->price_overridden)
                <div style="display:flex;justify-content:space-between;font-size:13px;align-items:center;">
                    <span style="color:#64748b;display:inline-flex;align-items:center;gap:5px;">
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fbbf24;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;">
                            <i class="fas fa-lock" style="font-size:9px;"></i> Fixed rate agreed at booking
                        </span>
                    </span>
                    <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
                </div>
                @else
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">
                        {{ $hoursBooked ?? 0 }} actual hr{{ ($hoursBooked ?? 0) != 1 ? 's' : '' }} × ₹{{ number_format($booking->room?->hourly_rate ?? 0) }}/hr
                    </span>
                    <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
                </div>
                @endif
                @else
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Slot: {{ $booking->timeSlot?->name ?? 'Fixed slot' }}
                        @if($booking->timeSlot) ({{ $booking->timeSlot->start_time }}–{{ $booking->timeSlot->end_time }}) @endif
                    </span>
                    <span style="font-weight:700;">₹{{ number_format($roomCost) }}</span>
                </div>
                @endif
                @if($mealCost > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">🍽️ Meal Plan</span>
                    <span style="font-weight:600;">₹{{ number_format($mealCost) }}</span>
                </div>
                @endif
                @if($extraBedCost > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">🛏️ Extra Bed ({{ $booking->extra_beds }})</span>
                    <span style="font-weight:600;">₹{{ number_format($extraBedCost) }}</span>
                </div>
                @endif
                @if(($extraChargesTotal ?? 0) > 0)
                <div style="border-top:1px dashed #e2e8f0;padding-top:8px;margin-top:2px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">🍽 Food &amp; Extra Bill Summary</div>
                    @foreach($booking->extraCharges as $ec)
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#475569;"><i class="fas fa-circle" style="margin-right:5px;font-size:7px;color:#94a3b8;"></i>{{ $ec->name }}
                            @if($ec->quantity != 1)<span style="color:#94a3b8;font-size:11px;"> ×{{ $ec->quantity }}</span>@endif
                        </span>
                        <span style="font-weight:600;color:#1e293b;">₹{{ number_format($ec->total_price) }}</span>
                    </div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;font-size:12px;border-top:1px dotted #e2e8f0;padding-top:5px;margin-top:3px;">
                        <span style="color:#64748b;font-weight:600;">Food &amp; Extra Total</span>
                        <span style="font-weight:700;color:#0f172a;">₹{{ number_format($extraChargesTotal) }}</span>
                    </div>
                </div>
                @endif
                @if($mealCost > 0 || $extraBedCost > 0 || ($extraChargesTotal ?? 0) > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px dashed #e2e8f0;padding-top:8px;">
                    <span style="color:#64748b;">Subtotal</span>
                    <span style="font-weight:700;">₹{{ number_format($actualTotal) }}</span>
                </div>
                @endif
                @if($taxRate > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">GST ({{ $taxRate }}%)</span>
                    <span style="font-weight:600;">₹{{ number_format($gstAmount) }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Total Charges</span>
                    <span style="font-weight:800;">₹{{ number_format($grandTotal) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Amount Paid</span>
                    <span style="font-weight:700;color:#16a34a;">₹{{ number_format($totalPaid) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;border-top:2px solid #0f172a;padding-top:12px;margin-top:4px;">
                    <span>Balance Due</span>
                    <span style="color:{{ $gstBalanceDue > 0 ? '#ef4444' : '#16a34a' }};font-size:26px;">₹{{ number_format($gstBalanceDue) }}</span>
                </div>
            </div>

            @if($booking->payments->where('status','completed')->count() > 0)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                <p style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Payment History</p>
                @foreach($booking->payments->where('status','completed') as $pmt)
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                    <span style="color:#64748b;">{{ ucfirst($pmt->payment_type) }} ({{ ucfirst($pmt->payment_method) }}) — {{ $pmt->created_at->format('d M Y') }}</span>
                    <span style="font-weight:700;color:#16a34a;">₹{{ number_format($pmt->amount) }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Overstay Banner --}}
    @if($isOverstay && $pricingType === 'per_night')
    <div style="background:#fffbeb;border:2px solid #f59e0b;border-radius:16px;padding:18px 20px;display:flex;align-items:flex-start;gap:14px;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#ea580c);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:16px;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:800;color:#92400e;font-size:15px;margin-bottom:4px;">Guest Overstay Detected</div>
            <div style="font-size:13px;color:#b45309;line-height:1.6;">
                Booking: <strong>{{ \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }}</strong>
                ({{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }})
                &nbsp;·&nbsp;
                Today: <strong>{{ now()->format('d M Y') }}</strong>
                @if($overstayNights > 0)
                — <span style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:700;">+{{ $overstayNights }} extra night{{ $overstayNights != 1 ? 's' : '' }}</span>
                @else
                — <span style="background:#fef3c7;color:#92400e;border:1px solid #fbbf24;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:700;">Past checkout time ({{ $hotelCheckOutTime }})</span>
                @endif
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" onclick="chooseActualNights()"
                    style="padding:9px 16px;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(245,158,11,.35);">
                    <i class="fas fa-calculator"></i>
                    @if($overstayNights > 0)
                        Charge Actual Stay ({{ $chargeableNights }} nights — ₹{{ number_format($chargeableNights * ($booking->room?->price_per_night ?? 0)) }})
                    @else
                        Charge Extra Night ({{ $chargeableNights }} nights — ₹{{ number_format($chargeableNights * ($booking->room?->price_per_night ?? 0)) }})
                    @endif
                </button>
                <button type="button" onclick="chooseBookingNights()"
                    style="padding:9px 16px;background:#fff;color:#92400e;border:1.5px solid #f59e0b;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-calendar-check"></i> Continue with Booking ({{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }})
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Early Checkout Banner --}}
    @if($isEarlyCheckout && $pricingType === 'per_night')
    <div style="background:#eff6ff;border:2px solid #3b82f6;border-radius:16px;padding:18px 20px;display:flex;align-items:flex-start;gap:14px;">
        <div style="width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="fas fa-clock" style="color:#fff;font-size:16px;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:800;color:#1d4ed8;font-size:15px;margin-bottom:4px;">Early Check-Out</div>
            <div style="font-size:13px;color:#1e40af;line-height:1.6;">
                Booked until <strong>{{ $booking->check_out_date->format('d M Y') }}</strong>
                ({{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }})
                &nbsp;·&nbsp;
                Checking out today: <strong>{{ now()->format('d M Y') }}</strong>
                — <span style="background:#dbeafe;color:#1d4ed8;border:1px solid #93c5fd;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:700;">{{ $bookingNights - $actualDaysStayed }} night{{ ($bookingNights - $actualDaysStayed) != 1 ? 's' : '' }} early</span>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" onclick="chooseEarlyActual()"
                    style="padding:9px 16px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(59,130,246,.35);">
                    <i class="fas fa-calendar-day"></i> Charge Actual Stay ({{ $actualDaysStayed }} night{{ $actualDaysStayed != 1 ? 's' : '' }} — ₹{{ number_format($actualDaysStayed * ($booking->room?->price_per_night ?? 0)) }})
                </button>
                <button type="button" onclick="chooseEarlyFull()"
                    style="padding:9px 16px;background:#fff;color:#1d4ed8;border:1.5px solid #3b82f6;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-receipt"></i> Charge Full Booking ({{ $bookingNights }} nights — ₹{{ number_format($bookingNights * ($booking->room?->price_per_night ?? 0)) }})
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Process Form --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;color:#1e293b;margin-bottom:20px;font-size:15px;"><i class="fas fa-sign-out-alt" style="color:#f59e0b;margin-right:8px;"></i>Complete Check-Out</h3>
        <form id="checkoutForm" action="{{ route('checkout.process', $booking->id) }}" method="POST">
            @csrf
            <input type="hidden" name="override_nights" id="overrideNightsInput" value="">
            @if($pricingType === 'per_hour')
            @if($booking->price_overridden)
            <div style="background:#fffbeb;border:1.5px solid #fbbf24;border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:#f59e0b;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-lock" style="color:#fff;font-size:14px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:14px;color:#92400e;">Fixed rate agreed at booking</div>
                    <div style="font-size:12px;color:#b45309;margin-top:2px;">A custom price of <strong>₹{{ number_format($roomCost) }}</strong> was set at the time of booking. Hours-based billing does not apply.</div>
                </div>
            </div>
            @else
            <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;padding:16px;margin-bottom:20px;">
                <label class="form-label" style="color:#7c3aed;"><i class="fas fa-clock mr-1"></i>Actual Hours Stayed</label>
                <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
                    <input type="number" name="override_hours" id="overrideHours"
                        value="{{ $hoursBooked }}" min="1" step="1"
                        style="width:100px;padding:10px 14px;border:2px solid #7c3aed;border-radius:10px;font-size:18px;font-weight:800;color:#7c3aed;text-align:center;">
                    <div>
                        <div style="font-size:12px;color:#64748b;">Check-in: <strong style="color:#7c3aed;">{{ $booking->actual_checkin_at ? \Carbon\Carbon::parse($booking->actual_checkin_at)->format('h:i A') : '—' }}</strong></div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">Rate: <strong>₹{{ number_format($booking->room?->hourly_rate ?? 0) }}/hr</strong></div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Adjust hours if needed — bill updates automatically.</div>
                    </div>
                </div>
                <div style="margin-top:10px;font-size:13px;color:#64748b;">
                    Estimated total: <span id="hoursTotal" style="font-weight:800;color:#7c3aed;font-size:16px;">₹{{ number_format($hoursBooked * ($booking->room?->hourly_rate ?? 0)) }}</span>
                    @if($taxRate > 0)<span style="font-size:11px;color:#94a3b8;"> + {{ $taxRate }}% GST</span>@endif
                </div>
            </div>
            @endif
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Final Payment (₹){{ $taxRate > 0 ? ' incl. GST' : '' }}</label>
                    <input type="number" name="final_payment" value="{{ $gstBalanceDue }}" min="0" step="0.01" class="form-input" id="finalPaymentInput">
                    <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Pre-filled with balance due{{ $taxRate > 0 ? ' (incl. ' . $taxRate . '% GST)' : '' }}. Adjust if needed.</p>
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="coPaymentMethod" class="form-input" onchange="toggleCoUpiBtn(this.value)">
                        <option value="cash">Cash</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                    @if(\App\Models\Module::isEnabled('payment_links'))
                    <button type="button" id="coUpiQrBtn" onclick="showCoUpiQr()"
                        style="display:none;margin-top:8px;width:100%;padding:9px 0;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:none;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-qrcode"></i> Show UPI QR for Guest
                    </button>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Check-Out Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any remarks about the stay or departure..."></textarea>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;">
                <button type="button" onclick="showVoidModal()"
                    style="display:inline-flex;align-items:center;background:#fff;color:#ef4444;border:1.5px solid #fca5a5;padding:9px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">
                    <i class="fas fa-ban" style="margin-right:7px;"></i>Void / Cancel Booking
                </button>
                <div style="display:flex;gap:10px;">
                    <a href="{{ route('checkout.index') }}" class="btn-secondary">Back</a>
                    <button type="submit" style="display:inline-flex;align-items:center;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;padding:11px 24px;border-radius:12px;font-weight:700;font-size:14px;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(245,158,11,.4);">
                        <i class="fas fa-sign-out-alt" style="margin-right:8px;"></i>Complete Check-Out & Generate Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Overstay Confirmation Modal --}}
@if($isOverstay && $pricingType === 'per_night')
<div id="overstayModal" style="display:none;position:fixed;inset:0;z-index:70;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:440px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#f59e0b,#ea580c);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:20px;"></i>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Guest Overstay — Choose Billing</div>
                <div style="color:#fef3c7;font-size:12px;">{{ $booking->customer?->name }} — Booking #{{ $booking->booking_number }}</div>
            </div>
        </div>
        <div style="padding:24px;">
            <p style="font-size:13px;color:#475569;margin-bottom:18px;line-height:1.6;">
                @if($overstayNights > 0)
                    Booked for <strong>{{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }}</strong>
                    ({{ \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }})
                    but has stayed <strong>{{ $todayNights }} nights</strong> as of today
                    (<strong>+{{ $overstayNights }} extra</strong>).
                @else
                    Booked checkout was <strong>{{ $booking->check_out_date->format('d M Y') }}</strong>
                    and it is now past the hotel checkout time of <strong>{{ $hotelCheckOutTime }}</strong>.
                    One additional night may apply.
                @endif
                <br>How should this checkout be billed?
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button type="button" onclick="confirmActualNights()"
                    style="width:100%;padding:13px 16px;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;border:none;border-radius:12px;font-weight:800;font-size:13px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-calculator" style="font-size:15px;flex-shrink:0;"></i>
                    <div>
                        @if($overstayNights > 0)
                        <div>Charge Actual Stay — {{ $chargeableNights }} nights</div>
                        @else
                        <div>Charge Extra Night — {{ $chargeableNights }} nights total</div>
                        @endif
                        <div style="font-size:11px;font-weight:600;opacity:.85;">₹{{ number_format($chargeableNights * ($booking->room?->price_per_night ?? 0)) }} room charge (before extras & GST)</div>
                    </div>
                </button>
                <button type="button" onclick="confirmBookingNights()"
                    style="width:100%;padding:13px 16px;background:#f1f5f9;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:12px;font-weight:700;font-size:13px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-calendar-check" style="font-size:15px;color:#64748b;flex-shrink:0;"></i>
                    <div>
                        <div>Continue with Booking — {{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }}</div>
                        <div style="font-size:11px;font-weight:600;color:#64748b;">₹{{ number_format($bookingNights * ($booking->room?->price_per_night ?? 0)) }} room charge (before extras & GST)</div>
                    </div>
                </button>
                <button type="button" onclick="document.getElementById('overstayModal').style.display='none'"
                    style="width:100%;padding:9px;background:transparent;border:none;color:#94a3b8;font-size:12px;cursor:pointer;margin-top:2px;">Cancel — go back</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Early Checkout Confirmation Modal --}}
@if($isEarlyCheckout && $pricingType === 'per_night')
<div id="earlyModal" style="display:none;position:fixed;inset:0;z-index:70;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:440px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-clock" style="color:#fff;font-size:20px;"></i>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Early Check-Out — Choose Billing</div>
                <div style="color:#bfdbfe;font-size:12px;">{{ $booking->customer?->name }} — Booking #{{ $booking->booking_number }}</div>
            </div>
        </div>
        <div style="padding:24px;">
            <p style="font-size:13px;color:#475569;margin-bottom:18px;line-height:1.6;">
                This guest was booked until <strong>{{ $booking->check_out_date->format('d M Y') }}</strong>
                ({{ $bookingNights }} night{{ $bookingNights != 1 ? 's' : '' }})
                but is checking out today <strong>{{ now()->format('d M Y') }}</strong>
                — <strong>{{ $bookingNights - $actualDaysStayed }} night{{ ($bookingNights - $actualDaysStayed) != 1 ? 's' : '' }} early</strong>.
                <br>How should this checkout be billed?
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button type="button" onclick="confirmEarlyActual()"
                    style="width:100%;padding:13px 16px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:12px;font-weight:800;font-size:13px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-calendar-day" style="font-size:15px;flex-shrink:0;"></i>
                    <div>
                        <div>Charge Actual Stay — {{ $actualDaysStayed }} night{{ $actualDaysStayed != 1 ? 's' : '' }}</div>
                        <div style="font-size:11px;font-weight:600;opacity:.85;">₹{{ number_format($actualDaysStayed * ($booking->room?->price_per_night ?? 0)) }} room charge (before extras & GST)</div>
                    </div>
                </button>
                <button type="button" onclick="confirmEarlyFull()"
                    style="width:100%;padding:13px 16px;background:#f1f5f9;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:12px;font-weight:700;font-size:13px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-receipt" style="font-size:15px;color:#64748b;flex-shrink:0;"></i>
                    <div>
                        <div>Charge Full Booking — {{ $bookingNights }} nights</div>
                        <div style="font-size:11px;font-weight:600;color:#64748b;">₹{{ number_format($bookingNights * ($booking->room?->price_per_night ?? 0)) }} room charge (before extras & GST)</div>
                    </div>
                </button>
                <button type="button" onclick="document.getElementById('earlyModal').style.display='none'"
                    style="width:100%;padding:9px;background:transparent;border:none;color:#94a3b8;font-size:12px;cursor:pointer;margin-top:2px;">Cancel — go back</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Void/Cancel Modal --}}
<div id="voidModal" style="display:none;position:fixed;inset:0;z-index:60;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:420px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#ef4444,#dc2626);padding:18px 22px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-ban" style="color:#fff;font-size:20px;"></i>
            <div>
                <div style="font-weight:800;color:#fff;font-size:15px;">Void / Cancel Booking</div>
                <div style="color:#fecaca;font-size:12px;">Booking #{{ $booking->booking_number }} — {{ $booking->customer?->name }}</div>
            </div>
        </div>
        <div style="padding:24px;">
            <p style="font-size:13px;color:#475569;margin-bottom:16px;">This will cancel the booking and free up the room. No invoice will be generated. This action cannot be undone.</p>
            <form action="{{ route('checkout.void', $booking->id) }}" method="POST">
                @csrf
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:700;color:#64748b;display:block;margin-bottom:6px;">Reason for cancellation (optional)</label>
                    <input type="text" name="reason" placeholder="e.g. Guest did not stay, test booking, early departure..."
                        style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;">
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="document.getElementById('voidModal').style.display='none'"
                        style="flex:1;padding:10px;background:#f1f5f9;border:none;border-radius:10px;font-weight:700;font-size:13px;color:#475569;cursor:pointer;">
                        Keep Booking
                    </button>
                    <button type="submit"
                        style="flex:1;padding:10px;background:#ef4444;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">
                        <i class="fas fa-ban" style="margin-right:5px;"></i>Confirm Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function showVoidModal() {
    document.getElementById('voidModal').style.display = 'flex';
}
document.getElementById('voidModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// ── Overstay JS ────────────────────────────────────────────────────────────
var _isOverstay          = {{ $isOverstay ? 'true' : 'false' }};
var _overstayNights      = {{ $overstayNights ?? 0 }};
var _bookingNights       = {{ $bookingNights ?? 0 }};
var _chargeableNights    = {{ $chargeableNights ?? 0 }};
var _nightlyRate         = {{ (float)($booking->room?->price_per_night ?? 0) }};
var _mealCost            = {{ (float)($booking->meal_cost ?? 0) }};
var _extraBedCost        = {{ (float)($booking->extra_bed_cost ?? 0) }};
var _extraTotal          = {{ $extraChargesTotal ?? 0 }};
var _taxRate             = {{ isset($taxRate) ? $taxRate : 0 }};
var _overstayDecisionMade = false;

function recalcAndSetPayment(nights) {
    var roomCost  = nights * _nightlyRate;
    var base      = roomCost + _mealCost + _extraBedCost + _extraTotal;
    var gst       = _taxRate > 0 ? Math.round(base * (_taxRate / 100) * 100) / 100 : 0;
    var grand     = base + gst;
    var totalPaid = {{ $totalPaid }};
    var balance   = Math.max(0, grand - totalPaid);
    var payEl     = document.getElementById('finalPaymentInput');
    if (payEl) payEl.value = balance.toFixed(2);
    var nightEl = document.getElementById('nightsDisplay');
    if (nightEl) nightEl.textContent = nights;
}

function chooseActualNights() {
    document.getElementById('overrideNightsInput').value = _chargeableNights;
    _overstayDecisionMade = true;
    recalcAndSetPayment(_chargeableNights);
    markBannerChoice('actual');
}

function chooseBookingNights() {
    document.getElementById('overrideNightsInput').value = '';
    _overstayDecisionMade = true;
    recalcAndSetPayment(_bookingNights);
    markBannerChoice('booking');
}

function confirmActualNights() {
    document.getElementById('overrideNightsInput').value = _chargeableNights;
    _overstayDecisionMade = true;
    recalcAndSetPayment(_chargeableNights);
    var m = document.getElementById('overstayModal');
    if (m) m.style.display = 'none';
    document.getElementById('checkoutForm').submit();
}

function confirmBookingNights() {
    document.getElementById('overrideNightsInput').value = '';
    _overstayDecisionMade = true;
    recalcAndSetPayment(_bookingNights);
    var m = document.getElementById('overstayModal');
    if (m) m.style.display = 'none';
    document.getElementById('checkoutForm').submit();
}

function markBannerChoice(choice) {
    var btns = document.querySelectorAll('[onclick="chooseActualNights()"], [onclick="chooseBookingNights()"]');
    btns.forEach(function(b) {
        b.style.opacity = '0.45';
        b.style.pointerEvents = 'none';
    });
    var chosen = document.querySelector('[onclick="choose' + (choice === 'actual' ? 'Actual' : 'Booking') + 'Nights()"]');
    if (chosen) { chosen.style.opacity = '1'; chosen.style.pointerEvents = 'auto'; chosen.style.outline = '2px solid #1e293b'; }
}

// Intercept form submit — overstay decision required
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    if (_isOverstay && !_overstayDecisionMade) {
        e.preventDefault();
        var m = document.getElementById('overstayModal');
        if (m) m.style.display = 'flex';
        return;
    }
    // Early checkout — must also make a decision
    if (_isEarlyCheckout && !_earlyDecisionMade) {
        e.preventDefault();
        var m = document.getElementById('earlyModal');
        if (m) m.style.display = 'flex';
    }
});

var overstayModalEl = document.getElementById('overstayModal');
if (overstayModalEl) {
    overstayModalEl.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
}

// ── Early Checkout JS ───────────────────────────────────────────────────────
var _isEarlyCheckout   = {{ $isEarlyCheckout ? 'true' : 'false' }};
var _actualDaysStayed  = {{ $actualDaysStayed ?? 0 }};
var _earlyDecisionMade = false;

function chooseEarlyActual() {
    document.getElementById('overrideNightsInput').value = _actualDaysStayed;
    _earlyDecisionMade = true;
    recalcAndSetPayment(_actualDaysStayed);
    markEarlyChoice('actual');
}

function chooseEarlyFull() {
    document.getElementById('overrideNightsInput').value = _bookingNights;
    _earlyDecisionMade = true;
    recalcAndSetPayment(_bookingNights);
    markEarlyChoice('full');
}

function confirmEarlyActual() {
    document.getElementById('overrideNightsInput').value = _actualDaysStayed;
    _earlyDecisionMade = true;
    recalcAndSetPayment(_actualDaysStayed);
    var m = document.getElementById('earlyModal');
    if (m) m.style.display = 'none';
    document.getElementById('checkoutForm').submit();
}

function confirmEarlyFull() {
    document.getElementById('overrideNightsInput').value = _bookingNights;
    _earlyDecisionMade = true;
    recalcAndSetPayment(_bookingNights);
    var m = document.getElementById('earlyModal');
    if (m) m.style.display = 'none';
    document.getElementById('checkoutForm').submit();
}

function markEarlyChoice(choice) {
    var btns = document.querySelectorAll('[onclick="chooseEarlyActual()"], [onclick="chooseEarlyFull()"]');
    btns.forEach(function(b) { b.style.opacity = '0.45'; b.style.pointerEvents = 'none'; });
    var chosen = document.querySelector('[onclick="chooseEarly' + (choice === 'actual' ? 'Actual' : 'Full') + '()"]');
    if (chosen) { chosen.style.opacity = '1'; chosen.style.pointerEvents = 'auto'; chosen.style.outline = '2px solid #1e293b'; }
}

var earlyModalEl = document.getElementById('earlyModal');
if (earlyModalEl) {
    earlyModalEl.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
}
</script>

@if(\App\Models\Module::isEnabled('payment_links'))
<div id="coUpiModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:340px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <i class="fas fa-qrcode" style="color:#fff;font-size:20px;"></i>
                <div>
                    <div style="font-weight:800;color:#fff;font-size:15px;">UPI Payment</div>
                    <div style="color:#ddd6fe;font-size:12px;">Guest scans to pay instantly</div>
                </div>
            </div>
            <button onclick="closeCoUpiModal()" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:18px;cursor:pointer;">&times;</button>
        </div>
        <div id="coUpiQrBody" style="padding:24px;text-align:center;">
            <div style="display:flex;align-items:center;justify-content:center;height:120px;">
                <div style="width:40px;height:40px;border:3px solid #7c3aed;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
            </div>
        </div>
    </div>
</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
var _coUpiBalance = {{ $gstBalanceDue }};
var _coBookingNum = {!! json_encode($booking->booking_number) !!};
var _coGuestName  = {!! json_encode($booking->customer?->name ?? '(Deleted Guest)') !!};
var _coGuestPhone = {!! json_encode(preg_replace('/[^0-9]/', '', $booking->customer?->phone ?? '')) !!};
var _coUpiId      = '';

function toggleCoUpiBtn(method) {
    var btn = document.getElementById('coUpiQrBtn');
    if (!btn) return;
    btn.style.display = (method === 'upi') ? 'flex' : 'none';
}

function showCoUpiQr() {
    var modal = document.getElementById('coUpiModal');
    modal.style.display = 'flex';
    document.getElementById('coUpiQrBody').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:120px;"><div style="width:40px;height:40px;border:3px solid #7c3aed;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div></div>';

    fetch('/payment-links/upi-config', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(cfg => {
            if (cfg.error) {
                document.getElementById('coUpiQrBody').innerHTML = '<p style="color:#ef4444;font-weight:600;padding:16px 0;">' + cfg.error + '</p>';
                return;
            }
            var amt    = parseFloat(_coUpiBalance).toFixed(2);
            var note   = 'Checkout ' + _coBookingNum;
            var upiUrl = 'upi://pay?pa=' + encodeURIComponent(cfg.upi_id)
                       + '&pn=' + encodeURIComponent(cfg.upi_name)
                       + '&am=' + amt
                       + '&cu=INR'
                       + '&tn=' + encodeURIComponent(note);
            _coUpiId = cfg.upi_id;

            document.getElementById('coUpiQrBody').innerHTML =
                '<div id="coQrCanvas" style="width:220px;height:220px;margin:0 auto;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.08);background:#fff;display:flex;align-items:center;justify-content:center;"></div>' +
                '<p style="margin-top:14px;font-size:22px;font-weight:900;color:#1e293b;">₹' + parseFloat(_coUpiBalance).toLocaleString('en-IN') + '</p>' +
                '<p style="font-size:13px;color:#64748b;margin-top:2px;">' + cfg.upi_name + '</p>' +
                '<p style="font-size:11px;color:#94a3b8;font-family:monospace;margin-top:2px;">' + cfg.upi_id + '</p>' +
                '<p style="font-size:11px;color:#94a3b8;margin-top:10px;">Scan with GPay · PhonePe · Paytm · any UPI app</p>' +
                '<div style="display:flex;gap:8px;margin-top:14px;">' +
                  '<button onclick="downloadCoQr()" style="flex:1;padding:9px 0;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;"><i class="fas fa-download"></i> Download QR</button>' +
                  '<button onclick="sendCoWhatsApp()" style="flex:1;padding:9px 0;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;"><i class="fab fa-whatsapp"></i> Send to Guest</button>' +
                '</div>' +
                '<button onclick="confirmCoUpiPaid()" style="margin-top:10px;width:100%;padding:11px;background:#0891b2;color:#fff;border:none;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:7px;"><i class="fas fa-check-circle"></i> Payment Received — Record &amp; Complete Checkout</button>' +
                '<button onclick="closeCoUpiModal()" style="margin-top:6px;width:100%;padding:8px;background:#f1f5f9;border:none;border-radius:10px;font-weight:600;font-size:12px;color:#475569;cursor:pointer;">Cancel</button>';

            loadQrLib(function() {
                new QRCode(document.getElementById('coQrCanvas'), {
                    text: upiUrl, width: 220, height: 220,
                    colorDark: '#1e293b', colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        })
        .catch(() => {
            document.getElementById('coUpiQrBody').innerHTML = '<p style="color:#ef4444;padding:16px 0;">Failed to load. Please try again.</p>';
        });
}

function closeCoUpiModal() {
    document.getElementById('coUpiModal').style.display = 'none';
}

function confirmCoUpiPaid() {
    var sel = document.getElementById('coPaymentMethod');
    if (sel) sel.value = 'upi';
    var amtInput = document.querySelector('#checkoutForm input[name="final_payment"]');
    if (amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) {
        amtInput.value = parseFloat(_coUpiBalance).toFixed(2);
    }
    closeCoUpiModal();
    document.getElementById('checkoutForm').submit();
}

function downloadCoQr() {
    var canvas = document.querySelector('#coQrCanvas canvas');
    if (!canvas) { alert('QR not ready — please wait a moment and try again.'); return; }
    var link = document.createElement('a');
    link.download = 'UPI-QR-' + _coBookingNum + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function sendCoWhatsApp() {
    var phone = _coGuestPhone;
    if (phone.length === 10) phone = '91' + phone;
    var msg = 'Dear ' + _coGuestName + ','
            + '\nYour checkout bill for Booking #' + _coBookingNum + ' is ₹' + parseFloat(_coUpiBalance).toLocaleString('en-IN') + '.'
            + '\n\nPlease pay via UPI: *' + _coUpiId + '*'
            + '\n\nThank you for staying with us!';
    window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank');
}

function loadQrLib(cb) {
    if (window.QRCode) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    s.onload = cb;
    document.head.appendChild(s);
}

document.getElementById('coUpiModal').addEventListener('click', function(e) {
    if (e.target === this) closeCoUpiModal();
});
</script>
@endif
@if($pricingType === 'per_hour')
<script>
// Live clock
(function() {
    function updateClock() {
        var el = document.getElementById('liveClockCheckout');
        if (!el) return;
        var now = new Date();
        var h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        el.textContent = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s + ' ' + ampm;
    }
    updateClock();
    setInterval(updateClock, 1000);
})();

// Hours override → recalculate estimated total display
var _hourlyRate = {{ (float)($booking->room?->hourly_rate ?? 0) }};
var _taxRate    = {{ $taxRate }};
var _addonTotal = {{ $booking->bookingAddOns ? $booking->bookingAddOns->sum('price') : 0 }};

function recalcHourlyTotal() {
    var hoursEl  = document.getElementById('overrideHours');
    var totalEl  = document.getElementById('hoursTotal');
    var payEl    = document.getElementById('finalPaymentInput');
    if (!hoursEl) return;
    var hours    = parseInt(hoursEl.value) || 1;
    var roomCost = hours * _hourlyRate + _addonTotal;
    var gst      = _taxRate > 0 ? Math.round(roomCost * (_taxRate / 100) * 100) / 100 : 0;
    var grand    = roomCost + gst;
    if (totalEl) totalEl.textContent = '₹' + roomCost.toLocaleString('en-IN');
    if (payEl) payEl.value = grand.toFixed(2);
}

var overrideEl = document.getElementById('overrideHours');
if (overrideEl) overrideEl.addEventListener('input', recalcHourlyTotal);
</script>
@endif
@endsection
