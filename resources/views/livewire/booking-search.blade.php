<div>

    {{-- ── Delete confirmation modal ────────────────────────────────────────── --}}
    <div x-data="bookingDeleteModal()" x-on:open-delete-confirm.window="open($event.detail.id, $event.detail.number)">

        {{-- Overlay (x-show here — no display:flex on this element) --}}
        <div x-show="show" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="position:fixed;inset:0;z-index:9990;background:rgba(15,23,42,0.45);backdrop-filter:blur(2px);"
            @click.self="close()">
        </div>

        {{-- Centering wrapper (always rendered, flex centres the card) --}}
        <div x-show="show" x-cloak
            style="position:fixed;inset:0;z-index:9991;display:flex;align-items:center;justify-content:center;padding:16px;pointer-events:none;">

        {{-- Modal card --}}
        <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:440px;box-shadow:0 24px 64px rgba(0,0,0,.2);overflow:hidden;pointer-events:auto;"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            {{-- Top accent bar --}}
            <div style="height:4px;background:linear-gradient(90deg,#ef4444,#f97316);"></div>

            <div style="padding:28px 28px 24px;">
                {{-- Icon + heading --}}
                <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:16px;">
                    <div style="flex-shrink:0;width:48px;height:48px;border-radius:14px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-trash-alt" style="font-size:20px;color:#dc2626;"></i>
                    </div>
                    <div>
                        <h3 style="margin:0 0 4px;font-size:17px;font-weight:700;color:#111827;">Delete Booking</h3>
                        <p style="margin:0;font-size:13px;color:#6b7280;" x-text="'#' + bookingNumber"></p>
                    </div>
                    <button @click="close()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:2px 4px;border-radius:6px;" title="Close">×</button>
                </div>

                {{-- Body text --}}
                <p style="margin:0 0 24px;font-size:14px;color:#374151;line-height:1.6;">
                    This will <strong style="color:#dc2626;">cancel the booking</strong> and mark the room as available again.<br>
                    <span style="font-size:13px;color:#9ca3af;">This action cannot be undone.</span>
                </p>

                {{-- Actions --}}
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button @click="close()"
                        style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#374151;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">
                        Keep Booking
                    </button>
                    <button @click="confirm()"
                        wire:loading.attr="disabled"
                        style="padding:9px 20px;border-radius:10px;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                        <span wire:loading.remove wire:target="deleteBooking"><i class="fas fa-trash-alt" style="font-size:12px;"></i> Delete Booking</span>
                        <span wire:loading wire:target="deleteBooking" style="display:flex;align-items:center;gap:6px;">
                            <svg style="width:14px;height:14px;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Deleting…
                        </span>
                    </button>
                </div>
            </div>
        </div>
        </div>{{-- /centering wrapper --}}
    </div>{{-- /x-data wrapper --}}

    {{-- Alpine data for the delete modal --}}
    <script>
        function bookingDeleteModal() {
            return {
                show: false,
                bookingId: null,
                bookingNumber: '',
                open(id, number) {
                    this.bookingId     = id;
                    this.bookingNumber = number;
                    this.show = true;
                    document.body.style.overflow = 'hidden';
                },
                close() {
                    this.show = false;
                    document.body.style.overflow = '';
                },
                confirm() {
                    this.$wire.deleteBooking(this.bookingId);
                    this.close();
                }
            };
        }
    </script>

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
                    @if(\App\Models\Module::isEnabled('booking-widget'))
                    <option value="website_pending">Website Pending</option>
                    @endif
                    <option value="pending_room_assignment">Pending Room</option>
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
                            'confirmed'              => ['lv-badge-cyan',   'fa-check-circle'],
                            'checked_in'             => ['lv-badge-green',  'fa-sign-in-alt'],
                            'checked_out'            => ['lv-badge-gray',   'fa-sign-out-alt'],
                            'cancelled'              => ['lv-badge-red',    'fa-times-circle'],
                            'website_pending'        => ['lv-badge-purple', 'fa-globe'],
                            'pending_room_assignment'=> ['lv-badge-amber',  'fa-bed'],
                        ];
                        [$sCls, $sIcon] = $sMap[$booking->status] ?? ['lv-badge-gray', 'fa-circle'];
                        $pMap = [
                            'paid'    => 'lv-badge-green',
                            'partial' => 'lv-badge-amber',
                            'unpaid'  => 'lv-badge-red',
                        ];
                        $pCls = $pMap[$booking->payment_status] ?? 'lv-badge-gray';
                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($booking->customer?->name ?? 'G') % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr class="lv-row">
                        <td class="lv-td">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="lv-mono" style="color:#0891b2;text-decoration:none;">
                                {{ $booking->booking_number }}
                            </a>
                            @if($booking->room?->pricing_type === 'per_slot')
                                <div class="lv-secondary" style="color:#7c3aed;">
                                    <i class="fas fa-clock" style="font-size:10px;"></i> Slot
                                </div>
                            @elseif($booking->room?->pricing_type === 'per_hour')
                                <div class="lv-secondary" style="color:#0891b2;">
                                    <i class="fas fa-hourglass-half" style="font-size:10px;"></i> Hourly
                                </div>
                            @else
                                <div class="lv-secondary">{{ $booking->nights }} night{{ $booking->nights != 1 ? 's' : '' }}</div>
                            @endif
                        </td>
                        <td class="lv-td">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="lv-avatar" style="width:36px;height:36px;font-size:14px;background:{{ $gradients[$ci] }};">
                                    {{ strtoupper(substr($booking->customer?->name ?? 'G', 0, 1)) }}
                                </div>
                                <div>
                                    @if($booking->customer)
                                    <a href="{{ route('customers.show', $booking->customer->id) }}" class="lv-name-link">{{ $booking->customer->name }}</a>
                                    @else
                                    <span class="lv-name-link" style="color:#94a3b8;">(Deleted Guest)</span>
                                    @endif
                                    <div class="lv-secondary">
                                        {{ $booking->adults }} adult{{ $booking->adults != 1 ? 's' : '' }}{{ $booking->children > 0 ? ' · ' . $booking->children . ' child' : '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="lv-td">
                            @if($booking->is_whole_hotel)
                            <div class="lv-room-pill" style="background:#f3e8ff;color:#6b21a8;">
                                <span class="lv-room-pill-label"><i class="fas fa-hotel"></i></span>
                                <span class="lv-room-pill-num">WH</span>
                            </div>
                            <div class="lv-secondary" style="margin-top:4px;color:#7e22ce;font-weight:600;">Whole Hotel</div>
                            @elseif($booking->room)
                            <div class="lv-room-pill">
                                <span class="lv-room-pill-label">RM</span>
                                <span class="lv-room-pill-num">{{ $booking->room?->room_number }}</span>
                            </div>
                            <div class="lv-secondary" style="margin-top:4px;">{{ ucfirst($booking->room?->type ?? '') }}</div>
                            @else
                            <div class="lv-room-pill" style="background:#fef3c7;color:#92400e;">
                                <span class="lv-room-pill-label">RM</span>
                                <span class="lv-room-pill-num">TBD</span>
                            </div>
                            <div class="lv-secondary" style="margin-top:4px;color:#d97706;">Pending</div>
                            @endif
                        </td>
                        {{-- Dates cell — varies by room pricing_type --}}
                        @if($booking->room?->pricing_type === 'per_slot')
                        <td class="lv-td">
                            <div style="font-size:13px;font-weight:600;color:#374151;">
                                {{ optional($booking->booking_date)->format('d M Y') }}
                            </div>
                            <div class="lv-secondary" style="color:#7c3aed;">
                                <i class="fas fa-clock" style="font-size:10px;"></i>
                                {{ $booking->timeSlot?->name ?? '—' }}
                            </div>
                        </td>
                        @elseif($booking->room?->pricing_type === 'per_hour')
                        <td class="lv-td">
                            <div style="font-size:13px;font-weight:600;color:#374151;">
                                {{ optional($booking->booking_date)->format('d M Y') }}
                            </div>
                            <div class="lv-secondary" style="color:#0891b2;">
                                <i class="fas fa-hourglass-half" style="font-size:10px;"></i>
                                {{ $booking->slot_start_time ? \Carbon\Carbon::parse($booking->slot_start_time)->format('g:i A') : '—' }}
                                @if($booking->hours_booked) · {{ $booking->hours_booked }} hr{{ $booking->hours_booked != 1 ? 's' : '' }} @endif
                            </div>
                        </td>
                        @else
                        <td class="lv-td">
                            <div style="font-size:13px;font-weight:600;color:#374151;">{{ optional($booking->check_in_date)->format('d M') }}</div>
                            <div class="lv-secondary">→ {{ optional($booking->check_out_date)->format('d M Y') }}</div>
                        </td>
                        @endif
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
                            @if($booking->price_overridden)
                            <div style="font-size:10px;color:#d97706;font-weight:600;margin-top:2px;display:flex;align-items:center;gap:3px;">
                                <i class="fas fa-pen" style="font-size:8px;"></i> Custom price
                            </div>
                            @endif
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
                                @if(\App\Services\PermissionService::check('bookings.delete'))
                                <button type="button"
                                    @click="$dispatch('open-delete-confirm', { id: {{ $booking->id }}, number: '{{ $booking->booking_number }}' })"
                                    class="lv-action-btn" title="Delete"
                                    style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif
                                @if($booking->status === 'website_pending')
                                <a href="{{ route('bookings.show', $booking->id) }}" class="lv-action-btn" title="Review &amp; Confirm" style="background:#fef3c7;color:#d97706;border:1px solid #fcd34d;">
                                    <i class="fas fa-check-circle"></i>
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
