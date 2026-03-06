@extends('layouts.admin')
@section('title','Check-Out')
@section('page-title','Check-Out Management')
@section('page-subtitle','Process guest departures')
@section('content')
<div class="space-y-5">
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
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex gap-3 items-center">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Guest name, phone, booking #, room..." class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            </div>
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl font-medium text-sm transition-all"><i class="fas fa-search mr-1"></i>Search</button>
            @if(request('search'))
            <a href="{{ route('checkout.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            @endif
        </form>
    </div>
    @if($pendingCheckouts->total() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($pendingCheckouts as $booking)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 card-hover">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">{{ substr($booking->customer->name,0,1) }}</div>
                    <div>
                        <div class="font-bold text-gray-800">{{ $booking->customer->name }}</div>
                        <div class="text-xs text-gray-400">Checked in {{ $booking->actual_checkin_at ? $booking->actual_checkin_at->format('d M') : $booking->check_in_date->format('d M') }}</div>
                    </div>
                </div>
                <span class="badge-green">Checked In</span>
            </div>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Room</span><span class="font-semibold">{{ $booking->room->room_number }} • {{ ucfirst($booking->room->type) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Checkout Due</span><span class="font-semibold {{ $booking->check_out_date->isPast() ? 'text-red-500' : '' }}">{{ $booking->check_out_date->format('d M Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Balance Due</span><span class="font-bold {{ $booking->balance_due > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($booking->balance_due) }}</span></div>
            </div>
            <a href="{{ route('checkout.show', $booking->id) }}" class="w-full text-center block bg-gradient-to-r from-amber-500 to-orange-600 text-white py-2.5 rounded-xl font-medium text-sm hover:from-amber-600 hover:to-orange-700 transition-all">
                <i class="fas fa-sign-out-alt mr-2"></i>Process Check-Out
            </a>
        </div>
        @endforeach
    </div>
    <div class="mt-2">{{ $pendingCheckouts->appends(request()->query())->links() }}</div>
    @else
    <div class="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
        <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-double text-amber-500 text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">No Pending Check-Outs</h3>
        <p class="text-gray-400">{{ request('search') ? 'No results found for your search.' : 'All guests have been checked out.' }}</p>
    </div>
    @endif
</div>
@endsection
