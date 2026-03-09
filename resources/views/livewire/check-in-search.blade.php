<div class="space-y-5">
    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 rounded-2xl p-6 text-white">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-sign-in-alt text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold">Pending Check-Ins</h2>
                <p class="text-cyan-100">{{ $pendingCheckins->total() }} guest(s) awaiting check-in</p>
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
                    class="w-full border border-gray-200 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none"
                >
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24">
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

    @if($pendingCheckins->total() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" wire:loading.class="opacity-60">
        @foreach($pendingCheckins as $booking)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 card-hover">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">{{ substr($booking->customer->name, 0, 1) }}</div>
                    <div>
                        <div class="font-bold text-gray-800">{{ $booking->customer->name }}</div>
                        <div class="text-xs text-gray-400">{{ $booking->customer->phone }}</div>
                    </div>
                </div>
                <span class="badge-blue">{{ $booking->booking_number }}</span>
            </div>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500"><i class="fas fa-door-open mr-1"></i>Room</span>
                    <span class="font-semibold">{{ $booking->room->room_number }} • {{ ucfirst($booking->room->type) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500"><i class="fas fa-calendar mr-1"></i>Check-In</span>
                    <span class="font-semibold">{{ $booking->check_in_date->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500"><i class="fas fa-moon mr-1"></i>Nights</span>
                    <span class="font-semibold">{{ $booking->nights }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500"><i class="fas fa-rupee-sign mr-1"></i>Balance Due</span>
                    <span class="font-bold {{ $booking->balance_due > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($booking->balance_due) }}</span>
                </div>
            </div>
            <a href="{{ route('checkin.show', $booking->id) }}" class="btn-primary w-full text-center block text-sm">
                <i class="fas fa-sign-in-alt mr-2"></i>Process Check-In
            </a>
        </div>
        @endforeach
    </div>
    <div class="mt-2">{{ $pendingCheckins->links() }}</div>
    @else
    <div class="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-double text-emerald-500 text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">All Clear!</h3>
        <p class="text-gray-400">{{ $search ? 'No results found for your search.' : 'No pending check-ins at this time.' }}</p>
        @if(!$search)
        <a href="{{ route('bookings.create') }}" class="btn-primary mt-5 inline-flex"><i class="fas fa-plus mr-2"></i>New Booking</a>
        @endif
    </div>
    @endif
</div>
