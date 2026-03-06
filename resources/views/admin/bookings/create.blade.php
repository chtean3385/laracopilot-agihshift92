@extends('layouts.admin')
@section('title', 'New Booking')
@section('page-title', 'Create New Booking')
@section('page-subtitle', 'Reserve a room for a guest')

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('bookings.index') }}" class="btn-secondary text-sm mb-5 inline-flex"><i class="fas fa-arrow-left mr-2"></i>Back to Bookings</a>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-plus text-cyan-500 mr-2"></i>Booking Details</h3>
        </div>
        <form action="{{ route('bookings.store') }}" method="POST" class="p-6" id="bookingForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Guest <span class="text-red-500">*</span></label>
                    <select name="customer_id" class="form-input @error('customer_id') border-red-400 @enderror" required>
                        <option value="">Select Guest</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>{{ $customer->name }} - {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Room <span class="text-red-500">*</span></label>
                    <select name="room_id" class="form-input @error('room_id') border-red-400 @enderror" required id="roomSelect">
                        <option value="">Select Available Room</option>
                        @foreach($rooms as $room)
                        <option value="{{ $room->id }}" data-price="{{ $room->price_per_night }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            Room {{ $room->room_number }} - {{ ucfirst($room->type) }} - ₹{{ number_format($room->price_per_night) }}/night
                        </option>
                        @endforeach
                    </select>
                    @error('room_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Check-In Date <span class="text-red-500">*</span></label>
                    <input type="date" name="check_in_date" id="checkIn" value="{{ old('check_in_date') }}" min="{{ date('Y-m-d') }}" class="form-input" required onchange="calculateTotal()">
                    @error('check_in_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Check-Out Date <span class="text-red-500">*</span></label>
                    <input type="date" name="check_out_date" id="checkOut" value="{{ old('check_out_date') }}" class="form-input" required onchange="calculateTotal()">
                    @error('check_out_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Adults <span class="text-red-500">*</span></label>
                    <input type="number" name="adults" value="{{ old('adults', 1) }}" min="1" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Children</label>
                    <input type="number" name="children" value="{{ old('children', 0) }}" min="0" class="form-input">
                </div>

                <!-- Price Summary -->
                <div class="md:col-span-2 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-2xl p-5 border border-cyan-100">
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
                            <div id="totalDisplay" class="text-2xl font-bold text-cyan-600">—</div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Advance Payment (₹)</label>
                    <input type="number" name="advance_payment" id="advancePayment" value="{{ old('advance_payment', 0) }}" step="0.01" min="0" class="form-input" onchange="calculateBalance()">
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
@push('scripts')
<script>
    function calculateTotal() {
        const checkin = document.getElementById('checkIn').value;
        const checkout = document.getElementById('checkOut').value;
        const roomSelect = document.getElementById('roomSelect');
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = selectedOption ? parseFloat(selectedOption.dataset.price) || 0 : 0;
        if (checkin && checkout) {
            const d1 = new Date(checkin);
            const d2 = new Date(checkout);
            const nights = Math.max(0, Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)));
            const total = nights * pricePerNight;
            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('rateDisplay').textContent = pricePerNight ? '₹' + pricePerNight.toLocaleString('en-IN') : '—';
            document.getElementById('totalDisplay').textContent = total ? '₹' + total.toLocaleString('en-IN') : '—';
        }
    }
    document.getElementById('roomSelect').addEventListener('change', calculateTotal);
</script>
@endpush
@endsection
