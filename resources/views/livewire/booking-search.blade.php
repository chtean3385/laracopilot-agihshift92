<div>

    {{-- Filter bar --}}
    <div style="background:#fff;border-radius:20px;padding:18px 22px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;margin-bottom:20px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">

            {{-- Search --}}
            <div style="flex:1;min-width:200px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Search</div>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Guest name or booking #…"
                        style="width:100%;padding:9px 36px 9px 36px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
                    <div wire:loading.delay wire:target="search" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);">
                        <svg class="animate-spin" style="width:14px;height:14px;color:#06b6d4;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div style="min-width:150px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Status</div>
                <select wire:model.live="status"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;background:#fff;cursor:pointer;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="">All Statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="checked_in">Checked In</option>
                    <option value="checked_out">Checked Out</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            {{-- From --}}
            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">From</div>
                <input type="date" wire:model.live="dateFrom"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            {{-- To --}}
            <div style="min-width:140px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">To</div>
                <input type="date" wire:model.live="dateTo"
                    style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            @if($search || $status || $dateFrom || $dateTo)
            <div>
                <div style="font-size:11px;font-weight:700;color:transparent;margin-bottom:6px;">.</div>
                <button wire:click="clearFilters"
                    style="padding:9px 18px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;"
                    onmouseenter="this.style.borderColor='#94a3b8';this.style.background='#f8fafc'" onmouseleave="this.style.borderColor='#e2e8f0';this.style.background='#fff'">
                    <i class="fas fa-times" style="margin-right:5px;font-size:11px;"></i>Clear
                </button>
            </div>
            @endif

        </div>

        @if($search || $status || $dateFrom || $dateTo)
        <div style="margin-top:12px;font-size:12px;color:#0891b2;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-filter"></i>
            <span wire:loading.remove wire:target="search,status,dateFrom,dateTo">
                <strong>{{ $bookings->total() }}</strong> result{{ $bookings->total() != 1 ? 's' : '' }} found
            </span>
            <span wire:loading wire:target="search,status,dateFrom,dateTo" style="color:#06b6d4;">
                <i class="fas fa-circle-notch fa-spin"></i> Updating…
            </span>
        </div>
        @endif
    </div>

    {{-- Table header + New Booking button --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="font-size:13px;color:#64748b;">
            <span wire:loading.remove>{{ $bookings->total() }} booking{{ $bookings->total() != 1 ? 's' : '' }}</span>
            <span wire:loading style="color:#06b6d4;"><i class="fas fa-circle-notch fa-spin"></i> Loading…</span>
        </div>
        @if(\App\Services\PermissionService::check('bookings.create'))
        <a href="{{ route('bookings.create') }}" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 12px rgba(6,182,212,.3);transition:all .15s;" onmouseenter="this.style.transform='translateY(-1px)'" onmouseleave="this.style.transform='translateY(0)'">
            <i class="fas fa-plus"></i> New Booking
        </a>
        @endif
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #f1f5f9;overflow:hidden;" wire:loading.class="opacity-60">

        {{-- Table header tile --}}
        <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f8fafc,#f1f5f9);display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-calendar-check" style="color:#fff;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-weight:800;color:#1e293b;font-size:15px;">All Bookings <span style="font-size:13px;font-weight:500;color:#94a3b8;">({{ $bookings->total() }})</span></div>
                <div style="font-size:11px;color:#94a3b8;">{{ ($search||$status||$dateFrom||$dateTo) ? 'Filtered results' : 'All reservations' }}</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:820px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Booking #</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Guest</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Room</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Dates</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Amount</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                        <th style="text-align:left;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Payment</th>
                        <th style="text-align:right;padding:11px 18px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                    @php
                        $statusStyles = [
                            'confirmed'   => ['#dbeafe','#1d4ed8'],
                            'checked_in'  => ['#dcfce7','#15803d'],
                            'checked_out' => ['#f3f4f6','#374151'],
                            'cancelled'   => ['#fee2e2','#b91c1c'],
                            'pending'     => ['#fef3c7','#92400e'],
                        ];
                        [$sBg,$sClr] = $statusStyles[$booking->status] ?? ['#f3f4f6','#374151'];

                        $payStyles = [
                            'paid'    => ['#dcfce7','#15803d'],
                            'partial' => ['#fef3c7','#92400e'],
                            'unpaid'  => ['#fee2e2','#b91c1c'],
                        ];
                        [$pBg,$pClr] = $payStyles[$booking->payment_status] ?? ['#f3f4f6','#374151'];

                        $gradients = ['linear-gradient(135deg,#22d3ee,#3b82f6)','linear-gradient(135deg,#a78bfa,#7c3aed)','linear-gradient(135deg,#34d399,#0d9488)','linear-gradient(135deg,#fb7185,#ec4899)','linear-gradient(135deg,#fbbf24,#f97316)'];
                        $ci = crc32($booking->customer->name) % 5;
                        if ($ci < 0) { $ci += 5; }
                    @endphp
                    <tr style="border-top:1px solid #f8fafc;transition:background .12s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">

                        {{-- Booking # --}}
                        <td style="padding:14px 18px;">
                            <a href="{{ route('bookings.show', $booking->id) }}" style="font-family:monospace;font-size:12px;font-weight:700;color:#0891b2;text-decoration:none;" onmouseenter="this.style.textDecoration='underline'" onmouseleave="this.style.textDecoration='none'">
                                {{ $booking->booking_number }}
                            </a>
                        </td>

                        {{-- Guest --}}
                        <td style="padding:14px 18px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;background:{{ $gradients[$ci] }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.1);">
                                    {{ strtoupper(substr($booking->customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $booking->customer->id) }}" style="font-weight:700;color:#1e293b;font-size:13px;text-decoration:none;" onmouseenter="this.style.color='#0891b2'" onmouseleave="this.style.color='#1e293b'">{{ $booking->customer->name }}</a>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">
                                        {{ $booking->adults }} adult{{ $booking->adults!=1?'s':'' }}{{ $booking->children > 0 ? ' · '.$booking->children.' child' : '' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Room --}}
                        <td style="padding:14px 18px;">
                            <div style="display:inline-flex;flex-direction:column;align-items:center;justify-content:center;width:46px;height:46px;background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:12px;">
                                <div style="font-size:9px;color:rgba(255,255,255,.5);font-weight:600;line-height:1;letter-spacing:.03em;">RM</div>
                                <div style="font-size:14px;font-weight:900;color:#fff;line-height:1.1;">{{ $booking->room->room_number }}</div>
                            </div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ ucfirst($booking->room->type) }}</div>
                        </td>

                        {{-- Dates --}}
                        <td style="padding:14px 18px;white-space:nowrap;">
                            <div style="font-size:12px;font-weight:600;color:#374151;">{{ $booking->check_in_date->format('d M Y') }}</div>
                            <div style="font-size:11px;color:#94a3b8;margin:1px 0;">→ {{ $booking->check_out_date->format('d M Y') }}</div>
                            <div style="font-size:11px;color:#0891b2;font-weight:600;">{{ $booking->nights }} night{{ $booking->nights!=1?'s':'' }}</div>
                        </td>

                        {{-- Amount --}}
                        <td style="padding:14px 18px;">
                            <div style="font-weight:800;color:#1e293b;font-size:14px;">₹{{ number_format($booking->total_amount) }}</div>
                            @if($booking->balance_due > 0)
                            <div style="font-size:11px;color:#e11d48;font-weight:600;margin-top:1px;">₹{{ number_format($booking->balance_due) }} due</div>
                            @else
                            <div style="font-size:11px;color:#16a34a;font-weight:600;margin-top:1px;">Settled</div>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td style="padding:14px 18px;">
                            <span style="display:inline-flex;align-items:center;padding:4px 12px;background:{{ $sBg }};color:{{ $sClr }};border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </span>
                        </td>

                        {{-- Payment --}}
                        <td style="padding:14px 18px;">
                            <span style="display:inline-flex;align-items:center;padding:4px 12px;background:{{ $pBg }};color:{{ $pClr }};border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">
                                {{ ucfirst($booking->payment_status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td style="padding:14px 18px;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <a href="{{ route('bookings.show', $booking->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;border-radius:9px;text-decoration:none;transition:background .12s;" title="View" onmouseenter="this.style.background='#dbeafe'" onmouseleave="this.style.background='#eff6ff'">
                                    <i class="fas fa-eye" style="font-size:11px;"></i>
                                </a>
                                @if($booking->status == 'confirmed')
                                <a href="{{ route('checkin.show', $booking->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f0fdf4;color:#16a34a;border-radius:9px;text-decoration:none;transition:background .12s;" title="Check In" onmouseenter="this.style.background='#dcfce7'" onmouseleave="this.style.background='#f0fdf4'">
                                    <i class="fas fa-sign-in-alt" style="font-size:11px;"></i>
                                </a>
                                @endif
                                @if($booking->status == 'checked_in')
                                <a href="{{ route('checkout.show', $booking->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#fffbeb;color:#d97706;border-radius:9px;text-decoration:none;transition:background .12s;" title="Check Out" onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='#fffbeb'">
                                    <i class="fas fa-sign-out-alt" style="font-size:11px;"></i>
                                </a>
                                @endif
                                @if(\App\Services\PermissionService::check('bookings.edit'))
                                <a href="{{ route('bookings.edit', $booking->id) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#fffbeb;color:#d97706;border-radius:9px;text-decoration:none;transition:background .12s;" title="Edit" onmouseenter="this.style.background='#fef3c7'" onmouseleave="this.style.background='#fffbeb'">
                                    <i class="fas fa-edit" style="font-size:11px;"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:60px 24px;text-align:center;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                <i class="fas fa-calendar-times" style="font-size:24px;color:#cbd5e1;"></i>
                            </div>
                            <div style="font-size:15px;font-weight:700;color:#475569;margin-bottom:6px;">No bookings found</div>
                            <div style="font-size:13px;color:#94a3b8;margin-bottom:16px;">{{ ($search||$status||$dateFrom||$dateTo) ? 'Try adjusting your filters' : 'No reservations yet' }}</div>
                            @if($search || $status || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#f1f5f9;border:none;border-radius:12px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            @elseif(\App\Services\PermissionService::check('bookings.create'))
                            <a href="{{ route('bookings.create') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;">
                                <i class="fas fa-plus"></i> Create First Booking
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:16px 22px;border-top:1px solid #f8fafc;">{{ $bookings->links() }}</div>
    </div>

</div>
