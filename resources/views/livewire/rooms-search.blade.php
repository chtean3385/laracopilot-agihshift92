<div class="space-y-5">
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

    <!-- Filters + Add Button -->
    <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Room #, type, view, amenities..."
                    class="border border-gray-200 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none w-64"
                >
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <select wire:model.live="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
            </select>
            <select wire:model.live="type" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">All Types</option>
                <option value="standard">Standard</option>
                <option value="deluxe">Deluxe</option>
                <option value="suite">Suite</option>
                <option value="villa">Villa</option>
                <option value="penthouse">Penthouse</option>
            </select>
            @if($search || $status || $type)
            <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</button>
            @endif
        </div>
        @if(\App\Services\PermissionService::check('rooms.create'))
        <a href="{{ route('rooms.create') }}" class="btn-primary"><i class="fas fa-plus mr-2"></i>Add New Room</a>
        @endif
    </div>

    @if($search || $status || $type)
    <p class="text-xs text-cyan-600 font-medium -mt-2">
        <i class="fas fa-filter mr-1"></i>Showing {{ $rooms->total() }} result(s) — filters active
    </p>
    @endif

    <!-- Rooms Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" wire:loading.class="opacity-60">
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
                <p class="text-xs text-gray-400 mb-2 line-clamp-2">{{ $room->amenities }}</p>
                @endif
                @if($room->has_breakfast || $room->has_lunch || $room->has_dinner || $room->has_extra_bed)
                <div class="flex flex-wrap gap-1 mb-3">
                    @if($room->has_breakfast)<span class="text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5"><i class="fas fa-coffee mr-0.5"></i>B</span>@endif
                    @if($room->has_lunch)<span class="text-xs bg-orange-100 text-orange-700 rounded-full px-2 py-0.5"><i class="fas fa-sun mr-0.5"></i>L</span>@endif
                    @if($room->has_dinner)<span class="text-xs bg-indigo-100 text-indigo-700 rounded-full px-2 py-0.5"><i class="fas fa-moon mr-0.5"></i>D</span>@endif
                    @if($room->has_extra_bed)<span class="text-xs bg-blue-100 text-blue-700 rounded-full px-2 py-0.5"><i class="fas fa-bed mr-0.5"></i>+Bed</span>@endif
                </div>
                @endif
                @if(\App\Services\PermissionService::check('rooms.edit'))
                <a href="{{ route('rooms.edit', $room->id) }}" class="w-full text-center block bg-cyan-50 hover:bg-cyan-100 text-cyan-700 py-2 rounded-xl text-xs font-semibold mb-2 transition-all">
                    <i class="fas fa-edit mr-1"></i>Edit Room
                </a>
                @endif
                <div class="flex gap-2">
                    @if(\App\Services\PermissionService::check('rooms.edit'))
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
                    @endif
                    @if(\App\Services\PermissionService::check('rooms.delete'))
                    <button onclick="confirmDelete({{ $room->id }}, '{{ $room->room_number }}')"
                        class="flex-1 text-center bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-xl text-xs font-semibold transition-all">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    @endif
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

    <!-- Confirmation Modal (wire:ignore prevents Livewire re-rendering the modal/script) -->
    <div wire:ignore>
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div id="modalHeader" class="px-6 py-4 flex items-center gap-3">
            <div id="modalIconWrap" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                <i id="modalIcon" class="text-lg"></i>
            </div>
            <h3 id="modalTitle" class="font-bold text-gray-800 text-lg"></h3>
        </div>
        <div class="px-6 pb-2">
            <p id="modalMessage" class="text-gray-500 text-sm"></p>
        </div>
        <div class="flex gap-3 px-6 py-4">
            <button onclick="closeModal()" class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl font-medium text-sm hover:bg-gray-50 transition-all">Cancel</button>
            <button id="modalConfirmBtn" class="flex-1 py-2.5 rounded-xl font-semibold text-sm text-white transition-all"></button>
        </div>
    </div>
</div>

<script>
function openModal() { document.getElementById('confirmModal').classList.replace('hidden','flex'); }
function closeModal() { document.getElementById('confirmModal').classList.replace('flex','hidden'); }

function confirmDeactivate(id, num) {
    document.getElementById('modalHeader').className = 'px-6 py-4 flex items-center gap-3 bg-gray-50';
    document.getElementById('modalIconWrap').className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-gray-200';
    document.getElementById('modalIcon').className = 'fas fa-toggle-off text-gray-600 text-lg';
    document.getElementById('modalTitle').textContent = 'Deactivate Room ' + num;
    document.getElementById('modalMessage').textContent = 'Room ' + num + ' will be marked as inactive and removed from booking availability.';
    var btn = document.getElementById('modalConfirmBtn');
    btn.textContent = 'Yes, Deactivate';
    btn.className = 'flex-1 py-2.5 rounded-xl font-semibold text-sm text-white bg-gray-500 hover:bg-gray-600 transition-all';
    btn.onclick = function() { document.getElementById('deactivate-form-' + id).submit(); };
    openModal();
}

function confirmActivate(id, num) {
    document.getElementById('modalHeader').className = 'px-6 py-4 flex items-center gap-3 bg-emerald-50';
    document.getElementById('modalIconWrap').className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-100';
    document.getElementById('modalIcon').className = 'fas fa-toggle-on text-emerald-600 text-lg';
    document.getElementById('modalTitle').textContent = 'Activate Room ' + num;
    document.getElementById('modalMessage').textContent = 'Room ' + num + ' will be marked as available for bookings.';
    var btn = document.getElementById('modalConfirmBtn');
    btn.textContent = 'Yes, Activate';
    btn.className = 'flex-1 py-2.5 rounded-xl font-semibold text-sm text-white bg-emerald-500 hover:bg-emerald-600 transition-all';
    btn.onclick = function() { document.getElementById('activate-form-' + id).submit(); };
    openModal();
}

function confirmDelete(id, num) {
    document.getElementById('modalHeader').className = 'px-6 py-4 flex items-center gap-3 bg-red-50';
    document.getElementById('modalIconWrap').className = 'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-red-100';
    document.getElementById('modalIcon').className = 'fas fa-trash text-red-500 text-lg';
    document.getElementById('modalTitle').textContent = 'Delete Room ' + num;
    document.getElementById('modalMessage').textContent = 'This will permanently delete Room ' + num + ' and all associated data. This cannot be undone.';
    var btn = document.getElementById('modalConfirmBtn');
    btn.textContent = 'Yes, Delete Permanently';
    btn.className = 'flex-1 py-2.5 rounded-xl font-semibold text-sm text-white bg-red-500 hover:bg-red-600 transition-all';
    btn.onclick = function() { document.getElementById('delete-form-' + id).submit(); };
    openModal();
}
</script>
</div>
</div>
