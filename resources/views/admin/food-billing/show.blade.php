@extends('layouts.admin')
@section('page-title', 'Food Billing — Room ' . ($booking->room?->room_number ?? '?'))
@section('page-subtitle', $booking->customer?->name ?? '(Deleted Guest)')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    {{-- Back + Guest Info --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('food-billing.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-sm">
                {{ $booking->room?->room_number ?? '?' }}
            </div>
            <div>
                <h2 class="font-bold text-gray-800">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</h2>
                <div class="text-xs text-gray-400">Room {{ $booking->room?->room_number }} &bull; Check-in {{ $booking->check_in_date?->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Existing charges --}}
    @if($booking->extraCharges->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-4 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-700 text-sm"><i class="fas fa-receipt text-amber-500 mr-2"></i>Food &amp; Extra Bill</h3>
            <span class="text-xs font-bold text-amber-700 bg-amber-100 px-3 py-0.5 rounded-full">
                ₹{{ number_format($booking->extraCharges->sum('total_price')) }} total
            </span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($booking->extraCharges as $charge)
            <div class="px-5 py-3 flex items-center justify-between group">
                <div>
                    <div class="text-sm font-semibold text-gray-700">{{ $charge->name }}</div>
                    <div class="text-xs text-gray-400">{{ $categories[$charge->category] ?? $charge->category }}
                        @if($charge->quantity != 1) &bull; qty {{ $charge->quantity }}@endif
                        @if($charge->notes) &bull; {{ $charge->notes }}@endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-gray-800">₹{{ number_format($charge->total_price) }}</span>
                    <form method="POST" action="{{ route('bookings.extra_charges.destroy', [$booking, $charge]) }}"
                          onsubmit="return confirm('Remove ₹{{ number_format($charge->total_price) }} charge?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="opacity-0 group-hover:opacity-100 transition-opacity w-7 h-7 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-amber-50 border border-amber-100 rounded-2xl px-5 py-4 text-sm text-amber-700 mb-4">
        <i class="fas fa-info-circle mr-2"></i>No food or extra charges added yet for this room.
    </div>
    @endif

    {{-- Add charge form --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-50">
            <h3 class="font-bold text-gray-700 text-sm"><i class="fas fa-plus-circle text-amber-500 mr-2"></i>Add Charge</h3>
        </div>
        <form method="POST" action="{{ route('bookings.extra_charges.store', $booking) }}" class="px-5 py-4 space-y-4">
            @csrf
            <div>
                <label class="form-label">Item / Description <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Paneer Tikka, Room Service, Laundry"
                       class="form-input">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Category <span class="text-red-500">*</span></label>
                    <select name="category" class="form-input" required>
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', 'food') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="0.01" step="0.01"
                           class="form-input" placeholder="1">
                </div>
            </div>
            <div>
                <label class="form-label">Unit Price (₹) <span class="text-red-500">*</span></label>
                <input type="number" name="unit_price" value="{{ old('unit_price') }}" min="0" step="0.01" required
                       placeholder="0.00" class="form-input">
            </div>
            <div>
                <label class="form-label">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-input"
                       placeholder="Special instructions or notes">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-plus mr-2"></i>Add to Bill
                </button>
                <a href="{{ route('food-billing.index') }}" class="btn-secondary px-5">Back</a>
            </div>
        </form>
    </div>

</div>
@endsection
