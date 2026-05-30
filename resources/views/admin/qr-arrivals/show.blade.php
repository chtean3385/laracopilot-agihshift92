@extends('layouts.admin')
@section('title','QR Arrival — ' . $guestRequest->name)
@section('page-title','QR Arrival Request')
@section('page-subtitle','Review and assign a room for this guest')

@section('content')
<div style="max-width:760px;" class="space-y-5">
    <a href="{{ route('qr-arrivals.index') }}" class="btn-secondary text-sm inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to QR Arrivals</a>

    {{-- Status Banner --}}
    @if($guestRequest->status === 'converted')
    <div style="background:#ecfdf5;border:1.5px solid #86efac;border-radius:12px;padding:14px 16px;color:#166534;font-weight:600;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle" style="color:#16a34a;font-size:16px;"></i>
        This request has been converted. Booking #{{ $guestRequest->booking?->booking_number ?? '—' }} created.
    </div>
    @elseif($guestRequest->status === 'cancelled')
    <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:14px 16px;color:#991b1b;font-weight:600;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-times-circle" style="color:#dc2626;font-size:16px;"></i>
        This request was cancelled.
        @if($guestRequest->notes) <span style="font-weight:400;">Reason: {{ $guestRequest->notes }}</span> @endif
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Guest Details --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
            <h3 style="font-weight:800;color:#1e293b;margin-bottom:16px;font-size:15px;"><i class="fas fa-user" style="color:#6366f1;margin-right:8px;"></i>Guest Details</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['Name', $guestRequest->name],
                    ['Phone', $guestRequest->phone],
                    ['Email', $guestRequest->email ?? '—'],
                    ['Date of Birth', $guestRequest->date_of_birth?->format('d M Y') ?? '—'],
                    ['ID Type', $guestRequest->id_type ?? '—'],
                    ['ID Number', $guestRequest->id_number ?? '—'],
                    ['Address', $guestRequest->address ?? '—'],
                    ['Guests', $guestRequest->guests_count],
                ] as [$label, $value])
                <div style="display:flex;justify-content:space-between;font-size:13px;border-bottom:1px solid #f8fafc;padding-bottom:6px;">
                    <span style="color:#64748b;">{{ $label }}</span>
                    <span style="font-weight:700;color:#1e293b;text-align:right;max-width:60%;">{{ $value }}</span>
                </div>
                @endforeach
                @if($guestRequest->requested_check_in)
                <div style="display:flex;justify-content:space-between;font-size:13px;border-bottom:1px solid #f8fafc;padding-bottom:6px;">
                    <span style="color:#64748b;">Requested Dates</span>
                    <span style="font-weight:700;color:#1e293b;">{{ $guestRequest->requested_check_in->format('d M') }} → {{ $guestRequest->requested_check_out?->format('d M Y') ?? '—' }}</span>
                </div>
                @endif
                <div style="font-size:12px;color:#94a3b8;">Received {{ $guestRequest->created_at->diffForHumans() }}</div>
            </div>
        </div>

        {{-- Documents & Signature --}}
        <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;display:flex;flex-direction:column;gap:16px;">
            {{-- ID Document --}}
            <div>
                <div style="font-weight:800;color:#1e293b;margin-bottom:10px;font-size:14px;"><i class="fas fa-id-card" style="color:#6366f1;margin-right:6px;"></i>ID Document</div>
                @if($guestRequest->id_document_path)
                @php $ext = pathinfo($guestRequest->id_document_path, PATHINFO_EXTENSION); @endphp
                @if(in_array(strtolower($ext), ['jpg','jpeg','png','heic','heif']))
                <a href="{{ asset('storage/' . $guestRequest->id_document_path) }}" target="_blank">
                    <img src="{{ asset('storage/' . $guestRequest->id_document_path) }}"
                        alt="ID Document" style="max-height:150px;border-radius:10px;object-fit:contain;border:1px solid #e2e8f0;width:100%;">
                </a>
                @else
                <a href="{{ asset('storage/' . $guestRequest->id_document_path) }}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:#f0f9ff;color:#0284c7;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-file-pdf"></i> View Document
                </a>
                @endif
                @else
                <span style="color:#94a3b8;font-size:13px;">No document uploaded</span>
                @endif
            </div>

            {{-- Signature --}}
            <div>
                <div style="font-weight:800;color:#1e293b;margin-bottom:10px;font-size:14px;"><i class="fas fa-signature" style="color:#6366f1;margin-right:6px;"></i>Digital Signature</div>
                @if($guestRequest->signature_data)
                <img src="{{ $guestRequest->signature_data }}" alt="Signature"
                    style="max-height:100px;border:1px solid #e2e8f0;border-radius:10px;background:#fafafa;width:100%;object-fit:contain;">
                @else
                <span style="color:#94a3b8;font-size:13px;">No signature captured</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Additional Guests --}}
    @if($guestRequest->additional_guests && count($guestRequest->additional_guests) > 0)
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:22px;">
        <h3 style="font-weight:800;color:#1e293b;margin-bottom:14px;font-size:15px;"><i class="fas fa-users" style="color:#6366f1;margin-right:8px;"></i>Additional Guests</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="padding:8px 12px;text-align:left;font-weight:700;color:#64748b;">#</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:700;color:#64748b;">Name</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:700;color:#64748b;">ID Type</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:700;color:#64748b;">ID Number</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guestRequest->additional_guests as $i => $guest)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:8px 12px;color:#94a3b8;">{{ $i + 1 }}</td>
                        <td style="padding:8px 12px;font-weight:700;color:#1e293b;">{{ $guest['name'] ?? '—' }}</td>
                        <td style="padding:8px 12px;color:#64748b;">{{ $guest['id_type'] ?? '—' }}</td>
                        <td style="padding:8px 12px;color:#64748b;">{{ $guest['id_number'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Assign Room Form --}}
    @if($guestRequest->status === 'pending')
    <div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;">
        <h3 style="font-weight:800;color:#1e293b;margin-bottom:20px;font-size:15px;"><i class="fas fa-door-open" style="color:#10b981;margin-right:8px;"></i>Assign Room & Confirm</h3>
        <form action="{{ route('qr-arrivals.assign', $guestRequest->id) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="form-label">Room <span style="color:#ef4444;">*</span></label>
                    @if($availableRooms->count() === 0)
                    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px;color:#991b1b;font-size:13px;">
                        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>No available rooms right now. Mark a room as available first.
                    </div>
                    @else
                    <select name="room_id" class="form-input" required>
                        <option value="">— Select a room —</option>
                        @foreach($availableRooms as $room)
                        <option value="{{ $room->id }}">
                            Room {{ $room->room_number }} — {{ ucfirst($room->type ?? 'standard') }}
                            @if($room->price_per_night) (₹{{ number_format($room->price_per_night) }}/night) @endif
                        </option>
                        @endforeach
                    </select>
                    @endif
                </div>
                <div>
                    <label class="form-label">Check-In Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="check_in_date" class="form-input" value="{{ $guestRequest->requested_check_in?->format('Y-m-d') ?? now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Check-Out Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="check_out_date" class="form-input" value="{{ $guestRequest->requested_check_out?->format('Y-m-d') ?? now()->addDay()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Number of Adults</label>
                    <input type="number" name="adults" class="form-input" value="{{ $guestRequest->guests_count }}" min="1">
                </div>
                <div>
                    <label class="form-label">Advance Payment (₹)</label>
                    <input type="number" name="advance_payment" class="form-input" value="0" min="0" step="1">
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
                @if($availableRooms->count() > 0)
                <button type="submit" class="btn-primary" style="flex:1;">
                    <i class="fas fa-check" style="margin-right:6px;"></i>Assign Room & Confirm (WhatsApp will be sent)
                </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Cancel Request --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #fee2e2;padding:18px 22px;">
        <h4 style="font-weight:700;color:#dc2626;margin-bottom:10px;font-size:14px;"><i class="fas fa-times-circle" style="margin-right:6px;"></i>Cancel This Request</h4>
        <form action="{{ route('qr-arrivals.cancel', $guestRequest->id) }}" method="POST" onsubmit="return confirm('Cancel this check-in request?')">
            @csrf
            <div style="display:flex;gap:10px;align-items:flex-end;">
                <input type="text" name="reason" placeholder="Reason (optional)" class="form-input" style="flex:1;">
                <button type="submit" style="padding:10px 18px;background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap;">Cancel Request</button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
