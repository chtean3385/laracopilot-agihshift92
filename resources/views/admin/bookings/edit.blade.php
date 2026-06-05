@extends('layouts.admin')
@section('title','Edit Booking')
@section('page-title','Edit Booking')
@section('page-subtitle','{{ $booking->booking_reference }}')

@section('content')
@php $pricingType = $booking->is_whole_hotel ? ($booking->whole_hotel_pricing_type ?? 'per_night') : ($booking->room?->pricing_type ?? 'per_night'); @endphp
<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        @if($booking->is_whole_hotel)
        <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
            <i class="fas fa-hotel text-amber-500"></i>
            <span class="font-semibold">Whole Hotel / Villa Booking</span>
            <span class="text-xs text-amber-600 ml-1">— Room selection not applicable</span>
        </div>
        @endif
        <form action="{{ route('bookings.update',$booking->id) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @if($booking->is_whole_hotel)
            <input type="hidden" name="is_whole_hotel" value="1">
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Guest *</label>
                    <select name="customer_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id',$booking->customer_id)==$c->id?'selected':'' }}>{{ $c->name }} — {{ $c->phone }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!$booking->is_whole_hotel)
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Room *</label>
                    <select name="room_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                        @foreach($rooms as $r)
                        @php
                            $rLabel = match($r->pricing_type ?? 'per_night') {
                                'per_slot' => 'Slot-based',
                                'per_hour' => '₹' . number_format($r->hourly_rate ?? 0) . '/hr',
                                default    => '₹' . number_format($r->price_per_night, 0) . '/night',
                            };
                        @endphp
                        <option value="{{ $r->id }}" {{ old('room_id', $booking->room_id) == $r->id ? 'selected' : '' }}>
                            Room {{ $r->room_number }} — {{ ucfirst($r->type) }} — {{ $rLabel }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Only rooms of the same pricing type are shown.</p>
                </div>
                @endif

                {{-- Per Night: check-in/out dates --}}
                @if($pricingType === 'per_night')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Check-In Date *</label>
                    <input type="date" name="check_in_date" value="{{ old('check_in_date',$booking->check_in_date->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Check-Out Date *</label>
                    <input type="date" name="check_out_date" value="{{ old('check_out_date',$booking->check_out_date->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                </div>
                @endif

                {{-- Per Slot: booking date + time slot --}}
                @if($pricingType === 'per_slot' && $slotModuleOn)
                <div class="md:col-span-2">
                    <div class="border border-violet-100 bg-violet-50 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-clock text-violet-500"></i>
                            <h4 class="font-bold text-slate-700">{{ $booking->is_whole_hotel ? 'Whole Hotel / Villa — Slot Booking' : 'Slot Booking' }}</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            @if($booking->is_whole_hotel)
                            {{-- Whole-hotel per_slot: check-in date + check-out date + arrival slot --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Check-In Date *</label>
                                <input type="date" name="check_in_date" value="{{ old('check_in_date', $booking->check_in_date->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Check-Out Date *</label>
                                <input type="date" name="check_out_date" value="{{ old('check_out_date', $booking->check_out_date->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Arrival Time Slot <span class="text-xs font-normal text-slate-400">(slot on check-in day)</span></label>
                                <select name="time_slot_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                                    <option value="">No specific slot (full-day check-in)</option>
                                    @foreach($timeSlots as $slot)
                                    <option value="{{ $slot->id }}" {{ old('time_slot_id', $booking->time_slot_id) == $slot->id ? 'selected' : '' }}>
                                        {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }}) — ₹{{ number_format($slot->base_price) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            {{-- Individual room per_slot: booking date + time slot --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Booking Date *</label>
                                <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Time Slot *</label>
                                <select name="time_slot_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                                    <option value="">Select a time slot...</option>
                                    @foreach($timeSlots as $slot)
                                    <option value="{{ $slot->id }}" {{ old('time_slot_id', $booking->time_slot_id) == $slot->id ? 'selected' : '' }}>
                                        {{ $slot->name }} ({{ $slot->start_time }}–{{ $slot->end_time }}) — ₹{{ number_format($slot->base_price) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Per Hour: booking date + start time + hours --}}
                @if($pricingType === 'per_hour' && $hourlyModuleOn)
                <div class="md:col-span-2">
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-hourglass-half text-amber-500"></i>
                            <h4 class="font-bold text-slate-700">Hourly Booking
                                @if($booking->room)
                                <span class="text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5 font-semibold ml-1">₹{{ number_format($booking->room?->hourly_rate ?? 0) }}/hr</span>
                                @endif
                            </h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Booking Date *</label>
                                <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Time *</label>
                                <input type="time" name="slot_start_time" value="{{ old('slot_start_time', $booking->slot_start_time) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                        </div>
                        <p class="text-xs text-amber-600 mt-2"><i class="fas fa-clock mr-1"></i>Billing calculated at check-out using actual hours stayed.</p>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Adults *</label>
                    <input type="number" name="adults" value="{{ old('adults',$booking->adults) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" min="1" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Children</label>
                    <input type="number" name="children" value="{{ old('children',$booking->children) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" min="0">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Status</label>
                    <select name="status" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        @foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $s)
                        <option value="{{ $s }}" {{ old('status',$booking->status)===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Total Amount (₹)
                        @if($booking->price_overridden)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 border border-amber-200">
                            <i class="fas fa-pen text-amber-500 mr-1" style="font-size:9px;"></i>Custom price
                        </span>
                        @endif
                    </label>
                    @if($pricingType === 'per_hour')
                    <input type="number" name="custom_total" value="{{ old('custom_total', $booking->price_overridden ? $booking->total_amount : '') }}"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                           min="0" step="0.01" placeholder="Leave blank to calculate at check-out">
                    <p class="text-xs text-amber-600 mt-1"><i class="fas fa-info-circle mr-1"></i>Set a fixed total to override hourly billing, or leave blank to calculate at check-out.</p>
                    @else
                    <input type="number" name="custom_total" value="{{ old('custom_total', $booking->price_overridden ? $booking->total_amount : '') }}"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                           min="0" step="0.01" placeholder="{{ $booking->price_overridden ? '' : 'Leave blank to use room rate' }}">
                    <p class="text-xs text-gray-400 mt-1">
                        @if($booking->price_overridden)
                            <i class="fas fa-pen text-amber-500 mr-1"></i>Custom price active — edit to change, clear to revert to room rate.
                        @else
                            Edit to override the room rate (₹{{ number_format($booking->room?->price_per_night ?? 0) }}/night). Leave blank to use standard rate.
                        @endif
                    </p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Advance Payment (₹)</label>
                    <input type="number" name="advance_payment" value="{{ old('advance_payment',$booking->advance_payment) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" min="0" step="0.01">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Special Requests</label>
                    <textarea name="special_requests" rows="2" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">{{ old('special_requests',$booking->special_requests) }}</textarea>
                </div>

                @if($pricingType === 'per_night')
                @if($booking->room && $booking->room->has_extra_bed)
                <div class="md:col-span-2">
                    <div class="border border-blue-100 bg-blue-50 rounded-2xl p-5">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed text-blue-500"></i>
                                <h4 class="font-bold text-slate-700">Extra Beds</h4>
                                <span class="text-xs text-gray-400">— ₹{{ number_format($booking->room->extra_bed_price) }}/bed/night</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-gray-600">Number of extra beds:</label>
                                <input type="number" name="extra_beds"
                                    value="{{ old('extra_beds', $booking->extra_beds) }}" min="0" max="10"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 w-20 text-center">
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if($booking->room && $booking->room->hasMeals())
                <div class="md:col-span-2">
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-utensils text-amber-500"></i>
                            <h4 class="font-bold text-slate-700">Meal Plan</h4>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            @if($booking->room->has_breakfast)
                            <label class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_breakfast" value="1"
                                    class="w-4 h-4 rounded text-amber-500"
                                    {{ old('meal_breakfast', $booking->meal_breakfast) ? 'checked' : '' }}>
                                <span class="font-semibold text-slate-700"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
                                <span class="text-sm text-amber-600 font-bold">₹{{ number_format($booking->room->breakfast_price) }}/night</span>
                            </label>
                            @endif
                            @if($booking->room->has_lunch)
                            <label class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_lunch" value="1"
                                    class="w-4 h-4 rounded text-orange-500"
                                    {{ old('meal_lunch', $booking->meal_lunch) ? 'checked' : '' }}>
                                <span class="font-semibold text-slate-700"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
                                <span class="text-sm text-orange-600 font-bold">₹{{ number_format($booking->room->lunch_price) }}/night</span>
                            </label>
                            @endif
                            @if($booking->room->has_dinner)
                            <label class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_dinner" value="1"
                                    class="w-4 h-4 rounded text-indigo-500"
                                    {{ old('meal_dinner', $booking->meal_dinner) ? 'checked' : '' }}>
                                <span class="font-semibold text-slate-700"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
                                <span class="text-sm text-indigo-600 font-bold">₹{{ number_format($booking->room->dinner_price) }}/night</span>
                            </label>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
                @endif
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-2.5 rounded-xl font-semibold text-sm hover:from-amber-600 hover:to-orange-600 transition"><i class="fas fa-save mr-2"></i>Update Booking</button>
                <a href="{{ route('bookings.show',$booking->id) }}" class="px-6 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-semibold text-sm hover:bg-slate-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
