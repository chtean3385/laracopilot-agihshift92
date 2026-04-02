@extends('layouts.admin')
@section('title','Edit Booking')
@section('page-title','Edit Booking')
@section('page-subtitle','{{ $booking->booking_reference }}')

@section('content')
@php $pricingType = $booking->room?->pricing_type ?? 'per_night'; @endphp
<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form action="{{ route('bookings.update',$booking->id) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Guest *</label>
                    <select name="customer_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id',$booking->customer_id)==$c->id?'selected':'' }}>{{ $c->name }} — {{ $c->phone }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Room</label>
                    {{-- Room is locked on edit to keep pricing-type context stable --}}
                    <input type="hidden" name="room_id" value="{{ $booking->room_id }}">
                    <div class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm text-slate-600">
                        @if($booking->room)
                            Room {{ $booking->room->room_number }} — {{ ucfirst($booking->room->type) }}
                            @if($pricingType === 'per_night') — ₹{{ number_format($booking->room->price_per_night, 0) }}/night
                            @elseif($pricingType === 'per_hour') — ₹{{ number_format($booking->room->hourly_rate ?? 0, 0) }}/hr
                            @else — Slot-based @endif
                            <span class="ml-1 text-xs text-slate-400">(locked — to reassign room, cancel and create a new booking)</span>
                        @else
                            Room #{{ $booking->room_id }}
                        @endif
                    </div>
                </div>

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
                            <h4 class="font-bold text-slate-700">Slot Booking</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
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
                                <span class="text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5 font-semibold ml-1">₹{{ number_format($booking->room->hourly_rate ?? 0) }}/hr</span>
                                @endif
                            </h4>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Booking Date *</label>
                                <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Time *</label>
                                <input type="time" name="slot_start_time" value="{{ old('slot_start_time', $booking->slot_start_time) }}" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hours *</label>
                                <input type="number" name="hours_booked" value="{{ old('hours_booked', $booking->hours_booked ?? 1) }}" min="1" max="24" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                            </div>
                        </div>
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
