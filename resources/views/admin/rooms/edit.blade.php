@extends('layouts.admin')
@section('title', 'Edit Room')
@section('page-title', 'Edit Room ' . $room->room_number)
@section('page-subtitle', 'Update room information and status')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('rooms.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to Rooms</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800"><i class="fas fa-edit text-amber-500 mr-2"></i>Edit Room {{ $room->room_number }}</h3>
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
                        @foreach(['standard', 'deluxe', 'suite', 'villa', 'penthouse'] as $type)
                        <option value="{{ $type }}" {{ old('type', $room->type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" class="form-input">
                </div>
                <div>
                    <label class="form-label">Price per Night (₹)</label>
                    <input type="number" name="price_per_night" value="{{ old('price_per_night', $room->price_per_night) }}" step="0.01" class="form-input">
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
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-utensils text-amber-500"></i>
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
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('breakfast')"
                                        {{ $bfOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
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
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('lunch')"
                                        {{ $luOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
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
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('dinner')"
                                        {{ $diOld ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
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
                    <div class="border border-blue-100 bg-blue-50 rounded-2xl p-5">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed text-blue-500"></i>
                                <h4 class="font-bold text-gray-700">Extra Bed</h4>
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="has_extra_bed" value="1" id="cb_extra_bed"
                                    class="w-4 h-4 rounded text-blue-500" onchange="toggleExtraBed()"
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
