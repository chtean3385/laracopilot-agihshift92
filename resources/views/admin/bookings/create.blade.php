@extends('layouts.admin')
@section('title', 'New Booking')
@section('page-title', 'Create New Booking')
@section('page-subtitle', 'Reserve a room for a guest')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" rel="stylesheet">
<style>
    .ts-wrapper { position: relative; }
    .ts-control {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0 40px 0 14px;
        min-height: 44px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        box-shadow: none;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #06b6d4;
        box-shadow: 0 0 0 3px rgba(6,182,212,.12);
        outline: none;
    }
    .ts-control input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        flex: 1;
        font-size: 14px;
        color: #374151;
        padding: 0 !important;
        margin: 0 !important;
        min-width: 60px;
        height: auto;
    }
    .ts-control .item {
        font-size: 14px;
        color: #374151;
        line-height: 1;
    }
    .ts-control::after {
        content: '';
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #9ca3af;
        pointer-events: none;
    }
    .ts-wrapper.open .ts-control::after { border-top: none; border-bottom: 5px solid #06b6d4; }
    .ts-wrapper.open .ts-control { border-color: #06b6d4; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    .ts-dropdown {
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        z-index: 9999;
        background: #fff;
        border: 1.5px solid #06b6d4;
        border-top: none;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1);
        overflow: hidden;
    }
    .ts-dropdown .ts-dropdown-content { max-height: 220px; overflow-y: auto; }
    .ts-dropdown-content::-webkit-scrollbar { width: 5px; }
    .ts-dropdown-content::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 13.5px;
        color: #374151;
        cursor: pointer;
        border-bottom: 1px solid #f9fafb;
        transition: background .1s;
    }
    .ts-dropdown .option:last-child { border-bottom: none; }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active { background: #f0fdfe; color: #0891b2; }
    .ts-dropdown .option.selected { background: #cffafe; color: #0e7490; font-weight: 500; }
    .ts-dropdown .no-results { padding: 12px 14px; font-size: 13px; color: #9ca3af; text-align: center; }
    .ts-dropdown .ts-dropdown-content .create { padding: 10px 14px; font-size: 13px; color: #06b6d4; }
</style>
@endpush

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
                    <select name="customer_id" id="guestSelect" class="@error('customer_id') border-red-400 @enderror" required>
                        <option value="">Search guest by name or phone...</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>{{ $customer->name }} — {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Room <span class="text-red-500">*</span></label>
                    <select name="room_id" id="roomSelect" class="@error('room_id') border-red-400 @enderror" required>
                        <option value="">Search room by number or type...</option>
                        @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                            data-price="{{ $room->price_per_night }}"
                            data-has-breakfast="{{ $room->has_breakfast ? '1' : '0' }}"
                            data-breakfast-price="{{ $room->breakfast_price ?? 0 }}"
                            data-has-lunch="{{ $room->has_lunch ? '1' : '0' }}"
                            data-lunch-price="{{ $room->lunch_price ?? 0 }}"
                            data-has-dinner="{{ $room->has_dinner ? '1' : '0' }}"
                            data-dinner-price="{{ $room->dinner_price ?? 0 }}"
                            {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            Room {{ $room->room_number }} — {{ ucfirst($room->type) }} — ₹{{ number_format($room->price_per_night) }}/night
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

                <!-- Meal Plan Section -->
                <div class="md:col-span-2" id="mealPlanSection" style="display:none">
                    <div class="border border-amber-100 bg-amber-50 rounded-2xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-utensils text-amber-500"></i>
                            <h4 class="font-bold text-gray-700">Meal Plan</h4>
                            <span class="text-xs text-gray-400 ml-1">— prices are per night</span>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label id="meal_breakfast_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_breakfast" value="1" id="meal_breakfast"
                                    class="w-4 h-4 rounded text-amber-500" onchange="calculateTotal()"
                                    {{ old('meal_breakfast') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-coffee text-amber-400 mr-1"></i>Breakfast</span>
                                <span id="meal_breakfast_price" class="text-sm text-amber-600 font-bold"></span>
                            </label>
                            <label id="meal_lunch_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_lunch" value="1" id="meal_lunch"
                                    class="w-4 h-4 rounded text-orange-500" onchange="calculateTotal()"
                                    {{ old('meal_lunch') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-sun text-orange-400 mr-1"></i>Lunch</span>
                                <span id="meal_lunch_price" class="text-sm text-orange-600 font-bold"></span>
                            </label>
                            <label id="meal_dinner_row" class="hidden items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="meal_dinner" value="1" id="meal_dinner"
                                    class="w-4 h-4 rounded text-indigo-500" onchange="calculateTotal()"
                                    {{ old('meal_dinner') ? 'checked' : '' }}>
                                <span class="font-semibold text-gray-700"><i class="fas fa-moon text-indigo-400 mr-1"></i>Dinner</span>
                                <span id="meal_dinner_price" class="text-sm text-indigo-600 font-bold"></span>
                            </label>
                        </div>
                    </div>
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
                            <div id="mealCostLine" class="text-xs text-amber-600 font-semibold mt-1"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Advance Payment (₹)</label>
                    <input type="number" name="advance_payment" id="advancePayment" value="{{ old('advance_payment', 0) }}" step="0.01" min="0" class="form-input">
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
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    new TomSelect('#guestSelect', {
        allowEmptyOption: false,
        placeholder: 'Search guest by name or phone...',
        maxOptions: 300,
    });

    const roomTS = new TomSelect('#roomSelect', {
        allowEmptyOption: false,
        placeholder: 'Search room by number or type...',
        maxOptions: 100,
        onChange: function() { updateMealOptions(); calculateTotal(); }
    });

    function updateMealOptions() {
        const roomEl = document.getElementById('roomSelect');
        const opt = roomEl.options[roomEl.selectedIndex];
        const meals = ['breakfast', 'lunch', 'dinner'];
        let hasMeals = false;
        meals.forEach(function(m) {
            const key = 'has' + m.charAt(0).toUpperCase() + m.slice(1);
            const has = opt && opt.dataset[key] === '1';
            const price = opt ? parseFloat(opt.dataset[m + 'Price'] || 0) : 0;
            const row = document.getElementById('meal_' + m + '_row');
            const priceSpan = document.getElementById('meal_' + m + '_price');
            if (row) {
                if (has) {
                    row.classList.remove('hidden');
                    row.classList.add('flex');
                    if (priceSpan) priceSpan.textContent = '₹' + price.toLocaleString('en-IN') + '/night';
                    hasMeals = true;
                } else {
                    row.classList.add('hidden');
                    row.classList.remove('flex');
                    const cb = document.getElementById('meal_' + m);
                    if (cb) cb.checked = false;
                }
            }
        });
        const section = document.getElementById('mealPlanSection');
        if (section) section.style.display = hasMeals ? '' : 'none';
    }

    function calculateTotal() {
        const checkin = document.getElementById('checkIn').value;
        const checkout = document.getElementById('checkOut').value;
        const roomEl = document.getElementById('roomSelect');
        const opt = roomEl.options[roomEl.selectedIndex];
        const pricePerNight = opt ? parseFloat(opt.dataset.price) || 0 : 0;
        if (checkin && checkout) {
            const d1 = new Date(checkin);
            const d2 = new Date(checkout);
            const nights = Math.max(0, Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)));
            let mealCost = 0;
            ['breakfast', 'lunch', 'dinner'].forEach(function(m) {
                const cb = document.getElementById('meal_' + m);
                const price = opt ? parseFloat(opt.dataset[m + 'Price'] || 0) : 0;
                if (cb && cb.checked) mealCost += nights * price;
            });
            const total = nights * pricePerNight + mealCost;
            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('rateDisplay').textContent = pricePerNight ? '₹' + pricePerNight.toLocaleString('en-IN') : '—';
            document.getElementById('totalDisplay').textContent = total ? '₹' + total.toLocaleString('en-IN') : '—';
            const mealLine = document.getElementById('mealCostLine');
            if (mealLine) mealLine.textContent = mealCost > 0 ? '(incl. ₹' + mealCost.toLocaleString('en-IN') + ' meals)' : '';
        }
    }

    document.getElementById('checkIn').addEventListener('change', calculateTotal);
    document.getElementById('checkOut').addEventListener('change', calculateTotal);
</script>
@endpush
@endsection
