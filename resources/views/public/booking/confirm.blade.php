<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Booking Confirmed – {{ $hotelSettings->resort_name ?? $hotel->name }}</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8f9fb; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 40px 16px; }

        .card { background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(0,0,0,.09); max-width: 520px; width: 100%; padding: 36px 32px; }

        .icon-wrap { text-align: center; margin-bottom: 18px; }
        .icon-circle { display: inline-flex; align-items: center; justify-content: center; width: 68px; height: 68px; border-radius: 50%; }
        .icon-circle.success { background: #d1fae5; }
        .icon-circle.pending { background: #fef9c3; }
        .icon-circle.conflict { background: #fee2e2; }
        .icon-circle svg { width: 34px; height: 34px; }

        h1 { font-size: 1.5rem; font-weight: 800; text-align: center; color: #111827; }
        .subtitle { text-align: center; color: #6b7280; margin-top: 6px; margin-bottom: 24px; font-size: .92rem; }

        .detail-box { background: #f9fafb; border-radius: 12px; padding: 18px; margin-bottom: 18px; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 7px 0; border-bottom: 1px solid #f0f0f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: .8rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
        .detail-value { font-size: .9rem; font-weight: 600; color: #111827; text-align: right; }

        .badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: .78rem; font-weight: 700; }
        .badge.pending { background: #dbeafe; color: #1d4ed8; }
        .badge.website_pending { background: #ede9fe; color: #6d28d9; }
        .badge.confirmed { background: #d1fae5; color: #065f46; }
        .badge.conflict { background: #fee2e2; color: #b91c1c; }

        .thank-box { background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: .88rem; color: #166534; }

        .upi-section { background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 16px; margin-bottom: 18px; }
        .upi-section h3 { font-size: .92rem; font-weight: 700; color: #9a3412; margin-bottom: 10px; }
        .upi-id { font-family: monospace; font-size: 1.1rem; font-weight: 700; color: #c2410c; background: #fff; border-radius: 6px; padding: 6px 10px; display: inline-block; margin-bottom: 8px; }
        .upi-qr { display: block; width: 130px; height: 130px; object-fit: contain; border-radius: 8px; margin: 8px 0; }

        .payment-form label { display: block; font-size: .8rem; font-weight: 600; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .03em; margin-top: 10px; }
        .payment-form input { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 9px 12px; font-size: .9rem; font-family: inherit; }
        .payment-form input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px #fed7aa55; }
        .btn-pay { margin-top: 10px; width: 100%; padding: 12px; background: #f97316; color: #fff; font-weight: 700; border: none; border-radius: 10px; font-size: .9rem; cursor: pointer; font-family: inherit; }
        .btn-pay:hover { opacity: .88; }

        .conflict-box { background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: .88rem; color: #7f1d1d; }

        .home-link { display: block; text-align: center; color: #9ca3af; font-size: .82rem; margin-top: 10px; }

        .alert-success { background: #d1fae5; color: #065f46; border-radius: 8px; padding: 10px 14px; font-size: .88rem; margin-bottom: 14px; }
    </style>
</head>
<body>

<div class="card">
    <div class="icon-wrap">
        @if($booking->status === 'confirmed')
            <div class="icon-circle success">
                <svg fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        @elseif($booking->status === 'pending_room_assignment')
            <div class="icon-circle conflict">
                <svg fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        @else
            <div class="icon-circle pending">
                <svg fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
        @endif
    </div>

    @if($booking->status === 'confirmed')
        <h1>Booking Confirmed!</h1>
        <p class="subtitle">Your reservation is confirmed. We look forward to welcoming you.</p>
    @elseif($booking->status === 'pending_room_assignment')
        <h1>Request Received</h1>
        <p class="subtitle">Your booking request is pending room assignment. Our team will contact you shortly.</p>
    @else
        <h1>Booking Received!</h1>
        <p class="subtitle">We've received your booking request. You'll hear from us soon.</p>
    @endif

    {{-- Booking Details --}}
    <div class="detail-box">
        <div class="detail-row">
            <span class="detail-label">Booking #</span>
            <span class="detail-value" style="font-family:monospace;">{{ $booking->booking_number }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Guest</span>
            <span class="detail-value">{{ $booking->customer->name ?? 'Guest' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Check-in</span>
            <span class="detail-value">{{ $booking->check_in_date->format('D, d M Y') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Check-out</span>
            <span class="detail-value">{{ $booking->check_out_date->format('D, d M Y') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Nights</span>
            <span class="detail-value">{{ $booking->nights }}</span>
        </div>
        @if($booking->total_amount > 0)
        <div class="detail-row">
            <span class="detail-label">Total</span>
            <span class="detail-value">₹{{ number_format($booking->total_amount, 0) }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <span class="badge {{ $booking->status }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span>
            </span>
        </div>
    </div>

    {{-- Conflict notice --}}
    @if($booking->status === 'pending_room_assignment')
    <div class="conflict-box">
        <strong>Room Assignment Pending:</strong> The requested room type may not be available for your dates. Our team will contact you to confirm an alternative or process a refund if needed.
    </div>
    @endif

    {{-- Thank you message --}}
    @if($booking->status !== 'pending_room_assignment')
    <div class="thank-box">
        {{ $ws?->thank_you_message ?? 'Thank you! We look forward to welcoming you.' }}
    </div>
    @endif

    {{-- UPI Payment Section --}}
    @if($ws && $ws->require_advance_payment && $ws->upi_id && $booking->status !== 'confirmed')
    <div class="upi-section">
        <h3>Complete Advance Payment</h3>
        <p style="font-size:.85rem;color:#9a3412;margin-bottom:8px;">
            Advance required: <strong>₹{{ number_format($ws->advance_payment_amount, 0) }}</strong>
        </p>
        @if($ws->upi_id)
        <p style="font-size:.82rem;color:#6b7280;margin-bottom:4px;">UPI ID</p>
        <div class="upi-id">{{ $ws->upi_id }}</div>
        @endif
        @if($ws->upi_qr_image)
        <img class="upi-qr" src="{{ Storage::url($ws->upi_qr_image) }}" alt="UPI QR Code">
        @endif

        @if(session('payment_submitted'))
        <div class="alert-success">Payment reference submitted successfully! Our team will verify it.</div>
        @else
        <form class="payment-form" method="POST" action="{{ route('public.booking.payment_ref', $hotel->slug) }}">
            @csrf
            <input type="hidden" name="booking_ref" value="{{ $booking->booking_number }}">
            <input type="hidden" name="amount" value="{{ $ws->advance_payment_amount }}">
            <label>UTR / Transaction ID</label>
            <input type="text" name="utr" placeholder="Enter UPI transaction reference" required>
            <button class="btn-pay" type="submit">Submit Payment Reference</button>
        </form>
        @endif
    </div>
    @endif

    <a href="{{ url('/book/' . $hotel->slug) }}" class="home-link">← Make another booking</a>
</div>

</body>
</html>
