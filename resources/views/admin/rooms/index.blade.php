@extends('layouts.admin')
@section('title', 'Rooms')
@section('page-title', 'Room Management')
@section('page-subtitle', 'Manage all resort rooms and their status')

@section('content')
<div class="space-y-5">
    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $stats['available'] }}</div>
                <div class="text-sm text-gray-500">Available</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-bed text-red-500 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $stats['occupied'] }}</div>
                <div class="text-sm text-gray-500">Occupied</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-tools text-amber-500 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $stats['maintenance'] }}</div>
                <div class="text-sm text-gray-500">Maintenance</div>
            </div>
        </div>
    </div>

    <!-- Filter + Actions -->
    <div class="flex flex-wrap gap-3 items-center justify-between">
        <form method="GET" class="flex flex-wrap gap-3 items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Room #, type, view, amenities..." class="border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none w-64">
            </div>
            <select name="status" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Status</option>
                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
            <select name="type" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Types</option>
                <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                <option value="deluxe" {{ request('type') == 'deluxe' ? 'selected' : '' }}>Deluxe</option>
                <option value="suite" {{ request('type') == 'suite' ? 'selected' : '' }}>Suite</option>
                <option value="villa" {{ request('type') == 'villa' ? 'selected' : '' }}>Villa</option>
                <option value="penthouse" {{ request('type') == 'penthouse' ? 'selected' : '' }}>Penthouse</option>
            </select>
            <button type="submit" class="btn-primary text-sm"><i class="fas fa-search mr-1"></i>Search</button>
            @if(request()->anyFilled(['search','status','type']))
            <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            @endif
        </form>
        @canDo('rooms.create')
        <a href="{{ route('rooms.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i>Add New Room</a>
        @endCanDo
    </div>

    <!-- Rooms Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($rooms as $room)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden card-hover">
            <div class="h-3 bg-gradient-to-r
                @if($room->status == 'available') from-emerald-400 to-teal-500
                @elseif($room->status == 'occupied') from-red-400 to-rose-500
                @else from-amber-400 to-yellow-500 @endif"></div>
            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">{{ $room->room_number }}</h3>
                        <span class="badge-{{ $room->type_color }} text-xs mt-1">{{ ucfirst($room->type) }}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        @if($room->status == 'available') bg-emerald-100 text-emerald-700
                        @elseif($room->status == 'occupied') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700 @endif">
                        {{ ucfirst($room->status) }}
                    </span>
                </div>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="fas fa-user text-cyan-400 w-4"></i>
                        {{ $room->capacity }} Guest(s)
                        @if($room->floor) • Floor {{ $room->floor }} @endif
                    </div>
                    @if($room->view)
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="fas fa-binoculars text-cyan-400 w-4"></i>
                        {{ $room->view }}
                    </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <i class="fas fa-rupee-sign text-emerald-400 w-4"></i>
                        <span class="text-lg font-bold text-gray-800">₹{{ number_format($room->price_per_night) }}</span>
                        <span class="text-xs text-gray-400">/night</span>
                    </div>
                </div>
                @if($room->amenities)
                <p class="text-xs text-gray-400 mb-4 line-clamp-2"><i class="fas fa-star text-amber-400 mr-1"></i>{{ $room->amenities }}</p>
                @endif
                <div class="flex gap-2">
                    <a href="{{ route('rooms.show', $room->id) }}" class="flex-1 text-center bg-cyan-50 hover:bg-cyan-100 text-cyan-700 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    @canDo('rooms.edit')
                    <a href="{{ route('rooms.edit', $room->id) }}" class="flex-1 text-center bg-amber-50 hover:bg-amber-100 text-amber-700 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    @endCanDo
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-4 text-center py-16 text-gray-400">
            <i class="fas fa-door-closed text-5xl mb-4"></i>
            <p class="font-semibold">No rooms found</p>
        </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $rooms->links() }}</div>
</div>
@endsection
