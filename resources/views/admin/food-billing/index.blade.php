@extends('layouts.admin')
@section('page-title', 'Food Billing')
@section('page-subtitle', 'Add food & extra charges to checked-in guests')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-utensils text-amber-500 mr-2"></i>Food Billing</h2>
            <p class="text-sm text-gray-500 mt-0.5">Select a checked-in room to add food or extra charges</p>
        </div>
        <span class="text-sm text-gray-400">{{ $bookings->count() }} room{{ $bookings->count() != 1 ? 's' : '' }} checked in</span>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @if($bookings->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-8 py-16 text-center">
        <i class="fas fa-door-open text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 font-medium">No rooms are currently checked in.</p>
        <p class="text-sm text-gray-400 mt-1">Once guests check in, they will appear here for food billing.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($bookings as $booking)
        @php
            $extraTotal = $booking->extraCharges->sum('total_price');
            $chargeCount = $booking->extraCharges->count();
        @endphp
        <a href="{{ route('food-billing.show', $booking) }}"
           class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-200 transition-all p-5 flex flex-col gap-3 group">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        {{ $booking->room?->room_number ?? '?' }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-800 text-sm">{{ $booking->room?->room_number ?? 'Room' }}</div>
                        <div class="text-xs text-gray-400">{{ $booking->room?->room_type ?? '' }}</div>
                    </div>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Checked In</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                    {{ substr($booking->customer?->name ?? '?', 0, 1) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-700">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                    <div class="text-xs text-gray-400">{{ $booking->customer?->phone ?? '' }}</div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                @if($chargeCount > 0)
                <span class="text-xs text-amber-600 font-semibold">
                    <i class="fas fa-receipt mr-1"></i>{{ $chargeCount }} charge{{ $chargeCount != 1 ? 's' : '' }} — ₹{{ number_format($extraTotal) }}
                </span>
                @else
                <span class="text-xs text-gray-400">No charges yet</span>
                @endif
                <span class="text-xs text-amber-500 group-hover:text-amber-700 font-semibold">Add Bill <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
