<div>

    {{-- Header bar --}}
    <div class="lv-filter-bar" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 22px;">
        <div class="lv-filter-group lv-filter-group-grow" style="max-width:420px;">
            <div class="lv-filter-icon-wrap">
                <i class="fas fa-search"></i>
                <input type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search by name, email or phone…"
                    class="lv-filter-input lv-filter-input-icon">
                <div wire:loading.delay wire:target="search" class="lv-filter-spinner">
                    <svg class="animate-spin" style="width:16px;height:16px;color:#06b6d4;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            @if($search)
            <button wire:click="clearFilters" class="lv-clear-btn">
                <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
            </button>
            @endif
            @if(\App\Services\PermissionService::check('guests.create'))
            <a href="{{ route('customers.create') }}" class="btn-primary">
                <i class="fas fa-user-plus" style="margin-right:8px;"></i>Add Guest
            </a>
            @endif
        </div>
    </div>

    {{-- Guest table --}}
    <div class="lv-card">

        <div class="lv-card-header" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="lv-card-icon" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="lv-card-title">All Guests <span>({{ $customers->total() }})</span></div>
                    <div class="lv-card-subtitle">{{ $search ? 'Filtered results' : 'All registered guests' }}</div>
                </div>
            </div>
            @if($search)
            <span class="lv-badge lv-badge-cyan">
                <i class="fas fa-filter"></i> Filter active
            </span>
            @endif
        </div>

        <div class="lv-table-wrap" wire:loading.class="opacity-60">
            <table class="lv-table">
                <thead>
                    <tr>
                        <th class="lv-th">Guest</th>
                        <th class="lv-th">Contact</th>
                        <th class="lv-th">ID Proof</th>
                        <th class="lv-th lv-td-center">Stays</th>
                        <th class="lv-th">Joined</th>
                        <th class="lv-th lv-th-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    @php
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($customer->name) % 5;
                        if ($ci < 0) { $ci += 5; }
                        $location = trim(($customer->city ?? '') . ($customer->country ? ', ' . $customer->country : ''));
                    @endphp
                    <tr class="lv-row">
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="lv-avatar" style="background:{{ $gradients[$ci] }};">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $customer->id) }}" class="lv-name-link">{{ $customer->name }}</a>
                                    @if($location)
                                    <div class="lv-secondary">{{ $location }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div>
                                    <div style="font-size:14px;color:#374151;font-weight:500;">{{ $customer->phone }}</div>
                                    <div class="lv-secondary">{{ $customer->email }}</div>
                                </div>
                                @if($customer->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="lv-action-btn lv-action-btn-green" title="WhatsApp">
                                    <i class="fab fa-whatsapp" style="font-size:13px;"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge lv-badge-blue">
                                {{ ucwords(str_replace('_', ' ', $customer->id_type)) }}
                            </span>
                        </td>
                        <td class="lv-td lv-td-center">
                            <span class="lv-avatar" style="width:32px;height:32px;font-size:13px;margin:auto;background:{{ $customer->bookings_count > 0 ? 'linear-gradient(135deg,#06b6d4,#3b82f6)' : '#f1f5f9' }};color:{{ $customer->bookings_count > 0 ? '#fff' : '#94a3b8' }};">
                                {{ $customer->bookings_count }}
                            </span>
                        </td>
                        <td class="lv-td">
                            <div class="lv-secondary" style="font-size:13px;color:#64748b;">{{ $customer->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="lv-td lv-td-right">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('customers.show', $customer->id) }}" class="lv-action-btn lv-action-btn-blue" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.edit'))
                                <a href="{{ route('customers.edit', $customer->id) }}" class="lv-action-btn lv-action-btn-amber" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                <a href="{{ route('documents.index', $customer->id) }}" class="lv-action-btn lv-action-btn-purple" title="Documents">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.delete'))
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Delete this guest?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="lv-action-btn lv-action-btn-red" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="lv-empty">
                            <div class="lv-empty-icon" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0);">
                                <i class="fas fa-users" style="font-size:24px;color:#cbd5e1;"></i>
                            </div>
                            <div class="lv-empty-title">No guests found</div>
                            <div class="lv-empty-sub">{{ $search ? 'Try a different search term' : 'Start by adding your first guest' }}</div>
                            @if(\App\Services\PermissionService::check('guests.create'))
                            <a href="{{ route('customers.create') }}" class="btn-primary" style="display:inline-flex;">
                                <i class="fas fa-user-plus" style="margin-right:8px;"></i>Add First Guest
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lv-pagination">{{ $customers->links() }}</div>
    </div>

</div>
