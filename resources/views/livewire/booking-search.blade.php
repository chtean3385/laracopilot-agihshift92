<div>

    {{-- Filter bar --}}
    <div class="lv-filter-bar">
        <div class="lv-filter-row">

            <div class="lv-filter-group lv-filter-group-grow">
                <label class="lv-filter-label">Search</label>
                <div class="lv-filter-icon-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Guest name or booking #…"
                        class="lv-filter-input lv-filter-input-icon">
                    <div wire:loading.delay wire:target="search" class="lv-filter-spinner">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#06b6d4;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="lv-filter-group" style="min-width:150px;">
                <label class="lv-filter-label">Status</label>
                <select wire:model.live="status" class="lv-filter-select">
                    <option value="">All Statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="checked_in">Checked In</option>
                    <option value="checked_out">Checked Out</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="lv-filter-group" style="min-width:150px;">
                <label class="lv-filter-label">Room Type</label>
                <select wire:model.live="roomType" class="lv-filter-select">
                    <option value="">All Types</option>
                    @foreach($roomTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lv-filter-group" style="min-width:135px;">
                <label class="lv-filter-label">From</label>
                <input type="date" wire:model.live="dateFrom" class="lv-filter-input">
            </div>

            <div class="lv-filter-group" style="min-width:135px;">
                <label class="lv-filter-label">To</label>
                <input type="date" wire:model.live="dateTo" class="lv-filter-input">
            </div>

            @if($search || $status || $roomType || $dateFrom || $dateTo)
            <div class="lv-filter-group" style="justify-content:flex-end;padding-bottom:0;">
                <label class="lv-filter-label" style="opacity:0;">.</label>
                <button wire:click="clearFilters" class="lv-clear-btn">
                    <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
                </button>
            </div>
            @endif
        </div>

        @if($search || $status || $roomType || $dateFrom || $dateTo)
        <div class="lv-filter-result" style="color:#0891b2;">
            <i class="fas fa-filter"></i>
            <span wire:loading.remove wire:target="search,status,roomType,dateFrom,dateTo">
                <strong>{{ $bookings->total() }}</strong> result{{ $bookings->total() != 1 ? 's' : '' }} found
            </span>
            <span wire:loading wire:target="search,status,roomType,dateFrom,dateTo">
                <i class="fas fa-circle-notch fa-spin"></i> Updating…
            </span>
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="lv-card" wire:loading.class="opacity-60">

        <div class="lv-card-header" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="lv-card-icon" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <div class="lv-card-title">All Bookings <span>({{ $bookings->total() }})</span></div>
                    <div class="lv-card-subtitle">{{ ($search||$status||$roomType||$dateFrom||$dateTo) ? 'Filtered results' : 'All reservations' }}</div>
                </div>
            </div>
            @if(\App\Services\PermissionService::check('bookings.create'))
            <a href="{{ route('bookings.create') }}" class="btn-primary">
                <i class="fas fa-plus" style="margin-right:8px;"></i>New Booking
            </a>
            @endif
        </div>

        <div class="lv-table-wrap">
            <table class="lv-table" style="min-width:820px;">
                <thead>
                    <tr>
                        <th class="lv-th">Booking #</th>
                        <th class="lv-th">Guest</th>
                        <th class="lv-th">Room</th>
                        <th class="lv-th">Dates</th>
                        <th class="lv-th">Status</th>
                        <th class="lv-th">Payment</th>
                        <th class="lv-th">Amount</th>
                        <th class="lv-th lv-th-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                    @php
                        $sMap = [
                            'confirmed'   => ['lv-badge-cyan',   'fa-check-circle'],
                            'checked_in'  => ['lv-badge-green',  'fa-sign-in-alt'],
                            'checked_out' => ['lv-badge-gray',   'fa-sign-out-alt'],
                            'cancelled'   => ['lv-badge-red',    'fa-times-circle'],
                        ];
                        [$sCls, $sIcon] = $sMap[$booking->status] ?? ['lv-badge-gray', 'fa-circle'];
                        $pMap = [
                            'paid'    => 'lv-badge-green',
                            'partial' => 'lv-badge-amber',
                            'unpaid'  => 'lv-badge-red',
                        ];
                        $pCls = $pMap[$booking->payment_status] ?? 'lv-badge-gray';
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($booking->customer->name) % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr class="lv-row">
                        <td class="lv-td">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="lv-mono" style="color:#0891b2;text-decoration:none;">
                                {{ $booking->booking_number }}
                            </a>
                            <div class="lv-secondary">{{ $booking->nights }} night{{ $booking->nights != 1 ? 's' : '' }}</div>
                        </td>
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="lv-avatar" style="width:36px;height:36px;font-size:14px;background:{{ $gradients[$ci] }};">
                                    {{ strtoupper(substr($booking->customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $booking->customer->id) }}" class="lv-name-link">{{ $booking->customer->name }}</a>
                                    <div class="lv-secondary">
                                        {{ $booking->adults }} adult{{ $booking->adults != 1 ? 's' : '' }}{{ $booking->children > 0 ? ' · ' . $booking->children . ' child' : '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="lv-td">
                            <div class="lv-room-pill">
                                <span class="lv-room-pill-label">RM</span>
                                <span class="lv-room-pill-num">{{ $booking->room->room_number }}</span>
                            </div>
                            <div class="lv-secondary" style="margin-top:4px;">{{ ucfirst($booking->room->room_type) }}</div>
                        </td>
                        <td class="lv-td">
                            <div style="font-size:13px;font-weight:600;color:#374151;">{{ $booking->check_in->format('d M') }}</div>
                            <div class="lv-secondary">→ {{ $booking->check_out->format('d M Y') }}</div>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge {{ $sCls }}">
                                <i class="fas {{ $sIcon }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </span>
                        </td>
                        <td class="lv-td">
                            <span class="lv-badge {{ $pCls }}">
                                {{ ucfirst($booking->payment_status) }}
                            </span>
                        </td>
                        <td class="lv-td">
                            <div style="font-weight:800;font-size:15px;color:#1e293b;">₹{{ number_format($booking->total_amount) }}</div>
                        </td>
                        <td class="lv-td lv-td-right">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="lv-action-btn lv-action-btn-blue" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(\App\Services\PermissionService::check('bookings.edit'))
                                <a href="{{ route('bookings.edit', $booking->id) }}" class="lv-action-btn lv-action-btn-amber" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                @if($booking->status === 'confirmed')
                                <a href="{{ route('checkin.show', $booking->id) }}" class="lv-action-btn lv-action-btn-green" title="Check In">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                                @endif
                                @if($booking->status === 'checked_in')
                                <a href="{{ route('checkout.show', $booking->id) }}" class="lv-action-btn lv-action-btn-cyan" title="Check Out">
                                    <i class="fas fa-sign-out-alt"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="lv-empty">
                            <div class="lv-empty-icon" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);">
                                <i class="fas fa-calendar-times" style="font-size:24px;color:#7dd3fc;"></i>
                            </div>
                            <div class="lv-empty-title">No bookings found</div>
                            <div class="lv-empty-sub">{{ ($search||$status||$roomType||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'No reservations yet' }}</div>
                            @if($search || $status || $roomType || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" class="lv-clear-btn">
                                <i class="fas fa-times" style="margin-right:5px;"></i>Clear Filters
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lv-pagination">{{ $bookings->links() }}</div>
    </div>

</div>
