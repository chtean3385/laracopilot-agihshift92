@extends('layouts.admin')
@section('title', 'Edit Room')
@section('page-title', 'Edit Room ' . $room->room_number)
@section('page-subtitle', 'Update room information and status')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('rooms.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to Rooms</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800"><i class="fas fa-edit mr-2" style="color: #c9a96e;"></i>Edit Room {{ $room->room_number }}</h3>
        </div>
        <form action="{{ route('rooms.update', $room->id) }}" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Room Number <span class="text-red-500">*</span></label>
                    <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Room Type <span class="text-red-500">*</span></label>
                    <select name="type" class="form-input" required>
                        @foreach(['standard'=>'Standard','deluxe'=>'Deluxe','non-ac'=>'Non-Ac','suite'=>'Suite','villa'=>'Villa','penthouse'=>'Penthouse','cottage'=>'Cottage','bhk'=>'BHK'] as $val=>$lbl)
                        <option value="{{ $val }}" {{ old('type', $room->type) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" max="50" class="form-input">
                </div>

                @if($slotModuleOn || $hourlyModuleOn)
                @php $curPricing = old('pricing_type', $room->pricing_type ?? 'per_night'); @endphp
                <div class="md:col-span-2">
                    <label class="form-label">Pricing Mode <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach(['per_night'=>['icon'=>'fa-moon','label'=>'Per Night','sub'=>'Standard nightly rate']] + ($slotModuleOn ? ['per_slot'=>['icon'=>'fa-clock','label'=>'Per Slot','sub'=>'Fixed time-block pricing']] : []) + ($hourlyModuleOn ? ['per_hour'=>['icon'=>'fa-hourglass-half','label'=>'Per Hour','sub'=>'Hourly rate pricing']] : []) as $val=>$info)
                        <label class="relative flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer transition-colors pricing-mode-card {{ $curPricing === $val ? '' : 'border-gray-200 hover:border-gray-300' }}"
                            style="{{ $curPricing === $val ? 'border-color: #c9a96e; background: rgba(201,169,110,.06);' : '' }}">
                            <input type="radio" name="pricing_type" value="{{ $val }}" class="hidden pricing-mode-radio" {{ $curPricing === $val ? 'checked' : '' }} onchange="onPricingModeChange(this)">
                            <i class="fas {{ $info['icon'] }} text-lg w-5 text-center" style="color: #c9a96e;"></i>
                            <div>
                                <div class="font-semibold text-gray-800 text-sm">{{ $info['label'] }}</div>
                                <div class="text-xs text-gray-400">{{ $info['sub'] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @else
                <input type="hidden" name="pricing_type" value="per_night">
                @endif

                <div id="pricePerNightWrap" class="{{ (old('pricing_type', $room->pricing_type ?? 'per_night') !== 'per_night' && ($slotModuleOn || $hourlyModuleOn)) ? 'hidden' : '' }}">
                    <label class="form-label">Price per Night (₹)</label>
                    <input type="number" id="pricePerNight" name="price_per_night" value="{{ old('price_per_night', $room->price_per_night) }}" step="0.01" class="form-input">
                </div>
                <div id="hourlyRateWrap" class="{{ (old('pricing_type', $room->pricing_type ?? 'per_night') !== 'per_hour' || !$hourlyModuleOn) ? 'hidden' : '' }}">
                    <label class="form-label">Hourly Rate (₹)</label>
                    <input type="number" id="hourlyRate" name="hourly_rate" value="{{ old('hourly_rate', $room->hourly_rate) }}" step="0.01" class="form-input" placeholder="200">
                </div>
                <div>
                    <label class="form-label">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor', $room->floor) }}" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">View</label>
                    <input type="text" name="view" value="{{ old('view', $room->view) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="available"   {{ old('status', $room->status) == 'available'   ? 'selected' : '' }}>Available</option>
                        <option value="occupied"    {{ old('status', $room->status) == 'occupied'    ? 'selected' : '' }}>Occupied</option>
                        <option value="maintenance" {{ old('status', $room->status) == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                        <option value="inactive"    {{ old('status', $room->status) == 'inactive'    ? 'selected' : '' }}>Inactive (Deactivated)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Amenities</label>
                    <input type="text" name="amenities" value="{{ old('amenities', $room->amenities) }}" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input">{{ old('description', $room->description) }}</textarea>
                </div>

                <!-- Meal Options -->
                <div class="md:col-span-2">
                    <div class="rounded-2xl p-5" style="border: 1px solid rgba(201,169,110,.15); background: rgba(201,169,110,.06);">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-utensils" style="color: #c9a96e;"></i>
                            <h4 class="font-bold text-gray-700">Meal Options</h4>
                            <span class="text-xs text-gray-400 ml-1">— tick meals included with this room. Price is per guest per night.</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @php
                                $bfOld  = old('has_breakfast',  $room->has_breakfast);
                                $luOld  = old('has_lunch',      $room->has_lunch);
                                $diOld  = old('has_dinner',     $room->has_dinner);
                            @endphp
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_breakfast" value="1" id="cb_breakfast"
                                        class="w-4 h-4 rounded" style="color: #c9a96e;" onchange="togglePrice('breakfast')"
                                        {{ $bfOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-coffee mr-1" style="color: #c9a96e;"></i>Breakfast</span>
                                </label>
                                <div id="bf_price_wrap" class="{{ $bfOld ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="breakfast_price" id="breakfast_price"
                                        value="{{ old('breakfast_price', $room->breakfast_price) }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 300">
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_lunch" value="1" id="cb_lunch"
                                        class="w-4 h-4 rounded" style="color: #c9a96e;" onchange="togglePrice('lunch')"
                                        {{ $luOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-sun mr-1" style="color: #c9a96e;"></i>Lunch</span>
                                </label>
                                <div id="lunch_price_wrap" class="{{ $luOld ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="lunch_price" id="lunch_price"
                                        value="{{ old('lunch_price', $room->lunch_price) }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 500">
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_dinner" value="1" id="cb_dinner"
                                        class="w-4 h-4 rounded" style="color: #c9a96e;" onchange="togglePrice('dinner')"
                                        {{ $diOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-moon mr-1" style="color: #c9a96e;"></i>Dinner</span>
                                </label>
                                <div id="dinner_price_wrap" class="{{ $diOld ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="dinner_price" id="dinner_price"
                                        value="{{ old('dinner_price', $room->dinner_price) }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 600">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Extra Bed -->
                <div class="md:col-span-2">
                    <div class="rounded-2xl p-5" style="border: 1px solid rgba(122,138,154,.2); background: rgba(122,138,154,.04);">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed" style="color: #7a8a9a;"></i>
                                <h4 class="font-bold text-gray-700">Extra Bed</h4>
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="has_extra_bed" value="1" id="cb_extra_bed"
                                    class="w-4 h-4 rounded" style="color: #7a8a9a;" onchange="toggleExtraBed()"
                                    {{ old('has_extra_bed', $room->has_extra_bed) ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700">Allow extra bed in this room</span>
                            </label>
                            @php $ebOld = old('has_extra_bed', $room->has_extra_bed); @endphp
                            <div id="extra_bed_price_wrap" class="flex items-center gap-3 {{ $ebOld ? '' : 'opacity-40 pointer-events-none' }}">
                                <label class="text-sm text-gray-500">Price per bed per night (₹)</label>
                                <input type="number" name="extra_bed_price" id="extra_bed_price"
                                    value="{{ old('extra_bed_price', $room->extra_bed_price) }}" min="0" step="0.01"
                                    class="form-input w-36 text-sm" placeholder="e.g. 1500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('rooms.show', $room->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Update Room</button>
            </div>
        </form>
    </div>
</div>
<script>
function onPricingModeChange(radio) {
    document.querySelectorAll('.pricing-mode-card').forEach(c => {
        c.style.borderColor = ''; c.style.background = '';
        c.classList.add('border-gray-200');
    });
    var card = radio.closest('.pricing-mode-card');
    card.style.borderColor = '#c9a96e'; card.style.background = 'rgba(201,169,110,.06)';
    card.classList.remove('border-gray-200');
    const mode = radio.value;
    const nightWrap  = document.getElementById('pricePerNightWrap');
    const hourlyWrap = document.getElementById('hourlyRateWrap');
    const priceInput = document.getElementById('pricePerNight');
    const hourInput  = document.getElementById('hourlyRate');
    if (nightWrap)  nightWrap.classList.toggle('hidden',  mode !== 'per_night');
    if (hourlyWrap) hourlyWrap.classList.toggle('hidden', mode !== 'per_hour');
    if (priceInput) priceInput.required = (mode === 'per_night');
    if (hourInput)  hourInput.required  = (mode === 'per_hour');
}
function togglePrice(meal) {
    const cb = document.getElementById('cb_' + meal);
    const wrap = document.getElementById((meal === 'breakfast' ? 'bf' : meal) + '_price_wrap');
    if (wrap) {
        wrap.classList.toggle('opacity-40', !cb.checked);
        wrap.classList.toggle('pointer-events-none', !cb.checked);
    }
}
function toggleExtraBed() {
    const cb = document.getElementById('cb_extra_bed');
    const wrap = document.getElementById('extra_bed_price_wrap');
    if (wrap) {
        wrap.classList.toggle('opacity-40', !cb.checked);
        wrap.classList.toggle('pointer-events-none', !cb.checked);
    }
}
</script>
@endsection
