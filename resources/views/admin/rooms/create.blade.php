@extends('layouts.admin')
@section('title', 'Add Room')
@section('page-title', 'Add New Room')
@section('page-subtitle', 'Create a new room listing')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('rooms.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to Rooms</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800"><i class="fas fa-door-open text-cyan-500 mr-2"></i>Room Details</h3>
        </div>
        <form action="{{ route('rooms.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Room Number <span class="text-red-500">*</span></label>
                    <input type="text" name="room_number" value="{{ old('room_number') }}" class="form-input @error('room_number') border-red-400 @enderror" placeholder="e.g. 101, V01, PH01" required>
                    @error('room_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Room Type <span class="text-red-500">*</span></label>
                    <select name="type" class="form-input" required>
                        <option value="">Select type</option>
                        <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="deluxe" {{ old('type') == 'deluxe' ? 'selected' : '' }}>Deluxe</option>
                        <option value="suite" {{ old('type') == 'suite' ? 'selected' : '' }}>Suite</option>
                        <option value="villa" {{ old('type') == 'villa' ? 'selected' : '' }}>Villa</option>
                        <option value="penthouse" {{ old('type') == 'penthouse' ? 'selected' : '' }}>Penthouse</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Capacity (Guests) <span class="text-red-500">*</span></label>
                    <input type="number" name="capacity" value="{{ old('capacity', 2) }}" min="1" max="20" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Price per Night (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="price_per_night" value="{{ old('price_per_night') }}" step="0.01" class="form-input" placeholder="5000" required>
                </div>
                <div>
                    <label class="form-label">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor') }}" min="0" class="form-input" placeholder="1">
                </div>
                <div>
                    <label class="form-label">View</label>
                    <input type="text" name="view" value="{{ old('view') }}" class="form-input" placeholder="e.g. Sea View, Garden View">
                </div>
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="occupied" {{ old('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Amenities</label>
                    <input type="text" name="amenities" value="{{ old('amenities') }}" class="form-input" placeholder="e.g. AC, Smart TV, WiFi, Jacuzzi, Mini Bar">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input" placeholder="Describe the room...">{{ old('description') }}</textarea>
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
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_breakfast" value="1" id="cb_breakfast"
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('breakfast')"
                                        {{ old('has_breakfast') ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
                                </label>
                                <div id="bf_price_wrap" class="{{ old('has_breakfast') ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="breakfast_price" id="breakfast_price"
                                        value="{{ old('breakfast_price') }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 300">
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_lunch" value="1" id="cb_lunch"
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('lunch')"
                                        {{ old('has_lunch') ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
                                </label>
                                <div id="lunch_price_wrap" class="{{ old('has_lunch') ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="lunch_price" id="lunch_price"
                                        value="{{ old('lunch_price') }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 500">
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-4">
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" name="has_dinner" value="1" id="cb_dinner"
                                        class="w-4 h-4 rounded text-amber-500" onchange="togglePrice('dinner')"
                                        {{ old('has_dinner') ? 'checked' : '' }}>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
                                </label>
                                <div id="dinner_price_wrap" class="{{ old('has_dinner') ? '' : 'opacity-40 pointer-events-none' }}">
                                    <label class="text-xs text-gray-500 mb-1 block">Price per night (₹)</label>
                                    <input type="number" name="dinner_price" id="dinner_price"
                                        value="{{ old('dinner_price') }}" min="0" step="0.01"
                                        class="form-input text-sm" placeholder="e.g. 600">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('rooms.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Save Room</button>
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
</script>
@endsection
