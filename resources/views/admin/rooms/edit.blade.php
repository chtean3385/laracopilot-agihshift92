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
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('rooms.show', $room->id) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Update Room</button>
            </div>
        </form>
    </div>
</div>
@endsection
