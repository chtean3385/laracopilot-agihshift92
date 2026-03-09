<div class="space-y-5">
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex gap-3 flex-1 max-w-lg items-center">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search by name, email, phone..."
                    class="w-full pl-11 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none text-sm"
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
        @if(\App\Services\PermissionService::check('guests.create'))
        <a href="{{ route('customers.create') }}" class="btn-primary whitespace-nowrap">
            <i class="fas fa-user-plus mr-2"></i> Add New Guest
        </a>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">
                All Guests
                <span class="text-sm font-normal text-gray-400 ml-2">({{ $customers->total() }})</span>
            </h3>
            @if($search)
            <span class="text-xs text-cyan-600 font-medium">
                <i class="fas fa-filter mr-1"></i>Filters active
            </span>
            @endif
        </div>
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Guest</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">ID Proof</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Bookings</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                    {{ substr($customer->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800 text-sm">{{ $customer->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $customer->city }}, {{ $customer->country }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div>
                                    <div class="text-sm text-gray-700">{{ $customer->phone }}</div>
                                    <div class="text-xs text-gray-400">{{ $customer->email }}</div>
                                </div>
                                @if($customer->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="text-green-500 hover:text-green-600" title="WhatsApp">
                                    <i class="fab fa-whatsapp text-lg"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="badge-blue">{{ ucwords(str_replace('_', ' ', $customer->id_type)) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-gray-700">{{ $customer->bookings_count }}</span>
                            <span class="text-xs text-gray-400 ml-1">stay(s)</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-400">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customers.show', $customer->id) }}" class="w-8 h-8 flex items-center justify-center bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" title="View">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.edit'))
                                <a href="{{ route('customers.edit', $customer->id) }}" class="w-8 h-8 flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @endif
                                <a href="{{ route('documents.index', $customer->id) }}" class="w-8 h-8 flex items-center justify-center bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-lg transition-all" title="Documents">
                                    <i class="fas fa-file text-xs"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.delete'))
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Delete this guest?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-all" title="Delete">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p class="font-medium">No guests found</p>
                            @if(\App\Services\PermissionService::check('guests.create'))
                            <a href="{{ route('customers.create') }}" class="text-cyan-600 hover:underline text-sm mt-2 inline-block">Add your first guest</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $customers->links() }}</div>
    </div>
</div>
