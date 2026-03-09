<div>

    {{-- Header bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <div style="position:relative;flex:1;max-width:420px;">
            <i class="fas fa-search" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search by name, email or phone…"
                style="width:100%;padding:11px 40px 11px 44px;border:1.5px solid #e2e8f0;border-radius:14px;font-size:13px;outline:none;background:#fff;transition:border-color .15s;box-sizing:border-box;"
                onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'"
            >
            <div wire:loading.delay wire:target="search" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);">
                <svg class="animate-spin" style="width:16px;height:16px;color:#06b6d4;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            @if($search)
            <button wire:click="clearFilters" style="font-size:12px;color:#64748b;background:none;border:none;cursor:pointer;text-decoration:underline;">Clear</button>
            @endif
            @if(\App\Services\PermissionService::check('guests.create'))
            <a href="{{ route('customers.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 12px rgba(6,182,212,.3);transition:all .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform='translateY(0)'">
                <i class="fas fa-user-plus"></i> Add Guest
            </a>
            @endif
        </div>
    </div>

    {{-- Guest table --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;">
        <div style="padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-users" style="color:#fff;font-size:14px;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:#1e293b;font-size:15px;">All Guests <span style="font-size:13px;font-weight:500;color:#94a3b8;">({{ $customers->total() }})</span></div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $search ? 'Filtered results' : 'All registered guests' }}</div>
                </div>
            </div>
            @if($search)
            <span style="font-size:12px;color:#0891b2;font-weight:600;background:#ecfeff;padding:4px 12px;border-radius:20px;border:1px solid #a5f3fc;">
                <i class="fas fa-filter" style="margin-right:4px;"></i>Filter active
            </span>
            @endif
        </div>

        <div style="overflow-x:auto;" wire:loading.class="opacity-60">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Guest</th>
                        <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Contact</th>
                        <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">ID Proof</th>
                        <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Stays</th>
                        <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Joined</th>
                        <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    @php
                        $colors = ['from-cyan-400 to-blue-500','from-violet-400 to-purple-500','from-emerald-400 to-teal-500','from-rose-400 to-pink-500','from-amber-400 to-orange-500'];
                        $ci = crc32($customer->name) % 5; if($ci<0) $ci+=5;
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                    @endphp
                    <tr style="border-top:1px solid #f8fafc;transition:background .12s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:42px;height:42px;background:{{ $gradients[$ci] }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0;box-shadow:0 3px 8px rgba(0,0,0,.12);">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $customer->id) }}" style="font-weight:700;color:#1e293b;font-size:14px;text-decoration:none;" onmouseenter="this.style.color='#0891b2'" onmouseleave="this.style.color='#1e293b'">{{ $customer->name }}</a>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $customer->city }}@if($customer->country), {{ $customer->country }}@endif</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div>
                                    <div style="font-size:13px;color:#374151;font-weight:500;">{{ $customer->phone }}</div>
                                    <div style="font-size:11px;color:#94a3b8;">{{ $customer->email }}</div>
                                </div>
                                @if($customer->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" style="width:28px;height:28px;background:#dcfce7;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#16a34a;text-decoration:none;flex-shrink:0;" title="WhatsApp" onmouseenter="this.style.background='#bbf7d0'" onmouseleave="this.style.background='#dcfce7'">
                                    <i class="fab fa-whatsapp" style="font-size:14px;"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                        <td style="padding:14px 20px;">
                            <span style="display:inline-flex;align-items:center;padding:4px 12px;background:#eff6ff;color:#1d4ed8;border-radius:20px;font-size:11px;font-weight:700;">
                                {{ ucwords(str_replace('_', ' ', $customer->id_type)) }}
                            </span>
                        </td>
                        <td style="padding:14px 20px;text-align:center;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:{{ $customer->bookings_count > 0 ? 'linear-gradient(135deg,#06b6d4,#3b82f6)' : '#f1f5f9' }};color:{{ $customer->bookings_count > 0 ? '#fff' : '#94a3b8' }};border-radius:50%;font-size:13px;font-weight:800;margin:auto;">{{ $customer->bookings_count }}</span>
                        </td>
                        <td style="padding:14px 20px;font-size:12px;color:#94a3b8;">{{ $customer->created_at->format('d M Y') }}</td>
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('customers.show', $customer->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;border-radius:9px;text-decoration:none;transition:background .12s;" title="View" onmouseenter="this.style.background='#dbeafe'" onmouseleave="this.style.background='#eff6ff'">
                                    <i class="fas fa-eye" style="font-size:11px;"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.edit'))
                                <a href="{{ route('customers.edit', $customer->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#fffbeb;color:#d97706;border-radius:9px;text-decoration:none;transition:background .12s;" title="Edit" onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='#fffbeb'">
                                    <i class="fas fa-edit" style="font-size:11px;"></i>
                                </a>
                                @endif
                                <a href="{{ route('documents.index', $customer->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#faf5ff;color:#7c3aed;border-radius:9px;text-decoration:none;transition:background .12s;" title="Documents" onmouseenter="this.style.background='#ede9fe'" onmouseleave="this.style.background='#faf5ff'">
                                    <i class="fas fa-file-alt" style="font-size:11px;"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('guests.delete'))
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Delete this guest?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#fff1f2;color:#e11d48;border-radius:9px;border:none;cursor:pointer;transition:background .12s;" title="Delete" onmouseenter="this.style.background='#ffe4e6'" onmouseleave="this.style.background='#fff1f2'">
                                        <i class="fas fa-trash" style="font-size:11px;"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:60px 24px;text-align:center;color:#94a3b8;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                <i class="fas fa-users" style="font-size:24px;color:#cbd5e1;"></i>
                            </div>
                            <div style="font-size:15px;font-weight:700;color:#475569;margin-bottom:6px;">No guests found</div>
                            <div style="font-size:13px;color:#94a3b8;margin-bottom:16px;">{{ $search ? 'Try a different search term' : 'Start by adding your first guest' }}</div>
                            @if(\App\Services\PermissionService::check('guests.create'))
                            <a href="{{ route('customers.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;padding:9px 20px;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;">
                                <i class="fas fa-user-plus"></i> Add First Guest
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #f8fafc;">{{ $customers->links() }}</div>
    </div>
</div>
