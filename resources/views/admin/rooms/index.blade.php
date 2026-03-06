@extends('layouts.admin')
@section('title', 'Rooms')
@section('page-title', 'Room Management')
@section('page-subtitle', 'Manage all resort rooms and their status')

@section('content')
<div class="space-y-5">

    @if(session('error'))
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-4">
        <i class="fas fa-exclamation-circle mt-0.5 text-red-500"></i>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif
    @if(session('success'))
    <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl px-5 py-4">
        <i class="fas fa-check-circle mt-0.5 text-emerald-500"></i>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
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
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-ban text-gray-400 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $stats['inactive'] }}</div>
                <div class="text-sm text-gray-500">Inactive</div>
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
                <option value="available"   {{ request('status') == 'available'   ? 'selected' : '' }}>Available</option>
                <option value="occupied"    {{ request('status') == 'occupied'    ? 'selected' : '' }}>Occupied</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="inactive"    {{ request('status') == 'inactive'    ? 'selected' : '' }}>Inactive</option>
            </select>
            <select name="type" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Types</option>
                <option value="standard"  {{ request('type') == 'standard'  ? 'selected' : '' }}>Standard</option>
                <option value="deluxe"    {{ request('type') == 'deluxe'    ? 'selected' : '' }}>Deluxe</option>
                <option value="suite"     {{ request('type') == 'suite'     ? 'selected' : '' }}>Suite</option>
                <option value="villa"     {{ request('type') == 'villa'     ? 'selected' : '' }}>Villa</option>
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
        <div class="bg-white rounded-2xl shadow-sm border {{ $room->status == 'inactive' ? 'border-gray-200 opacity-75' : 'border-gray-100' }} overflow-hidden card-hover">
            <div class="h-3 bg-gradient-to-r
                @if($room->status == 'available') from-emerald-400 to-teal-500
                @elseif($room->status == 'occupied') from-red-400 to-rose-500
                @elseif($room->status == 'inactive') from-gray-300 to-gray-400
                @else from-amber-400 to-yellow-500 @endif"></div>
            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-2xl font-bold {{ $room->status == 'inactive' ? 'text-gray-400' : 'text-gray-800' }}">{{ $room->room_number }}</h3>
                        <span class="badge-{{ $room->type_color }} text-xs mt-1">{{ ucfirst($room->type) }}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        @if($room->status == 'available') bg-emerald-100 text-emerald-700
                        @elseif($room->status == 'occupied') bg-red-100 text-red-700
                        @elseif($room->status == 'inactive') bg-gray-100 text-gray-500
                        @else bg-amber-100 text-amber-700 @endif">
                        @if($room->status == 'inactive')<i class="fas fa-ban mr-1 text-xs"></i>@endif
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

                <!-- Action Buttons -->
                <div class="flex gap-2 mb-2">
                    <a href="{{ route('rooms.show', $room->id) }}" class="flex-1 text-center bg-cyan-50 hover:bg-cyan-100 text-cyan-700 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    @canDo('rooms.edit')
                    @if($room->status != 'inactive')
                    <a href="{{ route('rooms.edit', $room->id) }}" class="flex-1 text-center bg-amber-50 hover:bg-amber-100 text-amber-700 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    @endif
                    @endCanDo
                </div>
                <div class="flex gap-2">
                    @canDo('rooms.edit')
                    @if($room->status == 'inactive')
                    <button onclick="confirmActivate({{ $room->id }}, '{{ $room->room_number }}')"
                        class="flex-1 text-center bg-emerald-50 hover:bg-emerald-100 text-emerald-700 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-toggle-on mr-1"></i>Activate
                    </button>
                    @else
                    <button onclick="confirmDeactivate({{ $room->id }}, '{{ $room->room_number }}')"
                        class="flex-1 text-center bg-gray-50 hover:bg-gray-100 text-gray-600 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-toggle-off mr-1"></i>Deactivate
                    </button>
                    @endif
                    @endCanDo
                    @canDo('rooms.delete')
                    <button onclick="confirmDelete({{ $room->id }}, '{{ $room->room_number }}')"
                        class="flex-1 text-center bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    @endCanDo
                </div>

                <!-- Hidden forms -->
                <form id="deactivate-form-{{ $room->id }}" action="{{ route('rooms.deactivate', $room->id) }}" method="POST" class="hidden">@csrf</form>
                <form id="activate-form-{{ $room->id }}"   action="{{ route('rooms.activate',   $room->id) }}" method="POST" class="hidden">@csrf</form>
                <form id="delete-form-{{ $room->id }}"     action="{{ route('rooms.destroy',    $room->id) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
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

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div id="modalHeader" class="px-6 py-4 flex items-center gap-3">
            <div id="modalIconWrap" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                <i id="modalIcon" class="text-lg"></i>
            </div>
            <h3 id="modalTitle" class="font-bold text-gray-800 text-lg"></h3>
        </div>
        <div class="px-6 pb-2">
            <p id="modalBody" class="text-gray-600 text-sm leading-relaxed"></p>
        </div>
        <div class="px-6 py-5 flex justify-end gap-3">
            <button onclick="closeModal()" class="btn-secondary">Cancel</button>
            <button id="modalConfirmBtn" class="btn-primary" onclick="submitModal()"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let pendingFormId = null;

    function showModal({ title, body, icon, iconBg, iconColor, btnText, btnClass, formId }) {
        pendingFormId = formId;
        document.getElementById('modalTitle').textContent    = title;
        document.getElementById('modalBody').innerHTML       = body;
        document.getElementById('modalIcon').className       = icon;
        document.getElementById('modalIconWrap').className   = `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${iconBg}`;
        document.getElementById('modalIcon').className       = `${icon} ${iconColor} text-lg`;
        const btn = document.getElementById('modalConfirmBtn');
        btn.textContent = btnText;
        btn.className   = btnClass;
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('confirmModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingFormId = null;
    }

    function submitModal() {
        if (pendingFormId) document.getElementById(pendingFormId).submit();
    }

    function confirmDeactivate(id, number) {
        showModal({
            title:    'Deactivate Room ' + number + '?',
            body:     'This room will be marked as <strong>Inactive</strong> and will not appear in new booking selections. The system will check that no guest is currently booked in before proceeding.',
            icon:     'fas fa-toggle-off',
            iconBg:   'bg-gray-100',
            iconColor:'text-gray-500',
            btnText:  'Yes, Deactivate',
            btnClass: 'px-5 py-2.5 rounded-xl text-sm font-semibold bg-gray-600 hover:bg-gray-700 text-white transition-all',
            formId:   'deactivate-form-' + id,
        });
    }

    function confirmActivate(id, number) {
        showModal({
            title:    'Activate Room ' + number + '?',
            body:     'Room <strong>' + number + '</strong> will be set back to <strong>Available</strong> and can be booked again.',
            icon:     'fas fa-toggle-on',
            iconBg:   'bg-emerald-100',
            iconColor:'text-emerald-600',
            btnText:  'Yes, Activate',
            btnClass: 'px-5 py-2.5 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-all',
            formId:   'activate-form-' + id,
        });
    }

    function confirmDelete(id, number) {
        showModal({
            title:    'Delete Room ' + number + '?',
            body:     '<span class="text-red-600 font-semibold">This action is permanent and cannot be undone.</span> All booking history for this room will also be removed. The system will block deletion if a guest is currently occupying the room.',
            icon:     'fas fa-trash',
            iconBg:   'bg-red-100',
            iconColor:'text-red-600',
            btnText:  'Yes, Delete Permanently',
            btnClass: 'px-5 py-2.5 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-700 text-white transition-all',
            formId:   'delete-form-' + id,
        });
    }

    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
@endpush
@endsection
