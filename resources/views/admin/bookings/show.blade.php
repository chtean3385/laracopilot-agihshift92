@extends('layouts.admin')
@section('title', 'Booking ' . $booking->booking_number)
@section('page-title', 'Booking Details')
@section('page-subtitle', $booking->booking_number)

@section('content')
@php
    $_showSettings = $settings;
    $_taxRate      = ($_showSettings && !empty($_showSettings->gst_number) && ($_showSettings->tax_rate ?? 0) > 0)
                        ? (float) $_showSettings->tax_rate : 0;
@endphp
<div class="space-y-6">
    {{-- ── Website Booking Confirm Banner ────────────────────────────────── --}}
    @if($booking->status === 'website_pending')
    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fbbf24;border-radius:16px;padding:20px 24px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:36px;height:36px;background:#f59e0b;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-globe" style="color:#fff;font-size:15px;"></i>
            </div>
            <div>
                <div style="font-weight:700;font-size:15px;color:#92400e;">Website Booking — Awaiting Confirmation</div>
                <div style="font-size:12px;color:#b45309;margin-top:1px;">Review the request and assign a room to confirm.</div>
            </div>
        </div>
        @if($errors->has('room_id'))
            <div style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:8px 12px;font-size:13px;margin-bottom:12px;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first('room_id') }}</div>
        @endif
        <form method="POST" action="{{ route('admin.booking-widget.confirm', $booking->id) }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            @csrf
            <select name="room_id" required style="flex:1;min-width:180px;padding:9px 12px;border:1.5px solid #fbbf24;border-radius:10px;background:#fff;font-size:13px;font-weight:600;color:#374151;">
                <option value="">— Select room to assign —</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ $booking->room_id == $room->id ? 'selected' : '' }}>
                        Room {{ $room->room_number }} — {{ $room->type }} ({{ ucfirst($room->status) }})
                    </option>
                @endforeach
            </select>
            <button type="submit" style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                <i class="fas fa-check-circle"></i> Confirm Booking
            </button>
            <a href="{{ route('bookings.edit', $booking->id) }}" style="display:inline-flex;align-items:center;gap:7px;padding:10px 16px;background:#fff;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                <i class="fas fa-edit"></i> Edit
            </a>
        </form>
    </div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('bookings.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Bookings</a>
        <div class="flex gap-2 flex-wrap">
            @if($booking->status == 'confirmed')
                <a href="{{ route('checkin.show', $booking->id) }}" class="btn-primary text-sm"><i class="fas fa-sign-in-alt mr-2"></i>Process Check-In</a>
            @endif
            @if($booking->status == 'checked_in')
                <a href="{{ route('checkout.show', $booking->id) }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-600 text-sm"><i class="fas fa-sign-out-alt mr-2"></i>Process Check-Out</a>
            @endif
            @if($booking->status === 'checked_in' && $booking->checkout_token)
                <button onclick="document.getElementById('bkGuestQrModal').style.display='flex'"
                    style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-qrcode"></i> Guest Checkout QR
                </button>
            @endif
            @if($booking->invoice)
                <a href="{{ route('invoices.show', $booking->invoice->id) }}" class="btn-secondary text-sm"><i class="fas fa-file-invoice mr-2"></i>View Invoice</a>
            @endif
            @if(\App\Models\Module::isEnabled('pathik'))
            <button onclick="fillPathik()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-clipboard-list"></i> Fill Pathik Portal
            </button>
            @endif
            @canDo('bookings.delete')
            @if(in_array($booking->status, ['confirmed', 'checked_in']))
            <form method="POST" action="{{ route('bookings.destroy', $booking->id) }}" onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fee2e2;color:#dc2626;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;border:1px solid #fecaca;">
                    <i class="fas fa-times-circle"></i>Cancel Booking
                </button>
            </form>
            @endif
            @endCanDo
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Booking Info -->
        <div class="space-y-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">Booking Info</h3>
                    <span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_',' ', $booking->status)) }}</span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Booking #</span><span class="text-sm font-mono font-bold text-cyan-600">{{ $booking->booking_number }}</span></div>
                    @php $rm = $booking->room; $pType = $booking->is_whole_hotel ? ($booking->whole_hotel_pricing_type ?? 'per_night') : ($rm?->pricing_type ?? 'per_night'); @endphp
                    @if($pType === 'per_slot' && $booking->booking_date)
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Check-In</span><span class="text-sm font-semibold">{{ $booking->check_in_date?->format('d M Y') ?? $booking->booking_date->format('d M Y') }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Check-Out</span><span class="text-sm font-semibold">{{ $booking->check_out_date?->format('d M Y') ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Time Slot</span>
                            <span class="text-sm font-semibold text-violet-600">
                                @if($booking->timeSlot)
                                    {{ $booking->timeSlot->name }} ({{ $booking->timeSlot->start_time }}–{{ $booking->timeSlot->end_time }})
                                @else — @endif
                            </span>
                        </div>
                    @elseif($pType === 'per_hour' && $booking->booking_date)
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Booking Date</span><span class="text-sm font-semibold">{{ $booking->booking_date->format('d M Y') }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Start Time</span><span class="text-sm font-semibold text-amber-600">{{ $booking->slot_start_time ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Duration</span><span class="text-sm font-semibold text-amber-600">{{ $booking->hours_booked ?? '—' }} hrs</span></div>
                    @else
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Check-In</span><span class="text-sm font-semibold">{{ $booking->check_in_date?->format('d M Y') ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Check-Out</span><span class="text-sm font-semibold">{{ $booking->check_out_date?->format('d M Y') ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-sm text-gray-500">Nights</span><span class="text-sm font-semibold">{{ $booking->nights }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-sm text-gray-500">Guests</span><span class="text-sm font-semibold">{{ $booking->adults }} Adults @if($booking->children > 0), {{ $booking->children }} Children @endif</span></div>
                </div>
                @if($booking->special_requests)
                <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-xs font-semibold text-amber-700"><i class="fas fa-star mr-1"></i>Special Requests</p>
                    <p class="text-sm text-amber-600 mt-1">{{ $booking->special_requests }}</p>
                </div>
                @endif
                @if($booking->checkin_notes)
                <div class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-xs font-semibold text-blue-700"><i class="fas fa-sign-in-alt mr-1"></i>Check-In Notes</p>
                    <p class="text-sm text-blue-600 mt-1">{{ $booking->checkin_notes }}</p>
                </div>
                @endif
                @if($booking->checkout_notes)
                <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <p class="text-xs font-semibold text-slate-600"><i class="fas fa-sign-out-alt mr-1"></i>Check-Out Notes</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $booking->checkout_notes }}</p>
                </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Guest</h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">{{ substr($booking->customer?->name ?? '(Deleted Guest)', 0, 1) }}</div>
                    <div>
                        <div class="font-semibold text-gray-800">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                        <div class="text-sm text-gray-400">{{ $booking->customer?->phone ?? '—' }}</div>
                    </div>
                </div>
                <a href="{{ route('customers.show', $booking->customer_id) }}" class="text-cyan-600 hover:underline text-sm"><i class="fas fa-external-link-alt mr-1"></i>View Guest Profile</a>
            </div>
        </div>

        <!-- Room & Payment -->
        <div class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        Room
                        @if($booking->is_whole_hotel)
                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold text-amber-800 bg-amber-100 border border-amber-200 rounded-full"><i class="fas fa-hotel text-amber-500"></i> Whole Hotel</span>
                        @endif
                    </h3>
                    @if($booking->is_whole_hotel)
                    <div class="flex flex-col items-center justify-center py-4 text-center">
                        <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                            <i class="fas fa-hotel text-amber-500 text-2xl"></i>
                        </div>
                        <p class="text-lg font-black text-amber-700">Whole Hotel / Villa</p>
                        <p class="text-xs text-gray-500 mt-1">All rooms blocked for this period</p>
                        @php
                            $whRoomCount = \App\Models\Room::where('hotel_id', $booking->hotel_id)->count();
                        @endphp
                        <div class="mt-3 space-y-1 w-full text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Pricing Type</span><span class="font-medium">{{ ucfirst(str_replace('_', ' ', $booking->whole_hotel_pricing_type ?? 'per night')) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Rooms Covered</span><span class="font-medium">{{ $whRoomCount }}</span></div>
                            @if($booking->price_overridden)
                            <div class="flex justify-between"><span class="text-gray-500">Custom Total</span><span class="font-bold text-amber-600">₹{{ number_format($booking->total_amount) }}</span></div>
                            @else
                            <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold text-emerald-600">₹{{ number_format($booking->total_amount) }}</span></div>
                            @endif
                        </div>
                    </div>
                    @elseif($booking->groupedBookings->isNotEmpty())
                    {{-- Group booking: primary room + all child rooms --}}
                    @php $allGroupedRooms = collect([$booking])->concat($booking->groupedBookings); @endphp
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="font-size:13px;font-weight:700;color:#1d4ed8;background:#dbeafe;padding:3px 10px;border-radius:20px;">
                            <i class="fas fa-layer-group" style="font-size:11px;margin-right:4px;"></i>
                            Group Booking · {{ $allGroupedRooms->count() }} Rooms
                        </span>
                    </div>
                    <div class="space-y-3">
                        @foreach($allGroupedRooms as $gbEntry)
                        @php
                            $gbRoom = $gbEntry->room;
                            // Primary booking stores the COMBINED total in total_amount.
                            // Derive its individual room portion from rate × nights so
                            // each card shows only that room's cost, not the group total.
                            if ($gbEntry->id === $booking->id && $gbRoom) {
                                $gbAmount = $booking->nights * $gbRoom->price_per_night
                                          + (float)($booking->meal_cost ?? 0)
                                          + (float)($booking->extra_bed_cost ?? 0);
                            } else {
                                // Child bookings already store their per-room amount
                                $gbAmount = (float) $gbEntry->total_amount;
                            }
                        @endphp
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-door-open" style="color:#fff;font-size:13px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;font-size:15px;color:#1e293b;">Room {{ $gbRoom?->room_number ?? '—' }}</div>
                                    <div style="font-size:12px;color:#64748b;">{{ ucfirst($gbRoom?->type ?? '') }}{{ $gbRoom?->floor ? ' · Floor '.$gbRoom->floor : '' }}</div>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;font-size:14px;color:#059669;">₹{{ number_format($gbAmount) }}</div>
                                <div style="font-size:11px;color:#94a3b8;">{{ $booking->nights }} night{{ $booking->nights != 1 ? 's' : '' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="margin-top:12px;padding:10px 12px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:13px;font-weight:700;color:#15803d;">Combined Total</span>
                        <span style="font-size:16px;font-weight:900;color:#15803d;">₹{{ number_format($booking->total_amount) }}</span>
                    </div>
                    @elseif($booking->room)
                    <div class="text-4xl font-black text-gray-800 mb-1">{{ $booking->room?->room_number }}</div>
                    <span class="badge-{{ $booking->room?->type_color }}">{{ ucfirst($booking->room?->type ?? '') }}</span>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Floor</span><span class="font-medium">{{ $booking->room?->floor }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">View</span><span class="font-medium">{{ $booking->room?->view }}</span></div>
                        @if($pType === 'per_slot' && $booking->timeSlot)
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Slot Price</span><span class="font-bold text-violet-600">₹{{ number_format($booking->timeSlot->base_price) }}</span></div>
                        @elseif($pType === 'per_hour')
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Rate/Hour</span><span class="font-bold text-amber-600">₹{{ number_format($booking->room?->hourly_rate ?? 0) }}</span></div>
                        @else
                        @if($booking->price_overridden)
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Custom Total</span><span class="font-bold text-amber-600">₹{{ number_format($booking->total_amount) }} <span class="text-xs font-normal text-amber-500">(custom)</span></span></div>
                        @else
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Rate/Night</span><span class="font-bold text-emerald-600">₹{{ number_format($booking->room?->price_per_night ?? 0) }}@if($_taxRate > 0)<span class="text-xs font-normal text-gray-400 ml-1">+ {{ $_taxRate }}% GST</span>@endif</span></div>
                        @endif
                        @endif
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                            <i class="fas fa-clock text-amber-500"></i>
                        </div>
                        <p class="text-sm font-semibold text-amber-700">Room Pending Assignment</p>
                        <p class="text-xs text-gray-400 mt-1">This booking came in via website and needs a room assigned.</p>
                        @if($booking->status === 'pending_room_assignment')
                        <button onclick="document.getElementById('assignRoomModal').style.display='flex'"
                                class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg"
                                style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                            <i class="fas fa-door-open"></i> Assign Room
                        </button>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Payment Summary</h3>
                    @php
                        $bSettings          = $settings;
                        $bTaxRate           = ($bSettings && $bSettings->gst_number && $bSettings->tax_rate > 0) ? (float) $bSettings->tax_rate : 0;
                        $bExtraChargesTotal = $booking->extraCharges->sum('total_price');

                        // Compute true base — respect custom price override if set
                        // NOTE: total_amount is incremented by BookingExtraChargeController when charges are added
                        if ($pType === 'per_hour' || $booking->is_whole_hotel) {
                            // Hourly or whole-hotel: base is in total_amount (set at checkout or booking); extra charges already incremented it
                            $bBase = (float) $booking->total_amount;
                        } elseif ($booking->price_overridden) {
                            // Custom price set at booking — total_amount already includes any extra charges added later
                            $bBase = (float) $booking->total_amount;
                        } elseif ($booking->groupedBookings->isNotEmpty()) {
                            // Group booking: total_amount stores the pre-computed combined total for all rooms
                            // (primary room + all child rooms + meal/extra-bed on primary).
                            // Extra charges added post-booking are also incremented into total_amount.
                            $bBase = (float) $booking->total_amount;
                        } elseif ($pType === 'per_slot' && $booking->timeSlot) {
                            $bBase = (float) $booking->timeSlot->base_price + $bExtraChargesTotal;
                        } else {
                            // per_night standard: recompute from room rate × nights + addons
                            $bRoomBase = $booking->room ? ($booking->nights * $booking->room->price_per_night) : 0;
                            $bBase = $bRoomBase + (float)($booking->meal_cost ?? 0) + (float)($booking->extra_bed_cost ?? 0) + $bExtraChargesTotal;
                        }

                        $bGst               = round($bBase * ($bTaxRate / 100), 2);
                        $bGrandTotal        = $bBase + $bGst;
                        $bTotalPaid         = $booking->payments->where('status','completed')->sum('amount');
                        $bBalance           = max(0, $bGrandTotal - $bTotalPaid);
                        $bOverpaid          = max(0, $bTotalPaid - $bGrandTotal);
                    @endphp
                    <div class="space-y-2">
                        @if($booking->is_whole_hotel)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-hotel text-amber-400 mr-1"></i>Whole Hotel / Villa</span>
                            <span class="font-medium">₹{{ number_format($booking->total_amount) }}</span>
                        </div>
                        @elseif($pType === 'per_slot' && $booking->timeSlot)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                <i class="fas fa-clock text-violet-400 mr-1"></i>{{ $booking->timeSlot->name }}
                                <span class="text-xs text-gray-400">({{ $booking->timeSlot->start_time }}–{{ $booking->timeSlot->end_time }}{{ $booking->timeSlot->is_overnight ? ' next day' : '' }})</span>
                            </span>
                            <span class="font-medium">₹{{ number_format($booking->timeSlot->base_price) }}</span>
                        </div>
                        @elseif($pType === 'per_hour')
                        @php $roomCost = max(0, $booking->total_amount - $bExtraChargesTotal); @endphp
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-gray-500">
                                <i class="fas fa-hourglass-half text-amber-400 mr-1"></i>Hourly booking
                                @if($booking->price_overridden)
                                <span class="inline-flex items-center gap-1 ml-1.5 px-1.5 py-0.5 rounded-md text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                                    <i class="fas fa-lock" style="font-size:8px;"></i> Fixed rate agreed at booking
                                </span>
                                @endif
                            </span>
                            <span class="font-medium">₹{{ number_format($roomCost) }}</span>
                        </div>
                        @elseif($booking->price_overridden)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-pen text-amber-400 mr-1"></i>Room charge <span class="text-xs text-amber-600">(custom price)</span></span>
                            <span class="font-medium">₹{{ number_format(max(0, (float)$booking->total_amount - $bExtraChargesTotal)) }}</span>
                        </div>
                        @elseif($booking->groupedBookings->isNotEmpty())
                        {{-- Group booking: combined total for all rooms (meal & extra beds already folded in) --}}
                        @php
                            $gbRoomsBase = max(0, (float)$booking->total_amount - $bExtraChargesTotal);
                        @endphp
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-layer-group text-blue-400 mr-1"></i>{{ $booking->groupedBookings->count() + 1 }} rooms · {{ $booking->nights }} night{{ $booking->nights != 1 ? 's' : '' }}</span>
                            <span class="font-medium">₹{{ number_format($gbRoomsBase) }}</span>
                        </div>
                        @else
                        @php $roomCost = $booking->room ? $booking->nights * $booking->room->price_per_night : $booking->total_amount; @endphp
                        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ $booking->nights }} nights × ₹{{ number_format($booking->room ? $booking->room->price_per_night : 0) }}</span><span class="font-medium">₹{{ number_format($roomCost) }}</span></div>
                        @endif
                        @if(!$booking->groupedBookings->isNotEmpty() && $booking->meal_cost > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-utensils text-amber-400 mr-1"></i>Meal Plan
                                @if($booking->meal_breakfast)<span class="ml-1 text-xs bg-amber-100 text-amber-700 rounded px-1">B</span>@endif
                                @if($booking->meal_lunch)<span class="ml-1 text-xs bg-orange-100 text-orange-700 rounded px-1">L</span>@endif
                                @if($booking->meal_dinner)<span class="ml-1 text-xs bg-indigo-100 text-indigo-700 rounded px-1">D</span>@endif
                            </span>
                            <span class="font-medium text-amber-600">₹{{ number_format($booking->meal_cost) }}</span>
                        </div>
                        @endif
                        @if(!$booking->groupedBookings->isNotEmpty() && $booking->extra_beds > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-bed text-blue-400 mr-1"></i>Extra Beds ({{ $booking->extra_beds }})</span>
                            <span class="font-medium text-blue-600">₹{{ number_format($booking->extra_bed_cost) }}</span>
                        </div>
                        @endif
                        @if($bExtraChargesTotal > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><i class="fas fa-utensils text-amber-400 mr-1"></i>Food &amp; Extra</span>
                            <span class="font-medium text-rose-600">₹{{ number_format($bExtraChargesTotal) }}</span>
                        </div>
                        @endif
                        @if($booking->meal_cost > 0 || $booking->extra_beds > 0 || $bExtraChargesTotal > 0)
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span class="font-medium">₹{{ number_format($bBase) }}</span></div>
                        @endif
                        @if($bTaxRate > 0)
                        <div class="flex justify-between text-sm"><span class="text-gray-500">GST ({{ $bTaxRate }}%)</span><span class="text-gray-600">₹{{ number_format($bGst) }}</span></div>
                        @endif
                        <div class="flex justify-between text-sm border-t pt-2"><span class="text-gray-500">Grand Total</span><span class="font-bold text-gray-800">₹{{ number_format($bGrandTotal) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total Paid</span><span class="font-semibold text-emerald-600">₹{{ number_format($bTotalPaid) }}</span></div>
                        @if($bOverpaid > 0)
                        <div class="flex justify-between text-sm"><span class="text-gray-500 text-violet-600">Overpayment/Credit</span><span class="font-semibold text-violet-600">₹{{ number_format($bOverpaid) }}</span></div>
                        @endif
                        <div class="flex justify-between text-sm border-t pt-2"><span class="font-semibold">Balance Due</span><span class="font-bold {{ $bBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($bBalance) }}</span></div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        <span class="badge-{{ $booking->payment_status_color }}">{{ ucfirst($booking->payment_status) }}</span>
                        @if($booking->price_overridden)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                            <i class="fas fa-pen" style="font-size:9px;"></i> Custom price
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Payment History</h3>
                    <a href="{{ route('payments.create') }}" class="text-cyan-600 hover:underline text-sm">+ Add Payment</a>
                </div>
                @if($booking->payments->count() > 0)
                <div class="divide-y divide-gray-50">
                    @foreach($booking->payments as $payment)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-700">{{ ucfirst($payment->payment_type) }} Payment</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($payment->payment_method) }} • {{ $payment->created_at->format('d M Y h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-emerald-600">₹{{ number_format($payment->amount) }}</div>
                            <div class="text-xs font-mono text-gray-400">{{ $payment->transaction_id }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-400 text-sm">No payments recorded</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Website Booking UTR / Payment References ─────────────────────────── --}}
@if($booking->source === 'website' && $booking->paymentReferences && $booking->paymentReferences->count() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-indigo-100 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-indigo-100 flex items-center gap-3" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
            <i class="fas fa-receipt text-white text-sm"></i>
        </div>
        <div>
            <h3 class="font-bold text-gray-800 text-sm">Payment References (UTR / Transaction ID)</h3>
            <p class="text-xs text-gray-500">Submitted by guest via website booking</p>
        </div>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($booking->paymentReferences as $ref)
        <div class="px-6 py-3 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-800 font-mono">{{ $ref->reference_number }}</div>
                <div class="text-xs text-gray-400">{{ ucfirst($ref->payment_type ?? 'UPI/Bank Transfer') }} · Submitted {{ $ref->created_at->format('d M Y h:i A') }}</div>
                @if($ref->notes)<div class="text-xs text-gray-500 mt-0.5">{{ $ref->notes }}</div>@endif
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                {{ $ref->verified ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $ref->verified ? '✓ Verified' : 'Pending Verification' }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(\App\Models\Module::isEnabled('pathik'))
{{-- Pathik Modal --}}
<div id="pathikModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;max-width:440px;padding:16px;">
        <div style="background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
            <div style="background:linear-gradient(135deg,#f97316,#ea580c);padding:20px;color:#fff;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;">&#128203;</div>
                        <div>
                            <h3 style="font-size:15px;font-weight:800;margin:0;">Pathik Portal Autofill</h3>
                            <p style="font-size:11px;opacity:.8;margin:2px 0 0;">Gujarat Tourist Registration</p>
                        </div>
                    </div>
                    <button onclick="closePathikModal()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:14px;">&#10005;</button>
                </div>
            </div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
                <div id="pathikStatus" style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;font-weight:600;display:none;">
                    <i class="fas fa-check-circle" style="margin-right:6px;"></i><span id="pathikStatusText"></span>
                </div>
                <div style="background:#f8fafc;border-radius:12px;padding:14px;border:1px solid #e2e8f0;">
                    <p style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Guest Data to Send</p>
                    <div style="display:grid;gap:5px;font-size:12px;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">Name</span><span style="font-weight:700;color:#1e293b;">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">Phone</span><span style="font-weight:600;color:#1e293b;">{{ $booking->customer?->phone ?? '—' }}</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">Check-In</span><span style="font-weight:600;color:#1e293b;">{{ $booking->check_in_date->format('d M Y') }}</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">Check-Out</span><span style="font-weight:600;color:#1e293b;">{{ $booking->check_out_date->format('d M Y') }}</span></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">Room</span><span style="font-weight:600;color:#1e293b;">{{ $booking->room?->room_number ?? 'TBD' }} ({{ ucfirst($booking->room?->type ?? 'Pending') }})</span></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="btnSendPathik" onclick="sendToPathik()" style="flex:1;padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension
                    </button>
                    <a id="btnOpenPortal" href="https://pathik.gujarat.gov.in" target="_blank" style="display:none;flex:1;padding:10px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;text-align:center;">
                        <i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Open Pathik Portal
                    </a>
                </div>
                <p style="font-size:11px;color:#94a3b8;text-align:center;">After sending, open the Pathik portal and click "Autofill Now" in the Chrome extension.</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Additional Guests Section ────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:24px;" id="guestsSection">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-users" style="color:#7c3aed;margin-right:8px;"></i>Additional Guests</h3>
            <p style="font-size:12px;color:#94a3b8;margin:4px 0 0;">All family / group members for police register compliance</p>
        </div>
        <button onclick="toggleAddGuestForm()" style="padding:8px 16px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;" id="btnAddGuestToggle">
            <i class="fas fa-plus" style="margin-right:5px;"></i>Add Guest
        </button>
    </div>

    {{-- Add Guest Form (hidden by default) --}}
    <div id="addGuestForm" style="display:none;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;padding:18px;margin-bottom:18px;">
        <h4 style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:14px;"><i class="fas fa-user-plus" style="color:#7c3aed;margin-right:6px;"></i>New Guest Details</h4>
        <form id="guestForm" onsubmit="submitGuest(event)" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;max-width:100%;">
                <div style="grid-column:1/-1;">
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Full Name *</label>
                    <input type="text" id="g_name" required placeholder="Guest full name" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Relation</label>
                    <select id="g_relation" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        @foreach(\App\Models\BookingGuest::relations() as $rel)
                        <option value="{{ $rel }}">{{ $rel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Age</label>
                    <input type="number" id="g_age" min="0" max="120" placeholder="Age" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Gender</label>
                    <select id="g_gender" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Nationality</label>
                    <input type="text" id="g_nationality" value="Indian" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">ID Type</label>
                    <select id="g_id_type" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        <option value="">Select</option>
                        @foreach(\App\Models\BookingGuest::idTypes() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">ID Number</label>
                    <input type="text" id="g_id_number" placeholder="ID number (optional)" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">Date of Birth</label>
                    <input type="date" id="g_dob" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:11px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px;">📸 ID Proof (JPG/PNG/PDF) *</label>
                    <input type="file" id="g_document" accept=".jpg,.jpeg,.png,.pdf" placeholder="Upload ID proof" style="width:100%;padding:9px 11px;border:1.5px solid #fecaca;border-radius:8px;font-size:13px;box-sizing:border-box;background:#fff7f7;" required>
                    <small style="font-size:10px;color:#7f1d1d;margin-top:3px;display:block;">Maximum 5MB. Required for guest registration.</small>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:14px;">
                <button type="submit" style="flex:1;padding:10px 14px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;" id="btnSubmitGuest">
                    <i class="fas fa-save" style="margin-right:5px;"></i>Save Guest
                </button>
                <button type="button" onclick="toggleAddGuestForm()" style="padding:10px 12px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Guests List --}}
    <div id="guestsList">
        @forelse($booking->bookingGuests as $guest)
        <div class="guest-row" id="guestRow{{ $guest->id }}" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:12px 14px;background:#f8fafc;border-radius:10px;margin-bottom:8px;border:1px solid #e2e8f0;">
            <div style="width:34px;height:34px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-user" style="color:#7c3aed;font-size:14px;"></i>
            </div>
            <div style="flex:1;min-width:120px;">
                <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $guest->name }}</div>
                <div style="font-size:11px;color:#64748b;">
                    {{ $guest->relation ?? '' }}{{ ($guest->relation && $guest->age) ? ' · ' : '' }}{{ $guest->age ? $guest->age . ' yrs' : '' }}{{ $guest->gender ? ' · ' . ucfirst($guest->gender) : '' }}
                </div>
            </div>
            <div style="flex:1;min-width:120px;">
                <div style="font-size:11px;font-weight:700;color:#7c3aed;">{{ \App\Models\BookingGuest::idTypes()[$guest->id_type] ?? ($guest->id_type ?? 'No ID') }}</div>
                <div style="font-size:12px;font-family:monospace;color:#1e293b;">{{ $guest->id_number ?? '-' }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                {{-- Document upload --}}
                <label title="{{ $guest->id_document_name ?? 'Upload ID document' }}" style="padding:5px 10px;background:{{ $guest->id_document_path ? '#dcfce7' : '#f1f5f9' }};color:{{ $guest->id_document_path ? '#16a34a' : '#64748b' }};border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;">
                    <i class="fas fa-{{ $guest->id_document_path ? 'check' : 'upload' }}"></i>
                    {{ $guest->id_document_path ? 'Doc' : 'Upload ID' }}
                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" style="display:none;" onchange="uploadDoc({{ $guest->id }}, this)">
                </label>
                {{-- Signature status --}}
                @if($guest->signature)
                <span style="padding:5px 10px;background:#dcfce7;color:#16a34a;border-radius:7px;font-size:11px;font-weight:700;">
                    <i class="fas fa-check"></i> Signed
                </span>
                @endif
                {{-- Remove --}}
                <button onclick="removeGuest({{ $guest->id }})" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;" title="Remove guest">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        @empty
        <div id="noGuestsMsg" style="text-align:center;padding:24px;color:#94a3b8;">
            <i class="fas fa-user-plus" style="font-size:28px;margin-bottom:8px;display:block;"></i>
            No additional guests added yet. Click "Add Guest" to register family or group members.
        </div>
        @endforelse
    </div>
</div>

<script>
var bookingId = {{ $booking->id }};
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function toggleAddGuestForm() {
    var f = document.getElementById('addGuestForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

function submitGuest(e) {
    e.preventDefault();
    var btn = document.getElementById('btnSubmitGuest');
    var docInput = document.getElementById('g_document');
    
    if (!docInput.files || docInput.files.length === 0) {
        alert('ID Proof document is required. Please upload a valid file (JPG, PNG, or PDF).');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:5px;"></i>Saving...';

    var data = new FormData();
    data.append('_token', csrfToken);
    data.append('name', document.getElementById('g_name').value);
    data.append('relation', document.getElementById('g_relation').value);
    data.append('age', document.getElementById('g_age').value);
    data.append('gender', document.getElementById('g_gender').value);
    data.append('nationality', document.getElementById('g_nationality').value);
    data.append('id_type', document.getElementById('g_id_type').value);
    data.append('id_number', document.getElementById('g_id_number').value);
    data.append('dob', document.getElementById('g_dob').value);
    data.append('document', docInput.files[0]);

    fetch('/bookings/' + bookingId + '/guests', { method: 'POST', headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With':'XMLHttpRequest'}, body: data })
    .then(function(r){ return r.json(); })
    .then(function(res){
        if (res.success) {
            var g = res.guest;
            var noMsg = document.getElementById('noGuestsMsg');
            if (noMsg) noMsg.remove();
            var html = '<div class="guest-row" id="guestRow' + g.id + '" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:12px 14px;background:#f8fafc;border-radius:10px;margin-bottom:8px;border:1px solid #e2e8f0;">'
                + '<div style="width:34px;height:34px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-user" style="color:#7c3aed;font-size:14px;"></i></div>'
                + '<div style="flex:1;min-width:120px;"><div style="font-size:13px;font-weight:700;color:#1e293b;">' + g.name + '</div>'
                + '<div style="font-size:11px;color:#64748b;">' + (g.relation || '') + (g.age ? ' · ' + g.age + ' yrs' : '') + '</div></div>'
                + '<div style="flex:1;min-width:120px;"><div style="font-size:11px;font-weight:700;color:#7c3aed;">' + (g.id_type || 'No ID') + '</div>'
                + '<div style="font-size:12px;font-family:monospace;color:#1e293b;">' + (g.id_number || '-') + '</div></div>'
                + '<div style="display:flex;align-items:center;gap:6px;">'
                + '<button onclick="removeGuest(' + g.id + ')" style="padding:5px 9px;background:#fee2e2;color:#dc2626;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;"><i class="fas fa-trash-alt"></i></button>'
                + '</div></div>';
            document.getElementById('guestsList').insertAdjacentHTML('beforeend', html);
            document.getElementById('g_name').value = '';
            document.getElementById('g_relation').value = 'Spouse';
            document.getElementById('g_age').value = '';
            document.getElementById('g_gender').value = '';
            document.getElementById('g_nationality').value = 'Indian';
            document.getElementById('g_id_type').value = '';
            document.getElementById('g_id_number').value = '';
            document.getElementById('g_dob').value = '';
            document.getElementById('g_document').value = '';
            toggleAddGuestForm();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save" style="margin-right:5px;"></i>Save Guest';
    }).catch(function(){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-save" style="margin-right:5px;"></i>Save Guest'; });
}

function removeGuest(guestId) {
    if (!confirm('Remove this guest from the booking?')) return;
    fetch('/bookings/' + bookingId + '/guests/' + guestId, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/json'},
        body: JSON.stringify({_token: csrfToken})
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) {
            var row = document.getElementById('guestRow' + guestId);
            if (row) row.remove();
        }
    });
}

function uploadDoc(guestId, input) {
    if (!input.files[0]) return;
    var data = new FormData();
    data.append('document', input.files[0]);
    data.append('_token', csrfToken);
    fetch('/bookings/' + bookingId + '/guests/' + guestId + '/document', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With':'XMLHttpRequest'},
        body: data
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) { location.reload(); }
        else alert('Upload failed.');
    });
}
</script>

<script>
var pathikData = {
    booking_id:     {{ $booking->id }},
    booking_number: {!! json_encode($booking->booking_number) !!},
    name:           {!! json_encode($booking->customer?->name ?? '(Deleted Guest)') !!},
    email:          {!! json_encode($booking->customer?->email ?? '') !!},
    phone:          {!! json_encode($booking->customer?->phone ?? '—') !!},
    address:        {!! json_encode($booking->customer->address ?? '') !!},
    city:           {!! json_encode($booking->customer->city ?? '') !!},
    state:          {!! json_encode($booking->customer->state ?? '') !!},
    country:        {!! json_encode($booking->customer->country ?? 'India') !!},
    nationality:    {!! json_encode($booking->customer->nationality ?? 'Indian') !!},
    id_type:        {!! json_encode($booking->customer?->id_type ?? '') !!},
    id_number:      {!! json_encode($booking->customer?->id_number ?? '') !!},
    date_of_birth:  '',
    check_in_date:  {!! json_encode($booking->check_in_date->format('Y-m-d')) !!},
    check_out_date: {!! json_encode($booking->check_out_date->format('Y-m-d')) !!},
    nights:         {{ $booking->nights }},
    adults:         {{ $booking->adults }},
    children:       {{ $booking->children }},
    room_number:    {!! json_encode((string)($booking->room?->room_number ?? 'TBD')) !!},
    room_type:      {!! json_encode($booking->room?->type ?? 'Pending') !!},
    total_amount:   {{ $booking->total_amount }},
};

function fillPathik() {
    document.getElementById('pathikModal').style.display = 'block';
}
function closePathikModal() {
    document.getElementById('pathikModal').style.display = 'none';
}
function sendToPathik() {
    var btn = document.getElementById('btnSendPathik');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Sending...';

    var form = new FormData();
    Object.keys(pathikData).forEach(function(k) { form.append(k, pathikData[k]); });

    fetch('{{ route('pathik.pending.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: form,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            var status = document.getElementById('pathikStatus');
            status.style.display = 'block';
            document.getElementById('pathikStatusText').textContent = 'Guest data ready for 60 minutes! Open the Pathik portal and click Autofill Now in the extension.';
            document.getElementById('btnOpenPortal').style.display = 'flex';
            btn.style.display = 'none';
            if (window.chrome && chrome.storage) {
                chrome.storage.local.set({ pathik_pending_token: data.token });
            }
        } else {
            alert('Error sending data. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
        }
    })
    .catch(function(err) {
        alert('Request failed: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i>Send to Extension';
    });
}
document.getElementById('pathikModal').addEventListener('click', function(e) {
    if (e.target === this) closePathikModal();
});
</script>
@endif
{{-- Assign Room Modal (website_pending / pending_room_assignment bookings) --}}
@if(in_array($booking->status, ['website_pending', 'pending_room_assignment']))
@php
    // Exclude rooms already confirmed/checked_in for overlapping dates
    $bookedRoomIds = \App\Models\Booking::withoutGlobalScopes()
        ->where('hotel_id', $booking->hotel_id)
        ->whereIn('status', ['confirmed', 'checked_in'])
        ->where('id', '!=', $booking->id)
        ->whereNotNull('room_id')
        ->where('check_in_date', '<', $booking->check_out_date)
        ->where('check_out_date', '>', $booking->check_in_date)
        ->pluck('room_id')
        ->toArray();

    $availableRooms = \App\Models\Room::where('hotel_id', $booking->hotel_id)
        ->where('status', 'available')
        ->whereNotIn('id', $bookedRoomIds)
        ->orderBy('room_number')
        ->get();
@endphp
<div id="assignRoomModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Assign Room & Confirm Booking</h3>
            <button onclick="document.getElementById('assignRoomModal').style.display='none'" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Select a room for <strong>{{ $booking->booking_number }}</strong> ({{ $booking->customer?->name }}).</p>
        <form method="POST" action="{{ route('admin.booking-widget.confirm', $booking->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                <select name="room_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">— Select room —</option>
                    @foreach($availableRooms as $rm)
                    <option value="{{ $rm->id }}">RM {{ $rm->room_number }} — {{ ucfirst($rm->type) }} (₹{{ number_format($rm->price_per_night) }}/night)</option>
                    @endforeach
                </select>
                @if($availableRooms->isEmpty())
                <p class="text-xs text-amber-600 mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>No available rooms right now. Update room status first.</p>
                @endif
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-2 text-sm font-semibold text-white rounded-lg" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                    <i class="fas fa-check mr-1"></i> Confirm & Assign
                </button>
                <button type="button" onclick="document.getElementById('assignRoomModal').style.display='none'"
                        class="flex-1 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Guest Checkout QR Modal --}}
@if($booking->checkout_token)
<div id="bkGuestQrModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:20px;padding:28px;max-width:340px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);" onclick="event.stopPropagation()">
        <div style="font-weight:800;color:#1e293b;font-size:16px;margin-bottom:4px;"><i class="fas fa-qrcode" style="color:#10b981;margin-right:8px;"></i>Guest Self-Checkout QR</div>
        <div style="font-size:12px;color:#64748b;margin-bottom:16px;">Guest scans this to view bill &amp; confirm payment</div>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('guest.checkout.show', $booking->checkout_token)) }}&bgcolor=ffffff&color=0f172a&qzone=1"
            alt="Checkout QR" style="width:200px;height:200px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:12px;">
        <div style="font-size:11px;color:#94a3b8;word-break:break-all;margin-bottom:14px;">{{ route('guest.checkout.show', $booking->checkout_token) }}</div>
        @if($booking->guest_checkout_submitted_at)
        <div style="background:#ecfdf5;border-radius:10px;padding:10px;font-size:12px;color:#166534;font-weight:600;margin-bottom:12px;">
            <i class="fas fa-check-circle" style="margin-right:4px;"></i>Guest confirmed payment
            <div style="font-weight:400;color:#15803d;margin-top:2px;">{{ ucfirst($booking->guest_payment_method ?? '—') }}{{ $booking->guest_payment_ref ? ' · Ref: ' . $booking->guest_payment_ref : '' }}</div>
        </div>
        @endif
        <button onclick="document.getElementById('bkGuestQrModal').style.display='none'"
            style="width:100%;padding:10px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;">Close</button>
    </div>
</div>
@endif

@endsection
