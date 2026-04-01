@extends('layouts.admin')
@section('title', 'Room ' . $room->room_number)
@section('page-title', 'Room ' . $room->room_number . ' Details')
@section('page-subtitle', ucfirst($room->type) . ' • ' . $room->view)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('rooms.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Rooms</a>
        <div class="flex gap-3">
            <a href="{{ route('bookings.create') }}" class="btn-primary text-sm"><i class="fas fa-plus mr-2"></i>New Booking</a>
            @canDo('rooms.edit')
            <a href="{{ route('rooms.edit', $room->id) }}" class="btn-secondary text-sm"><i class="fas fa-edit mr-2"></i>Edit Room</a>
            @endCanDo
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="h-2 -mt-6 -mx-6 mb-6 rounded-t-2xl bg-gradient-to-r
                @if($room->status == 'available') from-emerald-400 to-teal-500
                @elseif($room->status == 'occupied') from-red-400 to-rose-500
                @else from-amber-400 to-yellow-500 @endif"></div>
            <div class="text-center mb-6">
                <div class="text-5xl font-black text-gray-800">{{ $room->room_number }}</div>
                <span class="badge-{{ $room->type_color }} mt-2">{{ ucfirst($room->type) }}</span>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="font-semibold text-sm
                        @if($room->status == 'available') text-emerald-600
                        @elseif($room->status == 'occupied') text-red-600
                        @else text-amber-600 @endif">{{ ucfirst($room->status) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                    <span class="text-sm text-gray-500">Capacity</span>
                    <span class="font-semibold text-sm text-gray-700">{{ $room->capacity }} Guests</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                    <span class="text-sm text-gray-500">Floor</span>
                    <span class="font-semibold text-sm text-gray-700">{{ $room->floor ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                    <span class="text-sm text-gray-500">View</span>
                    <span class="font-semibold text-sm text-gray-700">{{ $room->view ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl">
                    <span class="text-sm text-gray-500">Price/Night</span>
                    <span class="font-bold text-emerald-600 text-lg">₹{{ number_format($room->price_per_night) }}</span>
                </div>
            </div>
            @if($room->amenities)
            <div class="mt-4 p-3 bg-cyan-50 rounded-xl">
                <p class="text-xs font-semibold text-cyan-700 mb-1">Amenities</p>
                <p class="text-sm text-cyan-600">{{ $room->amenities }}</p>
            </div>
            @endif
            @if($room->has_breakfast || $room->has_lunch || $room->has_dinner)
            <div class="mt-4 p-3 bg-amber-50 rounded-xl">
                <p class="text-xs font-semibold text-amber-700 mb-2"><i class="fas fa-utensils mr-1"></i>Meal Options</p>
                <div class="space-y-1">
                    @if($room->has_breakfast)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
                        <span class="font-semibold text-amber-700">₹{{ number_format($room->breakfast_price) }}/night</span>
                    </div>
                    @endif
                    @if($room->has_lunch)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
                        <span class="font-semibold text-orange-700">₹{{ number_format($room->lunch_price) }}/night</span>
                    </div>
                    @endif
                    @if($room->has_dinner)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
                        <span class="font-semibold text-indigo-700">₹{{ number_format($room->dinner_price) }}/night</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            @if($room->has_extra_bed)
            <div class="mt-4 p-3 bg-blue-50 rounded-xl">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600"><i class="fas fa-bed text-blue-400 mr-1"></i>Extra Bed</span>
                    <span class="font-semibold text-blue-700">₹{{ number_format($room->extra_bed_price) }}/bed/night</span>
                </div>
            </div>
            @endif
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800">Booking History ({{ $room->bookings->count() }})</h3>
            </div>
            @if($room->bookings->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Dates</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($room->bookings as $booking)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $booking->check_in_date->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-700">₹{{ number_format($booking->total_amount) }}</td>
                            <td class="px-6 py-3"><span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_',' ', $booking->status)) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12 text-gray-400"><i class="fas fa-calendar-times text-3xl mb-2"></i><p>No bookings for this room</p></div>
            @endif
        </div>
    </div>
</div>
@endsection
