@extends('layouts.admin')
@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-subtitle', 'Guest profile and booking history')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="btn-secondary text-sm"><i class="fas fa-arrow-left mr-2"></i>Back to Guests</a>
        <div class="flex gap-3">
            <a href="{{ route('documents.index', $customer->id) }}" class="btn-secondary text-sm"><i class="fas fa-file mr-2"></i>Documents ({{ $customer->documents->count() }})</a>
            <a href="{{ route('customers.edit', $customer->id) }}" class="btn-primary text-sm"><i class="fas fa-edit mr-2"></i>Edit Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-lg mx-auto mb-3">
                    {{ substr($customer->name, 0, 1) }}
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                <p class="text-gray-400 text-sm">{{ $customer->nationality }}</p>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-phone text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->phone }}</span>
                </div>
                @if($customer->email)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-envelope text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->email }}</span>
                </div>
                @endif
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-map-marker-alt text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->city }}, {{ $customer->state }}, {{ $customer->country }}</span>
                </div>
                @if($customer->date_of_birth)
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-birthday-cake text-cyan-500 w-4"></i>
                    <span class="text-sm text-gray-700">{{ $customer->date_of_birth->format('d M Y') }}</span>
                </div>
                @endif
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-id-card text-cyan-500 w-4"></i>
                    <div>
                        <div class="text-xs text-gray-400">{{ ucwords(str_replace('_', ' ', $customer->id_type)) }}</div>
                        <div class="text-sm text-gray-700 font-medium">{{ $customer->id_number }}</div>
                    </div>
                </div>
                @if($customer->address)
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                    <i class="fas fa-home text-cyan-500 w-4 mt-0.5"></i>
                    <span class="text-sm text-gray-700">{{ $customer->address }}</span>
                </div>
                @endif
            </div>
            @if($customer->notes)
            <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                <p class="text-xs font-semibold text-amber-700 mb-1">Notes</p>
                <p class="text-sm text-amber-600">{{ $customer->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Booking History -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-3xl font-bold text-cyan-600">{{ $customer->bookings->count() }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Stays</div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-3xl font-bold text-emerald-600">{{ $customer->bookings->sum('nights') }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Nights</div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
                    <div class="text-2xl font-bold text-violet-600">₹{{ number_format($customer->bookings->sum('total_amount')) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Spent</div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Booking History</h3>
                    <a href="{{ route('bookings.create') }}?customer_id={{ $customer->id }}" class="btn-primary text-xs"><i class="fas fa-plus mr-1"></i>New Booking</a>
                </div>
                @if($customer->bookings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking #</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Room</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Dates</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($customer->bookings as $booking)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm font-mono text-cyan-600">{{ $booking->booking_number }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">Room {{ $booking->room->room_number }}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $booking->check_in_date->format('d M Y') }} → {{ $booking->check_out_date->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700">₹{{ number_format($booking->total_amount) }}</td>
                                <td class="px-6 py-3"><span class="badge-{{ $booking->status_color }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                                <td class="px-6 py-3 text-right"><a href="{{ route('bookings.show', $booking->id) }}" class="text-cyan-600 hover:underline text-xs">View</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                    <p class="text-sm">No bookings yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
