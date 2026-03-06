@extends('layouts.admin')
@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('page-subtitle', 'View and manage all reservations')

@section('content')
<div class="space-y-5">
    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Guest name, booking #" class="form-input">
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                    <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
            <a href="{{ route('bookings.index') }}" class="btn-secondary"><i class="fas fa-times mr-1"></i>Clear</a>
        </form>
    </div>

    <div class="flex justify-end">
        <a href="{{ route('bookings.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i>New Booking</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
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
                        <td class="px-6 py-4"><span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                        <td class="px-6 py-4"><span class="badge-{{ $booking->payment_status_color }}">{{ ucfirst($booking->payment_status) }}</span></td>
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
                                @canDo('bookings.edit')
                                <a href="{{ route('bookings.edit', $booking->id) }}" class="w-8 h-8 flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @endCanDo
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-16 text-center text-gray-400">
                        <i class="fas fa-calendar-times text-4xl mb-3"></i>
                        <p>No bookings found</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $bookings->links() }}</div>
    </div>
</div>
@endsection
