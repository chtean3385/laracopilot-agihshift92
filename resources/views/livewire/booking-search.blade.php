<div class="space-y-5">
    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="form-label">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Guest name, booking #"
                        class="form-input pl-9"
                    >
                    <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select wire:model.live="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="checked_in">Checked In</option>
                    <option value="checked_out">Checked Out</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="form-label">From</label>
                <input type="date" wire:model.live="dateFrom" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" wire:model.live="dateTo" class="form-input">
            </div>
            @if($search || $status || $dateFrom || $dateTo)
            <button wire:click="clearFilters" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Clear
            </button>
            @endif
        </div>
        @if($search || $status || $dateFrom || $dateTo)
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
            <i class="fas fa-info-circle text-cyan-500"></i>
            Showing <span class="font-semibold text-cyan-600">{{ $bookings->total() }}</span> result{{ $bookings->total() != 1 ? 's' : '' }}
            — filters active
            <span wire:loading.delay class="text-cyan-500 font-medium ml-1">
                <i class="fas fa-circle-notch fa-spin"></i> updating…
            </span>
        </div>
        @endif
    </div>

    <div class="flex justify-between items-center">
        <p class="text-sm text-gray-500">
            <span wire:loading.remove>{{ $bookings->total() }} booking{{ $bookings->total() != 1 ? 's' : '' }} total</span>
            <span wire:loading class="text-cyan-500"><i class="fas fa-circle-notch fa-spin"></i> Loading…</span>
        </p>
        @php $canCreate = \App\Services\PermissionService::check('bookings.create'); @endphp
        @if($canCreate)
        <a href="{{ route('bookings.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i>New Booking</a>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking #</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Check-In</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Check-Out</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Payment</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $booking)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-xs font-mono text-cyan-600 font-semibold">{{ $booking->booking_number }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-sm">{{ $booking->customer->name }}</div>
                            <div class="text-xs text-gray-400">{{ $booking->adults }}A {{ $booking->children > 0 ? '+ ' . $booking->children . 'C' : '' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-sm">Rm {{ $booking->room->room_number }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($booking->room->type) }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $booking->check_in_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $booking->check_out_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-700">₹{{ number_format($booking->total_amount) }}</div>
                            @if($booking->balance_due > 0)
                            <div class="text-xs text-red-500">Due: ₹{{ number_format($booking->balance_due) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="badge-{{ $booking->payment_status_color }}">{{ ucfirst($booking->payment_status) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="w-8 h-8 flex items-center justify-center bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" title="View">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if($booking->status == 'confirmed')
                                <a href="{{ route('checkin.show', $booking->id) }}" class="w-8 h-8 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg transition-all" title="Check In">
                                    <i class="fas fa-sign-in-alt text-xs"></i>
                                </a>
                                @endif
                                @if($booking->status == 'checked_in')
                                <a href="{{ route('checkout.show', $booking->id) }}" class="w-8 h-8 flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg transition-all" title="Check Out">
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                </a>
                                @endif
                                @if(\App\Services\PermissionService::check('bookings.edit'))
                                <a href="{{ route('bookings.edit', $booking->id) }}" class="w-8 h-8 flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center text-gray-400">
                            <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                            <p>No bookings found</p>
                            @if($search || $status || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" class="mt-3 text-sm text-cyan-600 hover:underline">
                                Clear filters
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
