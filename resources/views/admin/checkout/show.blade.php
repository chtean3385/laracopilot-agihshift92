@extends('layouts.admin')
@section('title','Process Check-Out')
@section('page-title','Process Check-Out')
@section('page-subtitle','Settle bill for ' . $booking->customer->name)
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
                    <span style="font-weight:700;color:#1e293b;">{{ $booking->customer->name }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Phone</span>
                    <span style="font-weight:600;">{{ $booking->customer->phone }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room</span>
                    <span style="font-weight:800;font-size:22px;color:#0f172a;">{{ $booking->room->room_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Room Type</span>
                    <span style="font-weight:600;">{{ ucfirst($booking->room->type) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Booking #</span>
                    <span style="font-family:monospace;font-weight:700;color:#0891b2;">{{ $booking->booking_number }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Check-In Date</span>
                    <span style="font-weight:700;">{{ \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Check-Out Date</span>
                    <span style="font-weight:700;">{{ $booking->check_out_date->format('d M Y') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Booked Nights</span>
                    <span style="font-weight:800;font-size:20px;color:#0891b2;">{{ $actualNights }}</span>
                </div>
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
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">{{ $actualNights }} nights × Rs{{ number_format($booking->room->price_per_night) }}</span>
                    <span style="font-weight:700;">Rs{{ number_format($actualTotal) }}</span>
                </div>
                @if($taxRate > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">GST ({{ $taxRate }}%)</span>
                    <span style="font-weight:600;">Rs{{ number_format($gstAmount) }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid #f1f5f9;padding-top:10px;">
                    <span style="color:#64748b;">Total Charges</span>
                    <span style="font-weight:800;">Rs{{ number_format($grandTotal) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:#64748b;">Amount Paid</span>
                    <span style="font-weight:700;color:#16a34a;">Rs{{ number_format($totalPaid) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;border-top:2px solid #0f172a;padding-top:12px;margin-top:4px;">
                    <span>Balance Due</span>
                    <span style="color:{{ $gstBalanceDue > 0 ? '#ef4444' : '#16a34a' }};font-size:26px;">Rs{{ number_format($gstBalanceDue) }}</span>
                </div>
            </div>

            @if($booking->payments->where('status','completed')->count() > 0)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
                <p style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Payment History</p>
                @foreach($booking->payments->where('status','completed') as $pmt)
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                    <span style="color:#64748b;">{{ ucfirst($pmt->payment_type) }} ({{ ucfirst($pmt->payment_method) }}) — {{ $pmt->created_at->format('d M Y') }}</span>
                    <span style="font-weight:700;color:#16a34a;">Rs{{ number_format($pmt->amount) }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Process Form --}}
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;color:#1e293b;margin-bottom:20px;font-size:15px;"><i class="fas fa-sign-out-alt" style="color:#f59e0b;margin-right:8px;"></i>Complete Check-Out</h3>
        <form action="{{ route('checkout.process', $booking->id) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Final Payment (Rs){{ $taxRate > 0 ? ' incl. GST' : '' }}</label>
                    <input type="number" name="final_payment" value="{{ $gstBalanceDue }}" min="0" step="0.01" class="form-input">
                    <p style="font-size:12px;color:#94a3b8;margin-top:4px;">Pre-filled with balance due{{ $taxRate > 0 ? ' (incl. ' . $taxRate . '% GST)' : '' }}. Adjust if needed.</p>
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash">Cash</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Check-Out Notes</label>
                    <textarea name="notes" rows="2" class="form-input" placeholder="Any remarks about the stay or departure..."></textarea>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('checkout.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" style="display:inline-flex;align-items:center;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;padding:11px 24px;border-radius:12px;font-weight:700;font-size:14px;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(245,158,11,.4);">
                    <i class="fas fa-sign-out-alt" style="margin-right:8px;"></i>Complete Check-Out & Generate Invoice
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
