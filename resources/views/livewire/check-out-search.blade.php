<div class="space-y-5">
    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-sign-out-alt text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold">Pending Check-Outs</h2>
                <p class="text-amber-100">{{ $pendingCheckouts->total() }} guest(s) awaiting check-out</p>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <div class="flex gap-3 items-center">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Guest name, phone, booking #, room..."
                    class="w-full border border-gray-200 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                >
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            @if($search)
            <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 underline whitespace-nowrap">Clear</button>
            @endif
        </div>
    </div>

    @if($pendingCheckouts->total() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" wire:loading.class="opacity-60">
        @foreach($pendingCheckouts as $booking)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 card-hover">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">{{ substr($booking->customer?->name ?? 'G', 0, 1) }}</div>
                    <div>
                        <div class="font-bold text-gray-800">{{ $booking->customer?->name ?? '(Deleted Guest)' }}</div>
                        <div class="text-xs text-gray-400">Checked in {{ $booking->actual_checkin_at ? $booking->actual_checkin_at->format('d M') : $booking->check_in_date->format('d M') }}</div>
                    </div>
                </div>
                <span class="badge-green">Checked In</span>
            </div>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Room</span>
                    <span class="font-semibold">{{ $booking->is_whole_hotel ? 'Whole Hotel / Villa' : ($booking->room?->room_number . ' • ' . ucfirst($booking->room?->type ?? '')) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Checkout Due</span>
                    <span class="font-semibold {{ $booking->check_out_date->isPast() ? 'text-red-500' : '' }}">{{ $booking->check_out_date->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Balance Due</span>
                    @php
                        $coRoomPT  = $booking->room?->pricing_type ?? ($booking->whole_hotel_pricing_type ?? 'per_night');
                        $coTaxRate = $taxRateMap[$booking->hotel_id] ?? 0;
                        if ($coRoomPT !== 'per_hour') {
                            $coExtraCharges = $booking->extraCharges->sum('total_price');
                            if ($booking->price_overridden || $booking->is_whole_hotel) {
                                $coBase = max(0, (float)$booking->total_amount - $coExtraCharges);
                            } else {
                                $coCheckin  = \Carbon\Carbon::parse($booking->actual_checkin_at ?? $booking->check_in_date)->startOfDay();
                                $coCheckout = \Carbon\Carbon::parse($booking->check_out_date)->startOfDay();
                                $coNights   = max(1, $coCheckin->diffInDays($coCheckout));
                                $coBase     = $coNights * ($booking->room?->price_per_night ?? 0)
                                              + (float)($booking->meal_cost ?? 0)
                                              + (float)($booking->extra_bed_cost ?? 0);
                            }
                            $coTrueBase   = $coBase + $coExtraCharges;
                            $coGst        = round($coTrueBase * ($coTaxRate / 100), 2);
                            $coGrandTotal = $coTrueBase + $coGst;
                            $coPaid       = $booking->payments->where('status', 'completed')->sum('amount');
                            $coBalance    = max(0, $coGrandTotal - $coPaid);
                        }
                    @endphp
                    @if($coRoomPT === 'per_hour')
                    <span class="font-bold text-violet-600"><i class="fas fa-clock mr-1 text-xs"></i>Billed at checkout</span>
                    @else
                    <span class="font-bold {{ $coBalance > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($coBalance) }}</span>
                    @endif
                </div>
                @if($coRoomPT === 'per_hour' && $booking->actual_checkin_at)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Checked In At</span>
                    <span class="font-semibold text-violet-600">{{ $booking->actual_checkin_at->format('h:i A') }}</span>
                </div>
                @endif
            </div>
            <a href="{{ route('checkout.show', $booking->id) }}" class="w-full text-center block bg-gradient-to-r from-amber-500 to-orange-600 text-white py-2.5 rounded-xl font-medium text-sm hover:from-amber-600 hover:to-orange-700 transition-all">
                <i class="fas fa-sign-out-alt mr-2"></i>Process Check-Out
            </a>
        </div>
        @endforeach
    </div>
    <div class="mt-2">{{ $pendingCheckouts->links() }}</div>
    @else
    <div class="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
        <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-double text-amber-500 text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">No Pending Check-Outs</h3>
        <p class="text-gray-400">{{ $search ? 'No results found for your search.' : 'All guests have been checked out.' }}</p>
    </div>
    @endif
</div>
