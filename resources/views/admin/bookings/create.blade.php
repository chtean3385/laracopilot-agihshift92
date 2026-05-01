@extends('layouts.admin')
@section('title', 'New Booking')
@section('page-title', 'Create New Booking')
@section('page-subtitle', 'Reserve a room for a guest')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" rel="stylesheet">
<style>
    .ts-wrapper { position: relative; }
    .ts-control {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0 40px 0 14px;
        min-height: 44px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        box-shadow: none;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #06b6d4;
        box-shadow: 0 0 0 3px rgba(6,182,212,.12);
        outline: none;
    }
    .ts-control input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        flex: 1;
        font-size: 14px;
        color: #374151;
        padding: 0 !important;
        margin: 0 !important;
        min-width: 60px;
        height: auto;
    }
    .ts-control .item {
        font-size: 14px;
        color: #374151;
        line-height: 1;
    }
    .ts-control::after {
        content: '';
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #9ca3af;
        pointer-events: none;
    }
    .ts-wrapper.open .ts-control::after { border-top: none; border-bottom: 5px solid #06b6d4; }
    .ts-wrapper.open .ts-control { border-color: #06b6d4; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    .ts-dropdown {
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        z-index: 9999;
        background: #fff;
        border: 1.5px solid #06b6d4;
        border-top: none;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1);
        overflow: hidden;
    }
    .ts-dropdown .ts-dropdown-content { max-height: 220px; overflow-y: auto; }
    .ts-dropdown-content::-webkit-scrollbar { width: 5px; }
    .ts-dropdown-content::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 13.5px;
        color: #374151;
        cursor: pointer;
        border-bottom: 1px solid #f9fafb;
        transition: background .1s;
    }
    .ts-dropdown .option:last-child { border-bottom: none; }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active { background: #f0fdfe; color: #0891b2; }
    .ts-dropdown .option.selected { background: #cffafe; color: #0e7490; font-weight: 500; }
    .ts-dropdown .no-results { padding: 12px 14px; font-size: 13px; color: #9ca3af; text-align: center; }
    .ts-dropdown .ts-dropdown-content .create { padding: 10px 14px; font-size: 13px; color: #06b6d4; }
</style>
@endpush

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('bookings.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to Bookings</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-plus text-cyan-500 mr-2"></i>Booking Details</h3>
            <label class="inline-flex items-center gap-2 cursor-pointer select-none" title="Book entire property for the selected dates">
                <input type="checkbox" id="wholeHotelToggle" class="w-3.5 h-3.5 accent-amber-500 cursor-pointer" onclick="toggleWholeHotel()"{{ old('is_whole_hotel') == '1' ? ' checked' : '' }}>
                <span class="text-xs font-medium text-amber-700"><i class="fas fa-hotel mr-1"></i>Whole Hotel / Villa</span>
            </label>
        </div>
        {{-- Validation error banner --}}
        @if($errors->any())
        <div class="mx-6 mt-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
            <div class="text-sm text-red-700">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        <form action="{{ route('bookings.store') }}" method="POST" class="p-6" id="bookingForm">
            @csrf
            <input type="hidden" name="is_whole_hotel" id="isWholeHotelInput" value="{{ old('is_whole_hotel', '0') }}">
            <input type="hidden" name="whole_hotel_pricing_type" id="whPricingTypeInput" value="{{ old('whole_hotel_pricing_type', 'per_night') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $whPerNightSum = \App\Models\Room::where('hotel_id', session('crm_hotel_id'))
                        ->where('status','!=','maintenance')
                        ->whereIn('pricing_type', ['per_night', null])
                        ->sum('price_per_night') ?: 0;
                @endphp
                <script>window.whPerNightSum = {{ (float) $whPerNightSum }}; window.whSlotModuleOn = {{ $slotModuleOn ? 'true' : 'false' }};</script>
                {{-- ── Dates first row ────────────────────────────────────────── --}}
                <div id="perNightFields" class="contents">
                <div>
                    <label class="form-label">Check-In Date <span class="text-red-500">*</span></label>
                    <input type="date" name="check_in_date" id="checkIn" value="{{ old('check_in_date', request('date')) }}" min="{{ date('Y-m-d') }}" class="form-input" onchange="calculateTotal()">
                    @error('check_in_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Check-Out Date <span class="text-red-500">*</span></label>
                    <input type="date" name="check_out_date" id="checkOut" value="{{ old('check_out_date') }}" class="form-input" onchange="calculateTotal()">
                    @error('check_out_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                </div>
                {{-- ── Guest + Room row ────────────────────────────────────────── --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="form-label mb-0">Guest <span class="text-red-500">*</span></label>
                        <button type="button" onclick="openQuickGuestModal()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-600 hover:text-cyan-800 transition-colors"
                            title="Add new guest">
                            <i class="fas fa-user-plus text-xs"></i> + Add Guest
                        </button>
                    </div>
                    <select name="customer_id" id="guestSelect" class="@error('customer_id') border-red-400 @enderror" required>
                        <option value="">Search guest by name or phone...</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>{{ $customer->name }} — {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div id="roomSelectWrapper">
                    <label class="form-label">Room <span class="text-red-500">*</span></label>
                    <select name="room_id" id="roomSelect" class="@error('room_id') border-red-400 @enderror" required>
                        <option value="">Search room by number or type...</option>
                        @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                            data-price="{{ $room->price_per_night }}"
                            data-pricing-type="{{ $room->pricing_type ?? 'per_night' }}"
                            data-hourly-rate="{{ $room->hourly_rate ?? 0 }}"
                            data-slot-module="{{ ($hotelModules[$room->hotel_id]['slot'] ?? false) ? '1' : '0' }}"
                            data-hourly-module="{{ ($hotelModules[$room->hotel_id]['hourly'] ?? false) ? '1' : '0' }}"
                            data-has-breakfast="{{ $room->has_breakfast ? '1' : '0' }}"
                            data-breakfast-price="{{ $room->breakfast_price ?? 0 }}"
                            data-has-lunch="{{ $room->has_lunch ? '1' : '0' }}"
                            data-lunch-price="{{ $room->lunch_price ?? 0 }}"
                            data-has-dinner="{{ $room->has_dinner ? '1' : '0' }}"
                            data-dinner-price="{{ $room->dinner_price ?? 0 }}"
                            data-has-extra-bed="{{ $room->has_extra_bed ? '1' : '0' }}"
                            data-extra-bed-price="{{ $room->extra_bed_price ?? 0 }}"
                            {{ old('room_id', request('room_id')) == $room->id ? 'selected' : '' }}>
                            @php
                                $pType = $room->pricing_type ?? 'per_night';
                                $label = match($pType) {
                                    'per_slot' => '⏱ Slot-based',
                                    'per_hour' => '⏰ ₹' . number_format($room->hourly_rate ?? 0) . '/hr',
                                    default    => '₹' . number_format($room->price_per_night) . '/night',
                                };
                            @endphp
                            Room {{ $room->room_number }} — {{ ucfirst($room->type) }} — {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('room_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <div id="roomAvailBadge" class="hidden mt-2 text-xs font-medium rounded-lg px-3 py-1.5 border"></div>
                </div>

                @if($slotModuleOn)
                {{-- Whole Hotel: Arrival Time Slot row (shown only when whole hotel toggle is ON) --}}
                <div id="whArrivalSlotRow" class="md:col-span-2 hidden">
                    <div class="border border-amber-200 bg-amber-50 rounded-xl p-4">
                        <label class="form-label mb-2"><i class="fas fa-clock mr-1 text-amber-500"></i>Arrival Time Slot <span class="text-xs font-normal text-gray-400">(optional — leave blank for full-day check-in)</span></label>
                        <select name="time_slot_id" id="whArrivalSlotSelect" class="form-input" onchange="syncWhSlotMode()">
                            <option value="">No specific slot (full-day check-in)</option>
                            @foreach($timeSlots as $slot)
                            <option value="{{ $slot->id }}" data-price="{{ $slot->base_price }}"
                                {{ old('time_slot_id') == $slot->id ? 'selected' : '' }}>
                                {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }}) — ₹{{ number_format($slot->base_price) }}
                            </option>
                            @endforeach
                        </select>
                        @error('time_slot_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-amber-600 mt-2"><i class="fas fa-info-circle mr-1"></i>Select a slot if this booking starts from a specific time on check-in day (e.g. 5pm onward). Same-day bookings with non-overlapping slots can then coexist.</p>
                    </div>
                </div>
                @endif

                @if($slotModuleOn)
                {{-- Per Slot fields (time-slot-pricing module) --}}
                <div id="perSlotFields" class="md:col-span-2 hidden">
                    <div class="border border-violet-100 bg-violet-50 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-clock text-violet-500"></i>
                            <h4 class="font-bold text-gray-700">Slot Booking</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Booking Date <span class="text-red-500">*</span></label>
                                <input type="date" name="booking_date" id="slotBookingDate" value="{{ old('booking_date', request('date')) }}" min="{{ date('Y-m-d') }}" class="form-input" onchange="calculateSlotTotal(); refreshAvailableSlots();">
                            </div>
                            <div>
                                <label class="form-label">Time Slot <span class="text-red-500">*</span></label>
                                <select name="time_slot_id" id="timeSlotSelect" class="form-input" onchange="calculateSlotTotal(); updateSlotBadge();">
                                    <option value="">Select a time slot...</option>
                                    @foreach($timeSlots as $slot)
                                    <option value="{{ $slot->id }}" data-price="{{ $slot->base_price }}"
                                        {{ old('time_slot_id') == $slot->id ? 'selected' : '' }}>
                                        {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }}) — ₹{{ number_format($slot->base_price) }}
                                    </option>
                                    @endforeach
                                </select>
                                <div id="slotAvailBadge" class="hidden mt-2 flex items-center gap-2 text-sm rounded-xl px-3 py-2 font-medium border"></div>
                            </div>
                        </div>
                        @if($addOns->isNotEmpty())
                        <div>
                            <label class="form-label">Add-Ons <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                            <div class="flex flex-wrap gap-3 mt-1">
                                @foreach($addOns as $ao)
                                <label class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 cursor-pointer hover:border-violet-300 transition-colors text-sm">
                                    <input type="checkbox" name="addon_ids[]" value="{{ $ao->id }}" class="w-4 h-4 rounded text-violet-500 addon-check" data-price="{{ $ao->price }}" onchange="calculateSlotTotal()">
                                    <span class="font-medium text-gray-700">{{ $ao->name }}</span>
                                    <span class="text-violet-600 font-bold">+₹{{ number_format($ao->price) }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="bg-white rounded-xl border border-violet-100 px-4 py-4 space-y-2">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-rupee-sign text-violet-500"></i>
                                <span class="text-sm text-gray-500">Slot Total:</span>
                                <span id="slotTotalDisplay" class="text-xl font-bold text-violet-700">—</span>
                            </div>
                            <div class="flex items-center gap-3 pt-1 border-t border-violet-50">
                                <i class="fas fa-pen text-violet-300 text-xs"></i>
                                <span class="text-xs text-gray-400">Override price:</span>
                                <input type="number" id="slotCustomTotalInput" step="0.01" min="0"
                                       class="flex-1 text-sm font-bold text-violet-600 bg-transparent border-b border-dashed border-violet-300 focus:outline-none focus:border-violet-500 py-0.5 text-center"
                                       placeholder="Enter custom total to override">
                                <button type="button" id="resetSlotTotalBtn" onclick="resetSlotCustomTotal()"
                                        class="text-xs text-violet-500 hover:text-violet-700 underline hidden">↺ Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($hourlyModuleOn)
                {{-- Per Hour fields (hourly-pricing module) --}}
                <div id="perHourFields" class="md:col-span-2 hidden">
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-hourglass-half text-amber-500"></i>
                            <h4 class="font-bold text-gray-700">Hourly Booking</h4>
                            <span id="hourlyRateTag" class="text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5 font-semibold"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Booking Date <span class="text-red-500">*</span></label>
                                <input type="date" name="booking_date" id="hourBookingDate" value="{{ old('booking_date', request('date')) }}" min="{{ date('Y-m-d') }}" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Start Time <span class="text-red-500">*</span></label>
                                <input type="time" name="slot_start_time" id="slotStartTime" value="{{ old('slot_start_time') }}" class="form-input">
                            </div>
                        </div>
                        @if($addOns->isNotEmpty())
                        <div>
                            <label class="form-label">Add-Ons <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                            <div class="flex flex-wrap gap-3 mt-1">
                                @foreach($addOns as $ao)
                                <label class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 cursor-pointer hover:border-amber-300 transition-colors text-sm">
                                    <input type="checkbox" name="addon_ids[]" value="{{ $ao->id }}" class="w-4 h-4 rounded text-amber-500 addon-check-hour" data-price="{{ $ao->price }}">
                                    <span class="font-medium text-gray-700">{{ $ao->name }}</span>
                                    <span class="text-amber-600 font-bold">+₹{{ number_format($ao->price) }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="bg-white rounded-xl border border-amber-100 p-4 space-y-3">
                            <div class="flex items-center gap-2 text-sm text-amber-700">
                                <i class="fas fa-clock text-amber-500"></i>
                                <span class="font-medium">Billing is calculated at check-out from actual hours. Optionally set a fixed total below to override.</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-semibold text-gray-600 whitespace-nowrap">Custom Total (₹)</label>
                                <input type="number" id="hourCustomTotalInput" step="0.01" min="0"
                                       class="form-input flex-1 text-amber-700 font-bold"
                                       placeholder="Leave blank to calculate at check-out">
                                <button type="button" id="resetHourTotalBtn" onclick="resetHourCustomTotal()"
                                        class="text-xs text-amber-500 hover:text-amber-700 underline hidden">↺ Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div>
                    <label class="form-label">Adults <span class="text-red-500">*</span></label>
                    <input type="number" name="adults" value="{{ old('adults', 1) }}" min="1" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Children</label>
                    <input type="number" name="children" value="{{ old('children', 0) }}" min="0" class="form-input">
                </div>

                <!-- Meal Plan Section -->
                <div class="md:col-span-2" id="mealPlanSection" style="display:none">
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-utensils text-amber-500"></i>
                            <h4 class="font-bold text-gray-700">Meal Plan</h4>
                            <span class="text-xs text-gray-400 ml-1">— prices are per night</span>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label id="meal_breakfast_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_breakfast" value="1" id="meal_breakfast"
                                    class="w-4 h-4 rounded text-amber-500" onchange="calculateTotal()"
                                    {{ old('meal_breakfast') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
                                <span id="meal_breakfast_price" class="text-sm text-amber-600 font-bold"></span>
                            </label>
                            <label id="meal_lunch_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_lunch" value="1" id="meal_lunch"
                                    class="w-4 h-4 rounded text-orange-500" onchange="calculateTotal()"
                                    {{ old('meal_lunch') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
                                <span id="meal_lunch_price" class="text-sm text-orange-600 font-bold"></span>
                            </label>
                            <label id="meal_dinner_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_dinner" value="1" id="meal_dinner"
                                    class="w-4 h-4 rounded text-indigo-500" onchange="calculateTotal()"
                                    {{ old('meal_dinner') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
                                <span id="meal_dinner_price" class="text-sm text-indigo-600 font-bold"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Extra Bed Section -->
                <div class="md:col-span-2" id="extraBedSection" style="display:none">
                    <div class="border border-blue-100 bg-blue-50 rounded-2xl p-5">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed text-blue-500"></i>
                                <h4 class="font-bold text-gray-700">Extra Beds</h4>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-gray-600">How many extra beds?</label>
                                <input type="number" name="extra_beds" id="extraBedsInput"
                                    value="{{ old('extra_beds', 0) }}" min="0" max="10"
                                    class="form-input w-20 text-sm text-center" onchange="calculateTotal()">
                                <span id="extraBedPriceLabel" class="text-sm text-blue-600 font-semibold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Summary -->
                <div id="priceNightSummary" class="md:col-span-2 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-2xl p-5 border border-cyan-100">
                    <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-calculator text-cyan-500 mr-2"></i>Price Summary</h4>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-sm text-gray-500">Nights</div>
                            <div id="nightsCount" class="text-2xl font-bold text-gray-700">—</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Rate/Night</div>
                            <div id="rateDisplay" class="text-2xl font-bold text-gray-700">—</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Total Amount</div>
                            <input type="number" id="customTotalInput" name="custom_total" step="0.01" min="0"
                                   value="{{ old('custom_total') }}"
                                   class="text-2xl font-bold text-cyan-600 bg-transparent border-b-2 border-dashed border-cyan-300 w-full text-center focus:outline-none focus:border-cyan-500 py-0.5"
                                   placeholder="—">
                            <div id="mealCostLine" class="text-xs text-amber-600 font-semibold mt-1"></div>
                            <div class="mt-1 text-center">
                                <button type="button" id="resetTotalBtn" onclick="resetCustomTotal()"
                                        class="text-xs text-cyan-500 hover:text-cyan-700 underline hidden">↺ Reset to calculated</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Advance Payment (₹)</label>
                    <input type="number" name="advance_payment" id="advancePayment" value="{{ old('advance_payment', 0) }}" step="0.01" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash">Cash</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" rows="3" class="form-input" placeholder="Any special requests or requirements from the guest...">{{ old('special_requests') }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('bookings.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-calendar-check mr-2"></i>Create Booking</button>
            </div>
        </form>
    </div>
</div>
{{-- Quick Add Guest Modal --}}
<div id="quickGuestModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);" onclick="if(event.target===this)closeQuickGuestModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-t-2xl">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-cyan-500 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-plus text-white text-xs"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Quick Add Guest</h3>
                    <p class="text-xs text-gray-400">Guest will be saved to this hotel</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickGuestModal()" class="text-gray-400 hover:text-gray-600 transition-colors w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickGuestForm" class="p-6" novalidate>
            @csrf
            <div id="qgError" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
                <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                <span id="qgErrorMsg"></span>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="qg_name" name="name" class="form-input" placeholder="Guest full name" required>
                </div>
                <div>
                    <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" id="qg_phone" name="phone" class="form-input" placeholder="9876543210" required>
                    <p class="text-xs text-gray-400 mt-1">10-digit Indian mobile. Foreign guests: include country code (e.g. +447911123456).</p>
                </div>
                <div>
                    <label class="form-label">Email <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                    <input type="email" id="qg_email" name="email" class="form-input" placeholder="guest@email.com">
                </div>
                <div>
                    <label class="form-label">ID / Document Type <span class="text-red-500">*</span></label>
                    <select id="qg_id_type" name="id_type" class="form-input" required>
                        <option value="">Select document type</option>
                        <option value="aadhaar">Aadhaar Card</option>
                        <option value="passport">Passport</option>
                        <option value="driving_license">Driving License</option>
                        <option value="voter_id">Voter ID</option>
                        <option value="pan_card">PAN Card</option>
                        <option value="visa">Visa</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeQuickGuestModal()" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" id="qgSubmitBtn" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save mr-2"></i>Save Guest
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    const guestTomSelect = new TomSelect('#guestSelect', {
        allowEmptyOption: false,
        placeholder: 'Search guest by name or phone...',
        maxOptions: 300,
    });

    // ── TomSelect internally replaces <select> innerHTML on every value change,
    // wiping data-* attributes that updatePricingUI() reads. We restore them
    // before any pricing/slot logic runs.
    function restoreRoomDataAttrs() {
        const sel = document.getElementById('roomSelect');
        Array.from(sel.options).forEach(function(opt) {
            if (!opt.value) return;
            var room = _allRoomOptions.find(function(r) { return r.value == opt.value; });
            if (!room) return;
            Object.entries(room.dataset).forEach(function(kv) { opt.dataset[kv[0]] = kv[1]; });
        });
    }

    let roomTS = new TomSelect('#roomSelect', {
        allowEmptyOption: false,
        placeholder: 'Search room by number or type...',
        maxOptions: 100,
        onChange: function() {
            restoreRoomDataAttrs();  // must be first — rebuilds data-* before pricing reads them
            updatePricingUI();
            updateMealOptions();
            calculateTotal();
            refreshAvailableSlots();
        }
    });

    // Disable/enable all form inputs inside a container so hidden ones
    // are excluded from submission (fixes duplicate name="booking_date" issue).
    function setFieldsEnabled(container, enabled) {
        if (!container) return;
        container.querySelectorAll('input, select, textarea').forEach(function(el) {
            el.disabled = !enabled;
        });
    }

    function updatePricingUI() {
        const roomEl = document.getElementById('roomSelect');
        const opt    = roomEl.options[roomEl.selectedIndex];
        const pt     = opt ? (opt.dataset.pricingType || 'per_night') : 'per_night';

        // Per-room module flags baked into the option's data attributes
        const slotModuleEnabled   = opt ? opt.dataset.slotModule   === '1' : false;
        const hourlyModuleEnabled = opt ? opt.dataset.hourlyModule  === '1' : false;

        const perNightEl = document.getElementById('perNightFields');
        const perSlotEl  = document.getElementById('perSlotFields');
        const perHourEl  = document.getElementById('perHourFields');

        // Per-Night: show/hide + enable/disable + required flags
        if (perNightEl) {
            const showNight = pt === 'per_night' || (pt === 'per_slot' && !slotModuleEnabled) || (pt === 'per_hour' && !hourlyModuleEnabled);
            if (showNight) {
                perNightEl.classList.remove('hidden'); perNightEl.classList.add('contents');
                setFieldsEnabled(perNightEl, true);
                document.getElementById('checkIn').required  = true;
                document.getElementById('checkOut').required = true;
            } else {
                perNightEl.classList.remove('contents'); perNightEl.classList.add('hidden');
                setFieldsEnabled(perNightEl, false);
                document.getElementById('checkIn').required  = false;
                document.getElementById('checkOut').required = false;
            }
        }

        // Per-Slot: show only when room is per_slot AND slot module is enabled
        if (perSlotEl) {
            const showSlot = pt === 'per_slot' && slotModuleEnabled;
            perSlotEl.classList.toggle('hidden', !showSlot);
            setFieldsEnabled(perSlotEl, showSlot);
            const sd = document.getElementById('slotBookingDate');
            const st = document.getElementById('timeSlotSelect');
            if (sd) sd.required = showSlot;
            if (st) st.required = showSlot;
        }

        // Per-Hour: show only when room is per_hour AND hourly module is enabled
        if (perHourEl) {
            const showHour = pt === 'per_hour' && hourlyModuleEnabled;
            perHourEl.classList.toggle('hidden', !showHour);
            setFieldsEnabled(perHourEl, showHour);
            const hd = document.getElementById('hourBookingDate');
            const hs = document.getElementById('slotStartTime');
            if (hd) hd.required = showHour;
            if (hs) hs.required = showHour;
        }

        // Show/hide price summary
        const summaryEl = document.getElementById('priceNightSummary');
        if (summaryEl) summaryEl.classList.toggle('hidden', pt !== 'per_night');

        // Hourly rate tag
        const hrTag = document.getElementById('hourlyRateTag');
        if (hrTag && opt) hrTag.textContent = '₹' + parseFloat(opt.dataset.hourlyRate || 0).toLocaleString('en-IN') + '/hr';

        // Reset custom-total override flags when pricing type changes
        window._customTotalDirty     = false;
        window._slotCustomTotalDirty = false;
        window._hourCustomTotalDirty = false;
        const cti = document.getElementById('customTotalInput');
        if (cti) { cti.value = ''; }
        const sci = document.getElementById('slotCustomTotalInput');
        if (sci) { sci.value = ''; }
        const hci = document.getElementById('hourCustomTotalInput');
        if (hci) { hci.value = ''; }
        const rb  = document.getElementById('resetTotalBtn');
        const srb = document.getElementById('resetSlotTotalBtn');
        const hrb = document.getElementById('resetHourTotalBtn');
        if (rb)  rb.classList.add('hidden');
        if (srb) srb.classList.add('hidden');
        if (hrb) hrb.classList.add('hidden');
    }

    // Run on page load: disables hidden-section inputs from the start and
    // restores the correct pricing section if old('room_id') is set.
    (function initPricingUI() {
        updatePricingUI();   // always — ensures hidden sections are disabled
        updateMealOptions();
        calculateTotal();
        calculateSlotTotal();
        refreshAvailableSlots(); // check slot availability for any prefilled room+date
    })();

    function calculateSlotTotal() {
        const slotSel = document.getElementById('timeSlotSelect');
        const slotOpt = slotSel ? slotSel.options[slotSel.selectedIndex] : null;
        let total = slotOpt ? parseFloat(slotOpt.dataset.price || 0) : 0;
        document.querySelectorAll('.addon-check:checked').forEach(cb => { total += parseFloat(cb.dataset.price || 0); });
        const el = document.getElementById('slotTotalDisplay');
        if (el) el.textContent = total > 0 ? '₹' + total.toLocaleString('en-IN') : '—';
        // Sync to the slot custom override input (unless user manually overrode)
        if (!window._slotCustomTotalDirty) {
            const slotCustom = document.getElementById('slotCustomTotalInput');
            if (slotCustom) slotCustom.value = total > 0 ? total.toFixed(2) : '';
            // Also sync to the submittable hidden input
            const hiddenInp = document.getElementById('customTotalInput');
            if (hiddenInp) hiddenInp.value = total > 0 ? total.toFixed(2) : '';
        }
        const btn = document.getElementById('resetSlotTotalBtn');
        if (btn) btn.classList.toggle('hidden', !window._slotCustomTotalDirty);
    }

    // Fetch available slot IDs for the currently selected room + date and
    // mark unavailable slots as disabled in the Time Slot dropdown.
    function refreshAvailableSlots() {
        const roomEl = document.getElementById('roomSelect');
        const roomId = roomEl ? roomEl.value : '';
        const dateEl = document.getElementById('slotBookingDate');
        const date   = dateEl ? dateEl.value : '';
        const slotSel = document.getElementById('timeSlotSelect');
        if (!slotSel) return;

        // Reset all options to enabled first
        Array.from(slotSel.options).forEach(function(opt) {
            opt.disabled = false;
            opt.style.color = '';
            opt.title = '';
            if (opt.dataset.origText) {
                opt.textContent = opt.dataset.origText;
            }
        });

        if (!roomId || !date) return;

        fetch('/bookings/available-time-slots?room_id=' + encodeURIComponent(roomId) + '&date=' + encodeURIComponent(date), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            const available = data.available_slot_ids || [];
            Array.from(slotSel.options).forEach(function(opt) {
                if (!opt.value) return; // placeholder
                const slotId = parseInt(opt.value, 10);
                if (!available.includes(slotId)) {
                    // Preserve original text before modification
                    if (!opt.dataset.origText) opt.dataset.origText = opt.textContent;
                    opt.disabled = true;
                    opt.style.color = '#9ca3af';
                    opt.textContent = opt.dataset.origText + ' [Unavailable]';
                    opt.title = 'This slot is unavailable due to an overlapping booking';
                    // If the currently selected slot is now unavailable, deselect it
                    if (slotSel.value == opt.value) {
                        slotSel.value = '';
                        calculateSlotTotal();
                    }
                }
            });
            updateSlotBadge();
        })
        .catch(function() { /* silently ignore network errors */ });
    }

    function updateSlotBadge() {
        var slotSel = document.getElementById('timeSlotSelect');
        var badge   = document.getElementById('slotAvailBadge');
        if (!slotSel || !badge) return;
        if (!slotSel.value) {
            badge.className = 'hidden mt-2 flex items-center gap-2 text-sm rounded-xl px-3 py-2 font-medium border';
            badge.innerHTML = '';
            return;
        }
        var selectedOpt = slotSel.options[slotSel.selectedIndex];
        if (selectedOpt && selectedOpt.disabled) {
            badge.className = 'mt-2 flex items-center gap-2 text-sm rounded-xl px-3 py-2 font-medium border bg-red-50 border-red-200 text-red-700';
            badge.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i><span>Conflict — this room already has an overlapping booking on this date.</span>';
        } else if (selectedOpt && slotSel.value) {
            badge.className = 'mt-2 flex items-center gap-2 text-sm rounded-xl px-3 py-2 font-medium border bg-green-50 border-green-200 text-green-700';
            badge.innerHTML = '<i class="fas fa-check-circle text-green-500"></i><span>Available — room is free for this slot.</span>';
        } else {
            badge.className = 'hidden mt-2 flex items-center gap-2 text-sm rounded-xl px-3 py-2 font-medium border';
            badge.innerHTML = '';
        }
    }

    function updateMealOptions() {
        const roomEl = document.getElementById('roomSelect');
        const opt = roomEl.options[roomEl.selectedIndex];
        const meals = ['breakfast', 'lunch', 'dinner'];
        let hasMeals = false;
        meals.forEach(function(m) {
            const key = 'has' + m.charAt(0).toUpperCase() + m.slice(1);
            const has = opt && opt.dataset[key] === '1';
            const price = opt ? parseFloat(opt.dataset[m + 'Price'] || 0) : 0;
            const row = document.getElementById('meal_' + m + '_row');
            const priceSpan = document.getElementById('meal_' + m + '_price');
            if (row) {
                if (has) {
                    row.classList.remove('hidden');
                    row.classList.add('flex');
                    if (priceSpan) priceSpan.textContent = '₹' + price.toLocaleString('en-IN') + '/night';
                    hasMeals = true;
                } else {
                    row.classList.add('hidden');
                    row.classList.remove('flex');
                    const cb = document.getElementById('meal_' + m);
                    if (cb) cb.checked = false;
                }
            }
        });
        const section = document.getElementById('mealPlanSection');
        if (section) section.style.display = hasMeals ? '' : 'none';

        const hasExtraBed = opt && opt.dataset.hasExtraBed === '1';
        const extraBedPrice = opt ? parseFloat(opt.dataset.extraBedPrice || 0) : 0;
        const ebSection = document.getElementById('extraBedSection');
        const ebLabel   = document.getElementById('extraBedPriceLabel');
        if (ebSection) ebSection.style.display = hasExtraBed ? '' : 'none';
        if (!hasExtraBed) {
            const ebInput = document.getElementById('extraBedsInput');
            if (ebInput) ebInput.value = 0;
        }
        if (ebLabel) ebLabel.textContent = hasExtraBed ? '₹' + extraBedPrice.toLocaleString('en-IN') + '/bed/night' : '';
    }

    // Track whether the admin has manually overridden the price
    window._customTotalDirty     = false;
    window._slotCustomTotalDirty = false;

    function setCustomTotal(val) {
        const inp = document.getElementById('customTotalInput');
        const btn = document.getElementById('resetTotalBtn');
        if (!window._customTotalDirty && inp) {
            inp.value = val > 0 ? val.toFixed(2) : '';
        }
        if (btn) btn.classList.toggle('hidden', !window._customTotalDirty);
    }

    function resetCustomTotal() {
        window._customTotalDirty = false;
        calculateTotal();
        const btn = document.getElementById('resetTotalBtn');
        if (btn) btn.classList.add('hidden');
    }

    function resetSlotCustomTotal() {
        window._slotCustomTotalDirty = false;
        calculateSlotTotal();
        const btn = document.getElementById('resetSlotTotalBtn');
        if (btn) btn.classList.add('hidden');
    }

    document.getElementById('customTotalInput')?.addEventListener('input', function() {
        window._customTotalDirty = true;
        const btn = document.getElementById('resetTotalBtn');
        if (btn) btn.classList.remove('hidden');
    });

    document.getElementById('slotCustomTotalInput')?.addEventListener('input', function() {
        window._slotCustomTotalDirty = true;
        // Mirror to the hidden submittable input
        const hiddenInp = document.getElementById('customTotalInput');
        if (hiddenInp) hiddenInp.value = this.value;
        const btn = document.getElementById('resetSlotTotalBtn');
        if (btn) btn.classList.remove('hidden');
    });

    document.getElementById('hourCustomTotalInput')?.addEventListener('input', function() {
        window._hourCustomTotalDirty = true;
        // Mirror to the hidden submittable input
        const hiddenInp = document.getElementById('customTotalInput');
        if (hiddenInp) hiddenInp.value = this.value;
        const btn = document.getElementById('resetHourTotalBtn');
        if (btn) btn.classList.remove('hidden');
    });

    function resetHourCustomTotal() {
        window._hourCustomTotalDirty = false;
        const hci = document.getElementById('hourCustomTotalInput');
        if (hci) hci.value = '';
        const hiddenInp = document.getElementById('customTotalInput');
        if (hiddenInp) hiddenInp.value = '';
        const btn = document.getElementById('resetHourTotalBtn');
        if (btn) btn.classList.add('hidden');
    }

    function calculateTotal() {
        const checkin = document.getElementById('checkIn').value;
        const checkout = document.getElementById('checkOut').value;
        const roomEl = document.getElementById('roomSelect');
        const opt = roomEl.options[roomEl.selectedIndex];
        const pricePerNight = opt ? parseFloat(opt.dataset.price) || 0 : 0;
        if (checkin && checkout) {
            const d1 = new Date(checkin);
            const d2 = new Date(checkout);
            const nights = Math.max(1, Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)));
            let mealCost = 0;
            ['breakfast', 'lunch', 'dinner'].forEach(function(m) {
                const cb = document.getElementById('meal_' + m);
                const price = opt ? parseFloat(opt.dataset[m + 'Price'] || 0) : 0;
                if (cb && cb.checked) mealCost += nights * price;
            });
            const extraBeds = parseInt(document.getElementById('extraBedsInput')?.value || 0);
            const extraBedPrice = opt ? parseFloat(opt.dataset.extraBedPrice || 0) : 0;
            const hasExtraBed = opt && opt.dataset.hasExtraBed === '1';
            const extraBedCost = hasExtraBed ? extraBeds * extraBedPrice * nights : 0;
            const total = nights * pricePerNight + mealCost + extraBedCost;
            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('rateDisplay').textContent = pricePerNight ? '₹' + pricePerNight.toLocaleString('en-IN') : '—';
            setCustomTotal(total);
            const mealLine = document.getElementById('mealCostLine');
            let extras = [];
            if (mealCost > 0) extras.push('₹' + mealCost.toLocaleString('en-IN') + ' meals');
            if (extraBedCost > 0) extras.push('₹' + extraBedCost.toLocaleString('en-IN') + ' extra beds');
            if (mealLine) mealLine.textContent = extras.length > 0 ? '(incl. ' + extras.join(' + ') + ')' : '';
        }
    }

    document.getElementById('checkIn').addEventListener('change', calculateTotal);
    document.getElementById('checkOut').addEventListener('change', calculateTotal);

    // ── Whole-Hotel toggle ─────────────────────────────────────────────────
    function toggleWholeHotel() {
        const toggle         = document.getElementById('wholeHotelToggle');
        const isOn           = toggle.checked;
        const hiddenInput    = document.getElementById('isWholeHotelInput');
        const roomWrapper    = document.getElementById('roomSelectWrapper');
        const perNight       = document.getElementById('perNightFields');
        const perSlot        = document.getElementById('perSlotFields');
        const perHour        = document.getElementById('perHourFields');
        const mealSection    = document.getElementById('mealPlanSection');
        const extraBedSec    = document.getElementById('extraBedSection');
        const whArrivalRow   = document.getElementById('whArrivalSlotRow');

        hiddenInput.value = isOn ? '1' : '0';

        if (isOn) {
            // Hide room selector
            roomWrapper.classList.add('hidden');
            document.getElementById('roomSelect').required = false;
            document.getElementById('roomSelect').disabled = true;
            // Always show check-in / check-out date fields for whole-hotel
            if (perNight) { perNight.classList.remove('hidden'); perNight.classList.add('contents'); setFieldsEnabled(perNight, true); document.getElementById('checkIn').required = true; document.getElementById('checkOut').required = true; }
            // Hide individual-room per-slot / per-hour fields
            if (perSlot)  { perSlot.classList.add('hidden');  setFieldsEnabled(perSlot,  false); }
            if (perHour)  { perHour.classList.add('hidden');  setFieldsEnabled(perHour,  false); }
            // Hide meal and extra bed
            if (mealSection) mealSection.style.display = 'none';
            if (extraBedSec) extraBedSec.style.display = 'none';
            // Show arrival-slot row if slot module is active
            if (whArrivalRow && window.whSlotModuleOn) whArrivalRow.classList.remove('hidden');
            calcWhSuggestedTotal();
        } else {
            // Hide whole-hotel slot row and reset pricing type
            if (whArrivalRow) whArrivalRow.classList.add('hidden');
            const whPt = document.getElementById('whPricingTypeInput');
            if (whPt) whPt.value = 'per_night';
            const sel = document.getElementById('whArrivalSlotSelect');
            if (sel) sel.value = '';
            // Restore room selector
            roomWrapper.classList.remove('hidden');
            document.getElementById('roomSelect').required = true;
            document.getElementById('roomSelect').disabled = false;
            updatePricingUI();
            updateMealOptions();
        }
    }

    // Called when the arrival slot dropdown changes in whole-hotel mode
    function syncWhSlotMode() {
        const sel  = document.getElementById('whArrivalSlotSelect');
        const whPt = document.getElementById('whPricingTypeInput');
        if (!sel) return;
        const hasSlot = sel.value !== '';
        if (whPt) whPt.value = hasSlot ? 'per_slot' : 'per_night';
        // Suggest total from slot base price if not manually edited
        if (hasSlot) {
            const opt   = sel.options[sel.selectedIndex];
            const price = opt ? parseFloat(opt.dataset.price || 0) : 0;
            const inp   = document.getElementById('customTotalInput');
            if (inp && !inp._whUserEdited && price > 0) inp.value = price.toFixed(2);
        }
    }

    function calcWhSuggestedTotal() {
        const ci = document.getElementById('checkIn')?.value;
        const co = document.getElementById('checkOut')?.value;
        const priceInput = document.getElementById('customTotalInput');
        if (!priceInput) return;
        if (ci && co) {
            const nights = Math.max(1, Math.ceil((new Date(co) - new Date(ci)) / 86400000));
            const perNightSum = window.whPerNightSum || 0;
            const suggested = Math.round(perNightSum * nights);
            if (!priceInput._whUserEdited && suggested > 0) {
                priceInput.value = suggested;
            }
        }
    }

    // Recalculate suggested total when dates change (whole-hotel mode)
    document.getElementById('checkIn').addEventListener('change', function() { if (document.getElementById('isWholeHotelInput').value === '1') calcWhSuggestedTotal(); });
    document.getElementById('checkOut').addEventListener('change', function() { if (document.getElementById('isWholeHotelInput').value === '1') calcWhSuggestedTotal(); });

    // Track manual edits to Price Summary total so we don't overwrite in whole-hotel mode
    document.getElementById('customTotalInput')?.addEventListener('input', function() { this._whUserEdited = true; });

    // ── Restore whole-hotel state on page reload (e.g. after validation error) ──
    if (document.getElementById('isWholeHotelInput').value === '1') {
        document.getElementById('wholeHotelToggle').checked = true;
        toggleWholeHotel();
        // If a slot was previously selected, re-sync the pricing type hidden input
        const _restoreSel = document.getElementById('whArrivalSlotSelect');
        if (_restoreSel && _restoreSel.value) syncWhSlotMode();
    }

    // ── Booking form submit: show loading state ─────────────────────────────
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
        }
    });

    // ── Quick Add Guest Modal ─────────────────────────────────────────────
    function openQuickGuestModal() {
        document.getElementById('quickGuestModal').classList.remove('hidden');
        document.getElementById('qg_name').focus();
    }

    function closeQuickGuestModal() {
        document.getElementById('quickGuestModal').classList.add('hidden');
        document.getElementById('quickGuestForm').reset();
        document.getElementById('qgError').classList.add('hidden');
        document.getElementById('qgErrorMsg').textContent = '';
        const btn = document.getElementById('qgSubmitBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Guest';
    }

    // Close with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeQuickGuestModal();
    });

    document.getElementById('quickGuestForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('qgSubmitBtn');
        const errDiv = document.getElementById('qgError');
        const errMsg = document.getElementById('qgErrorMsg');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        errDiv.classList.add('hidden');
        try {
            const fd = new FormData(this);
            const res = await fetch('{{ route("customers.quickStore") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok) {
                const msgs = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message || 'Error saving guest.');
                errMsg.textContent = msgs;
                errDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Guest';
            } else {
                guestTomSelect.addOption({ value: data.id, text: data.label });
                guestTomSelect.setValue(data.id);
                closeQuickGuestModal();
            }
        } catch (err) {
            errMsg.textContent = 'Network error. Please try again.';
            errDiv.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Guest';
        }
    });

    // ── Date-driven room availability filtering ────────────────────────────
    // Capture all room options at page load so we can restore them after
    // TomSelect is destroyed/re-created when dates change.
    const _allRoomOptions = [];
    (function() {
        const sel = document.getElementById('roomSelect');
        Array.from(sel.options).forEach(function(opt) {
            if (!opt.value) return;
            _allRoomOptions.push({
                value:          opt.value,
                text:           opt.textContent.trim(),
                disabled:       false,
                selected:       opt.selected,
                dataset: {
                    price:           opt.dataset.price          || '0',
                    pricingType:     opt.dataset.pricingType    || 'per_night',
                    hourlyRate:      opt.dataset.hourlyRate      || '0',
                    slotModule:      opt.dataset.slotModule      || '0',
                    hourlyModule:    opt.dataset.hourlyModule    || '0',
                    hasBreakfast:    opt.dataset.hasBreakfast    || '0',
                    breakfastPrice:  opt.dataset.breakfastPrice  || '0',
                    hasLunch:        opt.dataset.hasLunch        || '0',
                    lunchPrice:      opt.dataset.lunchPrice      || '0',
                    hasDinner:       opt.dataset.hasDinner       || '0',
                    dinnerPrice:     opt.dataset.dinnerPrice     || '0',
                    hasExtraBed:     opt.dataset.hasExtraBed     || '0',
                    extraBedPrice:   opt.dataset.extraBedPrice   || '0',
                }
            });
        });
    })();

    function refreshAvailableRooms() {
        const params = new URLSearchParams();

        const checkIn  = document.getElementById('checkIn')?.value;
        const checkOut = document.getElementById('checkOut')?.value;
        const slotDate = document.getElementById('slotBookingDate')?.value;
        const slotId   = document.getElementById('timeSlotSelect')?.value;
        const hourDate = document.getElementById('hourBookingDate')?.value;

        if (checkIn && checkOut) {
            params.set('check_in',  checkIn);
            params.set('check_out', checkOut);
        } else if (slotDate) {
            params.set('date', slotDate);
            if (slotId) params.set('slot_id', slotId);
        } else if (hourDate) {
            params.set('date', hourDate);
        } else {
            updateRoomDropdown([]);
            return;
        }

        fetch('/bookings/available-rooms?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { updateRoomDropdown(data.unavailable_room_ids || []); })
        .catch(function() { /* silently ignore */ });
    }

    function updateRoomDropdown(unavailableIds) {
        const currentVal = roomTS.getValue();

        // 1. Rebuild the underlying <select> options with correct disabled state
        //    AND restore all data-* attributes from our snapshot.
        const sel = document.getElementById('roomSelect');
        const placeholder = sel.options[0]; // keep the empty placeholder
        sel.innerHTML = '';
        if (placeholder) sel.appendChild(placeholder);

        _allRoomOptions.forEach(function(room) {
            const isUnavailable = unavailableIds.includes(parseInt(room.value));
            const opt = document.createElement('option');
            opt.value       = room.value;
            opt.disabled    = isUnavailable;
            opt.textContent = room.text + (isUnavailable ? ' — Booked' : '');
            Object.entries(room.dataset).forEach(function(kv) { opt.dataset[kv[0]] = kv[1]; });
            sel.appendChild(opt);
        });

        // 2. Destroy old TomSelect and recreate from the updated <select>
        //    Using the same `let roomTS` variable so no stale references exist.
        roomTS.destroy();
        roomTS = new TomSelect('#roomSelect', {
            allowEmptyOption: false,
            placeholder: 'Search room by number or type...',
            maxOptions: 100,
            onChange: function() {
                restoreRoomDataAttrs();
                updatePricingUI();
                updateMealOptions();
                calculateTotal();
                refreshAvailableSlots();
            }
        });

        // Ensure data-* attributes are intact after TomSelect rebuild
        restoreRoomDataAttrs();

        // 3. Restore previous selection if still available; otherwise clear pricing
        if (currentVal && !unavailableIds.includes(parseInt(currentVal))) {
            roomTS.setValue(currentVal, true);
            restoreRoomDataAttrs(); // setValue may trigger updateOriginalInput again
        } else if (currentVal) {
            updatePricingUI(); // clears pricing display for now-booked room
        }

        // Show/update the availability badge
        const badge     = document.getElementById('roomAvailBadge');
        const total     = _allRoomOptions.length;
        const booked    = unavailableIds.filter(function(id) {
            return _allRoomOptions.some(function(r) { return parseInt(r.value) === id; });
        }).length;
        const available = total - booked;

        if (!badge) return;
        if (unavailableIds.length === 0) {
            badge.className = 'hidden mt-2 text-xs font-medium rounded-lg px-3 py-1.5 border';
            badge.textContent = '';
            return;
        }
        if (available > 0) {
            badge.className = 'mt-2 text-xs font-medium rounded-lg px-3 py-1.5 border bg-green-50 border-green-200 text-green-700';
            badge.textContent = available + ' of ' + total + ' rooms available for selected dates';
        } else {
            badge.className = 'mt-2 text-xs font-medium rounded-lg px-3 py-1.5 border bg-red-50 border-red-200 text-red-700';
            badge.textContent = 'No rooms available for the selected dates';
        }
    }

    // Wire up to all date inputs
    document.getElementById('checkIn')?.addEventListener('change',  refreshAvailableRooms);
    document.getElementById('checkOut')?.addEventListener('change', refreshAvailableRooms);
    document.getElementById('slotBookingDate')?.addEventListener('change', refreshAvailableRooms);
    document.getElementById('timeSlotSelect')?.addEventListener('change',  refreshAvailableRooms);
    document.getElementById('hourBookingDate')?.addEventListener('change', refreshAvailableRooms);
</script>
@endpush
@endsection
