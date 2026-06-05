<div>
    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-44">
                <label class="form-label">Search</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        class="form-input pr-9"
                        placeholder="User name, email, description…"
                    >
                    <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4" style="color: #c9a96e;" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="w-44">
                <label class="form-label">Module</label>
                <select wire:model.live="module" class="form-input">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                    <option value="{{ $mod }}">{{ $mod }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="form-label">Action</label>
                <select wire:model.live="action" class="form-input">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                    <option value="{{ $act }}">{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="form-label">Date</label>
                <input type="date" wire:model.live="date" class="form-input">
            </div>
            @if($isSuperAdmin)
            <div class="w-48">
                <label class="form-label">Hotel</label>
                <select wire:model.live="hotelFilter" class="form-input">
                    <option value="">All Hotels</option>
                    @foreach($allHotels as $h)
                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($search || $module || $action || $date || $hotelFilter)
            <div class="self-end pb-0.5">
                <button wire:click="clearFilters" class="btn-secondary text-sm px-4 py-2.5">Clear</button>
            </div>
            @endif
        </div>
        @if($search || $module || $action || $date || $hotelFilter)
        <p class="text-xs font-medium mt-3" style="color: #c9a96e;">
            <i class="fas fa-filter mr-1"></i>Showing {{ $logs->total() }} result(s) — filters active
        </p>
        @endif
    </div>

    <!-- Log Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-800">Audit Trail</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $logs->total() }} entries found</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <i class="fas fa-shield-halved" style="color: #c9a96e;"></i>
                Real-time activity tracking
            </div>
        </div>

        @if($logs->isEmpty())
        <div class="py-20 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-history text-gray-300 text-2xl"></i>
            </div>
            <p class="text-gray-400 font-medium">No activity logs found</p>
            <p class="text-gray-300 text-sm mt-1">Logs will appear here as actions are performed</p>
        </div>
        @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Date / Time</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">User</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">Role</th>
                        @if($isSuperAdmin)
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">Hotel</th>
                        @endif
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">Module</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">Action</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">Description</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($logs as $log)
                    @php
                        $roleBadge = match($log->user_role) {
                            'Super Admin' => 'badge-purple',
                            'Admin'       => 'badge-red',
                            'Manager'     => 'badge-blue',
                            'Receptionist'=> 'badge-green',
                            default       => 'badge-gray',
                        };
                        $actionBadge = match(true) {
                            str_contains($log->action, 'Delete') || $log->action === 'Deleted' => 'badge-red',
                            str_contains($log->action, 'Create') || $log->action === 'Created' => 'badge-green',
                            str_contains($log->action, 'Update') || $log->action === 'Updated' => 'badge-blue',
                            str_contains($log->action, 'Checked') => 'badge-purple',
                            default => 'badge-gray',
                        };
                        $hotelName = $isSuperAdmin
                            ? ($allHotels->firstWhere('id', $log->hotel_id)->name ?? ('Hotel #' . $log->hotel_id))
                            : null;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="text-sm font-semibold text-gray-800">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $log->created_at->format('h:i:s A') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-semibold text-gray-800">{{ $log->user_name }}</div>
                            <div class="text-xs text-gray-400">{{ $log->user_email }}</div>
                        </td>
                        <td class="px-4 py-3"><span class="{{ $roleBadge }}">{{ $log->user_role }}</span></td>
                        @if($isSuperAdmin)
                        <td class="px-4 py-3">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:#ede9fe;color:#6d28d9;">
                                <i class="fas fa-building" style="font-size:9px;"></i>
                                {{ $hotelName }}
                            </span>
                        </td>
                        @endif
                        <td class="px-4 py-3"><span class="badge-gray">{{ $log->module }}</span></td>
                        <td class="px-4 py-3"><span class="{{ $actionBadge }}">{{ $log->action }}</span></td>
                        <td class="px-4 py-3 max-w-xs"><span class="text-sm text-gray-600">{{ $log->description }}</span></td>
                        <td class="px-4 py-3"><span class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
        @endif
    </div>
</div>
